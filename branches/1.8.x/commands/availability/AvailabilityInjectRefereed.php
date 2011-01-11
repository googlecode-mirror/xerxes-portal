<?php	
	
	/**
	 * Determine if the journal is peer-reviewed or not
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	
	class Xerxes_Command_AvailabilityInjectRefereed extends Xerxes_Command_Availability 
	{
		public function doExecute()
		{
			// get all of the issns
			
			$arrIssn = $this->request->getData("//issn", null, "array");
			
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
				
				$this->request->addDocument($objXml);
			}
			
			return 1;
		}
	}	
?>