<?php

abstract class Xerxes_Controller_Search extends Xerxes_Framework_Controller
{
	protected $id;
	
	protected $config; // local config
	protected $query; // query object
	protected $engine; // search engine
	protected $max; // default records per page
	protected $max_allowed; // upper-limit per page
	protected $sort; // default sort
	
	public function search()
	{
		// set the url params for where are gong to redirect,
		// usually to the results action, but can be overriden
		
		$base = $this->searchRedirectParams();
		$params = $this->query->getAllSearchParams();
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
		
		// construct the actual url and redirect

		$url = $this->request->url_for($params);
		$this->response->setRedirect($url);
	}
	
	public function hits()
	{
		// create an identifier for this search
		
		$id = $this->getQueryID();
		
		// see if one exists in session already
		
		$total = $this->request->getSession($id);
		
		// nope
		
		if ( $total == null )
		{
			// so do a search (just the hit count) 
			// and cache the hit total
			
			$total = $this->engine->getHits($this->query);
			$this->request->setSession($id, (string) $total);
		}
		
		// and tell the browser too
		
		$this->response->add("hits", $total);
		$this->response->setView("xsl/search/hits.xsl");
	}
	
	public function results()
	{
		// defaults
		
		$this->max = $this->registry->getConfig("RECORDS_PER_PAGE", false, 10);
		$this->max = $this->config->getConfig("RECORDS_PER_PAGE", false, $this->max);
		
		$this->max_allowed = $this->registry->getConfig("MAX_RECORDS_PER_PAGE", false, 30);
		$this->max_allowed = $this->config->getConfig("MAX_RECORDS_PER_PAGE", false, $this->max_allowed);
		
		$this->sort = $this->registry->getConfig("SORT_ORDER", false, "relevance");
		$this->sort = $this->config->getConfig("SORT_ORDER", false, $this->sort);
		
		// params
		
		$start = $this->request->getParam('start', false, 1);
		$max = $this->request->getParam('max', false, $this->max);
		$sort = $this->request->getParam('sort', false, $this->sort);
		
		// swap for internal
		
		$sort = $this->config->swapForInternalSort($sort);
		
		// make sure records per page does not exceed upper bound
		
		if ( $max > $this->max_allowed )
		{
			$max = $this->max_allowed;
		}
		
		// search
				
		$results = $this->engine->searchRetrieve($this->query, $start, $max, $sort);
		
		// total
		
		$total = $results->getTotal();
		
		// cache it
		
		$id = $this->getQueryID();
		$this->request->setSession($id, (string) $total);
		
		// add links
		
		$this->addRecordLinks($results);
		$this->addFacetLinks($results);
		$this->addQueryLinks();
		
		// summary, sort & paging elements
		
		$results->summary = $this->summary($total, $start, $max);
		$results->pager = $this->pager($total, $start, $max);
		$results->sort_display = $this->sortDisplay($sort);
		
		// response
		
		$this->response->add("query", $this->query);
		$this->response->add("results", $results);
	}
	
	public function record()
	{
		$id = $this->request->getParam('id');
		$results = $this->engine->getRecord($id);
		
		$this->addRecordLinks($results);
		$this->response->add("results", $results);
	}
	
	public function lookup()
	{
		$id = $this->request->getParam("id");
		
		// we essentially create a mock object and add holdings
		
		$xerxes_record = new Xerxes_Record();
		$xerxes_record->setRecordID($id);
		$xerxes_record->setSource($this->id);
		
		$result = new Xerxes_Model_Search_Result($xerxes_record, $this->config);
		$result->addHoldings();
		
		$this->response->add("results", $result);
		$this->response->setView('xsl/search/lookup.xsl');
	}	

