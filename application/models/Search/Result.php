<?php

/**
 * Search Record
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Search_Result
{
	public $url_open; // open url
	public $openurl_kev_co;	 // just the key-encoded-values of the openurl
	public $xerxes_record; // record
	public $original_record; // original xml
	public $holdings; // holdings from an ils
	public $recommendations = array(); // recommendation objects	
	public $reviews; // reviews
	
	protected $registry; // global config
	protected $config; // local config
	protected $sid; // open url sid
	
	/**
	 * Constructor
	 * 
	 * @param Xerxes_Record $record						record
	 * @param Xerxes_Model_Search_Config $config		local config
	 */
	
	public function __construct(Xerxes_Record $record, Xerxes_Model_Search_Config $config)
	{
		$this->xerxes_record = $record;
		$this->registry = Xerxes_Framework_Registry::getInstance();
		$this->config = $config;
		
		// link resolver stuff
		
		$link_resolver = $this->config->getConfig("LINK_RESOLVER_ADDRESS", false, $this->registry->getConfig("LINK_RESOLVER_ADDRESS", true));
		$this->sid = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
		
		$this->url_open = $record->getOpenURL($link_resolver, $this->sid);
		$this->openurl_kev_co = $record->getOpenURL(null, $this->sid);
		
		// holdings
		
		$this->holdings = new Xerxes_Model_Search_Holdings();
		
		if ( $record->hasPhysicalHoldings() == false )
		{
			$this->holdings->checked = true;
		}
	}
	
	/**
	 * Enhance record with bx recommendations
	 */
	
	public function addRecommendations()
	{
		$configToken = $this->registry->getConfig("BX_TOKEN", false);
						
		if ( $configToken != null )
		{
			$configBX = $this->registry->getConfig("BX_SERVICE_URL", false);
			$configMinRelevance	= (int) $this->registry->getConfig("BX_MIN_RELEVANCE", false, 0);
			$configMaxRecords = (int) $this->registry->getConfig("BX_MAX_RECORDS", false, 10);
			
			$bx_engine = new Xerxes_Model_Bx_Engine($configToken, $this->sid, $configBX);
			$bx_records = $bx_engine->getRecommendations($this->xerxes_record, $configMinRelevance, $configMaxRecords);

			if ( count($bx_records) > 0 ) // only if there are any records
			{
				foreach ( $bx_records as $bx_record )
				{
					$result = new Xerxes_Model_Search_Result($bx_record, $this->config);
					array_push($this->recommendations, $result);
				}
			}			
		}
	}
	
	/**
	 * Add holdings to this result
	 */
	
	public function setHoldings( Xerxes_Model_Search_Holdings $holdings )
	{
		$this->holdings = $holdings;
	}
	
	/**
	 * Return item records
	 * 
	 * @return array of Xerxes_Model_Search_Item
	 */
	
	public function getHoldings()
	{
		return $this->holdings;
	}

	/**
	 * Fetch item and holding records from an ILS for this record
	 */
	
	public function fetchHoldings()
	{
		$xerxes_record = $this->getXerxesRecord();
		
		$id = $xerxes_record->getRecordID(); // id from the record
		$cache_id = $xerxes_record->getSource() . "." . $id; // to identify this in the cache
		$url = $this->config->getConfig("LOOKUP"); // url to availability server
		
		// mark that we've checked holdigns either way
		
		$this->holdings->checked = true;
		
		// no holdings source defined or somehow id's are blank
		
		if ( $xerxes_record->hasPhysicalHoldings() == false || $url == "" || $id == "" )
		{
			return null;
		}		

		// get the data
		
		$url .= "?action=status&id=" . urlencode($id);
		$data = Xerxes_Framework_Parser::request($url, 5);
		
		// echo $url; exit;
		
		// no data, what's up with that?
		
		if ( $data == "" )
		{
			throw new Exception("could not connect to availability server");
		}		
		
		
		// response is (currently) an array of json objects
		
		$results = json_decode($data);
		
		// parse the response
		
		if ( is_array($results) )
		{
			if ( count($results) > 0 )
			{
				// now just slot them into our item object
				
				foreach ( $results as $holding )
				{
					$is_holding = property_exists($holding, "holding"); 
										
					if ( $is_holding == true )
					{
						$item = new Xerxes_Model_Search_Holding();
						$this->holdings->addHolding($item);
					}
					else
					{
						$item = new Xerxes_Model_Search_Item();
						$this->holdings->addItem($item);
					}
					
					foreach ( $holding as $property => $value )
					{
						$item->setProperty($property, $value);
					}
				}
			}
		}
		
		// cache it for the future
		
		$cache = new Xerxes_Framework_Cache();
		
		$expiry = $this->config->getConfig("HOLDINGS_CACHE_EXPIRY", false, 2 * 60 * 60); // expiry set for two hours
		$expiry += time(); 
		
		$cache->set($cache_id, serialize($this->holdings), $expiry);
		
		return null;
	}
	
	/**
	 * Add reviews from Good Reads
	 */
	
	public function addReviews()
	{
		$xerxes_record = $this->getXerxesRecord();
		$isbn = $xerxes_record->getISBN();
		
		$key = $this->registry->getConfig("GOOD_READS_API_KEY", false );
		
		if ( $key != null )
		{
			$url = "http://www.goodreads.com/book/isbn?isbn=$isbn&key=$key";
			
			$data = Xerxes_Framework_Parser::request($url, 5);
			
			if ( $data != "" )
			{
				$xml = new DOMDocument();
				$xml->recover = true;
				$xml->loadXML($data);
				
				$this->reviews = $xml;
			}
		}
	}
	
	/**
	 * Get the Xerxes_Record object
	 */
	
	public function getXerxesRecord()
	{
		return $this->xerxes_record;
	}
}
