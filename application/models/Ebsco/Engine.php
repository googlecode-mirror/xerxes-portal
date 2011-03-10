<?php

/**
 * Ebsco Search Engine
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Ebsco_Engine extends Xerxes_Model_Search_Engine 
{
	protected $username; // ebsco username
	protected $password; // ebsco password
	
	private $deincrementing = 0; // ebsco hacks
	public $new_start = null; // ebsco hacks
	private $total = 0; // ebsco hacks
	
	public function __construct(Xerxes_Model_Ebsco_Config $config)
	{
		parent::__construct($config);
		
		$this->username = $config->getConfig("EBSCO_USERNAME");
		$this->password = $config->getConfig("EBSCO_PASSWORD");	
	}
	
	/**
	 * Return the total number of hits for the search
	 * 
	 * @return int
	 */	
	
	public function getHits( Xerxes_Model_Search_Query $search )
	{
		// get the results
		
		$results = $this->searchRetrieve( $search, 1, 1 );

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
		if ( ! strstr($id, "-") )
		{
			throw new Exception("could not find record");
		}
		
		$database = Xerxes_Framework_Parser::removeRight($id,"-");
		$id = Xerxes_Framework_Parser::removeLeft($id,"-");
		
		return $this->doSearch("AN $id", array($database), 1, 1);
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
		// prepare the query
		
		$terms = $search->getQueryTerms();
		$term = $terms[0];
		
		// get the results
		
		$results = $this->doSearch($term->field_internal . " " . $term->phrase, array(), $start, $max, $sort);
		
		
		
		return $results;
	}
	
	protected function doSearch($query, $databases, $start, $max, $sort = "relevance")
	{
		if ( $sort == "" )
		{
			$sort = "relevance";
		}
		
		$username = $this->username;
		$password = $this->password;

		// no database selected, so get 'em from config
		
		if ( count($databases) == 0 )
		{
			$databases_xml = $this->config->getConfig("EBSCO_DATABASES");
			
			if ( $databases_xml == "" )
			{
				throw new Exception("No databases defined");
			}
			
			foreach ( $databases_xml->database as $database )
			{
				array_push($databases, (string) $database["id"]);
			}
		}
		
		// construct url
		
		$this->url = "http://eit.ebscohost.com/Services/SearchService.asmx/Search?" . 
			"prof=$username" . 
			"&pwd=$password" . 
			"&authType=&ipprof=" . // empty params are necessary because ebsco is stupid
			"&query=" . urlencode($query) .		
			"&startrec=$start&numrec=$max" . 
			"&sort=$sort" .
			"&format=detailed";
		
		// add in the databases
		
		foreach ( $databases as $database )
		{
			$this->url .= "&db=$database";
		}
				
		// get the xml from ebsco
		
		$response = Xerxes_Framework_Parser::request($this->url);
		
		// testing
		// echo "<pre>$this->url<hr>$response</pre>";
		
		if ( $response == null )
		{
			throw new Exception("Could not connect to Ebsco search server");
		}
		
		// load it in
		
		$xml = new DOMDocument();
		$xml->recover = true;
		$xml->loadXML($response);
		
		$results = new Xerxes_Model_Search_ResultSet($this->config);
		
		// get hit total
		
		$total = 0;
		
		$hits = $xml->getElementsByTagName("Hits")->item(0);
		
		if ( $hits != null )
		{
			$this->total = (int) $hits->nodeValue;
		}
		
		
		
		### hacks until ebsco gives us proper hit counts, they are almost there
		
		$check = 0;
		
		foreach ( $xml->getElementsByTagName("rec") as $hits )
		{
			$check++;
		}
		
		// no hits, but we're above the first page, so the user has likely
		// skipped here, need to increment down until we find the true ceiling
		
		if ( $check == 0 && $start > $max )
		{
			// but let's not get crazy here
			
			if ( $this->deincrementing <= 8 )
			{
				$this->deincrementing++;
				$this->new_start = $start - $max;
				
				// register the change back up the stack
				// TODO: figure this crap out
				/*
				$this->request->setProperty("startRecord", $this->new_start, false, true );
				$this->request->setProperty("newStart", true, false, true );
				*/
				
				return $this->doSearch($query, $databases, $this->new_start, $max, $sort);
			}
		}
		
		// we've reached the end prematurely, so set this to the end
		
		$check_end = $start + $check;
		
		if ( $check < $max )
		{
			if ( $check_end	< $this->total )
			{
				$total = $check_end;
			}
		}
		
		## end hacks
		
		
		
		// set total
		
		$results->total = $this->total;
		
		// add records
		
		foreach ( $this->parseRecords($xml) as $record )
		{
			$results->addRecord($record);
		}
		
		// add clusters
		
		$facets = $this->parseFacets($xml);
		$results->setFacets($facets);
		
		return $results;
	}
	
	protected function parseRecords(DOMDocument $xml)
	{
		$records = array();

		$xpath = new DOMXPath($xml);
		 // $xpath->registerNamespace("ebsco", "http://epnet.com/webservices/SearchService/Response/2007/07/");
		
		$records_object = $xpath->query("//rec"); // records actually have null namespace, silly ebsco
		
		foreach ( $records_object as $record )
		{
			$xerxes_record = new Xerxes_Model_Ebsco_Record();
			$xerxes_record->loadXML($record);
			array_push($records, $xerxes_record);
		}
		
		return $records;
	}
	
	protected function parseFacets(DOMDocument $dom)
	{
		$facets = new Xerxes_Model_Search_Facets();

		$xml = simplexml_import_dom($dom->documentElement);
		
		// for now just the database hit counts
		
		$databases = $xml->Statistics->Statistic;
		
		if ( count($databases) > 1 )
		{
			$databases_facet_name = $this->config->getConfig("DATABASES_FACET_NAME", false, "Databases");
				
			$group = new Xerxes_Model_Search_FacetGroup("databases");
			$group->name = "databases";
			$group->public = $databases_facet_name;
			
			$databases_array = array();
			
			foreach ( $databases as $database )
			{
				$database_id = (string) $database->Database;
				$database_hits = (int) $database->Hits;
				
				// nix the empty ones
				
				if ( $database_hits == 0 )
				{
					continue;
				}
				
				$databases_array[$database_id] = $database_hits;
			}
			
			// get 'em in reverse order
			
			arsort($databases_array);
			
			foreach ( $databases_array as $database_id => $database_hits)
			{
				$facet = new Xerxes_Model_Search_Facet();
				$facet->name = $this->config->getDatabaseName($database_id);
				$facet->count = Xerxes_Framework_Parser::number_format( $database_hits );
					
				$group->addFacet($facet);
			}
			
			$facets->addGroup($group);
		}
		
		return $facets;
	}
}



















