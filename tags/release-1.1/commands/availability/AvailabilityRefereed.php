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
	
	class Xerxes_Command_AvailabilityRefereed extends Xerxes_Command_Availability
	{
		/**
		 * Performs an issn search of the refereed database and returns the location of a
		 * peer reviewed image if the journal is found
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return unknown
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$bolRefereed = false;
			$issn = $objRequest->getProperty("issn");
			$configBaseUrl = $objRegistry->getConfig("BASE_URL");
			
			if ( $issn != null && $issn != "")
			{
				// run the query
				
				$objData = new Xerxes_DataMap();
				$arrResults = $objData->getRefereed($issn);
				
				// if we got a hit, then we're good
				
				if ( count($arrResults) > 0 )
				{
					$bolRefereed = true;
				}
			}
			
			// redirect the browser to the correct image
			
			if ( $bolRefereed == true )
			{
				$objRequest->setRedirect($configBaseUrl . "/images/refereed.gif" );
			}
			else
			{
				$objRequest->setRedirect($configBaseUrl . "/images/empty.gif" );
			}
			
			return 1;
		}
	}	
?>