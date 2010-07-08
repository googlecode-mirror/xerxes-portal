<?php

/**
 * Search framework
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

abstract class Xerxes_Framework_Search
{
	public $id; // name of this search engine
	public $total; // total number of hits
	public $sort; // sort order
	public $query_normalized = ""; // normalized form of the query string
	public $query_hash = ""; // md5 rep of the normalized query
		
	protected $search_object_type = "Xerxes_Framework_Search_Engine";
	protected $query_object_type = "Xerxes_Framework_Search_Query";
	protected $record_object_type = "Xerxes_Record";
	
	protected $max = 10; // maximum records per page
	protected $sort_default = "relevance"; // default sort (this is the id)
	protected $sid; // sid for open url identification
	protected $link_resolver; // base address of link resolver	
	
	protected $search_fields_regex = "^query[0-9]{0,1}$|^field[0-9]{0,1}$|^boolean[0-9]{0,1}$";
	protected $limit_fields_regex = "^facet.*";
	protected $include_original;

	protected $results = array(); // search results
	protected $facets; // facets
	protected $recommendations = array(); // recommendations

	protected $query; // query object
	protected $config; // local config object
	protected $search_object; // search object
	protected $data_map; // data map object

	protected $request; // xerxes request object
	protected $registry; // xerxes global config object	
	
	public function __construct($objRequest, $objRegistry)
	{
		// make these available
		
		$this->request = $objRequest;
		$this->registry = $objRegistry;
		
		// local config
		
		$this->config = $this->getConfig();
		$this->request->addDocument($this->config->publicXML());
		
		// database access object
				
		$this->data_map = new Xerxes_DataMap();
		
		// facet object
		
		$this->facets = new Xerxes_Framework_Search_Facets();
		
		// set an instance of the query object
		
		$query_object = $this->query_object_type;
		$this->query = new $query_object();
		
		// populate it with the 'search' related params out of the url, 
		// these are things like query, field, boolean
		
		foreach ( $this->extractSearchGroupings() as $term )
		{
			$this->query->addTerm($term["id"], $term["boolean"], $this->swapForInternalField($term["field"]), $term["relation"], $term["query"]);
		}

		// also limits
		
		foreach ( $this->extractLimitGroupings() as $limit )
		{
			$this->query->addLimit($limit["field"], $limit["relation"], $limit["value"]);
		}			
		
		// config stuff
		
		$this->link_resolver = $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
		$this->sid = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
		$this->max = $this->registry->getConfig("RECORDS_PER_PAGE", false, 10);
		$this->include_original = $this->registry->getConfig("INCLUDE_ORIGINAL_XML", false, false);
		$this->max = $this->registry->getConfig("MAX_RECORDS_PER_PAGE", false, $this->max);	
		
		// used in a couple of place

		$this->sort = $this->request->getProperty("sortKeys");
		
		if ( $this->sort == null )
		{
			$this->sort = $this->sort_default;
		}
		
		// search object 
		
		$search_object_type = $this->search_object_type;
		$this->search_object = new $search_object_type();
		
		// calculate the normalized forms
		
		$this->calculateHash();
	}
	
	/**
	 * Subclass needs to define this to set the local config object
	 */
	
	protected abstract function getConfig();
	
	
	############
	#  PUBLIC  #
	############
	
	
	/**
	 * Any action that needs to take place on the home page search
	 */
	
	public function home()
	{
		
	}
	
	/**
	 * Initiate the search, check spelling, and forward to results
	 */
	
	public function search()
	{
		$base = $this->searchRedirectParams();
		$params = $this->getAllSearchParams();
		
		$params = array_merge($base, $params);
		
		// check spelling
		
		if ( $this->request->getProperty("spell") != "none" )
		{
			$spelling = $this->query->checkSpelling();
			
			foreach ( $spelling as $key => $correction )
			{
				$params["spelling_$key"] = $correction;
			}
		}
		
		$url = $this->request->url_for($params);
		
		$this->request->setRedirect($url);
	}

	/**
	 * Just get the hit counts on the search; in a metasearch, 
	 * this checks the the progress of the search
	 */	
	
	public function hits()
	{
		$hits = $this->search_object->hits($this->query);
		$this->request->addData("hits", "num", $hits);
	}
	
	/**
	 * Brief results, with paging navigation and other enhancements supplied
	 */
	
	public function results()
	{
		// start, stop, source, sort properties
		
		$start = $this->request->getProperty("startRecord");
		$max = $this->request->getProperty("maxRecords");

		// set some explicit defaults
		
		if ( $start == null || $start == 0 ) $start = 1;
		if ( $max != null && $max <= $this->max ) $this->max = $max;
		
		// we use public ids for some sort options, now switch it 
		// for the real internal sort field
		
		$sort = $this->swapForInternalSort($this->sort);
		
		// get results
		
		$xml = $this->search_object->searchRetrieve($this->query, $start, $this->max, $sort);
		
		// convert them to xerxes_record
		
		$this->results = $this->convertToXerxesRecords($xml);
		
		// extract any facets
		
		$this->extractFacets($xml);
		
		// mark peer-reviewed journals
		
		$this->markRefereed();
		
		// done
		
		$this->request->addDocument($this->resultsXML());
	}
	
	/**
	 * The full record page
	 */
	
	public function record()
	{
		$id = $this->request->getProperty("id");
		
		$xml = $this->search_object->record($id);
		
		$this->results = $this->convertToXerxesRecords($xml);
		
		$this->markRefereed();
		
		$this->request->addDocument($this->resultsXML());
	}
	
	/**
	 * Holdings look-up, via AJAX
	 */

	public function lookup()
	{
		$source = $this->request->getProperty("source");
		$id = $this->request->getProperty("id");
		$isbn = $this->request->getProperty("isbn");
		$oclc = $this->request->getProperty("oclc");
		
		$standard_numbers = array();
		
		if ( $id != null )
		{
			array_push($standard_numbers, "ID:$id");
		}
		if ( $isbn != null )
		{
			array_push($standard_numbers, "ISBN:$isbn");
		}
		if ( $oclc != null )
		{
			array_push($standard_numbers, "OCLC:$oclc");
		}		
		
		$xml = $this->getHoldings($source, $standard_numbers);
		
		$this->request->addDocument($xml);
	}	
	
	/**
	 * Generic option to do some post-processing before linking to a site
	 */
	
	public function bounce()
	{
		
	}
	
	/**
	 * Save or delete a record from this search
	 */
	
	public function saveDelete()
	{
		$username = $this->request->getSession("username");
		$original_id = $this->request->getProperty("id");
		$inserted_id = "";
		
		$already_added = $this->isMarkedSaved( $original_id );
		
		## insert or delete the record
		
		if ( $already_added == true )
		{
			// delete command
	
			$this->data_map->deleteRecordBySource( $username, $this->id, $original_id );
			$this->unmarkSaved( $original_id );
		}
		else
		{
			// add command
	
			// get record 
			
			$xml = $this->search_object->record($original_id);
			
			// convert it
			
			$this->results = $this->convertToXerxesRecords($xml);
			$record = $this->results[0];
				
			// add to database
	
			$this->data_map->addRecord( $username, $this->id, $original_id, $record );
			
			$inserted_id = $record->id;
			
			// mark saved for feedback on search results
				
			$this->markSaved( $original_id, $inserted_id );
		} 

		## build a response
	
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<results />" );
			
		if ( $already_added == true )
		{
			// flag this as being a delete comand in the view, in the event
			// user has javascript turned off and we need to show them an actual page

			$objDelete = $objXml->createElement( "delete", "1" );
			$objXml->documentElement->appendChild( $objDelete );
		} 
		else
		{
			// add inserted id for ajax response
			
			$objInsertedId = $objXml->createElement( "savedRecordID", $inserted_id );
			$objXml->documentElement->appendChild( $objInsertedId );
		}
		
		$this->request->addDocument($objXml);	
	}
	
	
	#################
	#  RESULTS XML  #
	#################
	
	
	/**
	 * Take search results and convert them to xml, with all the enhancements, including facets
	 */
	
	public function resultsXML()
	{
		$this->url = $this->search_object->getURL();
		
		if ( $this->total == null )
		{
			$this->total = $this->search_object->getTotal();
		}
		
		$results_xml = new DOMDocument( );
		$results_xml->loadXML( "<results />" );
		
		// spelling

		$spelling_url = $this->linkSpelling();
		
		$spelling = $results_xml->createElement( "spelling", Xerxes_Framework_Parser::escapeXml( $this->request->getProperty("spelling_query")));
		$spelling->setAttribute("url", $spelling_url);
		
		$results_xml->documentElement->appendChild( $spelling );
		
		// add in the original url for debugging
		
		$search_url = $results_xml->createElement( "search_url", Xerxes_Framework_Parser::escapeXml( $this->url ) );
		$results_xml->documentElement->appendChild( $search_url );

		// add total
		
		$total = $results_xml->createElement("total", $this->total);
		$results_xml->documentElement->appendChild( $total );
		
		// add facets that have been selected
		
		$facets_chosen = $this->request->getProperties("facet.*", true);
		
		if ( count($facets_chosen) > 0 )
		{
			$facet_applied = $results_xml->createElement("facets_applied");
			$results_xml->documentElement->appendChild( $facet_applied );
			
			foreach ( $facets_chosen as $key => $facet )
			{
				$facet_level = $results_xml->createElement("facet_level", Xerxes_Framework_Parser::escapeXml($facet));
				$facet_applied->appendChild($facet_level);
				
				$url = new Xerxes_Framework_Request_URL($this->currentParams());
				$url->removeProperty($key, $facet);
				$remove_url = $this->request->url_for($url);
				
				$facet_level->setAttribute("url", $remove_url);
			}
		}
		
		if ( count($this->results) > 0 )
		{
			## records
			
			$records_xml = $results_xml->createElement("records");
			$results_xml->documentElement->appendChild( $records_xml );
			
			foreach ( $this->results as $result )
			{
				$record_container = $results_xml->createElement( "record" );
				$records_xml->appendChild( $record_container );				
				
				// full-record link

				$record_link = Xerxes_Framework_Parser::escapeXml($this->linkFullRecord($result));
				$link_full = $results_xml->createElement("url", $record_link);
				$record_container->appendChild( $link_full );
				
					// this one for backwards compatability
	
					$link_full = $results_xml->createElement("url_full", $record_link);
					$record_container->appendChild( $link_full );					
				
				// open-url link (which may be a redirect)

				$record_openurl = Xerxes_Framework_Parser::escapeXml($this->linkOpenURL($result));
				$link_full = $results_xml->createElement("url_open", $record_openurl);
				$record_container->appendChild( $link_full );

				// save or delete link

				$record_save = Xerxes_Framework_Parser::escapeXml($this->linkSaveRecord($result));
				$link_save = $results_xml->createElement("url_save", $record_save);
				$record_container->appendChild( $link_save );

					// this one for backwards compatability
				
					$link_save = $results_xml->createElement("url_save_delete", $record_save);
					$record_container->appendChild( $link_save );		
				
		      	// openurl kev context object please
		      	
				$kev = Xerxes_Framework_Parser::escapeXml($result->getOpenURL(null, $this->sid));
				$open_url = $results_xml->createElement("openurl_kev_co", $kev);
				$record_container->appendChild( $open_url );

				// other links (probably things like author, subject links)
				
				$this->linkOther($result, $results_xml, $record_container);
				
				// xerxes-record
				
				$xerxes_xml = $result->toXML();
				$import = $results_xml->importNode( $xerxes_xml->documentElement, true );
				$record_container->appendChild( $import );

				// optionally import original xml

				if ( $this->include_original == true )
				{
					$original_xml = $result->getOriginalXML();
					$import = $results_xml->importNode( $original_xml, true );
					$record_container->appendChild( $import );
				}
			}
			
			## recommendations
			
			if ( count($this->recommendations) > 0 )
			{
				$recommend_xml = $results_xml->createElement("recommendations");
				$results_xml->documentElement->appendChild( $recommend_xml );
								
				foreach ( $this->recommendations as $record )
				{
					$record_xml = $results_xml->createElement("record");
					$recommend_xml->appendChild($record_xml);
					
					$import = $results_xml->importNode($record->toXML()->documentElement, true);
					$record_xml->appendChild($import);
					
					$open_url = $record->getOpenURL($this->link_resolver, $this->sid);
					
					$open_url_xml = $results_xml->createElement("url_open", Xerxes_Framework_Parser::escapeXML($open_url));
					$record_xml->appendChild($open_url_xml);
				}
			}

			$objPage = new Xerxes_Framework_Page();

			
			## summary
			
			$summary_xml = $objPage->summary(
				$this->total,
				(int) $this->request->getProperty("startRecord"),
				$this->max
				);
				
			if ( $summary_xml->documentElement != null )
			{
				$import = $results_xml->importNode( $summary_xml->documentElement, true );
				$results_xml->documentElement->appendChild($import);
			}
			
			## sorting

			$arrParams = $this->sortLinkParams();
				
			$query_string = $this->request->url_for($arrParams);
			
			$sort_options = $this->sortOptions();
			
			$current_sort = $this->sort;
			
			if ( $current_sort == null )
			{ 
				$current_sort = $this->sort_default;
			}
			
			$sort_xml = $objPage->sortDisplay( $query_string, $current_sort, $sort_options);

			$import = $results_xml->importNode( $sort_xml->documentElement, true );
			$results_xml->documentElement->appendChild($import);
			
			## pager
			
			$arrParams = $this->pagerLinkParams();
			
			$pager_xml = $objPage->pager_dom(
				$arrParams,
				"startRecord", (int) $this->request->getProperty("startRecord"),
				null,  (int) $this->total, 
				$this->max, $this->request
			);

			$import = $results_xml->importNode( $pager_xml->documentElement, true );
			$results_xml->documentElement->appendChild($import);			
		}
		
		## facets
		
		$facets = $this->facets->toXML();
		$import = $results_xml->importNode($facets->documentElement, true);
		$results_xml->documentElement->appendChild($import);
		
		return $results_xml;
	}
	
	
	##############
	#   LINKS    #
	##############
	
	
	/**
	 * Link for spelling correction
	 */
	
	protected function linkSpelling()
	{
		$params = $this->currentParams();
		$params["query"] = $this->request->getProperty("spelling_query");
		
		return $this->request->url_for($params);
	}
	
	/**
	 * URL for the full record display
	 * 
	 * @param $result Xerxes_Record object
	 * @return string url
	 */
	
	protected function linkFullRecord($result)
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "record",
			"id" => $result->getRecordID()
		);
		
		return $this->request->url_for($arrParams);
	}

	/**
	 * URL for the full record display
	 * 
	 * @param Xerxes_Record $result
	 * @return string url
	 */
	
	protected function linkSaveRecord($result)
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "save-delete",
			"id" => $result->getRecordID()
		);
		
		return $this->request->url_for($arrParams);
	}
	/**
	 * OpenURL link
	 * 
	 * @param Xerxes_Record $result 
	 * @return string url
	 */	
	
	protected function linkOpenURL($result)
	{
		return $result->getOpenURL($this->link_resolver, $this->sid);
	}

	/**
	 * Other links for the record beyond those supplied by the framework,
	 * such as lateral subject or author links; calling code needs to insert 
	 * directly into xml
	 * 
	 * @param Xerxes_Record $result 
	 * @param DOMDocument $results_xml the xml document to add your links to
	 * @param DOMNode $record_container the insertion point
	 */	
	
	protected function linkOther($result, $results_xml, $record_container)
	{
		
	}
	

	##########################
	#  SORT & FIELD OPTIONS  #
	##########################
	
	/**
	 * The options for the sorting mechanism
	 * @return array
	 */
	
	protected function sortOptions()
	{
		$options = array();
		
		$config = $this->config->getConfig("sort_options");
		
		if ( $config != null )
		{
			foreach ( $config->option as $option )
			{
				$options[(string)$option["id"]] = (string) $option["public"];
			}
		}
		
		return $options;
	}
	
	/**
	 * Swap the sort id for the internal sort option
	 * @param string $id 	public id
	 * @return string 		the internal sort option
	 */
	
	protected function swapForInternalSort($id)
	{
		$config = $this->config->getConfig("sort_options");
		
		if ( $config != null )
		{
			foreach ( $config->option as $option )
			{
				if ( (string) $option["id"] == $id )
				{
					return (string) $option["internal"];
				}
			}			
		}
		
		// if we got this far no mapping, so return original
		
		return $id; 
	}

	/**
	 * Swap the field id for the internal field index
	 * @param string $id 	public id
	 * @return string 		the internal field
	 */	
	
	protected function swapForInternalField($id)
	{
		$config = $this->config->getConfig("basic_search_fields");
		
		if ( $config != null )
		{
			foreach ( $config->field as $field )
			{
				if ( (string) $field["id"] == $id )
				{
					return (string) $field["internal"];
				}
			}			
		}
		
		// if we got this far no mapping, so return original
		
		return $id; 
	}	
	
	
	######################
	#  PARAMS FOR LINKS  #
	######################
	
	
	/**
	 * The current search-related parameters 
	 * @return array
	 */
	
	protected function currentParams()
	{
		$params = $this->getAllSearchParams();
		$params["base"] = $this->request->getProperty("base");
		$params["action"] = $this->request->getProperty("action");
		$params["sortKeys"] = $this->request->getProperty("sortKeys");
												
		return $params;
	}	
	
	/**
	 * Parameters to construct the url on the search redirect
	 * @return array
	 */
	
	protected function searchRedirectParams()
	{
		$params = $this->currentParams();
		$params["action"] = "results";
		
		return $params;
	}
	
	/**
	 * Parameters to construct the links for the paging element
	 * @return array
	 */
	
	protected function pagerLinkParams()
	{
		$params = $this->currentParams();
		return $params;
	}
	
	/**
	 * Parameters to construct the links for the sort
	 * @return array
	 */
	
	protected function sortLinkParams()
	{
		$params = $this->currentParams();
		
		// remove the current sort, since we'll add the new
		// sort explicitly to the url
		
		unset($params["sortKeys"]);
		
		return $params;
	}
	
	/**
	 * Extract both query and limit params from the URL
	 * @return array
	 */
	
	protected function getAllSearchParams()
	{
		$limits = $this->extractLimitParams();
		$search = $this->extractSearchParams();
		
		return array_merge($search, $limits);
	}		
	
	
	########################
	#  EXTRACT URL PARAMS  #
	########################	
	
	
	/**
	 * Get 'limit' params out of the URL, sub-class defines this
	 * @return array
	 */	
	
	protected function extractLimitParams()
	{
		if ( $this->limit_fields_regex != "" )
		{
			return $this->request->getProperties($this->limit_fields_regex);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get 'search' params out of the URL
	 * @return array
	 */		
	
	protected function extractSearchParams()
	{
		if ( $this->search_fields_regex != "" )
		{
			return $this->request->getProperties($this->search_fields_regex);
		}
		else
		{
			return array();
		}
	}
	
	protected function calculateHash()
	{
		// get the search params and sort them alphabetically
		
		$params = $this->getAllSearchParams();
		ksort($params);
		
		$this->query_normalized = "";
		
		// now put them back together in a normalized form
		
		foreach ( $params as $key => $value )
		{
			if ( is_array($value) )
			{
				foreach ($value as $part)
				{
					$this->query_normalized .= "&amp;$key=" . urlencode($part);
				}
			}
			else
			{
				$this->query_normalized .= "&amp;$key=" . urlencode($value);
			}
		}
		
		// give me the hash!
		
		$this->query_hash = md5($this->query_normalized);
	}
	
	/**
	 * Get 'limit' params out of the URL, organized into groupings for the 
	 * query object to parse
	 * @return array
	 */	
	
	protected function extractLimitGroupings()
	{
		$arrFinal = array();
		
		if ( $this->limit_fields_regex != "" )
		{
			foreach ( $this->extractLimitParams() as $key => $value )
			{
				if ( $value == "" )
				{
					continue;
				}
				
				$key = urldecode($key);
				
				if ( strstr($key, "_relation") )
				{
					continue;
				}
				
				$arrTerm = array();
				
				$arrTerm["field"] = $key;
				$arrTerm["relation"] = "=";
				$arrTerm["value"] = $value;
				
				$relation = $this->request->getProperty($key . "_relation");
				
				if ( $relation != null )
				{
					$arrTerm["relation"] = $relation;
				}
				
				array_push($arrFinal, $arrTerm);
			}
		}
		
		return $arrFinal;
	}	
	
	/**
	 * Get 'search' params out of the URL, organized into groupings for the 
	 * query object to parse
	 * @return array
	 */		
	
	protected function extractSearchGroupings()
	{
		$arrFinal = array();
		
		foreach ( $this->request->getAllProperties() as $key => $value )
		{
			$key = urldecode($key);
			
			// if we see 'query' as the start of a param, check if there are corresponding
			// entries for field and boolean; these will have a number after them
			// if coming from an advanced search form
				
			if ( preg_match("/^query/", $key) )
			{
				if ( $value == "" )
				{
					continue;
				}			
				
				$arrTerm = array();
				$arrTerm["id"] = $key;
				$arrTerm["relation"] = "=";
				
				$id = str_replace("query", "", $key);
				
				$boolean_id = "";
			
				if ( is_numeric($id) )
				{
					$boolean_id = $id - 1;
				}
				
				$arrTerm["query"] = $value;
				$arrTerm["field"] = $this->request->getProperty("field$id");
				
				// boolean only counts if this is not the first quert term
				
				if ( count($arrFinal) > 0 )
				{
					$arrTerm["boolean"] = $this->request->getProperty("boolean" . ( $boolean_id ) );
				}
				else
				{
					$arrTerm["boolean"] = "";
				}
				
				array_push($arrFinal, $arrTerm);
			}
		}
		
		return $arrFinal;
	}


	###############################
	#  XERXES RECORD ENHANCEMENT  #
	###############################		
	
	
	/**
	 * Convert MARC-XML response to Xerxes Record, 
	 * record sub-class can also map
	 */

	protected function convertToXerxesRecords(DOMDocument $xml)
	{
		$doc_type = $this->record_object_type . "_Document";
		$xerxes_doc = new $doc_type();
		$xerxes_doc->loadXML($xml);
		
		return $xerxes_doc->records();
	}
	
	/**
	 * Enhance record with bx recommendations
	 */
	
	protected function recommendations()
	{
		// only the first one yo!
		
		$record = $this->results[0];
		
		$configToken = $this->registry->getConfig("BX_TOKEN", false);
						
		if ( $configToken != null )
		{
			$configBX = $this->registry->getConfig("BX_SERVICE_URL", false, "http://recommender.service.exlibrisgroup.com/service");
			$configSID = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
				
			$open_url = $record->getOpenURL(null, $configSID);
				
			$url = $configBX . "/recommender/openurl?token=" . $configToken . "&" . $open_url;
				
			$xml = Xerxes_Framework_Parser::request($url);

			// header("Content-type: text/xml"); echo $xml; exit;
				
			$doc = new Xerxes_BxRecord_Document();
			$doc->loadXML($xml);
			
			$records = $doc->records();
			
			for ( $x = 1; $x < count($records); $x++ )
			{
				array_push($this->recommendations, $records[$x]);
			} 
		}
	}
	
	/**
	 * Add a peer-reviewed indicator for refereed journals
	 */
	
	protected function markRefereed()
	{
		// extract all the issns from the available records in one
		// single shot to make this more efficient
		
		$issns = $this->extractISSNs();

		if ( count($issns) > 0 )
		{		
			// get all from our peer-reviewed list
			
			$refereed_list = $this->data_map->getRefereed($issns);
			
			// now mark the records that matched
			
			for ( $x = 0; $x < count($this->results); $x++ )
			{
				$record = $this->results[$x];
				
				// check if the issn matched
				
				foreach ( $refereed_list as $refereed )
				{
					if ( in_array($refereed->issn,$record->getAllISSN()))
					{
						$record->setRefereed(true);
					}
				}
				
				$this->results[$x] = $record;
			}
		}
	}
	
	/**
	 * Add a full-text indicator for those records where link resolver indicates it
	 */
	
	protected function markFullText()
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
				
			for ( $x = 0; $x < count($this->results); $x++ )
			{
				$xerxes_record = $this->results[$x];
				
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
							$xerxes_record->setSubsctiption(true);
						}
					}
				}
				
				$this->results[$x] = $xerxes_record;
			}
		}		
	}

	
	########################
	#  SAVED RECORD STATE  #
	########################	
		
	
	// functions for saving saved record state from a result set in session
	// this is used for knowing whether to add or delete on a 'toggle' command
	// and also used for knowing whether to display a result line as saved or not. 
	
	protected function markSaved($original_id, $saved_id)
	{
		$_SESSION['resultsSaved'][$original_id]['xerxes_record_id'] = $saved_id;
	}
	
	protected function unmarkSaved($original_id)
	{
		if ( array_key_exists( "resultsSaved", $_SESSION ) && array_key_exists( $original_id, $_SESSION["resultsSaved"] ) )
		{
			unset( $_SESSION['resultsSaved'][$original_id] );
		}
	}
	
	protected function isMarkedSaved($original_id)
	{
		if ( array_key_exists( "resultsSaved", $_SESSION ) && array_key_exists( $original_id, $_SESSION["resultsSaved"] ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	protected function numMarkedSaved()
	{
		$num = 0;
		
		if ( array_key_exists( "resultsSaved", $_SESSION ) )
		{
			$num = count( $_SESSION["resultsSaved"] );
		}
		
		return $num;
	}
	

	###############################
	#  EXTRACT DATA FROM RESULTS  #
	###############################		
	
	
	/**
	 * Extract facets from the xml response, if available
	 */
	
	protected function extractFacets(DOMDocument $xml)
	{
		
	}	
	
	/**
	 * Extract all the ISSNs from the records, convenience funciton
	 */

	protected function extractISSNs()
	{
		$issns = array();
		
		foreach ( $this->results as $record )
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
		
		foreach ( $this->results as $record )
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
		
		foreach ( $this->results as $record )
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
		
		foreach ( $this->results as $record )
		{
			array_push($id, $record->getRecordID() );
		}
		
		$id = array_unique($id);
		
		return $id;
	}

	
	###################
	#  HOLDINGS DATA  #
	###################
	

	// this is pretty hacky until the new dlf-ils group come up 
	// with something that we can use
	
	protected function getHoldings($strSource, $arrIDs, $bolCache = false)
	{
		$url = $this->getHoldingsURL($strSource);
		
		$objXml = new DOMDocument();
		
		if ( $url == null )
		{
			$objXml->loadXML("<no_holdings />");
			return $objXml;
		}
		
		if ( $url != null && count($arrIDs) > 0 )
		{
			$id = implode(",", $arrIDs);
			
			if ( $bolCache == true)
			{
				$url .= "?action=cached&id=$id";
				
				$xml = Xerxes_Framework_Parser::request($url);
				
				if ( $xml != "" )
				{
					$objXml->loadXML($xml);
				}
				else
				{
					return $objXml;
				}
			}
			else
			{
				$objXml->loadXML("<cached />");
				
				$objObject = $objXml->createElement("object");
				$objObject->setAttribute("id", $id);
				$objXml->documentElement->appendChild($objObject);			
				
				$url .= "?action=records&id=$id&sameRecord=true";
				
				$xml = Xerxes_Framework_Parser::request($url);
				
				if ( $xml != "" )
				{
					$objRecord = new DOMDocument();
					$objRecord->recover = true;
					$objRecord->loadXML($xml);
					
					if ( $objRecord->documentElement instanceof DOMNode)
					{
						$objImport = $objXml->importNode($objRecord->documentElement, true);		
						$objObject->appendChild($objImport);
					}
				}
			}
			
			$objXml->documentElement->setAttribute("url", Xerxes_Framework_Parser::escapeXml($url));
		}
		
		return $objXml;
	}
	
	protected function getHoldingsURL($id)
	{
		return null;
	}
	
	protected function getHoldingsInject($bolCacheOnly = true)
	{
		$source = $this->request->getProperty("source");
		if ( $source == null ) $source = "local";
		
		$isbns = $this->extractISBNs();
		$oclcs = $this->extractOCLCNumbers();
		$ids = $this->extractRecordIDs();
		
		$standard_numbers = array();
		
		foreach ( $ids as $id )
		{
			array_push($standard_numbers, "ID:$id");
		}
		
		foreach ( $isbns as $isbn )
		{
			array_push($standard_numbers, "ISBN:$isbn");
		}
			
		foreach ( $oclcs as $oclc )
		{
			array_push($standard_numbers, "OCLC:$oclc");
		}
		
		// get any data we found in the cache for these records
							
		$objXml = $this->getHoldings($source, $standard_numbers, $bolCacheOnly);
			
		$this->request->addDocument($objXml);
	}
}

/**
 * Query class, providing a structure for search terms and functions for checking
 * spelling ,etc.
 */

abstract class Xerxes_Framework_Search_Query
{
	protected $query_list = array();
	protected $limit_list = array();
	
	public function getQueryTerms()
	{
		return $this->query_list;
	}

	public function getLimits()
	{
		return $this->limit_list;
	}	
	
	public function addTerm($id, $boolean, $field, $relation, $phrase)
	{
		$term = new Xerxes_Framework_Search_QueryTerm($id, $boolean, $field, $relation, $phrase);
		array_push($this->query_list , $term);
	}
	
	public function addLimit($field, $relation, $phrase)
	{
		if ( ! is_array($phrase) )
		{
			$phrase = array($phrase);
		}
		
		foreach ( $phrase as $value )
		{
			$term = new Xerxes_Framework_Search_LimitTerm($field, $relation, $value);
			array_push($this->limit_list , $term);
		}
	}
	
	public function checkSpelling()
	{
		$registry = Xerxes_Framework_Registry::getInstance();
		
		$strAltYahoo = $registry->getConfig("ALTERNATE_YAHOO_LOCATION", false);
		$configYahooID = $registry->getConfig( "YAHOO_ID", false, "calstate" );
		
		$spell_return = array(); // we'll return this one
		
		for ( $x = 0; $x < count($this->query_list); $x++ )
		{
			$term = $this->query_list[$x];
			$url = "";
			
			if ( $strAltYahoo != "" )
			{
				$url = $strAltYahoo;
			}
			else
			{
				$url = "http://api.search.yahoo.com/WebSearchService/V1/spellingSuggestion";
			}
			
			$url .= "?appid=" . $configYahooID . "&query=" . urlencode($term->phrase);
			
			$strResponse = Xerxes_Framework_Parser::request($url);
				
			$objSpelling = new DOMDocument();
			$objSpelling->loadXML($strResponse);
				
			if ( $objSpelling->getElementsByTagName("Result")->item(0) != null )
			{
				$term->spell_correct = $objSpelling->getElementsByTagName("Result")->item(0)->nodeValue;
				$spell_return[$term->id] = $term->spell_correct;
			}
			
			// also put it here so we can return it
			
			$this->query_list[$x] = $term;
		}
		
		return $spell_return;
	}
	
	abstract public function toQuery();
}

class Xerxes_Framework_Search_QueryTerm
{
	public $id;
	public $boolean;
	public $field;
	public $relation;
	public $phrase;
	public $spell_correct;
	
	public function __construct($id, $boolean, $field, $relation, $phrase)
	{
		$this->id = $id;
		$this->boolean = $boolean;
		$this->field = $field;
		$this->relation = $relation;
		$this->phrase = $phrase;		
	}
}

class Xerxes_Framework_Search_LimitTerm
{
	public $field;
	public $relation;
	public $value;
	
	public function __construct($field, $relation, $value)
	{
		$this->field = $field;
		$this->relation = $relation;
		$this->value = $value;		
	}
}

class Xerxes_Framework_Search_Engine
{
	protected $url;
	protected $total;
	
	public function getURL()
	{
		return $this->url;
	}
	
	public function getTotal()
	{
		return $this->total;	
	}

	public function searchRetrieve()
	{
		throw new Exception("you need to create your own search object for the search framework");
	}
}

/**
 * Data structure for facets
 */

class Xerxes_Framework_Search_Facets
{
	private $groups = array();
	
	public function addGroup($group)
	{
		array_push($this->groups, $group);
	}
	
	public function getGroups()
	{
		return $this->groups;
	}	
	
	public function toXML()
	{
		$xml = new DOMDocument();
		$xml->loadXML("<facets />");
		
		foreach ( $this->getGroups() as $group )
		{
			$group_node = $xml->createElement("group");
			$group_node->setAttribute("id", $group->id);
			$group_node->setAttribute("name", $group->name);
			$xml->documentElement->appendChild($group_node);
			
			foreach ( $group->getFacets() as $facet )
			{
				$facet_node = $xml->createElement("facet", $facet->count);
				$facet_node->setAttribute("name", $facet->name);
				$facet_node->setAttribute("url", $facet->url);
				$group_node->appendChild($facet_node);				
			}
		}
		
		return $xml;
	}
}

class Xerxes_Framework_Search_FacetGroup
{
	public $id;
	public $name;
	private $facets = array();

	public function addFacet($facet)
	{
		array_push($this->facets, $facet);
	}
	
	public function getFacets()
	{
		return $this->facets;
	}
}

class Xerxes_Framework_Search_Facet
{
	public $name;
	public $count;
	public $url;
}

?>