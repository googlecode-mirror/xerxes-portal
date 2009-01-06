<?php	
	
	/**
	 * Display the top-level categories from the Metalib KB
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_ListUserCategories extends Xerxes_Command_Collections
	{
		/**
		 * Return top level Metalib KB categories as XML
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
      // If supplied in URL, use that (for future API use). 
      $username = $objRequest->getProperty("username");
      if ( ! $username ) {
        // default to logged in user
        $username = $objRequest->getSession("username");
      }
      
      // We can only do this if we have a real user (not temp user), otherwise
      // just add no XML. 
      if ($username == null || ! Xerxes_Framework_Restrict::isAuthenticatedUser( $objRequest )) {
          return;
      }
      
			$objXml = new DOMDOcument();
			
			$objData = new Xerxes_DataMap();
			$arrResults = $objData->getUserCreatedCategories($username);
			
			$x = 1;
			
			if ( count($arrResults) > 0 )
			{
				$objXml->loadXML("<userCategories />");
				
				foreach ( $arrResults as $objCategoryData )
				{
					$objCategory = $objXml->createElement("category");
					$objCategory->setAttribute("position", $x);
					
					foreach ( $objCategoryData->properties() as $key => $value )
					{
						if ( $value != null )
						{
							$objElement = $objXml->createElement("$key", Xerxes_Parser::escapeXml($value));
							$objCategory->appendChild($objElement);
						}
					}
					
					// add the url for the category

					$arrParams = array(
						"base" => "collections",
						"action" => "subject",
            "username" => $username,
						"subject" => $objCategoryData->normalized
					);
					
					$url = Xerxes_Parser::escapeXml($objRequest->url_for($arrParams));
					
					$objElement = $objXml->createElement("url", $url); 
					$objCategory->appendChild($objElement);
					$objXml->documentElement->appendChild($objCategory);
					
					$x++;
				}
			}
			
			$objRequest->addDocument($objXml);
			
			return 1;
		}
	}	
?>