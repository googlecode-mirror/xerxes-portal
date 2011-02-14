<?php

/**
 * Search framework
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: Search.php 1622 2011-01-21 23:00:55Z dwalker@calstate.edu $
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
	
	protected $should_mark_refereed = false; // will add peer reviewed indicator to results
	protected $should_mark_fulltext = false; // will add full-text indicator to results
	protected $should_get_recommendations = false; // will add bx recommendations to full record
	protected $should_get_holdings = false; // will add local catalog holdings to records
	
	protected $max = 10; // maximum records per page
	protected $sort_default; // default sort
	protected $sid; // sid for open url identification
	protected $link_resolver; // base address of link resolver
	
	protected $search_fields_regex = '^query[0-9]{0,1}$|^field[0-9]{0,1}$|^boolean[0-9]{0,1}$';
	protected $limit_fields_regex = 'facet.*';
	
	protected $include_original = false; // add original xml to response

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
		$this->config->init();
		$this->request->addDocument($this->config->publicXML());
		
		// set default sort order
		
		$this->sort_default = $this->config->getConfig("SORT_ORDER_PRIMARY", false, "relevance");
		
		// database access object
				
		$this->data_map = new Xerxes_DataMap();
		
		// facet object
		
		$this->facets = new Xerxes_Framework_Search_Facets();
		
		// set an instance of the query object
		
		$this->query = $this->getQueryObject();
		
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
		$this->max = $this->registry->getConfig("MAX_RECORDS_PER_PAGE", false, $this->max);

		// include the original xml from the search engine?
		
		$include_original_main = $this->registry->getConfig("INCLUDE_ORIGINAL_XML", false, false);
		$include_original_module = $this->config->getConfig("INCLUDE_ORIGINAL_XML", false, false);
		$include_original_url = $this->request->getProperty("original");
		
		if ( $include_original_main == true || $include_original_module == true || $include_original_url != "")
		{
			$this->include_original = true;
		}
		
		
		// used in a couple of places

		$this->sort = $this->request->getProperty("sortKeys");
		
		if ( $this->sort == null )
		{
			$this->sort = $this->sort_default;
		}
		
		// search object 
		
		$this->search_object = $this->getSearchObject();
		
		// calculate the normalized forms
		
		$this->calculateHash();
		
		$this->request->addData("query_normalized", "url", $this->query_normalized);
	}
	
	/**
	 * Subclass needs to define this to set the local config object
	 */
	
	protected abstract function getConfig();
	

	
	############
	#  PUBLIC  #
	############	
	
	
	/**
	 * Get the md5 hash for the query as a kind of query identifier
	 */
	
	public function getHash()
	{
		return $this->query_hash;
	}
	
	/**
	 * ID for the hash
	 */
	
	public function getHashID()
	{
		return $this->id;
	}
	
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
	 * Just get the hit counts on the search
	 */	
	
	public function hits()
	{
		$id = $this->getHashID() . "-" . $this->getHash();
		
		$hits = $this->request->getSession($id);
		
		if ( $hits == null )
		{
			$hits = $this->search_object->hits($this->query);
			$hits = number_format($hits);
			$this->request->setSession($id, $hits);
		}
		
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
		
		if ( $this->should_mark_refereed == true )
		{
			$this->markRefereed();
		}
		
		// full-text pre-look-up

		if ( $this->should_mark_fulltext == true )
		{
			$this->markFullText();
		}
		
		// holdings
		
		if ( $this->should_get_holdings == true )
		{
			$this->getHoldingsInject();
		}
		
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
		
		// mark peer-reviewed journals
		
		if ( $this->should_mark_refereed == true )
		{
			$this->markRefereed();
		}
		
		// recommendations
		
		if ( $this->should_get_recommendations == true )
		{
			$this->addRecommendations();
		}
		
		// full-text pre-look-up
		
		if ( $this->should_mark_fulltext == true )
		{
			$this->markFullText();
		}
		
		// local holdings
		
		if ( $this->should_get_holdings == true )
		{
			$items = $this->getHoldings($this->extractLookupIDs());

			$record = $this->results[0];
			$record->addItems($items);
			$this->results[0] = $record;
		}
		
		// register this url as being viewed
			
		$this->request->setSession("last_page", $this->request->getServer('REQUEST_URI'));
		
		// done
		
		$this->request->addDocument($this->resultsXML());
	}
	
	/**
	 * Holdings look-up, via AJAX
	 */

	public function lookup()
	{
		$standard_numbers = array();
		
		$id = $this->request->getProperty("id");
		
		if ( $id != null )
		{
			array_push($standard_numbers, $id);
		}
		else
		{
			$isbn = $this->request->getProperty("isbn");
			$oclc = $this->request->getProperty("oclc");			
			
			if ( $oclc != null )
			{
				array_push($standard_numbers, "OCLC:$oclc");
			}
			if ( $isbn != null )
			{
				array_push($standard_numbers, "ISBN:$isbn");
			}
		}
		
		$items = $this->getHoldings($standard_numbers);
		
		$xerxes_record = new Xerxes_Record();
		$xerxes_record->addItems($items);
		
		$this->request->addDocument($xerxes_record->toXML());
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
			// flag this as being a delete command in the view, in the event
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
		
		// get total and set in session
		
		if ( $this->total == null )
		{
			$this->total = $this->search_object->getTotal();
		}

		$id = $this->getHashID() . "-" . $this->getHash();
		$this->request->setSession($id, number_format($this->total));
		
		
		$results_xml = new DOMDocument( );
		$results_xml->loadXML( "<results />" );
		
		// other cached search hits?
		
		foreach ( $this->request->getAllSession() as $session_id => $session_value )
		{
			if ( strstr($session_id,$this->query_hash) )
			{
				$id = str_replace("-" . $this->query_hash, "", $session_id);
				
				$other = $results_xml->createElement( "other", $session_value);
				$other->setAttribute("module", $id);		
				$results_xml->documentElement->appendChild( $other );		
			}
		}
		
		// spelling

		$spelling_url = $this->linkSpelling();
		
		$spelling = $results_xml->createElement( "spelling", Xerxes_Framework_Parser::escapeXml( $this->request->getProperty("spelling_query")));
		$spelling->setAttribute("url", $spelling_url);
		
		$results_xml->documentElement->appendChild( $spelling );
		
		// add in the original url for debugging
		
		$search_url = $results_xml->createElement( "search_url", Xerxes_Framework_Parser::escapeXml( $this->url ) );
		$results_xml->documentElement->appendChild( $search_url );

		// add total
		
		$total = $results_xml->createElement("total", number_format($this->total));
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
				
					// this one for backwards compatibility
	
					$link_full = $results_xml->createElement("url_full", $record_link);
					$record_container->appendChild( $link_full );					
				
				// open-url link (which may be a redirect)

				$record_openurl = Xerxes_Framework_Parser::escapeXml($this->linkOpenURL($result));
				$link_full = $results_xml->createElement("url_open", $record_openurl);
				$record_container->appendChild( $link_full );

				// sms link

				$record_sms = Xerxes_Framework_Parser::escapeXml($this->linkSMS($result));
				$link_sms = $results_xml->createElement("url_sms", $record_sms);
				$record_container->appendChild( $link_sms );
				
				// save or delete link

				$record_save = Xerxes_Framework_Parser::escapeXml($this->linkSaveRecord($result));
				$link_save = $results_xml->createElement("url_save", $record_save);
				$record_container->appendChild( $link_save );

					// this one for backwards compatibility
				
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
					
					$original_node = $original_xml;
					
					if ( $original_xml instanceof DOMDocument )
					{
						$original_node = $original_xml->documentElement;
					}
					
					$import = $results_xml->importNode( $original_node, true );

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
	
	
	#####################
	#  OBJECT CREATION  #
	#####################
	
	
	protected function getSearchObject()
	{
		$search_object_type = $this->search_object_type;
		return new $search_object_type();
	}
	
	protected function getQueryObject()
	{
		$query_object = $this->query_object_type;
		return new $query_object();		
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
	 * URL for the sms feature
	 * 
	 * @param Xerxes_Record $result
	 * @return string url
	 */	
	
	protected function linkSMS($result)
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "sms",
			"id" => $result->getRecordID()
		);
		
		return $this->request->url_for($arrParams);	
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
				$field_id = (string) $field["id"];
				
				if ( $field_id == "")
				{
					continue;
				}
				
				// if $id was blank, then we take the first
				// one in the list, otherwise, we're looking 
				// to match
				
				elseif ( $field_id == $id || $id == "")
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
	
	/**
	 * The database source ID
	 * @return string
	 */
	
	protected function getSource()
	{
		$strSource = $this->request->getProperty("source");
		
		if ( $strSource == "" )
		{
			$strSource = $this->id;
		}
		
		return $strSource;
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
	
	/**
	 * An md5 hash of the main search parameters, bascially to identify the search
	 */
	
	protected function calculateHash()
	{
		// get the search params and sort them alphabetically
		
		$params = $this->extractSearchParams();
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
	
	protected function addRecommendations()
	{
		// only the first one yo!
		
		$record = $this->results[0];
		
		$configToken = $this->registry->getConfig("BX_TOKEN", false);
						
		if ( $configToken != null )
		{
			$configBX		= $this->registry->getConfig("BX_SERVICE_URL", false, "http://recommender.service.exlibrisgroup.com/service");
			$configSID		= $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
			$configMaxRecords	= $this->registry->getConfig("BX_MAX_RECORDS", false, "10");
			$configMinRelevance	= $this->registry->getConfig("BX_MIN_RELEVANCE", false, "0");
				
			$open_url = $record->getOpenURL(null, $configSID);
				
			$url = $configBX . "/recommender/openurl?token=$configToken&$open_url&res_dat=source=global&threshold=$configMinRelevance&maxRecords=$configMaxRecords";
				
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
				$this->determineFullText($xerxes_record, $arrResults);
				$this->results[$x] = $xerxes_record;
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
	
	private function determineFullText(&$xerxes_record, $arrResults)
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
	
	protected function extractFacets($xml)
	{
		
	}
	
	/**
	 * Convert date facets based on Lucene types into decade groupings
	 * 
	 * @param array $facet_array	array of facets
	 * @param int $bottom_decade	default is 1900 
	 * @return array				associative array of facets and display info
	 */
	
	protected function luceneDateToDecade($facet_array, $bottom_decade = 1900)
	{
		// ksort($facet_array); print_r($facet_array);
		
		$bottom_year = $bottom_decade - 1; // the year before the bottom decade
		
		$decades = array();
		$decade_display = array();
		
		$top = date("Y"); // keep track of top most year
		$bottom = $bottom_year; // and the bottom most year
		$top_of_bottom = 0; // the top most of the bottom group
		
		foreach ( $facet_array as $year => $value)
		{
			// set a new top 
			
			if ( $year > $top )
			{
				$top = $year;			
			}
			
			// strip the end year, getting just century and decade
			
			$dec = substr($year,0,3);
			
			// if the end date in this decade is beyond the current year, then
			// we are in the current decade
			
			$dec_end = (int) $dec . "9";
							
			if ( $dec_end > date("Y") )
			{
				$display = $dec . "0-present";
			}
			else
			{
				// otherwise we're going DDD0-D9
				
				$display = $dec . "0-" . substr($dec,2,1) . "9";
			}
			
			// but the actual query is the dates themselves
							
			$query = "[" . $dec . "0 TO " . $dec . "9]";
			
			// for the old stuff, just group it together
			
			$bottom_decade = $bottom_decade - 1;
			
			if ( $year <= $bottom_year )
			{
				// set a new bottom for display purposes
				
				if ( $year < $bottom )
				{
					$bottom = $year;
				}

				// and the top of the bottom group
				
				if ( $year > $top_of_bottom )
				{
					$top_of_bottom = $year;
				}				
				
				$query = "[-999999999 TO $bottom_year]";
				$display = "before-$bottom_year";
			}
			
			$decade = array();
			$decade["display"] = $display;
			$decade["query"] = $query;
		
			$query = $decade["query"];
						
			$decade_display[$query] = $decade["display"];
					
			if ( array_key_exists($query, $decades) )
			{
				$decades[$query] += (int) $value; 
			}
			else
			{
				$decades[$query] = (int) $value; 
			}
		}
		
		// now replace the 'present' and 'bottom' place holders 
		// with actual top and bottom year values
		
		foreach ( $decade_display as $key => $value )
		{
			if ( strstr($value,"present") )
			{
				$decade_display[$key] = str_replace("present", $top, $value);
			}
			if ( strstr($value,"before") )
			{
				$decade_display[$key] = str_replace("before", $bottom, $value);
			}
			
			// now eliminate same year scenario
			
			$date = explode("-", $decade_display[$key]);
			
			if ( $date[0] == $date[1] )
			{
				$decade_display[$key] = $date[0];
			}
			
		}
		
		// sort em in date order
		
		krsort($decades);
		
		$final = array();
		$final["decades"] = $decades;
		$final["display"] = $decade_display;
		
		return $final;
	}
	
	/**
	 * Extract all the ISSNs from the records, convenience funciton
	 */

	protected function extractISSNs()
	{
		$issns = array();
		
		$records = array_merge($this->results, $this->recommendations);
		
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

	protected function extractLookupIDs()
	{
		return $this->extractRecordIDs();
	}	
	
	
	###################
	#  HOLDINGS DATA  #
	###################
	

	protected function getHoldings($arrIDs)
	{
		$items = new Xerxes_Record_Items();
		
		$cache_id = ""; // id used in cache
		$bib_id = ""; // bibliographic id number
		$oclc = ""; // oclc number
		
		// figure out what is what
		
		foreach ( $arrIDs as $id )
		{
			if ( stristr($id,"ISBN:") )
			{
				continue;
			}
			
			if ( stristr($id,"OCLC:") )
			{
				$oclc = $id;
			}
			else
			{
				$bib_id = $id;
			}
		}

		// no bib id supplied, so use oclc number as id
		
		if ( $bib_id == "")
		{
			if ( $oclc == "" )
			{
				throw new Exception("no bibliographic id or oclc number suppled in availability lookup");
			}
			
			$cache_id = str_replace("OCLC:", "",$oclc);
		}
		else
		{
			$cache_id = $bib_id;
		}
		
		// get url to availability server
		
		$strSource = $this->getSource();
		$url = $this->getHoldingsURL($strSource);
		
		// no holdings source defined or somehow id's are blank
		
		if ( $url == null || count($arrIDs) == 0 )
		{
			return $items; // empty items
		}
		
		// get the data
		
		$url .= "?action=status&id=" . urlencode(implode(" ", $arrIDs));
		$data = Xerxes_Framework_Parser::request($url);
		
		// echo $url; exit;
		
		// no data, what's up with that?
		
		if ( $data == "" )
		{
			throw new Exception("could not connect to availability server");
		}
		
		// echo $data; exit;
		
		// response is (currently) an array of json objects
		
		$arrResults = json_decode($data);
		
		// parse the response
		
		if ( is_array($arrResults) )
		{
			if ( count($arrResults) > 0 )
			{
				// now just slot them into our item object
				
				foreach ( $arrResults as $holding )
				{
					$is_holdings = property_exists($holding, "holding"); 
										
					if ( $is_holdings == true )
					{
						$item = new Xerxes_Record_Holding();
					}
					else
					{
						$item = new Xerxes_Record_Item();
					}
					
					foreach ( $holding as $property => $value )
					{
						$item->setProperty($property, $value);
					}
					
					$items->addItem($item);
				}
			}
		}
		
		// cache it for the future
		
		// expiry set for two hours
		
		$expiry = $this->config->getConfig("HOLDINGS_CACHE_EXPIRY", false, 2 * 60 * 60);
		$expiry += time(); 
		
		$cache = new Xerxes_Data_Cache();
		$cache->source = $this->getSource();
		$cache->id = $cache_id;
		$cache->expiry = $expiry;
		$cache->data = serialize($items);
		
		$this->data_map->setCache($cache);		
		
		return $items;
	}
	
	protected function getHoldingsURL($id)
	{
		return null;
	}
	
	/**
	 * Look for any holdings data in the cache and add it to results
	 */
	
	protected function getHoldingsInject()
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
			
				for( $x = 0; $x < count($this->results); $x++ )
				{
					$xerxes_record = $this->results[$x];
					
					if ( $xerxes_record->getRecordID() == $cache->id )
					{
						$xerxes_record->addItems($item);
					}
						
					$this->results[$x] = $xerxes_record;
				}
			}
		}
	}
}

/**
 * Query class, providing a structure for search terms and functions for checking
 * spelling, etc.
 */

abstract class Xerxes_Framework_Search_Query
{
	protected $query_list = array();
	protected $limit_list = array();
	protected $stop_words = "";
	
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

	protected function removeStopWords($strTerms)
	{
		/*
			"a","an","and","are","as","at","be","but","by","for","from", "had","have","he","her","his",
			"in","is","it","not","of","on","or","that","the","this","to","was","which","with","you"
		*/
		
		if ( $this->stop_words != "" )
		{
			$strFinal = "";
			
			$arrTerms = explode ( " ", $strTerms );
			
			foreach ( $arrTerms as $strChunk )
			{
				if ($strChunk == "AND" || $strChunk == "OR" || $strChunk == "NOT")
				{
					$strFinal .= " " . $strChunk;
				} 
				else
				{
					$strNormal = strtolower ( $strChunk );
					
					if (! in_array ( $strNormal, $this->stop_words ))
					{
						$strFinal .= " " . $strChunk;
					}
				}
			}
			
			return trim ( $strFinal );
		}
		else
		{
			return $strTerms;
		}
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
	protected $total = 0;
	
	public function getURL()
	{
		return $this->url;
	}
	
	public function hits($search)
	{
		$this->searchRetrieve($search,0,0);
		return $this->total;
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
	
	public function sortByName($order)
	{
		$names = array();
		
		// extract the names
		
		foreach ( $this->facets as $facet )
		{
			array_push($names, (string) $facet->name);
		}
		
		// now sort them, keeping the key associations
		
		if ( $order == "desc")
		{
			arsort($names);
		}
		elseif ( $oder == "asc")
		{
			asort($names);
		}
		else
		{
			throw new Exception("sort order must be 'desc' or 'asc'");
		}
		
		// now unset and re-add the facets based on those keys
		
		$facets = $this->facets;
		$this->facets = array();
		
		foreach ( $names as $key => $value )
		{
			array_push($this->facets, $facets[$key]);			
		}
	}
}

class Xerxes_Framework_Search_Facet
{
	public $name;
	public $count;
	public $url;
}

class Xerxes_Framework_Search_Config extends Xerxes_Framework_Registry
{
	private $facets = array();
	private $fields = array();
	
	public function init()
	{
		parent::init();
		
		// facets
		
		$facets = $this->xml->xpath("//config[@name='facet_fields']/facet");
		
		if ( $facets !== false )
		{
			foreach ( $facets as $facet )
			{
				$this->facets[(string) $facet["internal"]] = $facet;
			}
		}
		
		// fields
		
		$fields = $this->xml->xpath("//config[@name='basic_search_fields']/field");
		
		if ( $fields !== false )
		{
			foreach ( $fields as $field )
			{
				$this->fields[(string) $field["internal"]] = (string) $field["public"];
			}
		}
	}
	
	public function getFacetPublicName($internal)
	{
		if ( array_key_exists($internal, $this->facets) )
		{
			$facet = $this->facets[$internal];
			
			return (string) $facet["public"]; 
		}
		else
		{
			return null;
		}
	}

	public function getValuePublicName($internal_group, $internal_field)
	{
		if ( strstr($internal_field, "'") || strstr($internal_field, " ") )
		{
			return $internal_field;
		}
		
		$query = "//config[@name='facet_fields']/facet[@internal='$internal_group']/value[@internal='$internal_field']";
		
		$values = $this->xml->xpath($query);
		
		if ( count($values) > 0 )
		{
			return (string) $values[0]["public"];
		}
		else
		{
			return $internal_field;
		}
	}	
	
	public function getFacetType($internal)
	{
		$facet = $this->getFacet($internal);
		return (string) $facet["type"];
	}
	
	public function isDateType($internal)
	{
		if ( $this->getFacetType($internal) == "date" )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function getFacet($internal)
	{
		if ( array_key_exists($internal, $this->facets) )
		{
			return $this->facets[$internal];
		}
		else
		{
			return null;
		}
	}	
	
	public function getFacets()
	{
		return $this->facets;
	}
	
	public function getFields()
	{
		return $this->fields;
	}
	
	public function getFieldAttribute($field,$attribute)
	{
		$values = $this->xml->xpath("//config[@name='basic_search_fields']/field[@internal='$field']/@$attribute");
		
		if ( count($values) > 0 )
		{
			return (string) $values[0];
		}
		else
		{
			return null;
		}
	}
}



?>
