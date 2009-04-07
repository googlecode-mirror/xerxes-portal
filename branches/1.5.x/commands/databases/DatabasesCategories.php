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
	
	class Xerxes_Command_DatabasesCategories extends Xerxes_Command_Databases
	{
		/**
		 * Return top level Metalib KB categories as XML
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute()
		{
			$objXml = new DOMDOcument();
			
			$objData = new Xerxes_DataMap();
			$arrResults = $objData->getCategories();
			
			$x = 1;
			
			if ( count($arrResults) > 0 )
			{
				$objXml->loadXML("<categories />");
				
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
						"base" => "databases",
						"action" => "subject",
						"subject" => $objCategoryData->normalized
					);
					
					$url = Xerxes_Parser::escapeXml($this->request->url_for($arrParams));
					
					$objElement = $objXml->createElement("url", $url); 
					$objCategory->appendChild($objElement);
					$objXml->documentElement->appendChild($objCategory);
					
					$x++;
				}
			}
			
			$this->request->addDocument($objXml);
			
			return 1;
		}
	}	
?>