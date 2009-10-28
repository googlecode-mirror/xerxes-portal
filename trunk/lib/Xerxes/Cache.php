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
		private $arrMemory = array();		// temporarily stores the value for the duration of the request
		private $objDataMap = null;			// data map object
		private $intExpiry = 0;				// expiration date of the cached item
		
		public function __construct()
		{
			$this->objDataMap = new Xerxes_DataMap();
			
			// expiry will be 4:00 AM
			
			$time = time();
			$today = date("Y-m-d", $time);
			
			$this->intExpiry = strtotime($today . " 04:00");
			
			// if it is after 4:00 AM, set expiry to 4:00 AM tomorrow!

			$hour = (int) date("G", $time);
			
			if ( $hour > 4 )
			{
				$this->intExpiry += ( 24 * 60 * 60 );
			}
		}
		
		/**
		 * Retrieve data from the cache
		 *
		 * @param string $strGroup			the group identifier
		 * @param string $strType			the unique identifier
		 * @param string $strResponseType	[optional] can be SimpleXML, otherwise will return as DOMDocument
		 * @return mixed					DOMDocument (default) or SimpleXML
		 */
		
		public function getCache($strGroup, $strType, $strResponseType = null)
		{
			$objXml = new DOMDocument();
			
			// if document is already stored in the memory array, return it
			
			if ( array_key_exists("$strGroup-$strType", $this->arrMemory) )
			{
				$objXml = $this->arrMemory["$strGroup-$strType"];
			}
			else
			{
				// first time pulling from the cache for this request,
				// so populate all the cache types
				
				$arrResults = $this->objDataMap->getCacheGroup($strGroup, $this->intExpiry);
				
				foreach ( $arrResults as $objCache )
				{
					// convert the xml string to a dom document, and then load it into the array
					
					$objDocument = new DOMDocument();
					
					if ( $objCache->data != null )
					{
						$objDocument->loadXML($objCache->data);
						$this->arrMemory[$objCache->grouping . "-" . $objCache->id] = $objDocument;
					}
				}
				
				// make sure we got it from the database, and then assign it yo!
				
				if ( array_key_exists("$strGroup-$strType", $this->arrMemory ))
				{
					$objXml = $this->arrMemory["$strGroup-$strType"];
				}
				else
				{
					throw new Exception("error getting value from the database cache");
				}
			}
			
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
		 * @param string $strGroup		group identifier
		 * @param string $strType		unique identifier
		 * @param mixed $xml			string, SimpleXML or DOMDocument XML document
		 * @return int					status of the insert into cache
		 */
		
		public function setCache($strGroup, $strType, $xml)
		{
			// ensure the data is xml
			
			$objXml = new DOMDocument();
			$objXml = $this->convertToXML($xml);
			
			// save it in memory for the scope of the request
			
			$this->arrMemory["$strGroup-$strType"] = $objXml;
			
			// save it to the database
			
			$objCache = new Xerxes_Data_Cache();
			
			$objCache->source = "metalib";
			$objCache->grouping = $strGroup;
			$objCache->id = $strType;
			$objCache->data = $objXml->saveXML();
			$objCache->expiry = $this->intExpiry;
			
			return $this->objDataMap->setCache($objCache);
		}
		
		/**
		 * Convert XML as string or SimpleXML element to DOMDocument
		 *
		 * @param mixed $xml		xml string or simplexml element
		 * @return DOMDocument
		 */
		
		private function convertToXML($xml)
		{
			$objXml = new DOMDocument();
			
			// sometimes the object is simplexml, or xml string, so convert it here to DOMDocument 
			
			if ( $xml instanceof SimpleXMLElement )
			{
				$objXml->loadXML($xml->asXML());
			}
			elseif ( is_string($xml))
			{
				$objXml->loadXML($xml);
			}
			else
			{
				$objXml = $xml;
			}
			
			return $objXml;
		}
		
	}


?>