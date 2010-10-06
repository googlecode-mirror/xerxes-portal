<?php

/**
 * Search and retrieve records from worldcat api
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_WorldCat
{
	private $base = "http://www.worldcat.org/webservices/catalog/search/sru";
	private $xid = "http://xisbn.worldcat.org/webservices/xid/";
	private $url; // url of the request
	private $total; // total number of records
	private $limits; // additional search string information
	private $query; // the entire sru query sent to worldcat
	private $service = "full"; // service level for the api, full or brief
	private $key ;	// api access key
	private $frbr = true; // whether workset groupings should be on

	public function __construct($key)
	{
		$this->key = $key;
	}

	public function getEditions($id)
	{
		// temporarily set the workset grouping off
		
		$previous = $this->frbr;
		$this->frbr = false;
		
		$arrOthers = array();
		
		$id = str_replace("-", "", $id);
		
		$url = $this->xid . "oclcnum/$id";
		
		$xml = new SimpleXMLElement($url, null, true);
		
		foreach ( $xml->oclcnum as $oclcnum )
		{
			if ( ! $oclcnum["presentOclcnum"] )
			{
				array_push($arrOthers, (string) $oclcnum);
			}
			else
			{
				array_push($arrOthers, (string) $oclcnum["presentOclcnum"]);
			}
		}
			
		$records = $this->records($arrOthers);
		
		// and now back
		
		$this->frbr = $previous;
		
		return $records;
	}
	
	public function records($ids)
	{
		$arrFinal = array();
		
		foreach ( $ids as $id )
		{
			$type = "no";
			
			if ( strlen($id) == 10 || strlen($id) == 13)
			{
				$type = "sn";
			}
			
			array_push($arrFinal, "srw.$type=\"$id\"");
		}
		
		$strQuery = implode(" OR ", $arrFinal);
		
		return $this->searchRetrieve( $strQuery, 1, 50 );
	}
	
	/**
	 * Return individual record by OCLC number
	 *
	 * @param string $id			OCLC number
	 * @return DOMDocument			MARC-XML or Dublin Core XML
	 */
	
	public function record($id)
	{
		if ( $id == "" )
		{
			throw new Exception( "no number supplied" );
		}
		
		$strQuery = "srw.no=\"$id\"";

		return $this->searchRetrieve( $strQuery, 1, 1 );
	}
	
	/**
	 * Return holdings for a particular record
	 *
	 * @param string $id			OCLC number
	 * @param string $strLibraries	list of libraries, seperated by comma, which serve as a filter for checking holdings
	 * @param int $iStart			[optional] offset from which the number of holding libraries begins
	 * @param int $iMax				[optional] total number of libraries with holdings to return
	 * @return DOMDocument			holdings in ISO 20775 Holdings schema format
	 */
	
	public function holdings( $id, $strLibraries, $iStart = 1, $iMax = 10 )
	{
		if ( $id == "" ) throw new Exception( "no oclc number supplied" );
		if ( $iStart == null ) $iStart = 1;
		
		$this->url = "http://worldcat.org/webservices/catalog/content/libraries/" . $id;
		$this->url .= "?wskey=" . $this->key;
		$this->url .= "&startLibrary=" . $iStart;
		$this->url .= "&maximumLibraries=" . $iMax;
		$this->url .= "&servicelevel=" . $this->service;

		if ( $strLibraries != "" )
		{
			$this->url .= "&oclcsymbol=" . urlencode( $strLibraries );
		}
		else
		{
			throw new Exception("holdings lookup requires a list of libraries");
		}
		
		$objXml = new DOMDocument();
		$objXml->load( $this->url );
		
		return $objXml;
	}
	
	public function hits($search)
	{
		$this->searchRetrieve($search, 1,1);
		return $this->total;
	}
	
	/**
	 * Search and retieve records
	 *
	 * @throws Exception for diagnostic errors
	 * 
	 * @param mixed $strQuery			the query in CQL format
	 * @param int $iStartRecord			[optional] start record to begin with, default 1
	 * @param int $iMaxiumumRecords		[optional] maximum records to return, default 10
	 * @param string $strSort			[optional] index to sort records on
	 * @return DOMDocument				SRU results response
	 */
	
	public function searchRetrieve($search, $iStartRecord = 1, $iMaxiumumRecords = 10, $strSort = null)
	{
		$strQuery = null;
		
		if ( $search instanceof Xerxes_Framework_Search_Query )
		{
			$strQuery = $search->toQuery();
		}
		else
		{
			$strQuery = $search;
		}
		
		// append any limits set earlier
		
		$this->query = $strQuery . " " . $this->limits;
		
		// construct the request to the server

		$this->url = $this->base . "?wskey=" . $this->key;
		$this->url .= "&version=1.1";
		$this->url .= "&operation=searchRetrieve";
		$this->url .= "&query=" . urlencode( $this->query );
		$this->url .= "&startRecord=" . $iStartRecord;
		$this->url .= "&maximumRecords=" . $iMaxiumumRecords;
		$this->url .= "&recordSchema=marcxml";
		$this->url .= "&servicelevel=" . $this->service;
		
		// workset grouping
		
		if ( $this->frbr == false )
		{
			$this->url .= "&frbrGrouping=off";
		}
		
		// sort order
		
		if ( $strSort != "" )
		{
			$this->url .= "&sortKeys=" . $strSort;
		}
		
		// get the response from the server
		
		$objXml = new DOMDocument( );
		$objXml->load( $this->url );

		// make sure we got something
		
		if ( $objXml->documentElement == null )
		{
			throw new Exception( "Could not connect to WorldCat database." );
		}
		
		$objXPath = new DOMXPath( $objXml );
		$objXPath->registerNameSpace( "zs", "http://www.loc.gov/zing/srw/" );
		
		// check for diagnostic errors

		$objDiagnostics = $objXPath->query( "zs:diagnostics" );
		
		if ( $objDiagnostics->length > 0 )
		{
			$strError = "";
			$diagnostics = simplexml_import_dom( $objDiagnostics->item( 0 ) );
			
			foreach ( $diagnostics->diagnostic as $diagnostic )
			{
				$strError .= $diagnostic->message;
				
				if ( ( string ) $diagnostic->details != "" )
				{
					$strError .= ": " . $diagnostic->details;
				}
			}
			
			throw new Exception( $strError );
		}
		
		// extract total hits

		$objTotal = $objXPath->query( "zs:numberOfRecords" )->item( 0 );
		if ( $objTotal != null ) $this->total = ( int ) $objTotal->nodeValue;
		
		return $objXml;
	}
	
	public function clearLimits()
	{
		$this->limits = "";
	}
	
	/**
	 * Limit results to only the library specific
	 *
	 * @param string $strCode		OCLC code for the library
	 */
	
	public function limitToLibrary($strCode)
	{
		$this->limits .= " AND srw.li=\"$strCode\" ";
	}
	
	/**
	 * Limit results to those libraries specifid
	 *
	 * @param string $strCodes		Comma seperated list of OCLC codes for the libraries
	 */
	
	public function limitToLibraries($strCodes)
	{
		$arrLibraries = explode( ",", $strCodes );
		
		for ( $x = 0 ; $x < count( $arrLibraries ) ; $x ++ )
		{
			if ( $x == 0 )
			{
				$this->limits .= " AND ( srw.li=\"" . $arrLibraries[$x] . "\" ";
				
				if ( count( $arrLibraries ) == 1 )
				{
					$this->limits .= " )";
				}
			} 
			elseif ( $x + 1 == count( $arrLibraries ) )
			{
				$this->limits .= " OR srw.li=\"" . $arrLibraries[$x] . "\" )";
			} 
			else
			{
				$this->limits .= " OR srw.li=\"" . $arrLibraries[$x] . "\" ";
			}
		}
	}
	
	/**
	 * Exclude results held by the specified library
	 *
	 * @param string $strCode		OCLC code for the library
	 */
	
	public function excludeLibrary($strCode)
	{
		$this->excludeLibraries( $strCode );
	}
	
	/**
	 * Exclude results held by those libraries specifid
	 *
	 * @param string $strCodes		Comma seperated list of OCLC codes for the libraries
	 */
	
	public function excludeLibraries($strCodes)
	{
		$arrLibraries = explode( ",", $strCodes );
		
		foreach ( $arrLibraries as $strLibrary )
		{
			$this->limits .= " NOT srw.li=\"$strLibrary\" ";
		}
	}
	
	/**
	 * Limit results to specific format
	 *
	 * @param string $strFormat		format code [sperate multiple values by comma], acceptable values are:
	 * 	'bks' = Books, 'ser' = Serials, 'vis' = Visual materials, 'map' = Maps,
	 * 	'rec' = 'Sound recordings', 'sco' = Scores, 'com' = Computer files,
	 *  'mix' = Mixed materials, 'url' = Internet resources, 'art' = Papers/articles,
	 *  'int' = Continually updated resource
	 */
	
	public function limitToMaterialType($format)
	{
		$format = explode(",", $format);
		
		$this->limits .= " AND (";
			
		for ( $x = 0 ; $x < count( $format ) ; $x ++ )
		{
			if ( $x > 0 )
			{
				$this->limits .= " OR";
			}
				
			$this->limits .= " srw.mt=\"" . $format[$x] . "\"";
		}
		
		$this->limits .= ")";
	}
	
	/**
	 * Exclude specific format from results
	 *
	 * @param string $strFormat		format code [sperate multiple values by comma], acceptable values are:
	 * 	'bks' = Books, 'ser' = Serials, 'vis' = Visual materials, 'map' = Maps,
	 * 	'rec' = 'Sound recordings', 'sco' = Scores, 'com' = Computer files,
	 *  'mix' = Mixed materials, 'url' = Internet resources, 'art' = Papers/articles,
	 *  'int' = Continually updated resource
	 */
	
	public function excludeMaterialType($format)
	{
		$format = explode(",", $format);
		
		foreach ( $format as $strFormat )
		{
			$this->limits .= " NOT srw.mt=\"$strFormat\" ";
		}
	}
	
	public function setServiceLevel($level)
	{
		$this->service = $level;
	}
	
	public function setWorksetGroupings($bol)
	{
		$this->frbr = (bool) $bol;
	}
	
	/**
	 * Get SRU query sent to the server
	 *
	 * @return string
	 */
	
	public function getQuery()
	{
		return $this->query;
	}
	
	/**
	 * Get url sent to the SRU server
	 *
	 * @return string
	 */
	
	public function getURL()
	{
		return $this->url;
	}
	
	/**
	 * Total number of records in the response
	 *
	 * @return int
	 */
	
	public function getTotal()
	{
		return ( int ) $this->total;
	}
}

?>