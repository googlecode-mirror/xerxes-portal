<?php

/**
 * Saved Records
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_SavedRecords_Engine extends Xerxes_Model_Search_Engine 
{
	private $datamap; // data map
	
	/**
	 * Constructor
	 * 
	 * @param Xerxes_Model_Solr_Config $config
	 */
	
	public function __construct( Xerxes_Model_Search_Config $config )
	{
		parent::__construct($config);
		
		$this->datamap = new Xerxes_Model_DataMap_SavedRecords();
	}

	/**
	 * Return the total number of saved records
	 * 
	 * @return int
	 */		
	
	public function getHits( Xerxes_Model_Search_Query $search )
	{
	}

	/**
	 * Search and return results
	 * 
	 * @param Xerxes_Model_Search_Query $search		search object
	 * @param int $start							[optional] starting record number
	 * @param int $max								[optional] max records
	 * @param string $sort							[optional] sort order
	 * 
	 * @return Xerxes_Model_Search_Results
	 */	
	
	public function searchRetrieve( Xerxes_Model_Search_Query $search, $start = 1, $max = 10, $sort = "")
	{
		return $this->doSearch( $search, $start, $max, $sort);
	}	
	
	/**
	 * Return an individual record
	 * 
	 * @param string	record identifier
	 * @return Xerxes_Model_Search_Results
	 */
	
	public function getRecord( $id )
	{
	}

	/**
	 * Get record to save
	 * 
	 * @param string	record identifier
	 * @return int		internal saved id
	 */	
	
	public function getRecordForSave( $id )
	{
	}
	
	protected function doSearch(Xerxes_Model_Search_Query $search, $start = 1, $max = 10, $sort = "")
	{
		$username = $search->getQueryTerm(0)->phrase;
		$label = $search->getLimit("label");
		$format = $search->getLimit("format");
		
		$results = new Xerxes_Model_Search_ResultSet($this->config);
		$results->total = $this->datamap->totalRecords($username, $label, $format);
		
		// just the hit count please
		
		if ( $max == 0 )
		{
			return $results;
		}

		// no we want actual records too
		
		$records = array();
		
		if ( $label != "" )
		{
			$records = $this->datamap->getRecordsByLabel($username, $label, $sort, $start, $max);
		}
		elseif ( $format != "" )
		{
			$records = $this->datamap->getRecordsByFormat($username, $format, $sort, $start, $max);
		}
		else
		{
			$records = $this->datamap->getRecords($username, null, $sort, $start, $max);
		}
		
		foreach ( $records as $record )
		{
			$result = new Xerxes_Model_Search_Result($record->xerxes_record, $this->config);
			
			
			$results->addResult($result);
		}
		
		return $results;
	}
}
