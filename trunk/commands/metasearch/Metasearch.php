<?php

/**
 * Shared functions for metasearch commands
 */

abstract class Xerxes_Command_Metasearch extends Xerxes_Framework_Command
{
	private $objSearch = null; // metalib search object
	private $objCache = null; // cache object
	
	const DEFAULT_RECORDS_PER_PAGE = 10;
	const MARC_FIELDS_BRIEF = "LDR, 0####, 1####, 2####, 3####, 4####, 5####, 6####, 7####, 8####, ERI##, SID, YR";
	const MARC_FIELDS_FULL = "#####, OPURL";

	/**
	 * Seperate here so we can set a cache object for all commands
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 */
	
	public function execute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$this->objCache = new Xerxes_Cache( );
    	parent::execute($objRequest, $objRegistry);
	}
	
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
		
		$objMetalibUrl = $objXml->createElement( "metalib_url", Xerxes_Parser::escapeXml( $this->objSearch->getUrl() ) );
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
				
				if ( $base_info->set_number == $strResultSet )
				{
					$strSort = ( string ) $base_info->sort;
					
					$strTotalHits = $base_info->no_of_documents;
					
					if ( $strTotalHits == "888888888" )
					{
						$iTotalHits = 1;
					} 
					elseif ( $iTotalHits == null )
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
				
				$objResultSet = $objXml->createElement( "resultset_link", Xerxes_Parser::escapeXml( $strResultSetLink ) );
				$objDatabase = $objXml->createElement( "database", Xerxes_Parser::escapeXml( $strDatabaseTitle ) );
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
			$objFacetXml = $this->getCache( $strGroup, "facets-slim", "DOMDocument" );
			
			if ( $objFacetXml->documentElement != null )
			{
				$objImport = $objXml->importNode( $objFacetXml->getElementsByTagName( "cluster_facet_response" )->item( 0 ), true );
				$objXml->documentElement->appendChild( $objImport );
			}
		} 
		catch ( Exception $e )
		{
			if ( $bolFacets == true )
			{
				// since a missing facet is not a fatal thing, we'll just warn
				// here in case there is a problem

				error_log( $e->getMessage() );
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
			$objXerxesRecord = new Xerxes_Record( );         
			$objXerxesRecord->loadXml( $objRecord );
			array_push($arrXerxesRecords, $objXerxesRecord);
		 }    

	    // enhance with links computed from metalib templates.
	    
		Xerxes_Record::completeUrlTemplates($arrXerxesRecords, $this->request, $this->registry);
		
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
				$arrFullText["return"] = Xerxes_Parser::escapeXml($this->request->getServer("REQUEST_URI"));
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
		return $this->objCache->setCache( $strGroup, $strType, $xml );
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
		return $this->objCache->getCache( $strGroup, $strType, $strResponseType );
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
		$arrFields = split( ",", $configResultsFields );
		
		// fetch record from metalib

		return $objSearch->retrieve( $strResultSet, $iStartRecord, 1, null, "customize", $arrFields );
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
