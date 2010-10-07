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
	 * @deprecated
	 */
	
	class Xerxes_Command_AvailabilityRefereed extends Xerxes_Command_Availability
	{
		public function doExecute()
		{
			$bolRefereed = false;
			$issn = $this->request->getProperty("issn");
			$configBaseUrl = $this->request->getConfig("BASE_URL");
			
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
				$this->request->setRedirect($configBaseUrl . "/images/refereed.png" );
			}
			else
			{
				$this->request->setRedirect($configBaseUrl . "/images/empty.png" );
			}
			
			return 1;
		}
	}	
?>
