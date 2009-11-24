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
	public $id; // some id to keep this thing separate from other instances
	public $search_engine; // name of the underlying search engine
	public $databases; // some subset of the search engine
	
	public $sort_options; // available sorting options
	public $sort; // sort order
	public $spelling_correction; // spelling correction
	public $url; // url to web service
	public $total; // total number of hits
	
	public $query; // query object
	public $status;
	public $results = array();
	public $facets;
	public $recommendations = array();

	protected $max; // maximum records per page
	protected $sid; // sid for open url identification
	protected $link_resolver; // based address of link resolver	
	
	protected $request; // xerxes request object
	protected $registry; // xerxes config values
	protected $search_object;
	protected $data_map;
	
	public function __construct()
	{
		// Xerxes_DataMap contains a pdo object, which cannot be serialized,
		// so we set it up here, tear it down on __sleep, and back up again on __wake
		
		$this->data_map = new Xerxes_DataMap();
		$this->query = new Xerxes_Query();
	}
	
	public function initialize($objRequest, $objRegistry)
	{
		$this->request = $objRequest;
		$this->registry = $objRegistry;
		
		$this->link_resolver = $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true);
		$this->sid = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
		$this->max = $this->registry->getConfig("RECORDS_PER_PAGE", false, 10);
		$this->include_original = $this->registry->getConfig("INCLUDE_ORIGINAL_XML", false, false);
	}
	
	public function __sleep()
	{
		$this->data_map = null;
		$this->request = null;
		$this->registry = null;
		
		$keys = array();
		
		foreach ( $this as $key => $value )
		{
			array_push($keys, $key);
		}
		
		return $keys;
	}
	
	public function __wakeup()
	{
		$this->__construct();
	}
	
	public function __destruct()
	{
		$this->data_map = null;
	}
	
	public function search()
	{
		
	}
	
	public function progress()
	{
		
	}
	
	public function results()
	{
		
	}

	public function facet()
	{
		
	}
	
	public function record()
	{
		
	}
	
	protected function save()
	{
		
	}
	
	protected function delete()
	{
		
	}
	
	public function toXML()
	{
		$this->url = $this->search_object->getURL();
		
		if ( $this->total == null )
		{
			$this->total = $this->search_object->getTotal();
		}
		
		$results_xml = new DOMDocument( );
		$results_xml->loadXML( "<results />" );
		
		// add in the original url for debugging
		
		$search_url = $results_xml->createElement( "search_url", Xerxes_Parser::escapeXml( $this->url ) );
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
				// @todo make this overrideable
				
				// open-url redirect link
				// @todo make this overrideable
				
		      	// openurl kev context object please
		      	
				$kev = Xerxes_Parser::escapeXml($result->getOpenURL(null, $this->sid));
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
					
					$open_url_xml = $results_xml->createElement("url_open", Xerxes_Parser::escapeXML($open_url));
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

			// @todo make this overrideable
			
			$arrParams = array(
				"base" => "metasearch",
				"action" => "sort",
			);
				
			$query_string = $this->request->url_for($arrParams);
			
			// @todo make this overrideable
			
			$sort_options = array("rank" => "relevance", "year" => "date", "title" => "title",  "author" => "author");
			
			$current_sort = $this->sort;
			
			if ( $current_sort == null )
			{ 
				$current_sort = $this->registry->getConfig("SORT_ORDER_PRIMARY", false, "rank");
			}
					
			$sort_xml = $objPage->sortDisplay( $query_string, $current_sort, $sort_options);

			$import = $results_xml->importNode( $sort_xml->documentElement, true );
			$results_xml->documentElement->appendChild($import);

			
			## pager
			
			// @todo make this overrideable
			
			$arrParams = array(
				"base" => "metasearch",
				"action" => $this->request->getProperty("action"),
				"group" => $this->request->getProperty("group"),
				"resultSet" => $this->request->getProperty("resultSet") 
			);
			
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
	
	protected function convertToXerxesRecords(DOMDocument $xml)
	{
		$xerxes_doc = new Xerxes_Record_Document();
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
				
			$xml = Xerxes_Parser::request($url);

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
	// (MetasearchSaveDelete), and also used for knowing whether to display
	// a result line as saved or not. 
	
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

	private function extractISSNs()
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
}

class Xerxes_Query
{
	public $list;
	
	public function addTerm($field, $relation, $phrase)
	{
		$term = new Xerxes_Query_Term($field, $relation, $phrase);
		array_push($this->list, $term);
	}

	protected function checkSpelling()
	{
		$strAltYahoo = $this->registry->getConfig("ALTERNATE_YAHOO_LOCATION", false);
		$configYahooID = $this->registry->getConfig( "YAHOO_ID", false, "calstate" );
		
		for ( $x = 0; $x < count($this->list); $x++ )
		{
			$term = $this->list[$x];
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
			
			$strResponse = Xerxes_Parser::request($url);
				
			$objSpelling = new DOMDocument();
			$objSpelling->loadXML($strResponse);
				
			if ( $objSpelling->getElementsByTagName("Result")->item(0) != null )
			{
				$term->spell_correct = $objSpelling->getElementsByTagName("Result")->item(0)->nodeValue;
			}
		
			$this->list[$x] = $term;
		}
	}
}

class Xerxes_Query_Term
{
	public $field;
	public $relation;
	public $phrase;
	public $spell_correct;
	
	public function __construct($field, $relation, $phrase)
	{
		$term->field = $field;
		$term->relation = $relation;
		$term->phrase = $phrase;		
	}
}






?>