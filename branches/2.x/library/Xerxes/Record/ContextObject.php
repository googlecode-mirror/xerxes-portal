<?php

/**
 * Extract properties from OpenURL context object
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Record_ContextObject
{
	private $values = array();
	private $referrant = array();
	private $authors = array();
	
	public function __get($name)
	{
		if ( array_key_exists($name, $this->values))
		{
			return $this->values[$name];
		}
		elseif ( ! in_array($name, $this->referrant) )
		{
			throw new Exception("'$name' is not a valid Xerxes_Record_ContextObject property");
		}
		else
		{
			return null;
		}
	}
	
	public function __construct()
	{
		$this->referrant = array(
			"title","atitle","btitle","stitle","jtitle",
			"volume","issue","spage","epage",
			"genre","date","issn","isbn"
		);
	}
	
	public function getAuthors()
	{
		return $this->authors;
	}
	
	public function loadXML($xml)
	{
		$document = Xerxes_Framework_Parser::convertToDOMDocument($xml);
		$xpath = new DOMXPath($document);
		
		// test to see what profile the context object is using
		// set namespace accordingly

		if ($document->getElementsByTagNameNS( "info:ofi/fmt:xml:xsd:book", "book" )->item(0) != null)
		{
			$xpath->registerNamespace( "rft", "info:ofi/fmt:xml:xsd:book" );
		} 
		elseif ($document->getElementsByTagNameNS( "info:ofi/fmt:xml:xsd:dissertation", "dissertation" )->item(0) != null)
		{
			$xpath->registerNamespace( "rft", "info:ofi/fmt:xml:xsd:dissertation" );
		} 		
		elseif ($document->getElementsByTagNameNS( "info:ofi/fmt:xml:xsd", "journal" )->item(0) != null)
		{
			$xpath->registerNamespace( "rft", "info:ofi/fmt:xml:xsd" );
		}
		else
		{
			$xpath->registerNamespace( "rft", "info:ofi/fmt:xml:xsd:journal" );
		}
		
		// extract values
		
		foreach ( $this->referrant as $ref )
		{
			$node = $xpath->query( "//rft:$ref" )->item(0);
			
			if ( $node != null )
			{
				$this->values[$ref] = $node->nodeValue;
			}
		}
		
		// authors

		$authors = $xpath->query( "//rft:author[rft:aulast != '' or rft:aucorp != '']" );
		
		foreach ( $authors as $objAuthor )
		{
			$author_object = new Xerxes_Record_Author();
			
			foreach ( $objAuthor->childNodes as $objAuthAttr )
			{					
				switch ( $objAuthAttr->localName )
				{
					case "aulast":
						$author_object->last_name = $objAuthAttr->nodeValue;
						$author_object->type = "personal";
						break;
						
					case "aufirst":
						$author_object->first_name = $objAuthAttr->nodeValue;
						break;
						
					case "auinit":
						$author_object->init = $objAuthAttr->nodeValue;
						break;
						
					case "aucorp":
						$author_object->name = $objAuthAttr->nodeValue;
						$author_object->type = "corporate";
						break;							
				}
				
				array_push($this->authors, $author_object);
			}
		}
	}
}