<?php

/**
 * Extract properties for books, articles, and dissertations from SolrMarc implementation
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Solr_Record extends Xerxes_Record
{
	protected $source = "solr";
	protected $record_id;
	
	public function map()
	{
		parent::map();
		
		// see if there are any item records
		
		$registry = Xerxes_Model_Solr_Config::getInstance();
		$item_field = $registry->getConfig("ITEM_FIELD", false);
		$item_query = $registry->getConfig("ITEM_FIELD_QUERY", false);
		
		if ( $item_field != null ) 
		{
			// simple field value
			
			$items = $this->datafield($item_field);
			
			// print_r($items);
			
			if ( $items->length() == 0 )
			{
				$this->no_items = true;
			}
		}
		elseif ( $item_query != null )
		{
			// expressed as an xpath query
			
			$items = $this->xpath->query($item_query);
			
			$this->no_items = true;
			
			if ( $items->length > 0 )
			{
				$this->no_items = false;
			}
		}
	}

	public function getRecordID()
	{
		return $this->record_id;
	}
	
	public function getOpenURL($strResolver, $strReferer = null, $param_delimiter = "&")
	{
		$url = parent::getOpenURL($strResolver, $strReferer, $param_delimiter);
		
		// always ignore dates for journals and books, since catalog is describing
		// the item as a whole, not any specific issue or part
		
		return $url . "&sfx.ignore_date_threshold=1";
	}

	public function getOriginalXML($bolString = false)
	{
		$node = $this->document->getElementsByTagName("record" )->item( 0 );
		
		$string = $this->document->saveXML($node);
		
		if ( $bolString == true )
		{
			return $string;
		}
		else
		{
			$xml = new DOMDocument();
			$xml->loadXML($string);
			return $xml;
		}		
	}
}
