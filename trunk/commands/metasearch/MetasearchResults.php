<?php

/**
 * Return a group of results and display them to the user
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version 1.1
 * @package Xerxes
 */

class Xerxes_Command_MetasearchResults extends Xerxes_Command_Metasearch
{
	/**
	 * Return a group of results and display them to the user; Request should include
	 * params for: 'group' the search group number; 'resultSet' the result set from which
	 * the record came; and 'startRecord' the offset from which to begin
	 *
	 * @param Xerxes_Framework_Request $objRequest
	 * @param Xerxes_Framework_Registry $objRegistry
	 * @return int status
	 */
	
	public function doExecute(Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry)
	{
		$arrFields = array ( ); // fields to return in response
		$iMaximumRecords = 10; // maximum number of records to return
		$iTotalHits = 0; // total number of hits in response
		$arrResults = array ( ); // holds results returned

		// metalib search object

		$objSearch = $this->getSearchObject( $objRequest, $objRegistry );
		
		// parameters from request

		$strGroup = $objRequest->getProperty( "group" );
		$strResultSet = $objRequest->getProperty( "resultSet" );
		$iStartRecord = ( int ) $objRequest->getProperty( "startRecord" );
		
		// access control
		
		$objSearchXml = $this->getCache( $strGroup, "search", "DOMDocument" );
		Xerxes_Helper::checkDbListSearchableByUser( $objSearchXml, $objRequest, $objRegistry );
		
		// marc fields to return from metalib; we specify these here in order to keep
		// the response size as small (and thus as fast) as possible

		$strMarcFields = "LDR, 001, 007, 008, 016##, 020##, 022##, 035##, 072##, 100##, " . 
			"24###, 260##, 500##, 505##, 513##, 514##, 520##, 546##, 6####, 773##, " . 
			"856##, ERI##, SID, YR";
		
		// configuration options

		$configRecordPerPage = $objRegistry->getConfig( "RECORDS_PER_PAGE", false, 10 );
		$configMarcFields = $objRegistry->getConfig( "MARC_FIELDS_BRIEF", false );
		$configIncludeMarcRecord = $objRegistry->getConfig( "XERXES_BRIEF_INCLUDE_MARC", false, false );
		$configFacets = $objRegistry->getConfig( "FACETS", false, false );
		
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

		$arrFields = split( ",", $configMarcFields );
		
		// get results from metalib

		$objResultsXml = $objSearch->retrieve( $strResultSet, $iStartRecord, $iMaximumRecords, $iTotalHits, "customize", $arrFields );
		
		// build the response, including previous cached data	

		$objXml = new DOMDocument( );
		
		$objXml = $this->documentElement();
		$objXml = $this->addSearchInfo( $objXml, $strGroup );
		$objXml = $this->addStatus( $objXml, $strGroup, $strResultSet, $iTotalHits );
		$objXml = $this->addProgress( $objXml, $objRequest->getSession( "refresh-$strGroup" ) );
		
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
		
		$objRequest->addDocument( $objXml );
		
		return 1;
	}
}

?>
