<?php

/**
 * Base class for common functions for my saved records commands
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

abstract class Xerxes_Command_Folder extends Xerxes_Framework_Command
{
	const DEFAULT_RECORDS_PER_PAGE = 20; 
	private $iTotal = "";			// total number of saved recods, kept here for caching
	
	/**
	 * Ensure that the username stored in session matches the one being requested by url params
	 *
	 * @return mixed		string with a redirect url, null otherwise
	 */
	
	function enforceUsername()
	{
		if ( $this->request->getProperty("username") == "" || $this->request->getProperty("username") != $this->request->getSession("username") )
		{
			return $this->request->url_for( 
				array(
					"base" => "folder",
					"action" => "home",
					"username" => $this->request->getSession("username")
				)
			);
		}
		else
		{
			return null;
		}
	}
	
	public function add_export_options()
	{
	 	$objXml = new DOMDocument();
		$objXml->loadXML("<export_functions />");
		
		$all_params = array(
			array("id" => "email", "action" => "output_email"),
			array("id" => "endnote", "action" => "output_export_endnote" ),
			array( "id" => "text", "action" => "output_export_text"),
			array( "id" => "refworks", "action" => "output_refworks")
		);
		
		foreach ( $all_params as $params )
		{
			$option = $objXml->createElement("export_option");
			$option->setAttribute("id", $params["id"] );
			
			$arrParam = array(
				"base" => "folder",
				"username" => $this->request->getSession("username"),
				"action" => $params["action"],
				"sortKeys" => $this->request->getProperty("sortKeys"),
				"label" => $this->request->getProperty("label"),
				"type" => $this->request->getProperty("type")
			);
			
			$url_str = $this->request->url_for( $arrParam );
			
			$url = $objXml->createElement('url', $url_str );
			$option->appendChild( $url );
			$objXml->documentElement->appendChild( $option );
		}
		
		$this->request->addDocument( $objXml );
	}
	
	public function getTotal( $strUsername, $strLabel, $strType )
	{
		if ( $this->iTotal == null )
		{
			$objData = new Xerxes_DataMap;
			$this->iTotal = $objData->totalRecords($strUsername, $strLabel, $strType);
		}
		
		return $this->iTotal;
	}
	
	public function setTagsCache( $arrResults )
	{
		// we'll store the tags summary in session so that edits can be 
		// done without round-tripping to the database; xslt can display
		// the summary by getting it from the request xml
		
		// make sure they are alphabetical
		
		ksort($arrResults);
		
		$this->request->setSession("tags", $arrResults);
		
		// but also add it to the request with urls
		
		$objXml = new DOMDocument();
		$objXml->loadXML("<tags />");
		
		foreach ( $arrResults as $label => $total )
		{
			$objTag = $objXml->createElement("tag");
			$objXml->documentElement->appendChild($objTag);
			
			$arrUrl = array(
				"base" => "folder",
				"action" => "home",
				"username" => $this->request->getSession("username"),
				"label" => $label
			);
			
			$objTag->setAttribute("label", $label);
			$objTag->setAttribute("total", $total);
			$objTag->setAttribute("url", Xerxes_Framework_Parser::escapeXml($this->request->url_for($arrUrl)));
		}
		
		$this->request->addDocument($objXml);
	}

}


?>