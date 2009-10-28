<?php	
	
	/**
	 * Fetch saved records from the local database
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderResults extends Xerxes_Command_Folder
	{
		public function doExecute()
		{
			$this->add_export_options();
			
			// get request paramaters
			
			$strUsername = $this->request->getSession("username");
			$strOrder = $this->request->getProperty("sortKeys");
			$iStart = $this->request->getProperty("startRecord");
			$strReturn = $this->request->getProperty("return");
			$strLabel = $this->request->getProperty("label");
			$strType = $this->request->getProperty("type");
			$strView = $this->request->getProperty("view");

			// id numbers can come in the form of multiple 'record' params or a single
			// 'records' param, with the ids comma seperated
			
			$arrID = $this->request->getProperty("record", true);
			$strIDs = $this->request->getProperty("records");
			if ( $strIDs != null ) $arrID = explode(",", $strIDs);
			
			// these are typically set in actions.xml
			
			$strLimit = $this->request->getProperty("limit");
			$strLoginOverride = $this->request->getProperty("doNotEnforceLogin");
			
			// configuration settings
			
			// default records per page is either set explicitly in saved_record_per_page in config
			// or is the main (metasearch) defaul_records_per_page or 20 which is the const 
			
			$defaultMax = $this->registry->getConfig("DEFAULT_RECORDS_PER_PAGE", false, self::DEFAULT_RECORDS_PER_PAGE);
			$iCount = $this->registry->getConfig("SAVED_RECORDS_PER_PAGE", false, $defaultMax);

			$iCountExport = $this->registry->getConfig("MAXIMUM_RECORD_EXPORT_LIMIT", false, 1000);
			$configMarcBrief = $this->registry->getConfig("XERXES_BRIEF_INCLUDE_MARC", false, false);
			$configMarcFull = $this->registry->getConfig("XERXES_FULL_INCLUDE_MARC", false, false);
			
			// brief records and export actions should be set to export limit
			
			if ( $strLimit == null ) $iCount = $iCountExport;
			
			// save the return url back to metasearch page if specified
			
			if ( $strReturn != "" ) $this->request->setSession("SAVED_RETURN", $strReturn);
			
			### access control
			
			// can only override login if username is *NOT* supplied in the paramaters, 
			// this prevents people from manually attempting this; 'doNotEnforceLogin' 
			// must then only be used in conjunction with specific id numbers
			
			if ( $this->request->getProperty("username") != null && $strLoginOverride != null )
			{
				throw new Exception("access denied");
			}
			
			// ensure this is the same user, unless 'doNotEnforceLogin' overrides this, 
			// such as with RefWorks or other third-party export
			
			if ( $strLoginOverride == null )
			{
				$strRedirect = $this->enforceUsername();
				
				if ( $strRedirect != null )
				{
					$this->request->setRedirect($strRedirect);
					return 1;
				}
			}
			
			### records
			
			// get the total number of records
			
			$iTotal = $this->getTotal($strUsername, $strLabel, $strType);
			
			// fetch result(s) from the database
			
			$objData = new Xerxes_DataMap();
			$arrResults = array();
			
			if ( $arrID != "" )
			{
				$arrResults = $objData->getRecordsByID($arrID);
			}
			elseif ( $strLabel != "" )
			{
				$arrResults = $objData->getRecordsByLabel($strUsername, $strLabel, $strOrder, $iStart, $iCount);
			}
			elseif ( $strType != "" )
			{
				$arrResults = $objData->getRecordsByFormat($strUsername, $strType, $strOrder, $iStart, $iCount);
			}
			else
			{
				$arrResults = $objData->getRecords($strUsername, $strView, $strOrder, $iStart, $iCount);
			}
			
			// create master results xml doc
	
			$objXml = new DOMDocument();
			$objXml->recover = true;
			$objXml->loadXML("<results />");
			
			if ( count($arrResults) > 0 )
			{
				$objRecords = $objXml->createElement("records");
				$objXml->documentElement->appendChild($objRecords);

				
        /* Enhance records with links generated from metalib templates,
           and get a list of databases too. We need to get the Xerxes_Records out of our list of Xerxes_Data_Records first. */
        $xerxes_records = array();
        foreach($arrResults as $objDataRecord) {   
          if ( $objDataRecord->xerxes_record ) {
            array_push($xerxes_records, $objDataRecord->xerxes_record);
          }
        }
                
        
        Xerxes_MetalibRecord::completeUrlTemplates($xerxes_records, $this->request, $this->registry, $database_links_dom);         
        
        $database_links = $objXml->importNode($database_links_dom->documentElement, true);
        
                
        $objXml->documentElement->appendChild($database_links);        

        
    
        /*  Add the records */
				foreach ( $arrResults as $objDataRecord )
				{
					// create a new record
					
					$objRecord = $objXml->createElement("record");				
					$objRecords->appendChild($objRecord);
					
					// full record url
					
					$arrParams = array(
						"base" => "folder",
						"action" => "full",
						"username" => $strUsername,
						"record" => $objDataRecord->id
					);
					
					$url = $this->request->url_for( $arrParams);
					$objUrlFull = $objXml->createElement("url_full", $url);
			 		$objRecord->appendChild( $objUrlFull );
			 		
					// delete url
					
					$arrParams = array(
						"base" => "folder",
						"action" => "delete",
						"username" => $strUsername,
						"source" => $objDataRecord->source,
						"id" => $objDataRecord->original_id,
						"type" => $strType,
						"label" => $strLabel,
						"startRecord" => $iStart,
						"total" => $iTotal,
						"sortKeys" => $strOrder,
						"recordsPerPage" => $iCount,
					);
					
					$url = $this->request->url_for( $arrParams);
					$objUrlDelete = $objXml->createElement("url_delete", $url);
			 		$objRecord->appendChild( $objUrlDelete );

					// openurl link
					
					$arrParams = array(
						"base" => "folder",
						"action" => "redirect",
						"type" => "openurl",
						"id" => $objDataRecord->id,
					);
					
					$url = $this->request->url_for( $arrParams);
					$objUrlOpen = $objXml->createElement("url_open", $url);
			 		$objRecord->appendChild( $objUrlOpen ); 
			 		
					foreach ( $objDataRecord->properties() as $key => $value )
					{
						
						if ($key == "username" && $strLoginOverride != null )
						{
							// exclude the username if login overridden, for privacy
						}
						elseif ( $key == "xerxes_record" && $value != null)
						{
							// import the xerxes record
							
							$objXerxesRecord = $value;
							
							$objBibRecord = new DOMDocument();
							$objBibRecord = $objXerxesRecord->toXML();
								
							// import it
								
							$objImportNode = $objXml->importNode($objBibRecord->documentElement, true);
							$objRecord->appendChild($objImportNode);
              
              // and openurl kev context object from record
              $configSID = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
              $kev = Xerxes_Parser::escapeXml($objXerxesRecord->getOpenURL(null, $configSID));
              $objOpenUrl = $objXml->createElement("openurl_kev_co", $kev);
              $objRecord->appendChild( $objOpenUrl );
              
						}
						elseif ($key == "marc" && $value != null)
						{
							// import the marc record, but only if configured to do so; since both brief
							// and full record display come in on the same command, we'll use the record count 
							// here as an approximate for the brief versus full view -- hacky, hacky
							
							$iNumRecords = count($arrID);
							
							if (  ( $strView != "brief" && $configMarcFull == true && $iNumRecords == 1 ) || 
								  ( $strView != "brief" && $configMarcBrief == true && $iNumRecords != 1 ) )
							{
								$objMarcXml = new DOMDocument();
								$objMarcXml->recover = true;
								$objMarcXml->loadXML($value);
									
								$objImportNode = $objXml->importNode($objMarcXml->getElementsByTagName("record")->item(0), true);
								$objRecord->appendChild($objImportNode);
							}
						}
						else
						{
							$objElement = $objXml->createElement($key, Xerxes_Parser::escapeXml($value));
							$objRecord->appendChild($objElement);
						}
					}
					
					$arrMulti = array("tags");
					
					foreach ($arrMulti as $multi )
					{
						foreach ( $objDataRecord->$multi as $value )
						{
							// remove the trailing 's'
							
							$single = substr($multi, 0, strlen($multi) - 1);
							
							if ( $value != null )
							{
								$objElement = $objXml->createElement($single, Xerxes_Parser::escapeXml($value));
								$objRecord->appendChild($objElement);
							}
						}
					}

				}
			}
			
			$this->request->addDocument($objXml);
			
			return 1;
		}
	}
?>