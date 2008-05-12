<?php

	/**
	 * Download and cache database information, type, and categories data from Metalib KB
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 * @uses Xerxes_Parser
	 * @uses lib/xslt/marc-to-database.xsl
	 */

	class Xerxes_Command_PopulateDatabases extends Xerxes_Command_Databases
	{
		private $configIPAddress = "";			// config entry
		private $configInstitute = "";			// config entry
		private $objSearch = null;				// metasearch object
		private $arrMissing = array();			// databases assigned to a category but missing from the all db pull
		
		/**
		 * Download the Metalib KB and populate the local database with its information
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// set a higher than normal memory limit to account for 
			// pulling down large knowledgebases
			
			ini_set("memory_limit","20M");
			
			echo "\n\nMETALIB KNOWLEDGEBASE PULL \n\n";
			
			// get configuration settings
			
			$this->configIPAddress = $objRegistry->getConfig("IP_ADDRESS", true);
			$this->configInstitute = $objRegistry->getConfig("METALIB_INSTITUTE", true);
			
			$configMetalibAddress = $objRegistry->getConfig("METALIB_ADDRESS", true);
			$configMetalibUsername = $objRegistry->getConfig("METALIB_USERNAME", true);
			$configMetalibPassword = $objRegistry->getConfig("METALIB_PASSWORD", true);
			
			// metalib search object
			
			$this->objSearch = new Xerxes_MetaSearch($configMetalibAddress, $configMetalibUsername, $configMetalibPassword);
			
			// data map
			
			$objData = new Xerxes_DataMap();
			$objData->beginTransaction();
			
			$arrSubjects = array();			// array of category and subcategory value objects
			$arrTypes = array();			// array of type value objects
			$arrDatabases = array();		// array of datatbase value objects
			
			echo "   Flushing KB tables . . . ";
			
				$objData->clearKB();
			
			echo "done\n";
			
			echo "   Fetching types . . . ";
				
				$arrTypes = $this->types();
				
				foreach ( $arrTypes as $objType )
				{
					$objData->addType($objType);
				}
			
			echo "done\n";
			
			echo "   Fetching databases . . . ";
				
				$arrDatabases = $this->databases();

				foreach ( $arrDatabases as $objDatabase )
				{
					$objData->addDatabase($objDatabase);
				}
				
			echo "done\n";
			
			echo "   Fetching categories and assigning databases . . . ";
				
				$arrSubjects = $this->subjects($arrDatabases);
				
				foreach( $arrSubjects as $objCategory )
				{
					$objData->addCategory($objCategory);
				}
			
			echo "done\n";
			
			if ( count($this->arrMissing) > 0 )
			{
				echo "   The following databases were assigned subjects, but missing from the KB:\n";
				
				foreach ($this->arrMissing as $key => $value )
				{
					echo "\t $key \n";
				}
			}
			
			echo "   Committing changes . . . ";
				
				$objData->commit();
			
			echo "done\n";
			
			return 1;
			
		}
		
		/**
		 * Fetch category and subcategory information from metalib and add to database
		 *
		 */
		
		private function subjects($arrDatabases)
		{
			$arrSubjects = array();
			
			// fetch the categories from metalib
			
			$objXml = new DOMDocument();
			$objXml = $this->objSearch->categories($this->configIPAddress);
			
			$objXPath = new DOMXPath($objXml);
			$objCategories = $objXPath->query("//category_info");

			// we'll use $x to uniquely identify the categories since metalib has no id for them
			
			$x = 1;
			
			// GET EACH CATEGORY
			
			foreach ( $objCategories as $objCategory )
			{
				$objDataCategory = new Xerxes_Data_Category();
				
				// extract category data and assign to object
				
				$objName = $objCategory->getElementsByTagName("category_name")->item(0);
				$strName = ""; if ( $objName != null ) $strName = $objName->nodeValue;
				
				$objDataCategory->id = $x;
				$objDataCategory->name = $strName;
				$objDataCategory->normalized = $this->normalize($strName);
				$objDataCategory->old = $this->normalizeOld($strName);
				
				// GET EACH SUBCATEGORY
								
				$objSubCategories = $objCategory->getElementsByTagName("subcategory_info");
				
				// version 3 fix!
				
				if ( $objSubCategories->length == 0 )
				{
					$objSubCategories = $objCategory->getElementsByTagName("subcategory-info");
				}
				
				foreach ( $objSubCategories as $objSubCategory )
				{
					$objDataSubCategory = new Xerxes_Data_Subcategory();
					
					$objSubName = $objSubCategory->getElementsByTagName("subcategory_name")->item(0);
					$objSequence= $objSubCategory->getElementsByTagName("sequence")->item(0);
					
					$strSubName = ""; if ( $objSubName != null ) $strSubName = $objSubName->nodeValue;
					$strID = ""; if ( $objSequence != null ) $strID = $objSequence->nodeValue;
					
					$objDataSubCategory->metalib_id = $strID;
					$objDataSubCategory->name = $strSubName;
					
					// get the databases associated with this subcategory from metalib
					
					$objDatabasesXml = new DOMDocument();
					$objDatabasesXml = $this->objSearch->databasesSubCategory($strID, false);
										
					// extract just the database id
				
					$objXPath = new DOMXPath($objDatabasesXml);
					$objDatabases = $objXPath->query("//source_001");
					
					// GET EACH DATABASE ASSIGNED TO SUBCATEGORY
					
					foreach ( $objDatabases as $objDatabase )
					{
						$objData = new Xerxes_Data_Database();
						$objData->metalib_id = $objDatabase->nodeValue;
						
						// add it to the subcategory object only if the database already
						// exists in the KB, no reason why this shouldn't be the 
						// case, but could go wrong
						
						if ( array_key_exists($objData->metalib_id, $arrDatabases) )
						{
							array_push($objDataSubCategory->databases, $objData);
						}
						else
						{
							$this->arrMissing[$objData->metalib_id] = "missing";
						}
					}
					
					// add subcategory to the category object
					array_push($objDataCategory->subcategories, $objDataSubCategory);
				}

				$x++;
				
				// add category to master array
				array_push($arrSubjects, $objDataCategory);
			}
			
			return $arrSubjects;
		}		
		
		/**
		 * Pulls down type categories from Metalib and saves in cache
		 *
		 */
		
		private function types()
		{
			$arrTypes = array();
			
			// get types from metalib

			$objXml = new DOMDocument();
			$objXml = $this->objSearch->types($this->configInstitute);
			
			// extract just the type names
			
			$objXPath = new DOMXPath($objXml);
			$objTypes = $objXPath->query("//resource_type/@name");
			
			$x = 1;
			
			// cycle thru and add them to array of objects

			foreach ( $objTypes as $objType )
			{
				$objDataType = new Xerxes_Data_Type();
				
				$objDataType->id = $x;
				$objDataType->name = $objType->nodeValue;
				$objDataType->normalized = $this->normalize($objType->nodeValue);
				
				array_push($arrTypes, $objDataType);
				
				$x++;
			}
			
			return $arrTypes;
		}
		
		/**
		 * Pulls down a compiled list of all database from Metalib and saves in database
		 *
		 */
		
		private function databases()
		{
			$arrDatabases = array();
							
			// get all databases and convert to local format

			$objXml = new DOMDocument();
			$objXml = $this->objSearch->allDatabases($this->configInstitute, $this->configIPAddress, true);
			
			$strXml = Xerxes_Parser::transform($objXml, "xsl/utility/marc-to-database.xsl");
			
			// get just the database info
			
			$objSimple = new SimpleXMLElement($strXml);
			$objDatabases = $objSimple->xpath("//database");
			
			foreach ( $objDatabases as $objDatabase )
			{
				// populate data object with properties

				$objData = new Xerxes_Data_Database();
				
				// single value fields
				
				$objData->metalib_id = (string) $objDatabase->metalib_id;
				$objData->title_full = (string) $objDatabase->title_full;
				$objData->title_display = (string) $objDatabase->title_display;
				$objData->institute = (string) $objDatabase->institute;
				$objData->filter = (string) $objDatabase->filter;
				$objData->creator = (string) $objDatabase->creator;
				$objData->publisher = (string) $objDatabase->publisher;
				$objData->publisher_description = (string) $objDatabase->publisher_description;
				$objData->description = (string) $objDatabase->description;
				$objData->coverage = (string) $objDatabase->coverage;
				$objData->time_span = (string) $objDatabase->time_span;
				$objData->copyright = (string) $objDatabase->copyright;
				$objData->note_cataloger = (string) $objDatabase->note_cataloger;
				$objData->note_fulltext = (string) $objDatabase->note_fulltext;
				$objData->type = (string) $objDatabase->type;
				$objData->link_native_home = (string) $objDatabase->link_native_home;
				$objData->link_native_record = (string) $objDatabase->link_native_record;
				$objData->link_native_home_alternative = (string) $objDatabase->link_native_home_alternative;
				$objData->link_native_record_alternative = (string) $objDatabase->link_native_record_alternative;
				$objData->link_native_holdings = (string) $objDatabase->link_native_holdings;
				$objData->link_guide = (string) $objDatabase->link_guide;
				$objData->link_publisher = (string) $objDatabase->link_publisher;
				$objData->library_address = (string) $objDatabase->library_address;
				$objData->library_city = (string) $objDatabase->library_city;
				$objData->library_state = (string) $objDatabase->library_state;
				$objData->library_zipcode = (string) $objDatabase->library_zipcode;
				$objData->library_country = (string) $objDatabase->library_country;
				$objData->library_telephone = (string) $objDatabase->library_telephone;
				$objData->library_fax = (string) $objDatabase->library_fax;
				$objData->library_email = (string) $objDatabase->library_email;
				$objData->library_contact = (string) $objDatabase->library_contact;
				$objData->library_note = (string) $objDatabase->library_note;
				$objData->library_hours = (string) $objDatabase->library_hours;
				$objData->active = (string) $objDatabase->active;
				$objData->proxy = (string) $objDatabase->proxy;
				$objData->searchable = (string) $objDatabase->searchable;
				$objData->subscription = (string) $objDatabase->subscription;
				$objData->sfx_suppress = (string) $objDatabase->sfx_suppress;
				$objData->new_resource_expiry = (string) $objDatabase->new_resource_expiry;
				$objData->updated = (string) $objDatabase->updated;
				$objData->number_sessions = (int) $objDatabase->number_sessions;
				
				// multi-value fields
				
				foreach ( $objDatabase->keyword as $keyword )
				{
					array_push($objData->keywords, substr((string) $keyword, 0, 249));
				}
				foreach ( $objDatabase->note as $note )
				{
					array_push($objData->notes, (string) $note);
				}
				foreach ( $objDatabase->language as $language )
				{
					array_push($objData->languages, (string) $language);
				}
				foreach ( $objDatabase->title_alternate as $alt_title )
				{
					array_push($objData->alternate_titles, (string) $alt_title);
				}
				foreach ( $objDatabase->publisher_alternative as $alt_publisher )
				{
					$alt_publisher = trim($alt_publisher);
					
					if ($alt_publisher != "")
					{
						array_push($objData->alternate_publishers, (string) $alt_publisher);
					}
				}
				
				$arrDatabases[$objData->metalib_id] = $objData;
			}
			
			return $arrDatabases;
		}
		
		/**
		 * Converts a sting to a normalized (no-spaces, non-letters) string
		 *
		 * @param string $strSubject	original string
		 * @return string				normalized string
		 */
		
		private function normalize($strSubject)
		{
			$strNormalized = strtolower($strSubject);
			
			$strNormalized = str_replace("&amp;","", $strNormalized);
			$strNormalized = str_replace("'","", $strNormalized);
			$strNormalized = str_replace("+","-", $strNormalized);
			
			$strNormalized = preg_replace("/\W/","-",$strNormalized);
			
			while ( strstr($strNormalized, "--") )
			{
				$strNormalized = str_replace("--", "-", $strNormalized);
			}
			
			return $strNormalized;
		}
		
		private function normalizeOld($strSubject)
		{
			$strNormalized = "";
			
			$strNormalized = str_replace("&amp;","", $strSubject);
			$strNormalized = preg_replace("/\W/","",$strNormalized);
			
			return $strNormalized;
		}
	}

?>