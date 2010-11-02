<?php
	
	/**
	 * Persists data beyond session by storing values in the database
	 *
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version $Id$
	 * @package Xerxes
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @uses Xerxes_DataMap
	 */

	class Xerxes_Cache
	{
		private $datamap = null;			// data map object
		private $expiry = 0;				// expiration date of the cached item
		private $cache = "";
		private $id;
		
		public function __construct($strGroup)
		{
			$this->datamap = new Xerxes_DataMap();
			$this->id = $strGroup;
			
			if ( $this->cache == "")
			{
				$arrResults = $this->datamap->getCache("metalib", $strGroup);
				
				if ( count($arrResults) == 0 )
				{
					// looks like this is new, so make it
					$this->cache = new Xerxes_Cache_Metalib();
				}
				else
				{
					$objCache = $arrResults[0];
					$this->cache = unserialize($objCache->data);
				}
			}
			
			// set expiry to 4:00 AM in the morning
			
			$group = explode("-", $strGroup);
			array_pop($group);
			$search_date = implode("-",$group);
			
			$this->expiry = strtotime($search_date . " 04:00");
			$this->expiry = $this->expiry + ( 24 * 60 * 60 );
		}
		
		/**
		 * Retrieve data from the cache
		 *
		 * @param string $strType			the unique identifier
		 * @param string $strResponseType	[optional] can be SimpleXML, otherwise will return as DOMDocument
		 * @return mixed					DOMDocument (default) or SimpleXML
		 */
		
		public function getCache($strType, $strResponseType = null)
		{
			$objXml = new DOMDocument();
			
			// nada, baby
			
			if ( $this->cache->$strType == null )
			{
				throw new Exception("sorry, your search appears to have expired");
			}
			
			// convert it to XML
			
			$objXml->loadXML($this->cache->$strType);
			
			// switch it to simplexml if specified
				
			if ( $strResponseType == "SimpleXML")
			{			
				return simplexml_import_dom($objXml);
			}
			else
			{
				return $objXml;
			}
		}
		
		/**
		 * Add data to the cache
		 *
		 * @param string $strType		unique identifier
		 * @param mixed $xml			string, SimpleXML or DOMDocument XML document
		 * @return int					status of the insert into cache
		 */
		
		public function setCache($strType, $xml)
		{
			// ensure the data is xml
			
			$string = $this->convertToXMLString($xml);
			
			// save it in memory for the scope of the request
			
			$this->cache->$strType = $string;
		}
		
		/**
		 * Convert XML as string or SimpleXML element to DOMDocument
		 *
		 * @param mixed $xml		xml string or simplexml element
		 * @return DOMDocument
		 */
		
		private function convertToXMLString($xml)
		{
			$string = "";
			
			// sometimes the object is simplexml, or xml string, so convert it here to DOMDocument 
			
			if ( $xml instanceof SimpleXMLElement )
			{
				$string = $xml->asXML();
			}
			elseif ( $xml instanceof DOMDocument )
			{
				$string = $xml->saveXML();
			}
			else
			{
				$string = $xml;
			}
			
			return $string;
		}
		
		public function save()
		{
			// we're going out of scope, so save everything to the database
			
			$objCache = new Xerxes_Data_Cache();
			
			$objCache->source = "metalib";
			$objCache->id = $this->id;
			$objCache->data = serialize($this->cache);
			$objCache->expiry = $this->expiry;
			
			return $this->datamap->setCache($objCache);	
		}
		
		public function __destruct()
		{
			$this->save();
		}
	}
	
	class Xerxes_Cache_Metalib
	{
		public $search = "";
		public $group = "";
		public $facets = "";
		public $facets_slim = "";
	}


?>