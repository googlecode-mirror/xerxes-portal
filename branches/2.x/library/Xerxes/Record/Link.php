<?php

/**
 * Record Subject
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Record_Link
{
	protected $type;
	protected $display;
	protected $url;
	
	const PDF = 1; // link is to full-text pdf document
	const HTML = 2; // link is to full-text in HTML
	const ONLINE = 3; // link is to the online full-text, but we're unsure of exact format
	const INFORMATIONAL = 4; // this is merely an informational link about the item, e.g., TOC or publisher info
	const ORIGINAL_RECORD = 5; // link to the original record in the system of origin, no indication of full-text
	
	public function __construct($url, $type = null, $display = null)
	{
		$this->url = $url;
		$this->type = $this->extractType($type);
		$this->display = $display;
	}
	
	public function extractType($data)
	{
		if ( $data == null )
		{
			return null;
		}
		elseif ( stristr( $data, "PDF" ) )
		{
			return Xerxes_Record_Link::PDF;
		} 
		elseif ( stristr( $data, "HTML" ) )
		{
			return Xerxes_Record_Link::HTML;
		}
		else
		{
			return Xerxes_Record_Link::ONLINE;
		}
	}
	
	public function setType($type)
	{
		$this->type = $type;
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function getDisplay()
	{
		return $this->display;
	}
	
	public function getURL()
	{
		return $this->url;
	}	
	
	public function isFullText()
	{
		if ( $this->type == self::PDF || $this->type == self::HTML || $this->type == self::ONLINE )
		{
			return true; 
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 			// if this is a "construct" link, then the second element is an associative 
				// array of marc fields and their values for constructing a link based on
				// the metalib IRD record linking syntax
				
				if ( is_array($arrLink[1]) )
				{
					foreach ( $arrLink[1] as $strField => $strValue )
					{
						$objParam = $objXml->createElement("param", Xerxes_Framework_Parser::escapeXml($strValue));
						$objParam->setAttribute("field", $strField);
						$objLink->appendChild($objParam);
					}
				}
	 */
}
