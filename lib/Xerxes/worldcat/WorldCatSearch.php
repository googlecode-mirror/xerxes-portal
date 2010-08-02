<?php

/**
 * Worldcat Search
 * 
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: WorldCatSearch.php 1263 2010-07-08 14:04:50Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_WorldCatSearch extends Xerxes_Framework_Search 
{
	// basic

	public $id = "worldcat";
	protected $query_object_type = "Xerxes_WorldCatSearch_Query";
	protected $record_object_type = "Xerxes_WorldCatRecord";
	protected $limit_fields_regex = '^year$|^year_relation|^[a-z]{2}$|^[a-z]{2}_exact|advanced';

	protected $should_get_holdings = true;	
	
	// worldcat specific

	protected $groups_config = array();
	
	public function __construct($objRequest, $objRegistry)
	{
		parent::__construct($objRequest, $objRegistry);
		
		// max records
		
		$this->max = $this->config->getConfig("WORLDCAT_MAX_RECORDS", false, $this->max);			
		
		// basic
		
		$source = $this->request->getProperty("source");
		$this->search_object = $this->getWorldCatObject($source);
		
		// extra config stuff
		
		$this->addConfigToResponse();
	}
	
	/*
	public function __destruct()
	{
		print_r($this->search_object);
	}
	*/
	
	protected function getConfig()
	{
		$config = Xerxes_WorldCatConfig::getInstance();
		$config->init();

		return $config;
	}
	
	public function getHashID()
	{
		return $this->id . "-" . $this->request->getProperty("source");
	}	
	
	public function results()
	{
		// need to authenticate for non-local library
		
		$source = $this->request->getProperty("source");
		
		if ( $source != "local" )
		{
			$objRestrict = new Xerxes_Framework_Restrict($this->request);
			$objRestrict->checkIP();
		}
		
		// @todo factor this out to the framework
		
		$this->addAdvancedSearchLink();

		parent::results();
	}
	
	public function record()
	{
		// get the record
		
		parent::record();
		
		// show worldcat holdings ?
		
		$source  = $this->request->getProperty("source");
		$id  = $this->request->getProperty("id");
		
		$config = $this->getWorldcatGroup($source);
		
		if ( $config->show_holdings == true )
		{
			$worldcat_holdings = $this->search_object->holdings($id, $config->libraries_include, 1, 1000 );
			
			$xml = new DOMDocument();
			$xml->loadXML("<worldcat_holdings />");
			
			$import = $xml->importNode($worldcat_holdings->documentElement, true);
			$xml->documentElement->appendChild($import);
			
			$this->request->addDocument($xml);
		}		
	}
	
	public function bounce()
	{
		$url = $this->request->getProperty("url");
		$this->request->setRedirect($url);
		
		/*
		$html = Xerxes_Framework_Parser::request($url);
			
		$final = "";
		$arrMatch = array();
		$regex = "/<frame src=\"([^\"]*)\" name=\"mainFrame/";
			
		if ( preg_match($regex, $html, $arrMatch) )
		{
			$final = $arrMatch[1];
		}

		if ( $final == "" )
		{
			$this->request->setRedirect($url);
		}
		else
		{
			$this->request->setRedirect($final);
		}		
		
		$this->request->setRedirect($final);
		*/
	}
	
	/**
	 * Get the Worldcat search object, with some limits and things set by virtue of 
	 * config values
	 * 
	 * @param string $strSource		the id of the 'group' that sets the limits
	 */
	
	protected function getWorldCatObject($strSource = "")
	{
		$configKey = $this->config->getConfig("WORLDCAT_API_KEY", true);
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
				
			$objWorldcatConfig = $this->getWorldcatGroup($strSource);
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
	 * Special handling here for some worldcat-specific stuff
	 */

	protected function extractSearchGroupings()
	{
		$arrFinal = parent::extractSearchGroupings();
		
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

	protected function getHoldingsURL($strSource)
	{
		$objWorldcatConfig = $this->getWorldcatGroup($strSource);
		return $objWorldcatConfig->lookup_address;
	}	

	public function getWorldcatGroup($strSource)
	{
		if ( $strSource == "" )
		{
			$strSource = "local";
		}
		
		if ( ! array_key_exists($strSource, $this->groups_config) )
		{
			$this->groups_config[$strSource] = new Xerxes_WorldCatGroup($strSource, $this->config->getXML());
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
				
			$xml = $this->config->getXML()->configuration->worldcat_groups;
			
			if ( $xml != false )
			{
				$objGroupXml = new DOMDocument();
				$objGroupXml->loadXML($xml->asXML());
				$this->request->addDocument($objGroupXml);
			}
		}
		
		if ( $this->config->getXML()->configuration->worldcat_groups->group != false )
		{
			$arrSources = array();
			
			$objXml = new DOMDocument();
			$objXml->loadXML("<source_functions />");
			
			foreach ( $this->config->getXML()->configuration->worldcat_groups->group as $group )
			{
				array_push($arrSources, (string) $group["id"]);
			}
		
			foreach ( $arrSources as $strSource )
			{
				$objOption = $objXml->createElement("source_option");
				$objOption->setAttribute("source", $strSource );
				
				$arrUrl = array(
					"base" => $this->request->getProperty("base"),
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
	
	protected function currentParams()
	{
		$params = parent::currentParams();
		$params["source"] = $this->request->getProperty("source");
		
		return $params;
	}

	private function addAdvancedSearchLink()
	{
		$params = parent::getAllSearchParams();
		$params["base"] = $this->request->getProperty("base");
		$params["action"] = $this->request->getProperty("home");
		$params["advancedfull"] = "true";
		
		$url = $this->request->url_for($params);
		
		$advanced_xml = new DOMDocument();
		$advanced_xml->loadXML("<advanced_search />");
		$advanced_xml->documentElement->setAttribute("link", $url);
		
		$this->request->addDocument($advanced_xml);
	}
	
	protected function linkFullRecord($result)
	{
		$arrParams = array (
			"base" => $this->request->getProperty("base"), 
			"action" => "record", 
			"id" => $result->getControlNumber(), 
			"source" => $this->request->getProperty("source") 
		);
		
		return $this->request->url_for ( $arrParams );
	}
	
	protected function linkOpenURL($result)
	{
		// take a locally defined ill option, otherwise link resolver 
			
		$configSFX = $this->registry->getConfig("LINK_RESOLVER_ADDRESS");
		$configILL = $this->config->getConfig("INTERLIBRARY_LOAN", false, $configSFX);
					
		if ( $configILL == null )
		{
			throw new Exception("must have a config entry for LINK_RESOLVER_ADDRESS or INTERLIBRARY_LOAN");
		}

		return $result->getOpenURL($configILL, $this->sid);
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
				"source" => $this->request->getProperty("source"),
				"query" => Xerxes_Framework_Parser::escapeXML($strAuthorReverse),
				"field" => "au",
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
				"source" => $this->request->getProperty("source"),
				"query" => Xerxes_Framework_Parser::escapeXML($subject),
				"field" => "su",
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
	public function __construct()
	{
		$this->stop_words = array ("a", "an", "the" );
	}
	
	public function toQuery()
	{
		$query = "";
		
		foreach ( $this->getQueryTerms() as $term )
		{
			$query .= $this->keyValue($term->boolean, $term->field, $term->relation, $term->phrase );
		}
		
		$arrLimits = array();
		
		foreach ( $this->getLimits() as $limit )
		{
			if ( $limit->value == "" )
			{
				continue;
			}
			
			// publication year
			
			if ( $limit->field == "year" )
			{
				$year = $limit->value;
				$year_relation = $limit->relation;

				$arrYears = explode("-", $year);
				
				// there is a range
				
				if ( count($arrYears) > 1 )
				{
					if ( $year_relation == "=" )
					{
						$query .= " and srw.yr >= " . trim($arrYears[0]) . 
							" and srw.yr <= " . trim($arrYears[1]);
					}
					
					// this is probably erroneous, specifying 'before' or 'after' a range;
					// did user really mean this? we'll catch it here just in case
					
					elseif ( $year_relation == ">" )
					{
						array_push($arrLimits, " AND srw.yr > " .trim($arrYears[1] . " "));
					}
					elseif ( $year_relation == "<" )
					{
						array_push($arrLimits, " AND srw.yr < " .trim($arrYears[0] . " "));
					}					
				}
				else
				{
					// a single year
					
					array_push($arrLimits, " AND srw.yr $year_relation $year ");
				}
			}

			// language
					
			elseif ( $limit->field == "la")
			{
				array_push($arrLimits, " AND srw.la=\"" . $limit->value . "\"");
			}
					
			// material type
					
			elseif ( $limit->field == "mt")
			{
				array_push($arrLimits, " AND srw.mt=\"" . $limit->value . "\"");
			}
		}

		$limits = implode(" ", $arrLimits);
				
		if ( $limits != "" )
		{
			$query = "($query) $limits";
		}

		return $query;
	}
	
	/**
	 * Create an SRU boolean/key/value expression in the query, such as: 
	 * AND srw.su="xslt"
	 *
	 * @param string $boolean		default boolean operator to use, can be blank
	 * @param string $field			worldcat index
	 * @param string $relation		relation
	 * @param string $value			term(s)
	 * @param bool $neg				(optional) whether the presence of '-' in $value should indicate a negative expression
	 * 								in which case $boolean gets changed to 'NOT'
	 * @return string				the resulting SRU expresion
	 */
	
	private function keyValue($boolean, $field, $relation, $value, $neg = false)
	{
		$value = $this->removeStopWords($value);
		
		if ( $value == "" )
		{
			return "";
		}
		
		if ($neg == true && strstr ( $value, "-" ))
		{
			$boolean = "NOT";
			$value = str_replace ( "-", "", $value );
		}
		
		$together = "";
		
		if ( $relation == "exact")
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
}

?>