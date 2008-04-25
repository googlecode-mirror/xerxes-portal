<?php	
	
	/**
	 * Fetch saved records from the local database
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderResults extends Xerxes_Command_Folder
	{
		/**
		 * Fetch a group or an individual saved record, with options for full or brief views.
		 * Request params include 'username' the username; 'sortKeys' the current sort order;
		 * 'startRecord' the offset from which to start; 'record' multiple values that specify
		 * specific records to retrieve; 'brief' whether the records should be in brief; 'return'
		 * the return url to the metasearch page, should the user be coming here from the metasearch
		 * results.
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int		status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$this->add_export_options( $objRequest, $objRegistry );
		
			$strUsername = $objRequest->getSession("username");
			$strOrder = $objRequest->getProperty("sortKeys");
			$iStart = $objRequest->getProperty("startRecord");
			$arrID = $objRequest->getProperty("record", true);
			$strFullness = $objRequest->getProperty("brief");
			$strReturn = $objRequest->getProperty("return");
			
			$iCount = $objRegistry->getConfig("SAVED_RECORDS_PER_PAGE", false, 20);
			$configMarcBrief = $objRegistry->getConfig("XERXES_BRIEF_INCLUDE_MARC", false, false);
			$configMarcFull = $objRegistry->getConfig("XERXES_FULL_INCLUDE_MARC", false, false);
			
			// save the return url back to metasearch page if specified
			
			if ( $strReturn != "" ) $objRequest->setSession("SAVED_RETURN", $strReturn);
			
			// ensure this is the same user
			
			$strRedirect = $this->enforceUsername($objRequest, $objRegistry);
			
			if ( $strRedirect != null )
			{
				$objRequest->setRedirect($strRedirect);
				return 1;
			}
			
			// set view according to params
			
			if ( $strFullness != null )
			{
				$strFullness = "brief";
				$iCount = null;
			}
			else
			{
				$strFullness = "full";
			}
			
			// fetch result(s) from the database
			
			$objData = new Xerxes_DataMap();
			$arrResults = $objData->getRecords($strUsername, $strFullness, $strOrder, $arrID, $iStart, $iCount);			
			
			// create master results xml doc
	
			$objXml = new DOMDocument();
			$objXml->recover = true;
			$objXml->loadXML("<results />");

			
			if ( count($arrResults) > 0 )
			{
				$objRecords = $objXml->createElement("records");
				$objXml->documentElement->appendChild($objRecords);
				
				foreach ( $arrResults as $objDataRecord )
				{
					// create a new record
					
					$objRecord = $objXml->createElement("record");				
					$objRecords->appendChild($objRecord);
					
					# add url to record
					
					$arrParams = array(
						"base" => "folder",
						"action" => "full",
						"username" => $strUsername,
						"record" => $objDataRecord->id
					);
					
					
					$url = $objRequest->url_for( $arrParams);

					$objURL = $objXml->createElement("url", $url);
			 		$objRecord->appendChild( $objURL );
					
					foreach ( $objDataRecord->properties() as $key => $value )
					{
						if ( $key == "xerxes_record" && $value != null)
						{
							// import the xerxes record
							
							$objXerxesRecord = $value;
							
							$objBibRecord = new DOMDocument();
							$objBibRecord = $objXerxesRecord->toXML();
								
							// import it
								
							$objImportNode = $objXml->importNode($objBibRecord->documentElement, true);
							$objRecord->appendChild($objImportNode);
						}
						elseif ($key == "marc" && $value != null)
						{
							// import the marc record, but only if configured to do so; since both brief
							// and full record display come in on the same command, we'll use the record count 
							// here as an approximate for the brief versus full view
							
							$iNumRecords = count($arrID);
							
							if (  ( $strFullness == "full" && $configMarcFull == true && $iNumRecords == 1 ) || 
									( $strFullness == "full" && $configMarcBrief == true && $iNumRecords != 1 ) )
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
				}
			}
			
			$objRequest->addDocument($objXml);
			
			return 1;
		}
	 
	 
	public function add_export_options( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
	{
	 	$objXml = new DOMDocument();
		$objXml->loadXML("<export_functions />");
		
		$all_params = array(array( "id" => "email", "action" => "output_email"),
								  array( "id" => "endnote", "action" => "output_export_endnote" ),
								  array( "id" => "text", "action" => "output_export_text"));
		foreach ( $all_params as $params ) {
		  $option = $objXml->createElement("export_option");
		  $option->setAttribute("id", $params["id"] );
		  $url_str = $objRequest->url_for( array(
									"base" => "folder",
									"username" => $objRequest->getSession("username"),
									"action" => $params["action"],
									"sortKeys" => "title"));
		  $url = $objXml->createElement('url', $url_str );				 
		  $option->appendChild( $url );
		  $objXml->documentElement->appendChild( $option );
		}
		$objRequest->addDocument( $objXml );
	 }

	 
	}	
?>