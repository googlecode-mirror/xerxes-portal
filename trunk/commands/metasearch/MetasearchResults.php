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

		$id = $this->request->getProperty( "group" );
		$strGroup = $this->getGroupNumber();
		$strResultSet = $this->request->getProperty( "resultSet" );
		$iStartRecord = ( int ) $this->request->getProperty( "startRecord" );
		
		// access control
		
		$objSearchXml = $this->getCache( $id , "search", "DOMDocument" );
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

		$objXml = $this->getCache( $id , "group", "SimpleXML" );
		
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
		$objXml = $this->addSearchInfo( $objXml, $id );
		$objXml = $this->addStatus( $objXml, $id, $strResultSet, $iTotalHits );
		$objXml = $this->addProgress( $objXml, $this->request->getSession( "refresh-$strGroup" ) );
		
		// if this is a search-and-link resource add the original xml that contains the link

		if ( $objResultsXml->getElementsByTagName( "search_and_link" )->item( 0 ) != null )
		{
			$objImport = $objXml->importNode( $objResultsXml->documentElement, true );
			$objXml->documentElement->appendChild( $objImport );
			
			// this is a http post submission, so we need to see if we have added the post
			// data to the alternate record link in the IRD, since X-Server bug prevents this
			// from coming through in the response
			
			$objSearchType = $objResultsXml->getElementsByTagName( "search_and_link_type" )->item( 0 );
			
			if ( $objSearchType != null )
			{
				if ( $objSearchType->nodeValue == "POST")
				{
					$objSearchXml = $this->getCache( $id , "search", "SimpleXML" );
					$objGroupXml = $this->getCache( $id , "group", "SimpleXML" );
					
					$databases_id = $objGroupXml->xpath("//base_info[set_number = '$strResultSet']/base_001");
					
					if ( count($databases_id) != 1 )
					{
						throw new Exception("cannot find search-and-link database in group cache");
					}
					
					$metalib_id = (string) $databases_id[0];
					
					$databases = $objSearchXml->xpath("//database[@metalib_id='$metalib_id']");

					if ( count($databases) != 1 )
					{
						throw new Exception("cannot find database '$metalib_id' in search cache");
					}
					
					$database = $databases[0];
					
					// the form action
					
					$post = (string) $database->link_search_post;
					
					// the data to be posted
					
					$data = (string) $database->link_native_record_alternative;
					
					if ( $post == "" || $data == "" )
					{
						throw new Exception("cannot create http post elements for search-and-link database");
					}
					
					// xml close to what we need in html, just to make this easy
					
					$objPostXML = $objXml->createElement("post");
					$objXml->documentElement->appendChild($objPostXML);
					
					$objForm = $objXml->createElement("form");
					$objForm->setAttribute("action", Xerxes_Framework_Parser::escapeXml($post));
					$objPostXML->appendChild($objForm);
					
					foreach ( explode("&", $data) as $pair )
					{
						$arrKeys = explode("=", $pair);
						$key = $arrKeys[0];
						$value = $arrKeys[1];
						
						// metalib docs say only TERM1 is used in the 'URL mask', 
						
						if ( $value == "TERM1" )
						{ 
								$value = (string) $objSearchXml->pair[0]->query;
						}
						
						$objInput = $objXml->createElement("input");
						$objInput->setAttribute("name", $key);
						$objInput->setAttribute("value", $value);
						
						$objForm->appendChild($objInput);
					}
				}
			}
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
		
		$objXml = $this->addFacets( $objXml, $id, $configFacets );
		
		$this->request->addDocument( $objXml );
		
		// echo time(true) - $start . "<br>";
		
		return 1;
	}
}

?>