	public function save()
	{
		$datamap = new Xerxes_Model_DataMap_SavedRecords();
		
		$username = "testing"; // $this->request->getSession("username");
		$original_id = $this->request->getParam("id");

		$inserted_id = ""; // internal database id
		
		// delete command
		
		if ( $this->isMarkedSaved( $original_id ) == true )
		{
			$datamap->deleteRecordBySource( $username, $this->id, $original_id );
			$this->unmarkSaved( $original_id );
			$this->response->add("delete", "1");
		}

		// add command
		
		else
		{
			// get record
			
			$record = $this->engine->getRecord($original_id)->getRecord(0)->getXerxesRecord();
			
			// save it
			
			$inserted_id = $datamap->addRecord( $username, $this->id, $original_id, $record );
			
			// record this in session
				
			$this->markSaved( $original_id, $inserted_id );
			
			$this->response->add("savedRecordID", $inserted_id);
		} 
		
		// view
		
		$this->response->setView('xsl/search/save.xsl');
		$this->response->setView('xsl/search/save-ajax.xsl', 'json');
	}	
	
	
	######################
	#  DISPLAY HELPERS   #
	######################
	
	
	/**
	 * Displays paged information (e.g., 11-20 of 34 results)
	 *
	 * @param int $total 		total # of hits for query
	 * @param int $start 		start value for the page
	 * @param int $max 			maximum number of results to show
	 *
	 * @return array or null	summary of page results 
	 */
	
	public function summary( $total, $start, $max )
	{
		if ( $total < 1 )
		{
			return null;
		}
		
		if ( $start == 0 )
		{
			$start = 1;
		}
			
		// set end point
		
		$stop = $start + ($max - 1);
		
		// if end value of group of 10 exceeds total number of hits,
		// take total number of hits as end value 
		
		if ( $stop > $total )
		{
			$stop = $total;
		}
		
		return array ( 
			"range" => "$start-$stop",
			"total" => Xerxes_Framework_Parser::number_format( $total )
		);
	}
	
	/**
	 * Paging element
	 * 
	 * @param int $total 		total # of hits for query
	 * @param int $start 		start value for the page
	 * @param int $max 			maximum number of results to show
	 * 
	 * @return DOMDocument formatted paging navigation
	 */
	
	public function pager( $total, $start, $max )
	{
		if ( $total < 1 )
		{
			return null;
		}
		
		$objXml = new DOMDocument( );
		$objXml->loadXML( "<pager />" );
		
		$base_record = 1; // starting record in any result set
		$page_number = 1; // starting page number in any result set
		$bolShowFirst = false; // show the first page when you get past page 10
		
		if ( $start == 0 ) 
		{
			$start = 1;
		}
		
		$current_page = (($start - 1) / $max) + 1; // calculates the current selected page
		$bottom_range = $current_page - 5; // used to show a range of pages
		$top_range = $current_page + 5; // used to show a range of pages
		
		$total_pages = ceil( $total / $max ); // calculates the total number of pages
		
		// for pages 1-10 show just 1-10 (or whatever records per page)
		
		if ( $bottom_range < 5 )
		{
			$bottom_range = 0;
		}
		
		if ( $current_page < $max )
		{
			$top_range = 10;
		} 
		else
		{
			$bolShowFirst = true;
		}
		
		// chop the top pages as we reach the end range
		
		if ( $top_range > $total_pages )
		{
			$top_range = $total_pages;
		}
		
		// see if we even need a pager
		
		if ( $total > $max )
		{
			// show first page
			
			if ( $bolShowFirst == true )
			{
				$objPage = $objXml->createElement( "page", "1" );
				
				$params = $this->currentParams();
				$params["start"] = 1;
				
				$link = $this->request->url_for( $params );
				
				$objPage->setAttribute( "link", Xerxes_Framework_Parser::escapeXml( $link ) );
				$objPage->setAttribute( "type", "first" );
				$objXml->documentElement->appendChild( $objPage );
			}
			
			// create pages and links
			
			while ( $base_record <= $total )
			{
				if ( $page_number >= $bottom_range && $page_number <= $top_range )
				{
					if ( $current_page == $page_number )
					{
						$objPage = $objXml->createElement( "page", $page_number );
						$objPage->setAttribute( "here", "true" );
						$objXml->documentElement->appendChild( $objPage );
					} 
					else
					{
						$objPage = $objXml->createElement( "page", $page_number );
						
						$params = $this->currentParams();
						$params["start"] = $base_record;
						
						$link = $this->request->url_for( $params );
						
						$objPage->setAttribute( "link", Xerxes_Framework_Parser::escapeXml( $link ) );
						$objXml->documentElement->appendChild( $objPage );
					
					}
				}
				
				$page_number++;
				$base_record += $max;
			}
			
			$next = $start + $max;
			
			if ( $next <= $total )
			{
				$objPage = $objXml->createElement( "page", "" ); // element to hold the text_results_next label
				
				$params = $this->currentParams();
				$params["start"] =  $next;
				
				$link = $this->request->url_for( $params );
				
				$objPage->setAttribute( "link", Xerxes_Framework_Parser::escapeXml( $link ) );
				$objPage->setAttribute( "type", "next" );
				$objXml->documentElement->appendChild( $objPage );
			}
		}
		
		return $objXml;
	}
	
