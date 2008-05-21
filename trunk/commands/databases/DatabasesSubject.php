<?php	
	

	/**
	 * Display a single 'subject' in Xerxes, which is an inlined display of a subcategories
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_DatabasesSubject extends Xerxes_Command_Databases
	{
		/**
		 * Fetch a single top-level category and inline its subcategories as XML;
		 * Request param should be 'subject', the normalized name of the subject as
		 * created by PopulateDatabases
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$objXml = new DOMDOcument();
			$objXml->loadXML("<category />");

			$strOld = $objRequest->getProperty("category");
			$strSubject = $objRequest->getProperty("subject");
			
			$objData = new Xerxes_DataMap();
			$objCategoryData = $objData->getSubject($strSubject, $strOld);
			
			$y = 1;
			
			if ( $objCategoryData != null )
			{
				$objXml->documentElement->setAttribute("name", $objCategoryData->name);
        $objXml->documentElement->setAttribute("normalized", $objCategoryData->normalized );
				
        // Standard URL for the category        
        $arrParams = array(
          "base" => "databases",
          "action" => "subject",
          "subject" => $objCategoryData->normalized
        );					
        $url = Xerxes_Parser::escapeXml($objRequest->url_for($arrParams));					
        $objElement = $objXml->createElement("url", $url); 
        $objXml->documentElement->appendChild($objElement);
      
				// the attributes of the subcategories
				$x = 1;        
				foreach ( $objCategoryData->subcategories as $objSubData )
				{
					$objSubCategory = $objXml->createElement("subcategory");
					$objSubCategory->setAttribute("name", $objSubData->name);
					$objSubCategory->setAttribute("position", $y);
					$y++;
					
					// the database information
										
					foreach ( $objSubData->databases as $objDatabaseData )
					{
						$objDatabase = $objXml->createElement("database");
						
						foreach ( $objDatabaseData->properties() as $key => $value )
						{
							if ( $value != null )
							{
								$objElement = $objXml->createElement("$key", Xerxes_Parser::escapeXml($value));
								
								// assist here with the mechanism for pre-checking searchable
								// databases up to the search limit
								
								if ( $key == "searchable" && $value == "1")
								{
									$objElement->setAttribute("count", $x);
									$x++;
								}
								
								$objDatabase->appendChild($objElement);
							}
						}
						
						// add url to access xerxes database page
						$arrParams = array(
							"base" => "databases",
							"action" => "database",
							"id" => $objDatabaseData->metalib_id
						);						
						$url = Xerxes_Parser::escapeXml($objRequest->url_for($arrParams));						
						$objElement = $objXml->createElement("url", $url);
						$objDatabase->appendChild($objElement);
            
            //And one for the via-Xerxes native link. 
            $objElement = $objXml->createElement("xerxes_native_link_url",
                $objRequest->url_for( array(
                  "base" => "databases",
                  "action" => "proxy",
                  "database" => htmlentities($objDatabaseData->metalib_id)
                )));
            $objDatabase->appendChild($objElement);
            
						$objSubCategory->appendChild($objDatabase);
					}
					
					$objXml->documentElement->appendChild($objSubCategory);
				}
			}

			$objRequest->addDocument($objXml);
				
			return 1;
		}
	}	
?>