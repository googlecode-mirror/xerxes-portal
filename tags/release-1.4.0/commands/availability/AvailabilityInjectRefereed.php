<?php	
	
	/**
	 * Determine if the journal is peer-reviewed or not
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_AvailabilityInjectRefereed extends Xerxes_Command_Availability 
	{
		/**
		 * Extracts all of the ISSNs from the request and adds a new refereed node in the XML
		 * for all of the journals that are peer reviewed
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// get all of the issns
			
			$arrIssn = $objRequest->getData("//issn", null, "array");
			
			if ( count($arrIssn) > 0 )
			{
				// execute this in a single query
				
				$objData = new Xerxes_DataMap();
				$arrResults = $objData->getRefereed($arrIssn);
				
				// add the data back to the request
				
				$objXml = new DOMDocument();
				$objXml->loadXML("<refereed />");
				
				foreach ( $arrResults as $objPeer )
				{
					$objIssn = $objXml->createElement("issn", $objPeer->issn);				
					$objXml->documentElement->appendChild($objIssn);
				}
				
				$objRequest->addDocument($objXml);
			}
			
			return 1;
		}
	}	
?>