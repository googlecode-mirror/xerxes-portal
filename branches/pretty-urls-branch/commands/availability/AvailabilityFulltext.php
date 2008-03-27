<?php	
	
	/**
	 * Determine if full-text is available via SFX
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_AvailabilityFullText extends Xerxes_Command_Availability
	{
		/**
		 * Performs an issn / year search of the sfx institutional holdings data to determine
		 * if full-text is available via SFX, returns location of a full-text image if true
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return int	status
		 */
		
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$bolFullText = false;
			
			$issn = $objRequest->getProperty("issn");
			$year = $objRequest->getProperty("year");
			
			$configBaseUrl = $objRegistry->getConfig("BASE_URL");
			
			if ( $issn != null && $issn != "" )
			{
				// convert year to int

				if ( $year != null && $year != "") $year = (int) $year;
				
				// run the query
				
				$objData = new Xerxes_DataMap();
				$arrResults = $objData->getFullText($issn);
	
				foreach ( $arrResults as $objFulltext )
				{		
					// in case the database values are null, we'll assign the 
					// initial years as unreachable
						
					$iStart = 9999;
					$iEnd = 0;
						
					if ( $objFulltext->startdate != null )
					{
						$iStart = (int) $objFulltext->startdate;
					}
					if ( $objFulltext->enddate != null )
					{
						$iEnd = (int) $objFulltext->enddate;
					}
					if ( $objFulltext->embargo != null && (int) $objFulltext->embargo != 0 )
					{
						// convert embargo to years, we'll overcompensate here by rounding
						// up, still showing 'check for availability' but no guarantee of full-text
						
						$iEmbargoDays = (int) $objFulltext->embargo;
						$iEmbargoYears = (int) ceil($iEmbargoDays/365);
						
						// embargo of a year or more needs to go back to start of year, so increment
						// date plus an extra year
						
						if ( $iEmbargoYears >= 1 )
						{
							$iEmbargoYears++;
						}
						
						$iEnd = (int) date("Y");
						$iEnd = $iEnd - $iEmbargoYears;
					}
										
					if ( $year >= $iStart && $year <= $iEnd )
					{
						$bolFullText = true;
					}			
				}
			}
			
			// redirect the browser to the correct image
			
			if ( $bolFullText == true )
			{
				$objRequest->setRedirect($configBaseUrl . "/images/dynamic-full.gif" );
			}
			else
			{
				$objRequest->setRedirect($configBaseUrl . "/images/dynamic-sfx.gif" );
			}
			
			return 1;
		}
	}	
?>