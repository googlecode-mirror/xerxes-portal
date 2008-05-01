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
			
			if ( $strUsername != "")
			{
				// get the format counts for format
				
				$objData = new Xerxes_DataMap();
				$arrResults = $objData->getRecordFormats($strUsername);
				
				// transform them to XML
				
				$objXml = new DOMDocument();
				$objXml->loadXML("<format_facets />");
				
				foreach ( $arrResults as $objFacet )
				{
					$objFacetNode = $objXml->createElement("facet", $objFacet->total);
					$objFacetNode->setAttribute("name", $objFacet->format);
					
					$objXml->documentElement->appendChild($objFacetNode);
				}
				
				$objRequest->addDocument($objXml);
				
				
				
				// get tags and add them too
				
				$arrResults = $objData->getRecordTags($strUsername);

				// transform them to XML
				
				$objXml = new DOMDocument();
				$objXml->loadXML("<tags />");
				
				foreach ( $arrResults as $objTag )
				{
					$objTagNode = $objXml->createElement("tag", $objTag->total);
					$objTagNode->setAttribute("label", Xerxes_Parser::escapeXml($objTag->label));
					
					$objXml->documentElement->appendChild($objTagNode);
				}
				
				$objRequest->addDocument($objXml);
				
			}
			
			return 1;
		}
	}

?>