<?php

/**
 * Primo
 * 
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Primo
 */

class Xerxes_Model_Primo_Engine extends Xerxes_Model_Search_Engine 
{
	protected $server; // primo server address
	protected $institution; // primo institution id
	protected $vid; // not sure what this is, 'vendor' id?
	protected $loc = array(); // scope value(s)
	protected $on_campus; // on campus or not
	protected $url; // track the url

	/**
	 * Constructor
	 * 
	 * @param Xerxes_Model_Primo_Config $config
	 */
	
	public function __construct( Xerxes_Model_Primo_Config $config, $on_campus = true, $scope = "" )
	{
		parent::__construct($config);

		// server info
		
		$this->server = $this->config->getConfig('PRIMO_ADDRESS', true);

		if ( substr($this->server,-1,1) != "/" )
		{
			$this->server .= "/";
		}		
		
		// institutional id's
		
		$this->institution = $this->config->getConfig('INSTITUTION', true);
		$this->vid = $this->config->getConfig('VID', false);
		
		// scope
		
		$loc = $this->config->getConfig('LOC', false, $scope);
		
		if ( $loc != "" )
		{
			$this->loc = explode(";", $loc);
		}
		
		// on campus
		
		$this->on_campus = $on_campus;
	}
	
	/**
	 * Return the total number of hits for the search
	 * 
	 * @return int
	 */	
	
	public function getHits( Xerxes_Model_Search_Query $search )
	{
		// get the results, just the hit count
		
		$results = $this->doSearch($search, 1, 1, null);
		
		// return total
		
		return $results->getTotal();
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
		// get the results
		
		$results = $this->doSearch($search, $start, $max, $sort, true);
		
		// enhance
		
		$results->markFullText(); // sfx data
		$results->markRefereed(); // refereed
		
		return $results;
	}	
	
	/**
	 * Return an individual record
	 * 
	 * @param string	record identifier
	 * @return Xerxes_Model_Search_Results
	 */
	
	public function getRecord( $id )
	{
		$results = $this->doGetRecord($id);
		
		// enhance
		
		$results->getRecord(0)->addRecommendations(); // bx
		$results->markFullText(); // sfx data
		$results->markRefereed(); // refereed
		
		return $results;
	}

	/**
	 * Get record to save
	 * 
	 * @param string	record identifier
	 * @return int		internal saved id
	 */	
	
	public function getRecordForSave( $id )
	{
		return $this->doGetRecord($id);
	}

	/**
	 * Do the actual fetch of an individual record
	 * 
	 * @param string	record identifier
	 * @return Xerxes_Model_Search_Results
	 */		
	
	protected function doGetRecord( $id )
	{
		$results = $this->doSearch("rid,exact,$id", 1, 1);
		return $results;
	}

	/**
	 * Do the actual search
	 * 
	 * @param mixed $search							string or Xerxes_Model_Search_Query, the search query
	 * @param int $start							[optional] starting record number
	 * @param int $max								[optional] max records
	 * @param string $sort							[optional] sort order
	 * 
	 * @return Xerxes_Model_Search_Results
	 */

	protected function doSearch( $search, $start = 1, $max = 10, $sort = "" )
	{
		// parse the query
		
		$query = "";
		
		if ( $search instanceof Xerxes_Model_Search_Query )
		{
			foreach ( $search->getQueryTerms() as $term )
			{
				$query .= "&query=" . $term->field_internal . ",contains," . urlencode($term->phrase);
			}
			
			foreach ( $search->getLimits(true) as $limit )
			{
				$query .= "&query=facet_" . $limit->field . ",exact," . urlencode($limit->value);
			}
		}
		else
		{
			$query = "&query=" . urlencode($search);
		}
		
		// on campus as string
		
		$on_campus = "true";
		
		if ( $this->on_campus == false )
		{
			$on_campus = "false";
		}
		
		// create the url
		
		$this->url = $this->server . "xservice/search/brief?" .
			"institution=" . $this->institution .
			"&onCampus=" . $on_campus .
			$query .
			"&indx=$start" .
			"&bulkSize=$max";
			
		if ( $this->vid != "" )
		{
			$this->url .= "&vid=" . $this->vid;	
		}
		
		foreach ( $this->loc as $loc )
		{
			$this->url .= "&loc=" . $loc;
		}
			
		if ( $sort != "" )
		{
			$this->url .= "&sortField=$sort";
		}
		
		// get the response
		
		$response = Xerxes_Framework_Parser::request($this->url);
		
		// echo $response;
		
		if ( $response == "" )
		{
			throw new Exception("Could not connect to Primo server");
		}
		
		// load it

		$xml = new DOMDocument();
		$xml->loadXML($response);
		
		// parse it
		
		return $this->parseResponse($xml);
	}	

