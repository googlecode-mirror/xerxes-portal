<?php

/**
 * Worldcat Search
 * 
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_WorldCatSearch extends Xerxes_Framework_Search 
{
	// basic

	public $id = "worldcat";
	protected $query_object_type = "Xerxes_WorldCatSearch_Query";
	protected $record_object_type = "Xerxes_WorldCatRecord";
	
	// worldcat specific

	protected $groups_config = array();
	protected $worldcat_config;
	protected $relevance_type;
	
	protected $worldcat_query_regex = "^query[0-9]{0,1}$|^field[0-9]{0,1}$|^boolean[0-9]{0,1}$";
	protected $worldcat_limit_regex = "^year$|^year-relation|^[a-z]{2}$|^[a-z]{2}_exact$";	
	
	public function __construct($objRequest, $objRegistry)
	{
		parent::__construct($objRequest, $objRegistry);
			
		// worldcat specific
		
		$this->worldcat_config = Xerxes_WorldCatConfig::getInstance();
		$this->worldcat_config->init();
		
		$this->relevance_type = $this->worldcat_config->getConfig("WORLDCAT_RELEVANCE_TYPE", false, "Score");

		// basic

		$this->search_object = $this->getWorldCatObject($this->request->getProperty("source"));	
	}
		
	public function results()
	{
		// add the (public aspects of the) worldcat config file to the response
		// so we can make use of it in the interface 
		
		$this->addConfigToResponse();

		// max records
		
		$configMaxRecords = $this->worldcat_config->getConfig("WORLDCAT_MAX_RECORDS", false, 10);		
		
		// start, stop, source, sort properties
		
		$start = $this->request->getProperty("startRecord");
		$max = $this->request->getProperty("maxRecords");
		$desc = false;

		// if no sort defined, its relevance!
			
		if ( $this->sort == null )
		{
			$this->sort = $this->relevance_type;
		}
		
		// date, library count, and relevance should be sorted descending
		
		if ( $this->sort == "Date" || $this->sort == $this->relevance_type || $this->sort == "LibraryCount")
		{
			$desc = true;
		}		
		
		// set some explicit defaults
		
		if ( $start == null || $start == 0 ) $start = 1;
		if ( $max != null && $max <= $configMaxRecords ) $configMaxRecords = $max;
		
		$search = $this->query->toQuery();
		
		// echo $search; exit;
		
		// get results and convert them to xerxes_record
		
		$xml = $this->search_object->searchRetrieve($search, $start, $configMaxRecords, "marcxml", $this->sort, $desc);
		$this->results = $this->convertToXerxesRecords($xml);
		
		$this->getHoldingsInject();
		
		// done
		
		return $this->resultsXML();
	}
	
	public function record()
	{
		$this->addConfigToResponse();
		
		$xml = parent::record();
		$this->getHoldingsInject();
		
		return $xml;
	}
	
	public function lookup()
	{
		$source = $this->request->getProperty("source");
		$isbn = $this->request->getProperty("isbn");
		$oclc = $this->request->getProperty("oclc");
		
		$standard_numbers = array();

		if ( $isbn != null )
		{
			array_push($standard_numbers, "ISBN:$isbn");
		}
		if ( $oclc != null )
		{
			array_push($standard_numbers, "OCLC:$oclc");
		}		
		
		$xml = $this->getHoldings($source, $standard_numbers);
		
		return $xml;
	}
	
	/**
	 * Get the Worldcat search object, with some limits and things set by virtue of 
	 * config values
	 * 
	 * @param string $strSource		the id of the 'group' that sets the limits
	 */
	
	protected function getWorldCatObject($strSource = "")
	{
		$configKey = $this->worldcat_config->getConfig("WORLDCAT_API_KEY", true);
		$role = $this->request->getSession("role");
			
		// worldcat search object
			
		$objCatalog = new Xerxes_WorldCat($configKey);
		
		// if this is a guest, make it open, and return it pronto, since we 
		// can't use the limiters below

		if ( $role == "guest" )
		{
			$objCatalog->setServiceLevel("default");
			return $objCatalog;
		}		
		
		if ( $strSource != "" )
		{
			// search options configured
				
			$objWorldcatConfig = $this->getConfig($strSource);
			$configLibraryCodes = $objWorldcatConfig->libraries_include;
			$configExclude = $objWorldcatConfig->libraries_exclude;
			$configLimitDocTypes = $objWorldcatConfig->limit_material_types;
			$configExcludeDocTypes = $objWorldcatConfig->exclude_material_types;
			$configWorksetGrouping = $objWorldcatConfig->frbr;

			// no workset grouping, please
		
			if ( $configWorksetGrouping == "false" )
			{
				$objCatalog->setWorksetGroupings(false);
			}			
			
			// limit to certain libraries
				
			if ( $configLibraryCodes != null ) 
			{
				$objCatalog->limitToLibraries($configLibraryCodes);
			}
				
			// exclude certain libraries
			
			if ( $configExclude != null )
			{
				$objCatalog->excludeLibraries($configExclude);
			}
				
			// limit results to specific document types; a limit entry will
			// take presidence over any format specifically excluded
				
			if ( $configLimitDocTypes != null )
			{
				$objCatalog->limitToMaterialType($configLimitDocTypes);
			}
			elseif ( $configExcludeDocTypes != null )
			{
				$objCatalog->excludeMaterialType($configExcludeDocTypes);
			}
		}
		
		return $objCatalog;
	}
	
	/**
	 * Get just the 'search' params (query, boolean, field) out of the URL
	 * Special handling here for some worldcat-specific stuff
	 */

	protected function extractSearchParams()
	{
		$arrFinal = parent::extractSearchParams();
		
		# special cases for oclc number and isbn
		
		// oclc number
			
		$strOclc = $this->request->getProperty("rft.oclc");
					
		if ( $strOclc == "" )
		{
			$strOclc = $this->request->getProperty("oclc");
		}
		
		if ( $strOclc != "" )
		{
			$arrTerm["query"] = $strOclc;
			$arrTerm["field"] = "no";
		}
		
		// isbn
		
		$strIsbn = $this->request->getProperty("rft.isbn");

		if ( $strIsbn != "" )
		{
			$arrTerm["query"] = $strIsbn;
			$arrTerm["field"] = "bn";
		}
		
		// special handling of worldcat conventions
		
		for ( $x = 0; $x < count($arrFinal); $x++ )
		{
			// no field specified, assume keyword
			
			if ( $arrFinal[$x]["field"] == "" )
			{
				$arrFinal[$x]["field"] = "kw";
			}
		
			// a convention to use '_exact' at the end of the field
			// name to set an 'exact' relation			

			if ( strstr($arrFinal[$x]["field"], "_exact" ) )
			{
				$arrFinal[$x]["field"] = str_replace("_exact", "", $arrFinal[$x]["field"] );
				$arrFinal[$x]["relation"] = "exact";
			}
		}
				
		return $arrFinal;
	}

	/**
	 * Get just the 'limit' params (year, year-relation, and two letter worldcat fields) 
	 * out of the URL
	 */	
	
	protected function extractLimitParams()
	{
		$arrFinal = array();
		
		foreach ( $this->request->getAllProperties() as $key => $value )
		{
			$key = urldecode($key);
			
			// find year and two-letter fields relations relations
			
			if ( preg_match("/" . $this->worldcat_limit_regex . "/", $key) )
			{
				// slip empty fields
				
				if ( $value == "")
				{
					continue;
				}
				
				if ( is_array($value) )
				{
					$concated = "";
					
					foreach ( $value as $data )
					{
						if ( $data != "" )
						{
							if ($concated == "")
							{
								$concated = $data;
							}
							else
							{
								$concated .= "," . $data;
							}
						}
					}
					
					if ( $concated == "" )
					{
						continue;
					}
					
					$value = $concated;
				}
				
				$arrFinal[$key] = $value;
			}
		}
		
		return $arrFinal;
	}
	
	/**
	 * Extract both query and limit params from the URL
	 */
	
	protected function getAllSearchParams()
	{
		$final = array();
		
		foreach ( $this->request->getAllProperties() as $key => $value )
		{
			$key = urldecode($key);
						
			if ( preg_match("/" . $this->worldcat_query_regex . "|" . $this->worldcat_limit_regex . "/", $key) )
			{
				$final[$key] = $value;
			}
		}
		
		return $final;
	}

	protected function getConfig($strSource)
	{
		if ( $strSource == "" )
		{
			$strSource = "local";
		}
		
		if ( ! array_key_exists($strSource, $this->groups_config) )
		{
			$this->groups_config[$strSource] = new Xerxes_WorldCatGroup($strSource, $this->worldcat_config->getXML());
		}
		
		return $this->groups_config[$strSource];
	}
	
	protected function addConfigToResponse()
	{	
		// @todo simplify this; there are three different ways we are adding
		// the config information here, for some reason
		
		if ( $this->request->getSession("role") != "guest")
		{
			// group set-up
				
			$xml = $this->worldcat_config->getXML()->configuration->worldcat_groups;
			
			if ( $xml != false )
			{
				$objGroupXml = new DOMDocument();
				$objGroupXml->loadXML($xml->asXML());
				$this->request->addDocument($objGroupXml);
			}
		}
		
		$this->request->addDocument( $this->worldcat_config->publicXML() );
		
		if ( $this->worldcat_config->getXML()->configuration->worldcat_groups->group != false )
		{
			$arrSources = array();
			
			$objXml = new DOMDocument();
			$objXml->loadXML("<source_functions />");
			
			foreach ( $this->worldcat_config->getXML()->configuration->worldcat_groups->group as $group )
			{
				array_push($arrSources, (string) $group["id"]);
			}
		
			foreach ( $arrSources as $strSource )
			{
				$objOption = $objXml->createElement("source_option");
				$objOption->setAttribute("source", $strSource );
				
				$arrUrl = array(
					"base" => "search",
					"action" => "results",
					"source" => $strSource,
					"sortKeys" => $this->request->getProperty("sortKeys")
				);
				
				// other search and limit params in the url (but not the ones above yo!)  
				
				foreach ( $this->getAllSearchParams() as $key => $value)
				{
					$arrUrl[$key] = $value;
				}
				
				if ( $this->request->getProperty("spell") != null )
				{
					$arrUrl["spell"] = $this->request->getProperty("spell");
				}
				
				$strUrl = $this->request->url_for($arrUrl);	
				
				$objUrl = $objXml->createElement('url', Xerxes_Framework_Parser::escapeXML($strUrl) );
				$objOption->appendChild( $objUrl );
				$objXml->documentElement->appendChild( $objOption );
			}
			
			$this->request->addDocument( $objXml );
		}
	}
	
	protected function getHoldings($strSource, $arrIDs, $bolCache = false)
	{
		$objWorldcatConfig = $this->getConfig($strSource);
		$url = $objWorldcatConfig->lookup_address;
		
		$objXml = new DOMDocument();
		
		if ( $url != null )
		{
			$id = implode(",", $arrIDs);
			
			if ( $bolCache == true)
			{
				$url .= "?action=cached&id=$id";
				
				$xml = Xerxes_Framework_Parser::request($url);
				
				if ( $xml != "" )
				{
					$objXml->loadXML($xml);
				}
			}
			else
			{
				$objXml->loadXML("<cached />");
				
				$objObject = $objXml->createElement("object");
				$objObject->setAttribute("id", $id);
				$objXml->documentElement->appendChild($objObject);			
				
				$url .= "?action=records&id=$id&sameRecord=true";
				
				$xml = Xerxes_Framework_Parser::request($url);
				
				if ( $xml != "" )
				{
					$objRecord = new DOMDocument();
					$objRecord->loadXML($xml);
					$objImport = $objXml->importNode($objRecord->documentElement, true);		
					$objObject->appendChild($objImport);
				}
			}
			
			$objXml->documentElement->setAttribute("url", Xerxes_Framework_Parser::escapeXml($url));
		}
		
		return $objXml;
	}

	protected function getHoldingsInject()
	{
		$source = $this->request->getProperty("source");
		
		$isbns = $this->extractISBNs();
		$oclcs = $this->extractOCLCNumbers();
		
		$standard_numbers = array();
		
		foreach ( $isbns as $isbn )
		{
			array_push($standard_numbers, "ISBN:$isbn");
		}
			
		foreach ( $oclcs as $oclc )
		{
			array_push($standard_numbers, "OCLC:$oclc");
		}
		
		// get any data we found in the cache for these records
							
		$objXml = $this->getHoldings($source, $standard_numbers, true);
			
		$this->request->addDocument($objXml);
	}
			
	protected function pagerLinkParams()
	{
		$params = parent::pagerLinkParams();
		$params = $this->worldCatParams($params);
		return $params;
	}

	protected function sortLinkParams()
	{
		$params = parent::sortLinkParams();
		$params = $this->worldCatParams($params);
		return $params;
	}

	private function worldCatParams($params)
	{
		$params["source"] = $this->request->getProperty("source");
		
		foreach ( $this->getAllSearchParams() as $key => $value )
		{
			$params[$key] = $value;
		}

		return $params;
	}
	
	protected function sortOptions()
	{
		return array(
			$this->relevance_type => "relevance", 
			"Date" => "date", 
			"Title" => "title",  
			"Author" => "author"
		);
	}

	protected function linkFullRecord($result)
	{
		$arrParams = array(
			"base" => $this->request->getProperty("base"),
			"action" => "record",
			"id" => $result->getControlNumber(),
			"source" => $this->request->getProperty("source")
		);
		
		return $this->request->url_for($arrParams);
	}

	protected function linkOpenURL($result)
	{
		// take a locally defined ill option, otherwise link resolver 
			
		$configSFX = $this->registry->getConfig("LINK_RESOLVER_ADDRESS");
		$configILL = $this->worldcat_config->getConfig("INTERLIBRARY_LOAN", false, $configSFX);
					
		if ( $configILL == null )
		{
			throw new Exception("must have a config entry for LINK_RESOLVER_ADDRESS or INTERLIBRARY_LOAN");
		}

		$strILL = $result->getOpenURL($configILL, $this->sid);
		
		// @todo: figure out wtf this is
		
		$location = $this->request->getProperty("location");
		
		if ( $location != null )
		{
			$strILL = str_replace("{location}", $location, $strILL);
		}
		
		return $strILL;
	}

	protected function linkOther($result, $results_xml, $record_container)
	{
		// author links
			
		$arrAuthorsReverse = $result->getAuthors(false, true, true);
		$arrAuthorsForward = $result->getAuthors(false, true);

		for ( $a = 0; $a < count($arrAuthorsReverse); $a++ )
		{
			$strAuthorReverse = $arrAuthorsReverse[$a];
			$strAuthorForward = $arrAuthorsForward[$a];
				
			$arrLink = array(
				"base" => $this->request->getProperty("base"),
				"action" => "search",
				"query" => Xerxes_Framework_Parser::escapeXML($strAuthorReverse),
				"field" => "author",
				"exact" => "true",
				"spell" => "none"
			);
				
			$author_url = $this->request->url_for($arrLink);
			$objAuthorLink = $results_xml->createElement("author_link", Xerxes_Framework_Parser::escapeXML($strAuthorForward));
			$objAuthorLink->setAttribute("link", $author_url);
				
			$record_container->appendChild($objAuthorLink);
		}			
			
		// lateral subject links
			
		foreach ( $result->getSubjects() as $subject )
		{
			$arrLink = array(
				"base" => $this->request->getProperty("base"),
				"action" => "search",
				"query" => Xerxes_Framework_Parser::escapeXML($subject),
				"field" => "subject",
				"spell" => "none"
			);
			
			$subject_url = $this->request->url_for($arrLink);
			$objSubjectLink = $results_xml->createElement("subject_link", Xerxes_Framework_Parser::escapeXML($subject));
			$objSubjectLink->setAttribute("link", $subject_url);
			
			$record_container->appendChild($objSubjectLink);
		}			
	}
}

