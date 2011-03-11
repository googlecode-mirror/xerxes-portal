<?php

import("Summon", "lib/Summon/Summon.class.php");

/**
 * Summon Search Engine
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Solr
 */

class Xerxes_Model_Summon_Engine extends Xerxes_Model_Search_Engine 
{
	protected $config; // local config
	protected $client; // summon client, for now

	/**
	 * Constructor
	 * 
	 * @param Xerxes_Model_Solr_Config $config
	 */
	
	public function __construct( Xerxes_Model_Summon_Config $config )
	{
		parent::__construct($config);

		$id = $config->getConfig("SUMMON_ID", true);
		$key = $config->getConfig("SUMMON_KEY", true);		
				
		$this->client = new Summon($id, $key);
	}
	
	/**
	 * Return the total number of hits for the search
	 * 
	 * @return int
	 */	
	
	public function getHits( Xerxes_Model_Search_Query $search )
	{
		// get the results
		
		$results = $this->doSearch( $search, 1, 0 );

		// return total
		
		return $results->getTotal();
	}

	/**
	 * Return an individual record
	 * 
	 * @param string	record identifier
	 * @return Xerxes_Model_Solr_Results
	 */
	
	public function getRecord( $id )
	{
		// get result
		
		$results = $this->doGetRecord( $id );
		
		// enhance
		
		$results->getRecord(0)->addRecommendations();
		$results->markFullText();
		$results->markRefereed();
		
		return $results;
		
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
		$results = $this->doSearch( $search, $start, $max, $sort);
		
		// $results->markRefereed();
		
		$results->markFullText();
		
		return $results;
	}

	/**
	 * Do the actual fetch of an individual record
	 * 
	 * @param string	record identifier
	 * @return Xerxes_Model_Solr_Results
	 */	
	
	public function doGetRecord( $id )
	{
		// get result
		
		$summon_results = $this->client->getRecord($id);
		$results = $this->parseResponse($summon_results);
		
		return $results;
	}		
	
	/**
	 * Internal function that 
	 * 
	 * @param Xerxes_Model_Search_Query $search		search object
	 * @param int $start							[optional] starting record number
	 * @param int $max								[optional] max records
	 * @param string $sort							[optional] sort order
	 * 
	 * @return Xerxes_Model_Search_Results
	 */		
	
	protected function doSearch( Xerxes_Model_Search_Query $search, $start = 1, $max = 10, $sort = "")
	{ 	
		// prepare the query
		
		$terms = $search->getQueryTerms();
		$term = $terms[0];
		
		// limits
		
		$facets = array();
		
		foreach ( $search->getLimits(true) as $limit )
		{
			array_push($facets, $limit->field . "," . str_replace(',', '\,', $limit->value) . ",false");
		}		
		
		// summon deals in pages, not start record number
		
		if ( $max > 0 )
		{
			$page = ceil ($start / $max);
		}
		else
		{
			$page = 1;
		}
		
		// get the results
		
		$summon_results = $this->client->query($term->phrase, $facets, $page, $max, $sort);
		
		return $this->parseResponse($summon_results);
	}
	
	/**
	 * Parse the summon response
	 *
	 * @param array $summon_results		summon results array from client
	 * @return Xerxes_Model_Search_ResultSet
	 */
	
	protected function parseResponse($summon_results)
	{
		// testing
		// header("Content-type: text/plain"); print_r($summon_results); exit;	

		// nada
		
		if ( ! is_array($summon_results) )
		{
			throw new Exception("Cannot connect to Summon server");
		}		
		
		$result_set = new Xerxes_Model_Search_ResultSet($this->config);

		// total
		
		$total = $summon_results['recordCount'];
		$result_set->total = $total;
		
		// extract records
		
		foreach ( $this->extractRecords($summon_results) as $xerxes_record )
		{
			$result_set->addRecord($xerxes_record);
		}
		
		// extract facets
		
		$facets = $this->extractFacets($summon_results);		
		$result_set->setFacets($facets);
		
		return $result_set;
	}
	
	/**
	 * Parse records out of the response
	 *
	 * @param array $summon_results
	 * @return array of Xerxes_Model_Summon_Record's
	 */
	
	protected function extractRecords($summon_results)
	{
		$records = array();
		
		if ( array_key_exists("documents", $summon_results) )
		{
			foreach ( $summon_results["documents"] as $document )
			{
				$xerxes_record = new Xerxes_Model_Summon_Record();
				$xerxes_record->load($document);
				array_push($records, $xerxes_record);
			}
		}
		
		return $records;
	}
	
	/**
	 * Parse facets out of the response
	 *
	 * @param array $summon_results
	 * @return Xerxes_Model_Search_Facets
	 */	
	
	protected function extractFacets($summon_results)
	{
		$facets = new Xerxes_Model_Search_Facets();
		
		if ( array_key_exists("facetFields", $summon_results) )
		{		
			// take them in the order defined in config
				
			foreach ( array_keys($this->config->getFacets()) as $group_internal_name )
			{
				foreach ( $summon_results["facetFields"] as $facetFields )
				{
					if ( $facetFields["displayName"] == $group_internal_name)
					{
						$group = new Xerxes_Model_Search_FacetGroup();
						$group->name = $facetFields["displayName"];
						$group->public = $this->config->getFacetPublicName($facetFields["displayName"]);
							
						$facets->addGroup($group);
							
						foreach ( $facetFields["counts"] as $counts )
						{
							$facet = new Xerxes_Model_Search_Facet();
							$facet->name = $counts["value"];
							$facet->count = $counts["count"];
								
							$group->addFacet($facet);
						}
					}
				}
			}
		}
		
		return $facets;
	}
}
