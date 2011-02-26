<?php

/**
 * Solr Search Engine
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Solr
 */

class Xerxes_Model_Solr_Engine extends Xerxes_Model_Search_Engine 
{
	protected $server; // solr server address
	protected $config; // config?
	protected $url;

	/**
	 * Constructor
	 * 
	 * @param string $server	address of the solr server
	 */
	
	public function __construct($server)
	{
		parent::__construct();
		
		$this->server = $server;
		
		if ( substr($this->server,-1,1) != "/" )
		{
			$this->server .= "/";
		}
		
		$this->server .= "select/?version=2.2";

		// local config
		
		$this->config = Xerxes_Model_Solr_Config::getInstance();
		
		// change max, if set
		
		$this->max = $this->config->getConfig("MAX_RECORDS_PER_PAGE", false, $this->max);	
	}
	
	/**
	 * Return the total number of hits for the search
	 * 
	 * @return int
	 */	
	
	public function getHits( Xerxes_Model_Search_Query $search )
	{
		$results = $this->searchRetrieve($search, 0, 0, null, false);
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
		$this->url = $this->server .= "&q=" . urlencode("id:$id");
		$results = $this->doSearch($this->url);

		$results->getRecord(0)->addRecommendations();
		
		return $results;
		
	}	
	
	/**
	 * Search and return results
	 * 
	 * @return Xerxes_Model_Search_Results
	 */	
	
	public function searchRetrieve( Xerxes_Model_Search_Query $search, $start, $max = null, $sort = null, $include_facets = true)
	{
		### defaults
		
		// sort
		
		if ( $sort == null )
		{
			$sort = $this->config->getConfig("SORT_ORDER_PRIMARY", false, $this->sort);
		}
		
		$sort = $this->config->swapForInternalSort($sort);
		
		// start
		
		if ( $start > 0)
		{
			$start--; // solr is 0-based
		}
		
		// override the max
		
		if ( $max !== null )
		{
			$this->max = $max; 
		}
		
		### parse the query
		
		$query = ""; // query, these are url params, not just the query itself
		$type = ""; // dismax or standard
	
		// get just the first term for now
		
		$terms = $search->getQueryTerms();
		$term = $terms[0];
		
		// decide between basic and dismax handler
		
		$trunc_test = $this->config->getFieldAttribute($term->field, "truncate");
		
		// use dismax if this is a simple search, that is:
		// only if there is one phrase (i.e., not advanced), no boolean OR and no wildcard

		if ( count($terms) == 1 && 
			! strstr($term->phrase, " OR ") && 
			! strstr($term->phrase, "*") && 
			$trunc_test == null )
		{
			# dismax
			
			$type = "&defType=dismax";

			$term = $terms[0];
			
			$phrase = $term->phrase;
			$phrase = strtolower($phrase);
			$phrase = str_replace(" NOT ", " -", $phrase);
			
			if ( $term->field != "" )
			{
				$query .= "&qf=" . urlencode($term->field);
				$query .= "&pf=" . urlencode($term->field);
			}
	
			$query .= "&q=" . urlencode($phrase);
		}
		else
		{
			# standard
			
			$query = "";
			
			foreach ( $terms as $term )
			{
				$phrase = $term->phrase;
				$phrase = strtolower($phrase);
				$phrase = str_replace(':', '', $phrase);
				$phrase = $this->alterQuery($phrase, $term->field, $this->config);
				
				// break up the query into words
				
				$objQuery = new Xerxes_QueryParser();
				$arrQuery = $objQuery->normalizeArray( $phrase, false );
				
				// we'll now search for this term across multiple fields
				// specified in the config
	
				if ( $term->field != "" )
				{
					// we'll use this to get the phrase as a whole, but minus
					// the boolean operators in order to boost this
					
					$boost_phrase = ""; 
					
					foreach ( $arrQuery as $strPiece )
					{
						// just add the booelan value straight-up
						
						if ( $strPiece == "AND" || $strPiece == "OR" || $strPiece == "NOT" )
						{
							$query .= " $strPiece ";
							continue;			
						}
						
						$boost_phrase .= " " . $strPiece;
						
						// try to mimick dismax query handler as much as possible
						
						$query .= " (";
						$local = array();
						
						// take the fields we're searching on,
						
						foreach ( explode(" ", $term->field) as $field )
						{
							// split them out into index and boost score
						
							$parts = explode("^",$field);
							$field_name = $parts[0];
							$boost = "";
							
							// make sure there really was a  boost score
							
							if ( array_key_exists(1,$parts) )
							{
								$boost = "^" . $parts[1];
							}
							
							// put them together 
							
							array_push($local, $field_name . ":" . $strPiece . $boost);
						}
						
						$query .= implode(" OR ", $local);
							
						$query .= " )";
					}
					
					// $boost_phrase = trim($boost_phrase);
					// $query = "($query) OR \"" . $boost_phrase . '"';
				}
			}
			
			$query = "&q=" . urlencode($query);
		}
		
		// facets selected
		
		foreach ( $search->getLimits() as $limit )
		{
			if ( strstr($limit->field,"facet.date") ) // dates are an exception
			{
				$field = str_replace("facet.date.", "", $limit->field);
				$index = Xerxes_Framework_Parser::removeRight($field,".");
				$value = Xerxes_Framework_Parser::removeLeft($field,".");
			}
			elseif ( strstr($limit->field,"facet") )
			{
				$index = str_replace("facet.", "", $limit->field);
				$value = '"' . $limit->value . '"';
			}
			
			$query .= "&fq=" . urlencode( "$index:$value");
		}
		
		$auto_limit = $this->config->getConfig("LIMIT", false);
		
		if ( $auto_limit != null )
		{
			$query .= "&fq=" . urlencode($auto_limit);
		}
		
		
		### now the url
		
		
		$this->url = $this->server . $type . $query;

		$this->url .= "&start=$start&rows=" . $this->max . "&sort=" . urlencode($sort);
		
		if ( $include_facets == true )
		{
			$this->url .= "&facet=true&facet.mincount=1";
			
			foreach ( $this->config->getFacets() as $facet => $attributes )
			{
				$sort = (string) $attributes["sort"];
				$max = (string) $attributes["max"];
				
				$this->url .= "&facet.field=$facet";

				if ( $sort != "" )
				{
					$this->url .= "&f.$facet.facet.sort=$sort";
				}				
				
				if ( $max != "" )
				{
					$this->url .= "&f.$facet.facet.limit=$max";
				}					
			}
		}
		
		return $this->doSearch($this->url);
	}
	
