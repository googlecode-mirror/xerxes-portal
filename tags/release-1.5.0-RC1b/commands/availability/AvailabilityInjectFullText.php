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
	
	class Xerxes_Command_AvailabilityInjectFullText extends Xerxes_Command_Availability 
	{
		public function doExecute()
		{
			$arrFullText = array();		// list of records with full text
			$arrPairs = array();		// issn-year combo array
			$arrIssn = array();			// just the issns
			
			// get all of the records that have an issn and a year
			// as well as no native full-text link already
			
			$strXpath = "//xerxes_record[not(links/link[@type = 'pdf' or @type = 'html' or @type = 'online']) and standard_numbers/issn and year]";
			$objSimple = simplexml_import_dom($this->request->getData());
			$arrRecords = $objSimple->xpath($strXpath);
			
			// pair-up the issn-year into a simple array here
			
			foreach ( $arrRecords as $xerxes_record )
			{
				$issn = (string) $xerxes_record->standard_numbers->issn;
				$year = (string) $xerxes_record->year;
				
				// add the pairs to a master list, and then also just the issn for 
				// efficiency of the query
				
				array_push($arrPairs, array($issn, $year));
				array_push($arrIssn, $issn);
			}
			
			if ( count($arrPairs) > 0 )
			{
				// execute this in a single query							
				// reduce to just the unique ISSNs
				
				$arrIssn = array_unique($arrIssn);		
				$objData = new Xerxes_DataMap();
				$arrResults = $objData->getFullText($arrIssn);
				
				// we'll now go back over the record issn => year pairs, looking to see 
				// if one the results of our query matches it
				
				foreach ($arrPairs as $arrValues )
				{
					$strRecordIssn = $arrValues[0];
					$strRecordYear = $arrValues[1];
				
					foreach ( $arrResults as $objFulltext )
					{
						// convert query issn back to dash
	
						if ( $strRecordIssn == $objFulltext->issn )
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
							
							// if it falls within our range, add it to final array
							
							if ( $strRecordYear >= $iStart && $strRecordYear <= $iEnd )
							{
								array_push($arrFullText, array($strRecordIssn, $strRecordYear));
							}
						}
					}
				}
				
				// add the data back to the request
				
				$objXml = new DOMDocument();
				$objXml->loadXML("<fulltext />");
				
				foreach ( $arrFullText as $arrValues)
				{
					$objIssn = $objXml->createElement("issn", $arrValues[0]);
					$objIssn->setAttribute("year", $arrValues[1]);			
					$objXml->documentElement->appendChild($objIssn);
				}
				
				$this->request->addDocument($objXml);
			}
			
			return 1;
		}
	}	
?>