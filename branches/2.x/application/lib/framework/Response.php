<?php

/**
 * Response Object
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes_Framework
 * @uses Xerxes_Framework_Parser
 */

class Xerxes_Framework_Response
{
	private $_data = array(); // data
	private $_redirect = ""; // redirect url
	
	private static $instance; // singleton pattern

	protected function __construct()
	{
	}
	
	/**
	 * Get an instance of the file; Singleton to ensure correct data
	 *
	 * @return Xerxes_Framework_Request
	 */
	
	public static function getInstance()
	{
		if ( empty( self::$instance ) )
		{
			self::$instance = new Xerxes_Framework_Response();
		}
		
		return self::$instance;
	}	
	
	/**
	 * Add data to the response
	 * 
	 * @param mixed $object		any kind of object you likes
	 * @param string $id		an identifier for this object
	 */
	
	public function add($object, $id)
	{
		$this->_data[$id] = $object;
	}
	
	/**
	 * Set the URL for redirect
	 *
	 * @param string $url
	 */
	
	public function setRedirect($url)
	{
		$this->_redirect = $url;
	}
	
	/**
	 * Get the URL to redirect user
	 *
	 * @return unknown
	 */
	
	public function getRedirect()
	{
		return $this->_redirect;
	}
	
	/**
	 * Output results to XML
	 */
	
	public function toXML()
	{
		$xml = new DOMDocument();
		$xml->loadXML("<xerxes />");
		
		foreach ( $this->_data as $id => $object )
		{
			$this->addToXML($xml, $id, $object);
		}
		
		return $xml;
	}
	
	/**
	 * Recursively convert data to XML
	 */
	
	private function addToXML(DOMDocument &$xml, $id, $object)
	{	
		$object_xml = null;
		
		if ( is_int($id) )
		{
			$id = "object_$id";
		}
		
		// no value, no mas!
		
		if ( $object == "" )
		{
			return null;
		}
		
		// already in xml, so take it
		
		elseif ( $object instanceof DOMDocument )
		{
			$object_xml = $object;
		}
		
		// simplexml, same deal, but make it dom, yo
		
		elseif ( $object instanceof SimpleXMLElement )
		{
			$object_xml = new DOMDocument();
			$object_xml->loadXML($object->asXML());
		}
		
		// object
		
		elseif ( is_object($object) )
		{
			// this object defines its own toXML method, so use that
		
			if ( method_exists($object, "toXML") )
			{
				$object_xml = $object->toXML();
			}
			else
			{
				// this object tells us to use this id in the xml
				
				if ( property_exists($object, "id") )
				{
					$id = $object->id;
				}
				
				$object_xml = new DOMDocument();
				$object_xml->loadXML("<$id />");
				
				// only public properties
				
				$reflection = new ReflectionObject($object);
				
				foreach ( $reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property )
				{
					$this->addToXML($object_xml, $property->name, $property->getValue($object));
				}
			}
		}
		
		// array
		
		elseif ( is_array($object) ) 
		{
			if ( count($object) == 0 )
			{
				return null;
			}
			
			$object_xml = new DOMDocument();
			$object_xml->loadXML("<$id />");
			
			foreach ( $object as $property => $value )
			{
				// if the name of the array is plural, then make the childen singular
				// if this is an array of objects, then the object may override this
				
				if ( is_int($property) && substr($id,-1) == "s" )
				{
					$property = substr($id,0,-1);
				}
				
				$this->addToXML($object_xml, $property, $value);
			}
		}
		
		// assumed to be primitive type (string, bool, or int)		
		
		else 
		{
			// just create a simple new element and return this thing
			
			$element = $xml->createElement($id, Xerxes_Framework_Parser::escapeXml($object) );
			$xml->documentElement->appendChild($element);
			return $xml;
		}
		
		// if we got this far, then we've got a domdocument to add
		
		$import = $xml->importNode($object_xml->documentElement, true);
		$xml->documentElement->appendChild($import);			
		
		return $xml;
	}
}
