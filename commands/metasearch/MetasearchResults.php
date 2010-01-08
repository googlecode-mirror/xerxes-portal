<?php

/**
 * Return a group of results and display them to the user
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_MetasearchResults extends Xerxes_Command_Metasearch
{
	public function doExecute()
	{
		$start = microtime(true);
	
		$arrFields = array ( ); // fields to return in response
		$iMaximumRecords = 10; // maximum number of records to return
		$iTotalHits = 0; // total number of hits in response
		$arrResults = array ( ); // holds results returned

		// metalib search object

		$objSearch = $this->getSearchObject();
		
		// parameters from request

		$strGroup = $this->request->getProperty( "group" );
		$strResultSet = $this->request->getProperty( "resultSet" );
		$iStartRecord = ( int ) $this->request->getProperty( "startRecord" );
		
		// access control
		
		$objSearchXml = $this->getCache( $strGroup, "search", "DOMDocument" );
		Xerxes_Helper::checkDbListSearchableByUser( $objSearchXml, $this->request, $this->registry );
		
		// marc fields to return from metalib; we specify these here in order to keep
		// the response size as small (and thus as fast) as possible
			
		$strMarcFields = self::MARC_FIELDS_BRIEF;
		
		// $strMarcFields = "#####, OPURL";
		
		// configuration options

		$configRecordPerPage = $this->registry->getConfig( "RECORDS_PER_PAGE", false, self::DEFAULT_RECORDS_PER_PAGE );
		$configMarcFields = $this->registry->getConfig( "MARC_FIELDS_BRIEF", false );
		$configIncludeMarcRecord = $this->registry->getConfig( "XERXES_BRIEF_INCLUDE_MARC", false, false );
		$configFacets = $this->registry->getConfig( "FACETS", false, false );
		
		// add additional marc fields specified in the config file

		if ( $configMarcFields != null )
		{
			$configMarcFields .= ", " . $strMarcFields;
		} 
		else
		{
			$configMarcFields = $strMarcFields;
		}
		
		// empty querystring values will return as 0, so fix here in case

		if ( $iStartRecord == 0 ) $iStartRecord = 1;
		
		$iMaximumRecords = ( int ) $configRecordPerPage;
		
		// extract total hits from this result set

		$objXml = $this->getCache( $strGroup, "group", "SimpleXML" );
		
		foreach ( $objXml->xpath( "//base_info" ) as $base_info )
		{
			if ( $base_info->set_number == $strResultSet )
			{
				$strTotalHits = $base_info->no_of_documents;
				
				if ( $strTotalHits == "888888888" )
				{
					$iTotalHits = 1;
				} 
				else
				{
					$iTotalHits = ( int ) $strTotalHits;
				}
			}
		}
		
		// set marc fields to return in response

		$arrFields = explode( ",", $configMarcFields );
		
		// get results from metalib

		$objResultsXml = $objSearch->retrieve( $strResultSet, $iStartRecord, $iMaximumRecords, $iTotalHits, "customize", $arrFields );
		
		// build the response, including previous cached data	

		$objXml = new DOMDocument( );
		
		$objXml = $this->documentElement();
		$objXml = $this->addSearchInfo( $objXml, $strGroup );
		$objXml = $this->addStatus( $objXml, $strGroup, $strResultSet, $iTotalHits );
		$objXml = $this->addProgress( $objXml, $this->request->getSession( "refresh-$strGroup" ) );
		
		// if this is a search-and-link resource add the original xml that contains the link

		if ( $objResultsXml->getElementsByTagName( "search_and_link" )->item( 0 ) != null )
		{
			$objImport = $objXml->importNode( $objResultsXml->documentElement, true );
			$objXml->documentElement->appendChild( $objImport );
		} 
		else
		{
			// add the records themselves

			foreach ( $objResultsXml->getElementsByTagName( "record" ) as $objRecord )
			{
				array_push( $arrResults, $objRecord );
			}
			
			// this will also convert the marc-xml to xerxes_record, and check for an already
			// saved record
      
			$objXml = $this->addRecords( $objXml, $arrResults, $configIncludeMarcRecord );
		}
		
		$objXml = $this->addFacets( $objXml, $strGroup, $configFacets );
		
		$this->request->addDocument( $objXml );
		
		// echo time(true) - $start . "<br>";
		
		return 1;
	}
}

?>