	/**
	 * Creates a sorting page element
	 *
	 * @param string $sort			current sort
	 *
	 * @return DOMDocument 			sort navigation
	 */
	
	public function sortDisplay($sort)
	{
		$sort_options = $this->config->sortOptions();
		
		$xml = new DOMDocument();
		$xml->loadXML( "<sort_display />" );
		
		$x = 1;
		
		foreach ( $sort_options as $key => $value )
		{
			if ( $key == $sort )
			{
				$here = $xml->createElement( "option", $value );
				$here->setAttribute( "active", "true" );
				$xml->documentElement->appendChild( $here );
			} 
			else
			{
				$params = $this->sortLinkParams();
				$params["sort"] = $key;
				
				$here = $xml->createElement( "option", $value );
				$here->setAttribute( "active", "false" );
				$here->setAttribute( "link", $this->request->url_for($params) );
				$xml->documentElement->appendChild( $here );
			}
			
			$x++;
		}
		
		return $xml;
	}
	
	
	######################
	#        LINKS       #
	######################
	
	
	/**
	 * Add links to search results
	 * 
	 * @param Xerxes_Model_Search_Results $results
	 */

	protected function addRecordLinks( Xerxes_Model_Search_ResultSet &$results )
	{	
		// results
				
		foreach ( $results->getRecords() as $result )
		{
			$xerxes_record = $result->getXerxesRecord();
			
			// full-record link
			
			$result->url = $this->linkFullRecord($xerxes_record);
			$result->url_full = $result->url; // backwards compatibility
				
			// sms link
			
			$result->url_sms = $this->linkSMS($xerxes_record);
				
			// save or delete link
			
			$result->url_save = $this->linkSaveRecord($xerxes_record);
			$result->url_save_delete = $result->url_save; // backwards compatibility
			
			// other links
			
			$this->linkOther($result);
		}
	}
	
	/**
	 * Add links to facets
	 * 
	 * @param Xerxes_Model_Search_Results $results
	 */	
	
	protected function addFacetLinks( Xerxes_Model_Search_ResultSet &$results )
	{	
		// facets

		$facets = $results->getFacets();
		
		if ( $facets != "" )
		{
			foreach ( $facets->getGroups() as $group )
			{
				foreach ( $group->getFacets() as $facet )
				{
					// existing url
						
					$url = $this->currentParams();
							
					// now add the new one
							
					if ( $facet->is_date == true ) // dates are different 
					{
						$url["facet.date." . $group->name . "." . 
							urlencode($facet->key)] = $facet->name;
					}
					else
					{
						$url["facet." . $group->name] = $facet->name;									
					}
							
					$facet->url = $this->request->url_for($url);
				}
			}
		}
	}
	
	/**
	 * Add links to the query object limits
	 */
	
