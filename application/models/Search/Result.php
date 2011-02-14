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
	protected $id = "record";
	
	public $url_open; // open url
	public $url_save_delete; // url for saving and deleting the record
	public $openurl_kev_co;	 // just the key-encoded-values of the openurl
	public $xerxes_record; // record
	public $original_record; // original xml
	public $holdings; // holdings from an ils
	public $recommendations; // recommendations object	
	public $reviews; // reviews
	
	protected $registry;
	
	/**
	 * Constructor
	 * 
	 * @param Xerxes_Record
	 */
	
	public function __construct(Xerxes_Record $record)
	{
		$this->xerxes_record = $record;
		$this->registry = Xerxes_Framework_Registry::getInstance();
	}
	
	/**
	 * Enhance record with bx recommendations
	 */
	
	public function addRecommendations()
	{
		$configToken = $this->registry->getConfig("BX_TOKEN", false);
						
		if ( $configToken != null )
		{
			$configBX = $this->registry->getConfig("BX_SERVICE_URL", false, 
				"http://recommender.service.exlibrisgroup.com/service");
			$configSID = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
			$configMaxRecords = $this->registry->getConfig("BX_MAX_RECORDS", false, "10");
			$configMinRelevance	= $this->registry->getConfig("BX_MIN_RELEVANCE", false, "0");
			
			
			// now get the open url
				
			$open_url = $this->xerxes_record->getOpenURL(null, $configSID);
			
			// send it to bx service
			
			$url = $configBX . "/recommender/openurl?token=$configToken&$open_url" .
				"&res_dat=source=global&threshold=$configMinRelevance&maxRecords=$configMaxRecords";
				
			$xml = Xerxes_Framework_Parser::request($url, 10);

			// header("Content-type: text/xml"); echo $xml; exit;
			
			if ( $xml != "" ) // only if we got a response
			{
				$records = array();
				
				$objDocument = new DOMDocument();
				$objDocument->loadXML($xml);				

				$objXPath = new DOMXPath($objDocument);
				$objXPath->registerNamespace("ctx", "info:ofi/fmt:xml:xsd:ctx");
				
				$objRecords = $objXPath->query("//ctx:context-object");
				
				foreach ( $objRecords as $objRecord )
				{
					$record = new Xerxes_BxRecord();
					$record->loadXML($objRecord);
					array_push($records, $record);
				}
				
				if ( count($records) > 0 ) // and only if there are any records
				{
					$this->recommendations = new Xerxes_Model_Search_Records(); 
							
					foreach ( $records as $bx_record )
					{
						$this->recommendations->addRecord($bx_record);
					} 
				}
			}
		}
	}
	
	/**
	 * Fetch item and holding records from an ILS for this record
	 */
	
	public function addHoldings()
	{
		$arrIDs = array(); // TODO: make this a look up
		
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
			return null; // empty items
		}
		
		// get the data
		
		$url .= "?action=status&id=" . urlencode(implode(" ", $arrIDs));
		$data = Xerxes_Framework_Parser::request($url, 10);
		
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
		
		$expiry = $this->config->getConfig("HOLDINGS_CACHE_EXPIRY", false, 2 * 60 * 60); // expiry set for two hours
		$expiry += time(); 
		
		$cache = new Xerxes_Data_Cache();
		$cache->source = $this->getSource();
		$cache->id = $cache_id;
		$cache->expiry = $expiry;
		$cache->data = serialize($items);
		
		$this->data_map->setCache($cache);
		
		// add it to the record
		
		$this->items = $items;
		
		return null;
	}
	
	public function getXerxesRecord()
	{
		return $this->xerxes_record;
	}
}