	/**
	 * Parse the primo response
	 *
	 * @param DOMDocument $xml	primo results
	 * @return Xerxes_Model_Search_ResultSet
	 */	
	
	protected function parseResponse(DOMDocument $xml)
	{
		// check for errors
		
		$error = $xml->getElementsByTagName("ERROR")->item(0);
		
		if ( $error != "" )
		{
			throw new Exception($error->getAttribute("MESSAGE"));
		}
		
		// set-up the result set
		
		$result_set = new Xerxes_Model_Search_ResultSet($this->config);		
		
		// total
		
		$docset = $xml->getElementsByTagName("DOCSET")->item(0);
		
		if ( $docset == null )
		{
			throw new Exception("Could not determine total number of records");
		}
		
		$total = $docset->getAttribute("TOTALHITS");
		
		$result_set->total = $total;

		// extract records
		
		foreach ( $this->extractRecords($xml) as $xerxes_record )
		{
			$result_set->addRecord($xerxes_record);
		}		
		
		// facets
		
		$facets = $this->extractFacets($xml);
		$result_set->setFacets($facets);
		
		return $result_set; 
	}

	/**
	 * Parse records out of the response
	 *
	 * @param DOMDocument $dom 	Primo XML
	 * @return array of Xerxes_Model_Primo_Record's
	 */	
	
	protected function extractRecords(DOMDocument $dom)
	{
		$final = array();
		
		$xpath = new DOMXPath($dom);
		$xpath->registerNamespace("sear", "http://www.exlibrisgroup.com/xsd/jaguar/search");
		
		$records = $xpath->query("//sear:DOC");
		
		foreach ( $records as $record )
		{
			$xerxes_record = new Xerxes_Model_Primo_Record();
			$xerxes_record->loadXML($record);
			array_push($final, $xerxes_record);
		}
		
		return $final;
	}
	
	/**
	 * Parse facets out of the response
	 *
	 * @param DOMDocument $dom 	Primo XML
	 * @return Xerxes_Model_Search_Facets
	 */	
		
	protected function extractFacets(DOMDocument $dom)
	{
		$facets = new Xerxes_Model_Search_Facets();
		
		// echo $dom->saveXML();
		
		$groups = $dom->getElementsByTagName("FACET");
		
		if ( $groups->length > 0 )
		{
			// we'll pass the facets into an array so we can control both which
			// ones appear and in what order in the Xerxes config
			
			$facet_group_array = array();
			
			foreach ( $groups as $facet_group )
			{
				$facet_values = $facet_group->getElementsByTagName("FACET_VALUES");
				
				// if only one entry, then all the results have this same facet,
				// so no sense even showing this as a limit
				
				if ( $facet_values->length <= 1 )
				{
					continue;
				}
				
				$group_internal_name = $facet_group->getAttribute("NAME");
				
				$facet_group_array[$group_internal_name] = $facet_values;
			}
			
			// now take the order of the facets as defined in xerxes config
			
			foreach ( $this->config->getFacets() as $group_internal_name => $facet_values )
			{
				// we defined it, but it's not in the primo response
				
				if ( ! array_key_exists($group_internal_name, $facet_group_array) )
				{
					continue;
				}
				
				$group = new Xerxes_Model_Search_FacetGroup();
				$group->name = $group_internal_name;
				$group->public = $this->config->getFacetPublicName($group_internal_name);
				
				// get the actual facets out of the array above
				
				$facet_values = $facet_group_array[$group_internal_name];

				// and put them in their own array so we can mess with them
				
				$facet_array = array();
				
				foreach ( $facet_values as $facet_value )
				{
					$key = $facet_value->getAttribute("KEY");
					$value = $facet_value->getAttribute("VALUE");
					$facet_array[$key] = $value;
				}
				
				// date

				$decade_display = array();
				
				$is_date = $this->config->isDateType($group_internal_name);
				
				if ( $is_date == true )
				{
					$date_arrays = $group->luceneDateToDecade($facet_array);
					$decade_display = $date_arrays["display"];
					$facet_array = $date_arrays["decades"];		
				}
				else 
				{	
					// not a date, sort by hit count
					
					arsort($facet_array);
				}
				
				// now make them into group facet objects
				
				foreach ( $facet_array as $key => $value )
				{
					$public_value = $this->config->getValuePublicName($group_internal_name, $key);
					
					$facet = new Xerxes_Model_Search_Facet();
					$facet->name = $public_value;
					$facet->count = $value;
					
					// dates are different
					
					if ( $is_date == true )  
					{
						$facet->name = $decade_display[$key];
						$facet->is_date = true;
						$facet->key = $key;
					}					
					
					$group->addFacet($facet);
				}
				
				$facets->addGroup($group);
			}
		}
		
		return $facets;
	}
}
