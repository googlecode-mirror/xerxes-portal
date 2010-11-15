<?php

	/**
	 * Download and cache database information, type, and categories data from Metalib KB
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 * @uses Xerxes_Framework_Parser
	 * @uses lib/xslt/marc-to-database.xsl
	 */

	class Xerxes_Command_PopulateDatabases extends Xerxes_Command_Databases
	{
		private $configInstitute = "";			// config entry
		private $configPortal = "";				// config entry
		private $configLanguages = "";			// config entry
		private $configChunk = false;			// config entry
		private $objSearch = null;				// metasearch object
		private $category_count = 1;			// to keep track of category id's
		
		public function doExecute()
		{
			// in case this is being called from the web, plaintext
			
			if ( $this->request->isCommandLine() == false )
			{
				header("Content-type: text/plain");
			}
      
			// set a higher than normal memory limit to account for 
			// pulling down large knowledgebases
			
      		$configMemory = $this->registry->getConfig("HARVEST_MEMORY_LIMIT", false, "500M");
			ini_set("memory_limit",$configMemory);
			
			echo "\n\nMETALIB KNOWLEDGEBASE PULL \n\n";
			
			// get configuration settings
			
			$this->configInstitute = $this->registry->getConfig("METALIB_INSTITUTE", true);			
			$this->configPortal = $this->registry->getConfig("METALIB_PORTAL", false, $this->configInstitute);
			$this->configLanguages = $this->registry->getConfig("LANGUAGES", false);
			
			$this->configChunk = $this->registry->getConfig("CHUNK_KB_PULL", false, false);
			
			$configMetalibAddress = $this->registry->getConfig("METALIB_ADDRESS", true);
			$configMetalibUsername = $this->registry->getConfig("METALIB_USERNAME", true);
			$configMetalibPassword = $this->registry->getConfig("METALIB_PASSWORD", true);
			
			// metalib search object
			
			$this->objSearch = new Xerxes_MetaSearch($configMetalibAddress, $configMetalibUsername, $configMetalibPassword);
			
			// data map
			
			$objData = new Xerxes_DataMap();
			
			// clear the cache, while we're at it
			
			echo "   Pruning cache table . . . ";
			
				$status = $objData->pruneCache();
				
				if ( $status != 1 )
				{
					throw new Exception("could not prune cache");
				}
				else
				{
					echo "done\n";
				}
			
			// now the real kb stuff
			
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
				
				$languages = array(array("code" => "eng", "locale" => "C"));
			
				if ( $this->configLanguages != null )
				{
					$languages = $this->configLanguages->language;
				}
			
				foreach ( $languages as $language )
				{
					$locale = (string) $language["locale"];
					$lang = (string) $language["code"];
					
					$oldlocale = setlocale( LC_CTYPE, 0 );
					setlocale( LC_CTYPE, $locale ); // this influences the iconv() call with 'ASCII//TRANSLIT' target					
					
					$arrSubjects = $this->subjects($arrDatabases, $lang);
					
					foreach( $arrSubjects as $objCategory )
					{
						$objData->addCategory($objCategory);
					}
					
					setlocale( LC_CTYPE, $oldlocale );
				}
				
			
			echo "done\n";
			
			echo "   Synching user saved databases . . . ";
			
				$objData->synchUserDatabases();
			
			echo "done\n";
			
			echo "   Committing changes . . . ";
				
				$objData->commit();
			
			echo "done\n";
			
			return 1;
			
		}
		
		/**
		 * Fetch category and subcategory information from metalib and add to database
		 *
		 */
		
		private function subjects($arrDatabases, $lang)
		{
			$arrSubjects = array();
			
			// not actually specified
			
			$lang_metalib = strtoupper($lang);
			
			// fetch the categories from metalib
			
			$objXml = new DOMDocument();
			
			$objXml = $this->objSearch->categories(
				$this->configInstitute, 
				$this->configPortal, 
				$lang_metalib
			);
			
			$objXPath = new DOMXPath($objXml);
			$objCategories = $objXPath->query("//category_info");

			if ( $objCategories->length < 1 ) throw new Exception("Could not find any categories in the Metalib KB");
			
			// GET EACH CATEGORY
			
			foreach ( $objCategories as $objCategory )
			{
				$objDataCategory = new Xerxes_Data_Category();
				
				// extract category data and assign to object
				
				$objName = $objCategory->getElementsByTagName("category_name")->item(0);
				$strName = ""; if ( $objName != null ) $strName = $objName->nodeValue;
				
				// we'll use incrementer to uniquely identify the categories since metalib has no id for them
				
				$objDataCategory->id = $this->category_count;
				$objDataCategory->name = $strName;
				$objDataCategory->normalized = $this->normalize($strName);
				$objDataCategory->old = $this->normalizeOld($strName);
				$objDataCategory->lang = $lang;
				
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
						// exists in the KB, if not the case, then we've got mismatched
						// categories and databases from different institutes
						
						if ( array_key_exists($objData->metalib_id, $arrDatabases) )
						{
							array_push($objDataSubCategory->databases, $objData);
						}
						else
						{
							throw new Exception("Could not find database (" . $objData->metalib_id . 
								") assigned to category; make sure config entry ip_address is part " .
								"of the IP range associated with this Metalib instance");
						}
					}
					
					// add subcategory to the category object
					array_push($objDataCategory->subcategories, $objDataSubCategory);
				}

				$this->category_count++;
				
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
			$objXml = $this->objSearch->allDatabases($this->configInstitute, true, $this->configChunk);
			
			$strXml = Xerxes_Framework_Parser::transform($objXml, "xsl/utility/marc-to-database.xsl");

			if ( $this->request->getProperty("test") )
			{
				$objXml->save("metalib.xml");
				file_put_contents("xerxes.xml", $strXml);
			}			
			
			$strXml = Xerxes_Framework_Parser::transform($objXml, "xsl/utility/marc-to-database.xsl");
      
			// get just the database info
			
			$objSimple = new SimpleXMLElement($strXml);
			$arrDBs = $objSimple->xpath("//database");
			
			if ( count($arrDBs) < 1 )
			{
				throw new Exception("Could not find any databases in the Metalib KB. " . 
					$this->objSearch->getWarnings(true) );
			}
			
			foreach ( $arrDBs as $objDatabase )
			{       
				// populate data object with properties

				$objData = new Xerxes_Data_Database();
				$objData->metalib_id = (string) $objDatabase->metalib_id;
				$objData->title_display = (string) $objDatabase->title_display;
				$objData->type = (string) $objDatabase->type;
				$objData->data = $objDatabase->asXML();
				
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
			return Xerxes_Data_Category::normalize($strSubject);
		}
		
		private function normalizeOld($strSubject)
		{
			$strNormalized = "";
			
			$strNormalized = str_replace("&amp;","", $strSubject);
			$strNormalized = preg_replace('/\W/',"",$strNormalized);
			
			return $strNormalized;
		}
		
	   	/* We need to register a straight function for the XSL to call with php:function. Sorry. */
   
		public static function splitToNodeset($strList, $separator = ",")
		{
	   		$dom = new domdocument;
	   		$dom->loadXML("<list />");
	   		$docEl = $dom->documentElement;
	   		$arr = explode($separator, $strList);
	   		
	   		$found = false;
	   		foreach ($arr as $item)
	   		{
	   			if (! empty($item))
	   			{
	   				$found = true;
	   				$element = $dom->createElement("item", $item);
	   				$element->setAttribute("value", $item);
	   				$docEl->appendChild($element);
	   			}
	   		}
	   		return $dom->documentElement;
	   	}
	}

?>
