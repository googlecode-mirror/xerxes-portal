<?php

/**
 * Shared functions for metasearch commands
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

abstract class Xerxes_Command_Metasearch extends Xerxes_Framework_Command
{
	private $objSearch = null; // metalib search object
	protected $cache = null; // cache object
	
	const DEFAULT_RECORDS_PER_PAGE = 10;
	const MARC_FIELDS_BRIEF = "LDR, 0####, 1####, 2####, 3####, 4####, 5####, 6####, 7####, 8####, 901##, ERI##, SID, YR";
	const MARC_FIELDS_FULL = "#####, OPURL";

	/**
	 * Get the metalib search object, here so we can ensure that we re-up the 
	 * session if it goes dormant
	 *
	 * @return Xerxes_Metasearch object
	 */
	
	protected function getSearchObject()
	{
		$configMetalibAddress = $this->registry->getConfig( "METALIB_ADDRESS" );
		$configMetalibUsername = $this->registry->getConfig( "METALIB_USERNAME" );
		$configMetalibPassword = $this->registry->getConfig( "METALIB_PASSWORD" );
		
		// re-up the metalib session if it has gone dead
		// first, check to see if the session id and expiry date are set

		$strSession = $this->request->getSession( "metalib_session_id" );
		$datReconnect = ( int ) $this->request->getSession( "metalib_session_expires" );
		
		// use the stored metalib session id if less than 20 minutes since the last 
		// request; otherwise set it to null to force a new session

		if ( time() > $datReconnect )
		{
			$strSession = null;
		}
		
		// set the next expiry time to 20 minutes from now

		$datReconnect = time() + 1200;
		$this->request->setSession( "metalib_session_expires", $datReconnect );
		
		// create metalib search object
		
		$this->objSearch = new Xerxes_MetaSearch( $configMetalibAddress, $configMetalibUsername, $configMetalibPassword, $strSession );
		
		$this->request->setSession( "metalib_session_id", $this->objSearch->getSession() );
		
		return $this->objSearch;
	}
	
	/**
	 * Get the base xml for our response, including the metalib url for debugging
	 *
	 * @return DOMDocument	base xml
	 */
	
	protected function documentElement()
	{
		// the wrapper for this part of the response

		$objXml = new DOMDocument( );
		$objXml->loadXML( "<results />" );
		
		// add in the original metalib url for debugging
		
		$objMetalibUrl = $objXml->createElement( "metalib_url", Xerxes_Framework_Parser::escapeXml( $this->objSearch->getUrl() ) );
		$objXml->documentElement->appendChild( $objMetalibUrl );
		
		return $objXml;
	}
	
	/**
	 * Fetch the search xml from cache and add it to the response
	 *
	 * @param DOMDocument $objXml		base xml document
	 * @param string $strGroup			group number
	 * @return DOMDOocument				base xml document with search info added
	 */
	
	protected function addSearchInfo($objXml, $strGroup)
	{
		// information about the search, stored in cache
		
		$objSearchXml = $this->getCache( $strGroup, "search", "DOMDocument" );
		
		if ( $objSearchXml->documentElement != null )
		{
      
			$objImport = $objXml->importNode( $objSearchXml->documentElement, true );
			$objXml->documentElement->appendChild( $objImport );
		}
		
		return $objXml;
	}
	
	/**
	 * Fetch the status information from cache and add it to the response, also 
	 * seperates out some key pieces of information for convenience in the XSLT
	 *
	 * @param DOMDocument $objXml		base xml document
	 * @param string $strGroup			group number
	 * @return DOMDOocument				base xml document with search status added
	 */
	
	protected function addStatus($objXml, $strGroup, $strResultSet = null, $iTotalHits = null)
	{
		// status of the search, stored in cache

		$objGroupXml = $this->getCache( $strGroup, "group", "DOMDocument" );
		
		if ( $objGroupXml->documentElement != null )
		{
			$objSimple = simplexml_import_dom( $objGroupXml );
			
			$strSort = ""; // last set sort order
			$strDatabaseTitle = ""; // database title
			
			// in case we have an international implementor who wants something different
			// like spaces or something
			
			$strThousSep = $this->registry->getConfig( "HITS_THOUSANDS_SEPERATOR", false, "," );
			
			// add links to group info and extract data for convenience
				
			foreach ( $objSimple->xpath( "//base_info" ) as $base_info )
			{
				// create the link
				
				$arrParams = array(
					"base" => "metasearch",
					"action" => "results",
					"group" => $strGroup,
					"resultSet" => (string) $base_info->set_number
				);
				
				$base_info->url = $this->request->url_for($arrParams);
				
				// format total number of hits
				
				$strTotalHits = (string) $base_info->no_of_documents;
				
				if ( $strTotalHits == "888888888" )
				{
					$base_info->no_of_documents = $strTotalHits;
				}
				elseif ( ! preg_match("/[a-zA-Z]{1}/", $strTotalHits) )
				{
					// just making doubly sure in case there is text here
					
					$base_info->no_of_documents = number_format( (int) $strTotalHits, 0, null, $strThousSep);
				}
				
				if ( $base_info->set_number == $strResultSet )
				{
					$strSort = ( string ) $base_info->sort;
					
					if ( $iTotalHits == null )
					{
						$iTotalHits = ( int ) $strTotalHits;
					}
					
					$strBase = ( string ) $base_info->base;
					
					if ( $strBase == "MERGESET" )
					{
						$strDatabaseTitle = "Top Results";
					}
					else
					{
						$strDatabaseTitle = (string) $base_info->full_name;
					}
				}
			}
			
			// only if a resultset was specified, so this doesn't appear in the hits page
			
			if ( $strResultSet != "" )
			{
				// link to the start of a resultset, convenience link for the
				// full record breadcrumbs and the like 
				
				$strGroup =	$this->request->getProperty("group");		
				$strStart = $this->request->getProperty("startRecord");
				$configRecordPerPage = $this->registry->getConfig( "RECORDS_PER_PAGE", false, self::DEFAULT_RECORDS_PER_PAGE );
				
				$iStart = 1;
				
				if ( $strStart != "" )
				{
					$iStart = (int) $strStart;
				}
				
				$arrParams = array(
					"base" => "metasearch",
					"action" => "results",
					"group" => $strGroup,
					"resultSet" => $strResultSet
				);
				
				// the start record of the current page of brief results 
				// (useful for full record view to provide a link back!)
				
				$iBase = ( floor( ( $iStart - 1 ) / $configRecordPerPage ) * $configRecordPerPage ) + 1;
				$arrParams["startRecord"] = $iBase;		
				
				$strResultSetLink = $this->request->url_for($arrParams);
				
				// add these in
				
				$objResultSet = $objXml->createElement( "resultset_link", Xerxes_Framework_Parser::escapeXml( $strResultSetLink ) );
				$objDatabase = $objXml->createElement( "database", Xerxes_Framework_Parser::escapeXml( $strDatabaseTitle ) );
				$objHits = $objXml->createElement( "hits", $iTotalHits );
				$objSort = $objXml->createElement( "sort", $strSort );
				
				$objXml->documentElement->appendChild( $objResultSet );
				$objXml->documentElement->appendChild( $objDatabase );
				$objXml->documentElement->appendChild( $objHits );
				$objXml->documentElement->appendChild( $objSort );
			}
			

			// pass back the url-enhanced group info status
			
			$objUpdatedGroup = new DOMDocument();
			$objUpdatedGroup->loadXML($objSimple->asXML());

			// append the search status xml to the response
	
			$objImport = $objXml->importNode( $objUpdatedGroup->getElementsByTagName( "find_group_info_response" )->item( 0 ), true );
			$objXml->documentElement->appendChild( $objImport );
		}
		
		return $objXml;
	}
	
	/**
	 * Add progress info to master xml document
	 *
	 * @param DOMDocument $objXml		base xml document
	 * @param string $strProgress		progress indicator
	 * @return DOMDOocument				base xml document with search progress data added
	 */
	
	protected function addProgress($objXml, $strProgress)
	{
		if ( $strProgress != null )
		{
			$objProgress = $objXml->createElement( "progress", $strProgress );
			$objXml->documentElement->appendChild( $objProgress );
		}
		
		return $objXml;
	}
	
	/**
	 * Fetch slimmed-down facet data from cache and add to master xml document
	 *
	 * @param DOMDocument $objXml		base xml document
	 * @param string $strGroup			group number
	 * @param bool	$bolFacets			[optional] whether facets should be required
	 * @return DOMDOocument				base xml document with facet info added
	 */
	
	protected function addFacets($objXml, $strGroup, $bolFacets = false)
	{
		// facets, stored in cache

		try
		{
			$objFacetXml = $this->getCache( $strGroup, "facets_slim", "DOMDocument" );
			
			if ( $objFacetXml->documentElement != null )
			{
				$objSimple = simplexml_import_dom( $objFacetXml );
				
				foreach ( $objSimple->cluster_facet_response->cluster_facet as $facet )
				{
					foreach ( $facet->node as $node )
					{
						$arrParam = array(
							"base" => "metasearch",
							"action" => "facet",
							"group" => $strGroup,
							"resultSet" => $this->request->getProperty("resultSet"),
							"facet" => (string) $facet["position"],
							"node" => (string) $node["position"]
						);
						
						$node->url = $this->request->url_for($arrParam);
					}
				}
				
				$objUpdatedFacets = new DOMDocument();
				$objUpdatedFacets->loadXML($objSimple->asXML());
				
				$objImport = $objXml->importNode( $objUpdatedFacets->getElementsByTagName( "cluster_facet_response" )->item( 0 ), true );
				$objXml->documentElement->appendChild( $objImport );
			}
		} 
		catch ( Exception $e )
		{
			if ( $bolFacets == true )
			{
				// may be missing because too few results or only one database selected
				// uncomment code here to track this if it is a problem
				// error_log( "facets not stored in cache" );
			}
		}
		
		return $objXml;
	}
	
	/**
	 * Converts records from marc to xerxes_record and adds them to the master xml response
	 * Also adds info on whether the record has already been saved this session. 
	 *
	 * @param DOMDOcument $objXml		master xml document
	 * @param array $arrRecords			an array of marc records
	 * @param bool $configMarcResults	whether to append the original marc records to the response
	 * @return DOMDOcument				master xml response updated with record data
	 */
	
	protected function addRecords($objXml, $arrRecords, $configMarcResults)
	{    
		$objRecords = $objXml->createElement( "records" );
		
		$arrXerxesRecords = array();
		
		foreach($arrRecords as $objRecord)
		{
			$objXerxesRecord = new Xerxes_MetalibRecord( );         
			$objXerxesRecord->loadXml( $objRecord );
			array_push($arrXerxesRecords, $objXerxesRecord);
		 }    

	    // enhance with links computed from metalib templates.
	    
		Xerxes_MetalibRecord::completeUrlTemplates($arrXerxesRecords, $this->request, $this->registry);
		
		$position = $this->request->getProperty("startRecord");
		
		if ( $position == "" )
		{
			$position = 1;
		}
    
		foreach ( $arrXerxesRecords as $objXerxesRecord )
		{
			$objRecordContainer = $objXml->createElement( "record" );
			$objRecords->appendChild( $objRecordContainer );
			
				
			// basis for most of the links below

			$arrParams = array(
				"base" => "metasearch",
				"group" => $this->request->getProperty("group"),
				"resultSet" => $objXerxesRecord->getResultSet(),
				"startRecord" => $objXerxesRecord->getRecordNumber()
			);
			
			// full-text link
			
			$arrFullText = $arrParams;
			$arrFullText["action"] = "record";
			
			if ( $this->request->getProperty("facet") != "" )
			{
				// append this so the full record page knows how to get back
				$arrFullText["return"] = Xerxes_Framework_Parser::escapeXml($this->request->getServer("REQUEST_URI"));
			}
			else
			{
				// this is a regular (non-facet) result
				
				// we keep current resultset and position (rather than original resultset 
				// and recordNumber) for the benefit of the merged set where these are different
				
				$arrFullText["resultSet"] = $this->request->getProperty("resultSet");
				$arrFullText["startRecord"] = $position;
			}
					
			$url = $this->request->url_for( $arrFullText );
			$objUrlFull = $objXml->createElement("url_full", $url);
			$objRecordContainer->appendChild( $objUrlFull );

			// save-delete link
			
			$arrSave = $arrParams;
			$arrSave["action"] = "save-delete";
								
			$url = $this->request->url_for( $arrSave );
			$objUrlSave = $objXml->createElement("url_save_delete", $url);
			$objRecordContainer->appendChild( $objUrlSave );

			// openurl redirect link
			
			$arrOpen = $arrParams;
			$arrOpen["action"] = "sfx";
								
			$url = $this->request->url_for( $arrOpen );
			$objOpenUrl = $objXml->createElement("url_open", $url);
			$objRecordContainer->appendChild( $objOpenUrl );			
			
      // openurl kev context object please
      $configSID = $this->registry->getConfig("APPLICATION_SID", false, "calstate.edu:xerxes");
      $kev = Xerxes_Framework_Parser::escapeXml($objXerxesRecord->getOpenURL(null, $configSID));
			$objOpenUrl = $objXml->createElement("openurl_kev_co", $kev);
      $objRecordContainer->appendChild( $objOpenUrl );
			
			// import xerxes xml
			
			$objXerxesXml = $objXerxesRecord->toXML();
			$objImportRecord = $objXml->importNode( $objXerxesXml->documentElement, true );
			$objRecordContainer->appendChild( $objImportRecord );
			
			// optionally import marc-xml

			if ( $configMarcResults == true )
			{
				$objMarcRecord = $objXerxesRecord->getMarcXML();
				$objImportRecord = $objXml->importNode( $objMarcRecord->getElementsByTagName( "record" )->item( 0 ), true );
				$objRecordContainer->appendChild( $objImportRecord );
			}
			
			$position++;
		}
		
		$objXml->documentElement->appendChild( $objRecords );
		
		
    
		return $objXml;
	}
	
	/**
	 * Add xml data  to the cache
	 *
	 * @param string $strGroup			the 'group' number
	 * @param string $strType			the type of data, either 'search', 'group' (i.e., status), 'facets', 'facets-slim'
	 * @param mixed $xml				either string, DOMDocument, or SimpleXML XML
	 * @return int status
	 */
	
	protected function setCache($strGroup, $strType, $xml)
	{
		if ( $this->cache == null )
		{
			 $this->cache = new Xerxes_Cache($strGroup);
		}
		
		return $this->cache->setCache( $strType, $xml );
	}
	
	/**
	 * Retrieve xml data from the cache
	 *
	 * @param string $strGroup			the 'group' number
	 * @param string $strType			the type of data, either 'search', 'group' (i.e., status), 'facets', 'facets-slim'
	 * @param $strResponseType			[optional] 'SimpleXML' for SimpeXML, otherwise DOMDocument
	 * @return int status
	 */
	
	protected function getCache($strGroup, $strType, $strResponseType = null)
	{
		if ( $this->cache == null )
		{
			 $this->cache = new Xerxes_Cache($strGroup);
		}
		
		return $this->cache->getCache( $strType, $strResponseType );
	}
	
	protected function saveCache()
	{
		if ( $this->cache != null )
		{
			$this->cache->save();
		}
	}
	
	/**
	 * Get a single record from metalib, used by serveral commands; Request should include
	 * params 'resultSet' the result set id, 'startRecord' the record's position in that 
	 * resultset
	 *
	 * @return DOMDocument 		marc-xml response from metalib
	 */
	
	protected function getRecord()
	{
		$objSearch = $this->getSearchObject(); // metalib search object
		
		// parameters from request
		
		$strResultSet = $this->request->getProperty( "resultSet" );
		$iStartRecord = ( int ) $this->request->getProperty( "startRecord" );
		
		// marc fields

		$strMarcFields = self::MARC_FIELDS_FULL;
		$configResultsFields = $this->registry->getConfig( "MARC_FIELDS_FULL", false, $strMarcFields );
		$arrFields = explode( ",", $configResultsFields );
		
		// fetch record from metalib

		return $objSearch->retrieve( $strResultSet, $iStartRecord, 1, null, "customize", $arrFields );
	}
	
	protected function getSearchDate()
	{
		$time = time();
		$hour = (int) date("G", $time);
		$flush_hour = $this->registry->getConfig("METALIB_RESTART_HOUR", false, 4);
			
		if ( $hour < $flush_hour )
		{
			// use yesterday's date
			// by setting a time at least one hour greater than the flush hour, 
			// so for example 5 hours ago if flush hour is 4:00 AM
			
			$time = $time - ( ($flush_hour + 1) * (60 * 60) );
		}
		
		return date("Y-m-d", $time);
	}
	
	protected function getGroupNumber()
	{
		$group = $this->request->getProperty( "group" );
		return array_pop(explode("-", $group));
	}
  
  	
	// Functions for saving saved record state from a result set. Just convenience
	// call to helper. 

	protected function markSaved($objRecord)
	{
		return Xerxes_Helper::markSaved( $objRecord );
	}
	protected function unmarkSaved($strResultSet, $strRecordNumber)
	{
		return Xerxes_Helper::unmarkSaved( $strResultSet, $strRecordNumber );
	}
	protected function isMarkedSaved($strResultSet, $strRecordNumber)
	{
		return Xerxes_Helper::isMarkedSaved( $strResultSet, $strRecordNumber );
	}

}

?>