	/**
	 * Actually send the url to Solr and parse the response
	 * 
	 * @param string $url 
	 * @return Xerxes_Model_Search_ResultSet
	 */

	private function doSearch($url)
	{
		// get the data
		
		$response = Xerxes_Framework_Parser::request($url, 10);
		$xml = simplexml_load_string($response);
		
		if ( $response == null || $xml === false )
		{
			throw new Exception("Could not connect to search engine.");
		}
		
		// parse the results
		
		$results = new Xerxes_Model_Search_ResultSet($this->config);
		
		// extract total
		
		$results->total = (int) $xml->result["numFound"]; 
		
		// extract records
		
		foreach ( $this->extractRecords($xml) as $record )
		{
			$results->addRecord($record);
		}
		
		// extract facets
		
		$results->facets = $this->extractFacets($xml);
		
		return $results;
	}
	
	/**
	 * Extract records from the Solr response
	 * 
	 * @param simplexml	$xml	solr response
	 * @return array of Xerxes_Model_Search_Records
	 */	
	
	protected function extractRecords($xml)
	{
		$records = array();
		$docs = $xml->xpath("//doc");
		
		if ( $docs !== false && count($docs) > 0 )
		{
			foreach ( $docs as $doc )
			{
				$id = null;
				$format = null;
				$xml_data = "";
				
				foreach ( $doc->str as $str )
				{
					// marc record
											
					if ( (string) $str["name"] == 'fullrecord' )
					{
						$marc = trim( (string) $str );
						
						// marc-xml or marc-y marc -- come on, come on, feel it, feel it!
						
						if ( substr($marc, 0, 5) == '<?xml')
						{
							$xml_data = $marc;
						}
						else
						{
					        $marc = preg_replace('/#31;/', "\x1F", $marc);
					        $marc = preg_replace('/#30;/', "\x1E", $marc);
					        
					        $marc_file = new File_MARC($marc, File_MARC::SOURCE_STRING);
					        $marc_record = $marc_file->next();
					        $xml_data = $marc_record->toXML();
						}
					}
					
					// record id
					
					elseif ( (string) $str["name"] == 'id' )
					{
						$id = (string) $str;
					}
				}
				
				// format
				
				foreach ( $doc->arr as $arr )
				{
					if ( $arr["name"] == "format" )
					{
						$format = (string) $arr->str;
					}
				}
				
				$record = new Xerxes_Record();
				$record->loadXML($xml_data);
				
				$record->setRecordID($id);
				$record->setFormat($format);
				
				array_push($records, $record);
			}
		}
		
		return $records;
	}
	
	/**
	 * Extract facets from the Solr response
	 * 
	 * @param simplexml	$xml	solr response
	 * @return Xerxes_Model_Search_Facets, null if none
	 */
	
	protected function extractFacets($xml)
	{
		$groups = $xml->xpath("//lst[@name='facet_fields']/lst");
		
		if ( $groups !== false && count($groups) > 0 )
		{
			$facets = new Xerxes_Model_Search_Facets();
			
			$strThousSep = $this->registry->getConfig( "HITS_THOUSANDS_SEPERATOR", false, "," );
			
			foreach ( $groups as $facet_group )
			{
				// if only one entry, then all the results have this same facet,
				// so no sense even showing this as a limit
				
				$count = count($facet_group->int);
				
				if ( $count <= 1 )
				{
					continue;
				}
				
				$group_internal_name = (string) $facet_group["name"];
				
				$group = new Xerxes_Model_Search_FacetGroup();
				$group->name = $group_internal_name;
				$group->public = $this->config->getFacetPublicName($group_internal_name);
				
				// put facets into an array
				
				$facet_array = array();
				
				foreach ( $facet_group->int as $int )
				{
					$facet_array[(string)$int["name"]] = (int) $int;
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
				
				foreach ( $facet_array as $key => $value )
				{
					$facet = new Xerxes_Model_Search_Facet();
					$facet->name = $key;
					$facet->count = number_format( $value, 0, null, $strThousSep);
					
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
			
			return $facets;
		}
		else
		{
			return null;
		}
	}
}
