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
	
	public $query; // query object
	
	protected $query_object_type = "Xerxes_Framework_Search_Query";
	protected $record_object_type = "Xerxes_Record";

	public $results = array();
	public $facets;
	public $recommendations = array();
	
	protected $max; // maximum records per page
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
		
		// set an instance of the query object
		
		$query_object = $this->query_object_type;
		$this->query = new $query_object(); 
	
		// config stuff
		
		$this->link_resolver = $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
		$this->sid = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
		$this->max = $this->registry->getConfig("RECORDS_PER_PAGE", false, 10);
		$this->include_original = $this->registry->getConfig("INCLUDE_ORIGINAL_XML", false, false);
		
		// used in a couple of place

		$this->sort = $this->request->getProperty("sortKeys");
	}
	
	public function __destruct()
	{
		$this->data_map = null;
	}
	
	public function search()
	{
		// get the 'search' related params out of the url, these are things like 
		// query, field, boolean
		
		$terms = $this->extractSearchParams();
		
		// add them to our query object
		
		foreach ( $terms as $term )
		{
			$this->query->addTerm($term["id"], $term["boolean"], $term["field"], $term["relation"], $term["query"]);
		}
		
		// check spelling
		
		$spelling = $this->query->checkSpelling();
		
		foreach ( $spelling as $key => $correction )
		{
			$this->request->setProperty("spelling_$key", $correction);
		}
		
		// echo $this->query->toQuery(); exit;
	}
	
	public function progress()
	{
		
	}
	
	public function results()
	{
		// max records
		
		$configMaxRecords = $this->registry->getConfig("MAX_RECORDS_PER_PAGE", false, 10);		
		
		// start, stop, source, sort properties
		
		$start = $this->request->getProperty("startRecord");
		$max = $this->request->getProperty("maxRecords");

		// set some explicit defaults
		
		if ( $start == null || $start == 0 ) $start = 1;
		if ( $max != null && $max <= $configMaxRecords ) $configMaxRecords = $max;
		
		$search = $this->query->toQuery();
		
		// get results and convert them to xerxes_record
		
		$xml = $this->search_object->searchRetrieve($search, $start, $configMaxRecords, $this->sort);
		$this->results = $this->convertToXerxesRecords($xml);
		
		// done
		
		return $this->resultsXML();
	}

	public function facet()
	{
		
	}
	
	public function record()
	{
		$id = $this->request->getProperty("id");
		
		$xml = $this->search_object->record($id);
		$this->results = $this->convertToXerxesRecords($xml);
		
		return $this->resultsXML();
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
		
		return $objXml;		
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
				
		      	// openurl kev context object please
		      	
				$kev = Xerxes_Framework_Parser::escapeXml($result->getOpenURL(null, $this->sid));
				$open_url = $results_xml->createElement("openurl_kev_co", $kev);
				$record_container->appendChild( $open_url );				
				
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
				$current_sort = $this->registry->getConfig("SORT_ORDER_PRIMARY", false, "Score");
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
		
		return $results_xml;
	}
	
	protected function linkFullRecord($result)
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "record",
			"id" => $result->getControlNumber()
		);
		
		return $this->request->url_for($arrParams);
	}
	
	protected function linkOpenURL($result)
	{
		return $result->getOpenURL($this->link_resolver, $this->sid);
	}
	
	protected function sortOptions()
	{
		return array();
	}
	
	protected function pagerLinkParams()
	{
		return array(
			"base" => $this->request->getProperty("base"),
			"action" => $this->request->getProperty("action"),
		);
	}
	
	protected function sortLinkParams()
	{
		return array(
			"base" => $this->request->getProperty("base"),
			"action" => $this->request->getProperty("action"),
		);		
	}

	protected function extractSearchParams()
	{
		$arrFinal = array();
		
		foreach ( $this->request->getAllProperties() as $key => $value )
		{
			$key = urldecode($key);
				
			// if we see a 'query' in the params, check if there are corresponding
			// entries for field and boolean; these will have a number after them
			// if coming from the advanced form
				
			if ( strstr($key, "query"))
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
				$arrTerm["boolean"] = $this->request->getProperty("boolean" . ( $boolean_id ) );
				
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
	
	public function addLimit($boolean, $field, $relation, $phrase)
	{
		$term = new Xerxes_Framework_Search_LimitTerm($boolean, $field, $relation, $phrase);
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

class Xerxes_Framework_Search_LimitTerm extends Xerxes_Framework_Search_QueryTerm
{
}

?>