class Xerxes_WorldCatSearch_Query extends Xerxes_Framework_Search_Query 
{
	public function toQuery()
	{
		$query = "";
		
		foreach ( $this->getQueryTerms() as $term )
		{
			$query .= $this->keyValue($term->boolean, $term->field, $term->phrase );
		}
		
		return $query;
	}
	
	/**
	 * Create an SRU boolean/key/value expression in the query, such as: 
	 * AND srw.su="xslt"
	 *
	 * @param string $boolean		default boolean operator to use, can be blank
	 * @param string $field			worldcat index
	 * @param string $value			term(s)
	 * @param bool $neg				(optional) whether the presence of '-' in $value should indicate a negative expression
	 * 								in which case $boolean gets changed to 'NOT'
	 * @return string				the resulting SRU expresion
	 */
	
	private function keyValue($boolean, $field, $value, $neg = false)
	{
		if ($neg == true && strstr ( $value, "-" ))
		{
			$boolean = "NOT";
			$value = str_replace ( "-", "", $value );
		}
		
		$together = "";
		
		if ( strstr($field, "exact"))
		{
			$value = str_replace ( "\"", "", $value );
			$together = " srw.$field exact \" $value \"";
		} 
		else
		{
			$together = $this->normalizeQuery ( "srw.$field", $value );
		}
		
		return " $boolean ( $together ) ";
	}
	
