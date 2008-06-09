<?php

/**
 * Base class for common functions for my saved records commands
 *
 */

abstract class Xerxes_Command_Folder extends Xerxes_Framework_Command
{
	
	private $iTotal = "";			// total number of saved recods, kept here for caching
	
	/**
	 * Ensure that the username stored in session matches the one being requested by url params
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return mixed		string with a redirect url, null otherwise
	 */
	
	function enforceUsername( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
	{
		if ( $objRequest->getProperty("username") == "" || $objRequest->getProperty("username") != $objRequest->getSession("username") )
		{
			return $objRequest->url_for( 
				array(
					"base" => "folder",
					"action" => "home",
					"username" => $objRequest->getSession("username")
				)
			);
		}
		else
		{
			return null;
		}
	}
	
	public function add_export_options( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
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
				"username" => $objRequest->getSession("username"),
				"action" => $params["action"],
				"sortKeys" => $objRequest->getProperty("sortKeys"),
				"label" => $objRequest->getProperty("label"),
				"type" => $objRequest->getProperty("type")
			);
			
			$url_str = $objRequest->url_for( $arrParam );
			
			$url = $objXml->createElement('url', $url_str );
			$option->appendChild( $url );
			$objXml->documentElement->appendChild( $option );
		}
		
		$objRequest->addDocument( $objXml );
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
	
	public function setTagsCache(Xerxes_Framework_Request $objRequest, $arrResults)
	{
		// we'll store the tags summary in session so that edits can be 
		// done without round-tripping to the database; xslt can display
		// the summary by getting it from the request xml
		
		// make sure they are alphabetical
		
		ksort($arrResults);
		
		$objRequest->setSession("tags", $arrResults);
		
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
				"username" => $objRequest->getSession("username"),
				"label" => $label
			);
			
			$objTag->setAttribute("label", $label);
			$objTag->setAttribute("total", $total);
			$objTag->setAttribute("url", Xerxes_Parser::escapeXml($objRequest->url_for($arrUrl)));
		}
		
		$objRequest->addDocument($objXml);
	}

}


?>