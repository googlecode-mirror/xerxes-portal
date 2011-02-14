<?php

/**
 * Search Results
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Search_ResultSet
{
	public $total = 0; // total number of hits
	public $records = array(); // records object
	public $facets; // facet object
	
	protected $datamap; // database access object
	
	/**
	 * Constructor
	 * 
	 * @param string $response		response from the search engine
	 */
	
	public function __construct()
	{
		$this->data_map = new Xerxes_DataMap();
	}	
	
	public function getRecord($id)
	{
		if ( array_key_exists($id, $this->records) )
		{
			return $this->records[$id];
		}
		else
		{
			return null;
		}
	}

	public function getRecords()
	{
		return $this->records;
	}

	public function addRecord( Xerxes_Record $record)
	{
		$record = new Xerxes_Model_Search_Result($record);
		array_push($this->records, $record);
	}
	
	/**
	 * Add a peer-reviewed indicator for refereed journals
	 */
	
	public function markRefereed()
	{
		// extract all the issns from the available records in one
		// single shot to make this more efficient
		
		$issns = $this->extractISSNs();

		if ( count($issns) > 0 )
		{		
			// get all from our peer-reviewed list
			
			$refereed_list = $this->data_map->getRefereed($issns);
			
			// now mark the records that matched
			
			for ( $x = 0; $x < count($this->records); $x++ )
			{
				$record = $this->records[$x];
				
				// check if the issn matched
				
				foreach ( $refereed_list as $refereed )
				{
					if ( in_array($refereed->issn,$record->getAllISSN()))
					{
						$record->setRefereed(true);
					}
				}
				
				$this->records[$x] = $record;
			}
		}
	}
	
	/**
	 * Add a full-text indicator for those records where link resolver indicates it
	 */
	
	public function markFullText()
	{
		// extract all the issns from the available records in one
		// single shot to make this more efficient
		
		$issns = $this->extractISSNs();
			
		if ( count($issns) > 0 )
		{
			// execute this in a single query							
			// reduce to just the unique ISSNs
				
			$arrResults = $this->data_map->getFullText($issns);
			
			// we'll now go back over the results, looking to see 
			// if also the years match, marking records as being in our list
			
			for ( $x = 0; $x < count($this->records); $x++ )
			{
				$xerxes_record = $this->records[$x];
				$this->determineFullText($xerxes_record, $arrResults);
				$this->records[$x] = $xerxes_record;
			}

			// do the same for recommendations
			
			for ( $x = 0; $x < count($this->recommendations); $x++ )
			{
				$xerxes_record = $this->recommendations[$x];
				$this->determineFullText($xerxes_record, $arrResults);
				$this->recommendations[$x] = $xerxes_record;
			}		
		}		
	}
	
	/**
	 * Given the results of a query into our SFX export, based on ISSN,
	 * does the year of the article actually meet the criteria of full-text
	 * 
	 * @param object $xerxes_record		the search result
	 * @param array $arrResults			the array from the sql query 
	 */
	
	protected function determineFullText(&$xerxes_record, $arrResults)
	{
		$strRecordIssn = $xerxes_record->getIssn();
		$strRecordYear = $xerxes_record->getYear();

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
							
				// if it falls within our range, mark the record as having it
				
				if ( $strRecordYear >= $iStart && $strRecordYear <= $iEnd )
				{
					$xerxes_record->setSubscription(true);
				}
			}
		}		
	}

	/**
	 * Extract all the ISSNs from the records, convenience funciton
	 */

	protected function extractISSNs()
	{
		$issns = array();
		
		$records = array_merge($this->records, $this->recommendations);
		
		foreach ( $records as $record )
		{
			foreach ( $record->getAllISSN() as $record_issn )
			{
				array_push($issns, $record_issn);
			}
		}
		
		$issns = array_unique($issns);
		
		return $issns;
	}

	/**
	 * Extract all the ISBNs from the records, convenience funciton
	 */	
	
	protected function extractISBNs()
	{
		$isbns = array();
		
		foreach ( $this->records as $record )
		{
			foreach ( $record->getAllISBN() as $record_isbn )
			{
				array_push($isbns, $record_isbn);
			}
		}
		
		$isbns = array_unique($isbns);
		
		return $isbns;
	}

	/**
	 * Extract all the OCLC numbers from the records, convenience funciton
	 */	
	
	protected function extractOCLCNumbers()
	{
		$oclc = array();
		
		foreach ( $this->records as $record )
		{
			array_push($oclc, $record->getOCLCNumber() );
		}
		
		$oclc = array_unique($oclc);
		
		return $oclc;
	}

	/**
	 * Extract all the record ids from the records, convenience funciton
	 */	
	
	protected function extractRecordIDs()
	{
		$id = array();
		
		foreach ( $this->records as $record )
		{
			array_push($id, $record->getRecordID() );
		}
		
		$id = array_unique($id);
		
		return $id;
	}

	protected function extractLookupIDs()
	{
		return $this->extractRecordIDs();
	}
	
	/**
	 * Look for any holdings data in the cache and add it to results
	 */
	
	public function getHoldingsInject()
	{
		$strSource = $this->getSource();
		
		// get the record ids for all search results

		$ids = $this->extractRecordIDs();
		
		// only if there are actually records
		
		if ( count($ids) > 0 )
		{
			// we do this all in one database query for speed
					
			$arrResults = $this->data_map->getCache($strSource,$ids);
			
			foreach ( $arrResults as $cache )
			{
				$item = unserialize($cache->data);
				
				if ( ! $item instanceof Xerxes_Record_Items )
				{
					throw new Exception("cached item (" . $cache->id. ") is not an instance of Xerxes_Record_Items");
				}
				
				// now associate this item with its corresponding record
			
				for( $x = 0; $x < count($this->records); $x++ )
				{
					$xerxes_record = $this->records[$x];
					
					if ( $xerxes_record->getRecordID() == $cache->id )
					{
						$xerxes_record->addItems($item);
					}
						
					$this->records[$x] = $xerxes_record;
				}
			}
		}
	}		
	
}
