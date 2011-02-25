<?php

/**
 * Search Item
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: Facet.php 1658 2011-02-15 23:02:54Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_Model_Search_Item
{
	protected $bib_id; 		// the bibliographic record ID
    protected $availability; // boolean: is this item available for checkout?
    protected $status; 	// string describing the status of the item
    protected $location; // string describing the physical location of the item
    protected $reserve; // string indicating “on reserve” status – legal values: 'Y' or 'N'
    protected $callnumber; // the call number of this item
    protected $duedate; // string showing due date of checked out item (null if not checked out)
    protected $number; 	// the copy number for this item (note: although called “number”, 
    					//this may actually be a string if individual items are named rather than numbered)
    protected $barcode; // the barcode number for this item
	
	
	public function setProperty($name, $value)
	{
		if ( property_exists($this, $name) )
		{
			$this->$name = $value;
		}
	}
	
	public function toXML()
	{
		$xml = new DOMDocument();
		$xml->loadXML("<item />");
		
		foreach ( $this as $key => $value )
		{
			if ( $value == "")
			{
				continue;
			}
			
			$key = preg_replace('/\W|\s/', '', $key);
			
			$element = $xml->createElement($key, Xerxes_Framework_Parser::escapeXml($value));
			$xml->documentElement->appendChild($element);
		}
		
		return $xml;
	}
}
