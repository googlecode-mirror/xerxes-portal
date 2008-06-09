<?php	
	
	/**
	 * Provide a tags and format summary for a user's records
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_FolderTags extends Xerxes_Command_Folder
	{
		/**
		 * Provide a tags and format summary for a user's records
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$strUsername = $objRequest->getSession("username");
			
			$arrSessionArray = array();
			
			if ( $strUsername != "")
			{
				### FORMATS / TYPES
				
				$objData = new Xerxes_DataMap();
				$arrResults = $objData->getRecordFormats($strUsername);
				
				// transform them to XML
				
				$objXml = new DOMDocument();
				$objXml->loadXML("<format_facets />");
				
				foreach ( $arrResults as $objFacet )
				{
					$objFacetNode = $objXml->createElement("facet", $objFacet->total);
					$objFacetNode->setAttribute("name", $objFacet->format);
					
					$arrParams = array(
						"base" => "folder",
						"action" => "home",
						"username" => $objRequest->getProperty("username"),
						"type" => $objFacet->format
					);
					
					$objFacetNode->setAttribute("url", $objRequest->url_for($arrParams));
					
					$objXml->documentElement->appendChild($objFacetNode);
				}
				
				$objRequest->addDocument($objXml);

				
				
				### TAGS
				
				// we'll store the tags summary in session so that edits can be 
				// done without round-tripping to the database; xslt can display
				// the summary by getting it from the request xml

				$arrResults = $objData->getRecordTags($strUsername);
				
				foreach ( $arrResults as $objTag )
				{
					$arrSessionArray[$objTag->label] = $objTag->total;
				}
				
				$this->setTagsCache($objRequest, $arrSessionArray);
			}
			
			return 1;
		}
	}

?>