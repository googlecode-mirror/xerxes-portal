<?php

/**
 * Search Holding
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Search_Holding
{
	private $data = array();
	
	public function setProperty($name, $value)
	{
		if ( $name != "holding" && $name != "id" )
		{
			$this->data[$name] = $value;
		}
	}
	
	public function toXML()
	{
		$xml = new DOMDocument();
		$xml->loadXML("<holding />");
		
		foreach ( $this->data as $key => $value )
		{
			$element = $xml->createElement("data");
			$element->setAttribute("key", $key);
			$element->setAttribute("value", $value);
			$xml->documentElement->appendChild($element);
		}
		
		return $xml;
	}	
	
}