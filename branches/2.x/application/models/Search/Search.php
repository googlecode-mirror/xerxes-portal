<?php


abstract class Xerxes_Framework_Search
{
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
}
