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
	public $max = 10; // maximum records per page
	
	public $query; // query object
	
	protected $search_object_type = "Xerxes_Framework_Search_Engine";
	protected $query_object_type = "Xerxes_Framework_Search_Query";
	protected $record_object_type = "Xerxes_Record";
	
	protected $schema;
	protected $sort_default;
	
	protected $search_fields_regex = "^query[0-9]{0,1}$|^field[0-9]{0,1}$|^boolean[0-9]{0,1}$";
	protected $limit_fields_regex = "";	
	protected $include_original;
	
	public $results = array(); // search results
	public $facets; // facets
	public $recommendations = array(); // recommendations

	protected $sid; // sid for open url identification
	protected $link_resolver; // based address of link resolver	
	
	protected $request; // xerxes request object
	protected $registry; // xerxes config values
	protected $search_object; // search object
	protected $data_map; // data map
	
	public function __construct($objRequest, $objRegistry)
	{
		// make these available
		
		$this->request = $objRequest;
		$this->registry = $objRegistry;
		
		// database access object
				
		$this->data_map = new Xerxes_DataMap();
		
		// facet object
		
		$this->facets = new Xerxes_Framework_Search_Facets();
		
		// set an instance of the query object
		
		$query_object = $this->query_object_type;
		$this->query = new $query_object();
		
		// populate it with it with the 'search' related params out of the url, 
		// these are things like query, field, boolean
		
		foreach ( $this->extractSearchGroupings() as $term )
		{
			$this->query->addTerm($term["id"], $term["boolean"], $term["field"], $term["relation"], $term["query"]);
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
		
		// search object 
		
		$search_object_type = $this->search_object_type;
		$this->search_object = new $search_object_type();
	}
	
	public function search()
	{
		$base = $this->searchRedirectParams();
		$params = $this->getAllSearchParams();
		
		$params = array_merge($base, $params);
		
		// print_r($this->request); print_r($params); exit;
		
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
	
	public function progress()
	{
		
	}
	
	public function results()
	{
		// start, stop, source, sort properties
		
		$start = $this->request->getProperty("startRecord");
		$max = $this->request->getProperty("maxRecords");

		// set some explicit defaults
		
		if ( $start == null || $start == 0 ) $start = 1;
		if ( $max != null && $max <= $this->max ) $this->max = $max;
		
		$search = $this->query->toQuery();
		
		// get results
		
		$xml = $this->search_object->searchRetrieve($search, $start, $this->max, $this->schema, $this->sort);
		
		// convert them to xerxes_record
		
		$this->results = $this->convertToXerxesRecords($xml);
		
		// get any facets
		
		$this->extractFacets($xml);
		
		// done
		
		$this->request->addDocument($this->resultsXML());
	}
	
	public function record()
	{
		$id = $this->request->getProperty("id");
		
		$xml = $this->search_object->record($id);
		$this->results = $this->convertToXerxesRecords($xml);
		
		$this->request->addDocument($this->resultsXML());
	}
	
	public function bounce()
	{
		
	}
	
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
			
			$record_object_type = $this->record_object_type;
				
			$record = new $record_object_type();
			$record->loadXML($xml);
				
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
	
	public function resultsXML()
	{
		$this->url = $this->search_object->getURL();
		
		if ( $this->total == null )
		{
			$this->total = $this->search_object->getTotal();
		}
		
		$results_xml = new DOMDocument( );
		$results_xml->loadXML( "<results />" );
		
		// add in the original url for debugging
		
		$search_url = $results_xml->createElement( "search_url", Xerxes_Framework_Parser::escapeXml( $this->url ) );
		$results_xml->documentElement->appendChild( $search_url );

		// add total
		
		$total = $results_xml->createElement("total", $this->total);
		$results_xml->documentElement->appendChild( $total );
		
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
				
				// open-url link (which may be a redirect)

				$record_openurl = Xerxes_Framework_Parser::escapeXml($this->linkOpenURL($result));
				$link_full = $results_xml->createElement("url_open", $record_openurl);
				$record_container->appendChild( $link_full );

				// save or delete link

				$record_save = Xerxes_Framework_Parser::escapeXml($this->linkSaveRecord($result));
				$link_save = $results_xml->createElement("url_save", $record_save);
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
					$recommend_xml->documentElement->appendChild($record_xml);
					
					$import = $results_xml->importNode($record->toXML()->documentElement, true);
					$results_xmls->appendChild($objImport);
					
					$open_url = $record->getOpenURL($this->link_resolver, $this->sid);
					
					$open_url_xml = $results_xml->createElement("url_open", Xerxes_Framework_Parser::escapeXML($open_url));
					$results_xml->appendChild($open_url_xml);
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
		
		// facets
		
		$facets = $this->facets->toXML();
		$import = $results_xml->importNode($facets->documentElement, true);
		$results_xml->documentElement->appendChild($import);
		
		return $results_xml;
	}
	
	protected function linkFullRecord($result)
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "record",
			"id" => $result->getRecordID()
		);
		
		return $this->request->url_for($arrParams);
	}

	protected function linkSaveRecord($result)
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "save-delete",
			"id" => $result->getRecordID()
		);
		
		return $this->request->url_for($arrParams);
	}	
	
	protected function linkOpenURL($result)
	{
		return $result->getOpenURL($this->link_resolver, $this->sid);
	}
	
	protected function linkOther($result, $results_xml, $record_container)
	{
		
	}
	
	protected function sortOptions()
	{
		return array();
	}
	
	protected function searchRedirectParams()
	{
		$params = $this->getAllSearchParams();
		$params["sortKeys"] = $this->request->getProperty("sortKeys");
		$params["base"] = $this->request->getProperty("base");
		$params["action"] = "results";
		
		return $params;
	}
	
	protected function pagerLinkParams()
	{
		$params = $this->sortLinkParams();
		$params["sortKeys"] = $this->request->getProperty("sortKeys");
		
		return $params;
		
	}
	
	protected function sortLinkParams()
	{
		$params = $this->getAllSearchParams();
		$params["base"] = $this->request->getProperty("base");
		$params["action"] = $this->request->getProperty("action");
		
		return $params;
	}
	
	/**
	 * Extract both query and limit params from the URL
	 */
	
	protected function getAllSearchParams()
	{
		$limits = $this->extractLimitParams();
		$search = $this->extractSearchParams();
		
		return array_merge($limits, $search);
	}		
	
	/**
	 * Get 'limit' params out of the URL, sub-class defines this
	 */	
	
	protected function extractLimitParams()
	{
		if ( $this->limit_fields_regex != "" )
		{
			return $this->request->getProperties($this->limit_fields_regex, true);
		}
		else
		{
			return array();
		}
	}

	protected function extractSearchParams()
	{
		if ( $this->search_fields_regex != "" )
		{
			return $this->request->getProperties($this->search_fields_regex, true);
		}
		else
		{
			return array();
		}
	}

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

	protected function convertToXerxesRecords(DOMDocument $xml)
	{
		$doc_type = $this->record_object_type . "_Document";
		$xerxes_doc = new $doc_type();
		$xerxes_doc->loadXML($xml);
		
		return $xerxes_doc->records();
	}
	
	protected function extractFacets(DOMDocument $xml)
	{
		
	}
	
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
				
			$this->recommendations = $doc->records();
		}
	}
	
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

	// Functions for saving saved record state from a result set in session
	// This is used for knowing whether to add or delete on a 'toggle' command
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
}

class Xerxes_Framework_Search_Query
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
		$term = new Xerxes_Framework_Search_LimitTerm($field, $relation, $phrase);
		array_push($this->limit_list , $term);
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
	
	protected function toQuery()
	{
		
	}
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
			$group_node->setAttribute("name", $group->name);
			$xml->documentElement->appendChild($group_node);
			
			foreach ( $group->getFacets() as $facet )
			{
				$facet_node = $xml->createElement("facet", $facet->count);
				$facet_node->setAttribute("name", $facet->name);
				$group_node->appendChild($facet_node);				
			}
		}
		
		return $xml;
	}
}

class Xerxes_Framework_Search_FacetGroup
{
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