	protected function addQueryLinks()
	{
		// link to corrected spelling
		
		$spelling_corrected = $this->request->getParam("spelling_query");
		
		if ( $spelling_corrected != null )
		{
			$spell = array();
			$spell["url"] = $this->linkSpelling();
			$spell["text"] = $spelling_corrected;
			
			$this->query->spelling_url = $spell;
		}
		
		// tab links
		
		$tabs = $this->registry->getConfig('tabs');
		
		if ( $tabs instanceof SimpleXMLElement )
		{
			foreach ( $tabs->top->tab as $tab )
			{
				// format the number
				
				// is this the current tab?

				if ( $this->request->getParam('base') == (string) $tab["id"] 
				     && ( $this->request->getParam('source') == (string) $tab["source"] 
				     	|| (string) $tab["source"] == '') )
				    {
				    	$tab->addAttribute('current', "1");	
				    }
				
				// url
				
				$params = $this->query->extractSearchParams();
				
				$params['base'] = (string) $tab["id"];
				$params['action'] = "results";
				$params['source'] = (string) $tab["source"];
				
				$url = $this->request->url_for($params);
				
				$tab->addAttribute('url', $url);
				
				// cached search hit count?
		
				foreach ( $this->request->getAllSession() as $session_id => $session_value )
				{
					// does this value in the cache have the save id as our tab?
					
					$id = str_replace("_" . $this->query->getHash(), "", $session_id);
					
					if ( $id == (string) $tab["id"] )
					{
						// yup, so add it
						
						$tab->addAttribute('hits', Xerxes_Framework_Parser::number_format($session_value));
					}
				}
			}
			
			$this->response->add("tabs", $tabs);
		}
		
		// links to remove facets
		
		foreach ( $this->query->getLimits() as $limit )
		{
			$url = new Xerxes_Framework_URL($this->currentParams());
			$url->removeParam($limit->field, $limit->value);
			$limit->remove_url = $this->request->url_for($url);
		}		
	}
	
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
	
	protected function linkFullRecord( Xerxes_Record $result )
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
	
	protected function linkSaveRecord( Xerxes_Record $result )
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "save",
			"id" => $result->getRecordID()
		);
		
		return $this->request->url_for($arrParams);
	}
	
	/**
	 * URL for the sms feature
	 * 
	 * @param Xerxes_Record $result
	 * @return string url
	 */	
	
	protected function linkSMS(  Xerxes_Record $result )
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
	 * such as lateral subject or author links
	 * 
	 * @param Xerxes_Model_Search_Result $result 
	 */	
	
	protected function linkOther( Xerxes_Model_Search_Result $result )
	{
		return $result;
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
		$params = $this->query->getAllSearchParams();
		$params["base"] = $this->request->getProperty("base");
		$params["action"] = $this->request->getProperty("action");
		$params["sort"] = $this->request->getProperty("sort");
		
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
		
		unset($params["sort"]);
		
		return $params;
	}
	
	/**
	 * Return identifier for this query = search ID + query hash
	 */
	
	protected function getQueryID()
	{
		return $this->id . "_" . $this->query->getHash();
	}
	
	
	########################
	#  SAVED RECORD STATE  #
	########################	
	
	
	/**
	 * Store in session the fact this record is saved
	 *
	 * @param string $original_id		original id of the record
	 * @param string $saved_id		the internal id in the database
	 */ 
	
	protected function markSaved( $original_id, $saved_id )
	{
		$_SESSION['resultsSaved'][$original_id]['xerxes_record_id'] = $saved_id;
	}

	/**
	 * Delete from session the fact this record is saved
	 *
	 * @param string $original_id		original id of the record
	 */ 
	
	protected function unmarkSaved( $original_id )
	{
		if ( array_key_exists( "resultsSaved", $_SESSION ) && array_key_exists( $original_id, $_SESSION["resultsSaved"] ) )
		{
			unset( $_SESSION['resultsSaved'][$original_id] );
		}
	}

	/**
	 * Determine whether this record is already saved in session
	 *
	 * @param string $original_id		original id of the record
	 */ 
	
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

	/**
	 * Get the number of records saved in session
	 */ 
	
	protected function numMarkedSaved()
	{
		$num = 0;
		
		if ( array_key_exists( "resultsSaved", $_SESSION ) )
		{
			$num = count( $_SESSION["resultsSaved"] );
		}
		
		return $num;
	}
}