	private function normalizeQuery($strSearchField, $strTerms)
	{
		$strSruQuery = "";
		
		$objQuery = new Xerxes_QueryParser();
		$arrQuery = $objQuery->normalizeArray( $strTerms );
		
		foreach ( $arrQuery as $strPiece )
		{
			$strPiece = trim ( $strPiece );
			
			if ($strPiece == "AND" || $strPiece == "OR" || $strPiece == "NOT")
			{
				$strSruQuery .= " " . $strPiece;
			} 
			else
			{
				$strPiece = str_replace ( "\"", "", $strPiece );
				$strSruQuery .= " $strSearchField = \"$strPiece\"";
			}
		}
		
		return $strSruQuery;
	}
	
	private function removeStopWords($strTerms)
	{
		$arrStopWords = array ("a", "an", "the" );
		
		/*
			"a","an","and","are","as","at","be","but","by","for","from", "had","have","he","her","his",
			"in","is","it","not","of","on","or","that","the","this","to","was","which","with","you"
		*/
		
		$strFinal = "";
		
		$arrTerms = explode ( " ", $strTerms );
		
		foreach ( $arrTerms as $strChunk )
		{
			if ($strChunk == "AND" || $strChunk == "OR" || $strChunk == "NOT")
			{
				$strFinal .= " " . $strChunk;
			} 
			else
			{
				$strNormal = strtolower ( $strChunk );
				
				if (! in_array ( $strNormal, $arrStopWords ))
				{
					$strFinal .= " " . $strChunk;
				}
			}
		}
		
		return trim ( $strFinal );
	}
}

?>