<?php	
	
	/**
	 * Determine if full-text is available via SFX
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 * @deprecated
	 */
	
	class Xerxes_Command_AvailabilityFullText extends Xerxes_Command_Availability
	{
		public function doExecute()
		{
			$bolFullText = false;
			
			$issn = $this->request->getProperty("issn");
			$year = $this->request->getProperty("year");
			
			$configBaseUrl = $this->request->getConfig("BASE_URL");
			
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
				$this->request->setRedirect($configBaseUrl . "/images/dynamic-full.png" );
			}
			else
			{
				$this->request->setRedirect($configBaseUrl . "/images/dynamic-sfx.png" );
			}
			
			return 1;
		}
	}	
?>
