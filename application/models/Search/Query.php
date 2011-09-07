<?php

/**
 * Search Query
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Search_Query
{
	public $terms = array(); // search terms
	public $limits = array(); // limits
	
	protected $stop_words = "";
	protected $search_fields_regex = '^query[0-9]{0,1}$|^field[0-9]{0,1}$|^boolean[0-9]{0,1}$';
	protected $limit_fields_regex = 'facet.*';	
	
	protected $request; // xerxes request object
	protected $config; // local config
	
	/**
	 * Constructor
	 * 
	 * @param Xerxes_Framework_Request $request
	 */
	
	public function __construct(Xerxes_Framework_Request $request = null, Xerxes_Model_Search_Config $config = null )
	{
		$this->config = $config;
		
		if ( $request != null )
		{
			// make these available
			
			$this->request = $request;
	
			// populate it with the 'search' related params out of the url
			
			foreach ( $this->extractSearchGroupings() as $term )
			{
				$this->addTerm(
					$term["id"], 
					$term["boolean"], 
					$term["field"], 
					$term["relation"], 
					$term["query"]);
			}
	
			// also limits
			
			foreach ( $this->extractLimitGroupings() as $limit )
			{
				$this->addLimit($limit["field"], $limit["relation"], $limit["value"]);
			}
		}
	}
	
	/**
	 * Get a specific qury term
	 *
	 * @param int $id		the position of the term in the list of terms
	 * @return Xerxes_Model_Search_QueryTerm
	 */
	
	public function getQueryTerm($id)
	{
		if ( ! array_key_exists($id, $this->terms) )
		{
			throw new Exception("No query term with id '$id'");
		}
		
		return $this->terms[$id];
	}
	
	/**
	 * Return the query terms
	 * 
	 * @return array
	 */	
	
	public function getQueryTerms()
	{
		return $this->terms;
	}
	
	/**
	 * Get a specific limit
	 *
	 * @param string $id		the limit name
	 * @return Xerxes_Model_Search_LimitTerm
	 */
	
	public function getLimit($id)
	{
		foreach ( $this->limits as $limit )
		{
			if ( $limit->field == $id )
			{
				return $limit;
			}
		}
		
		// we didn't find it?
		
		return null;
	}	
	
	/**
	 * Return all limits
	 *
	 * @param bool $facets_to_field		whether to return facets with key convention as limits
	 * @return array
	 */
	
	public function getLimits($facets_to_field = false)
	{
		if ( $facets_to_field == false )
		{
			return $this->limits;
		}
		else
		{
			$final = array();
			
			foreach ( $this->limits as $limit )
			{
				$new_limit = clone $limit; // make a copy
				
				// take the field name out of our facet.* param
				
				if ( strstr($new_limit->field,"facet.") )
				{
					$parts = explode('.', $new_limit->field);
					
					// if it has 3 parts, it's our special 'key' convention, where
					// the third part is the value, so swap it in for the value
					
					if ( count($parts) == 3 )
					{
						$new_limit->value = array_pop($parts);
						$new_limit->field = array_pop($parts);
						$new_limit->key = true;
					}
					else
					{
						// the field name is the only thing there, value is already
						// populated in the value of the limit object
						
						$new_limit->field = array_pop($parts);
					}
				}
				
				// put it into the return array
				
				array_push($final, $new_limit);
			}

			return $final;
		}
	}
	
	/**
	 * Add a query term
	 * 
	 * @param string $id		identifier for this query term
	 * @param string $boolean	boolean operator combining this phrase to the total query
	 * @param string $field		field to search on
	 * @param string $relation	operator
	 * @param string $phrase	search term value
	 */
	
	public function addTerm($id, $boolean, $field, $relation, $phrase)
	{
		if ( $field == "" )
		{
			$field = "keyword";
		}
		
		// alter query based on config
		
		$field_internal = "";
				
		if ( $this->config != null )
		{
			$field_internal = $this->config->swapForInternalField($field);
			$phrase = $this->alterQuery( $phrase, $field );
		}		
		
		$term = new Xerxes_Model_Search_QueryTerm($id, $boolean, $field, $field_internal, $relation, $phrase);
		array_push($this->terms , $term);
	}
	
	/**
	 * Add a limit
	 * 
	 * @param string $field		field name
	 * @param string $relation	operator
	 * @param string $phrase	the value of the limit
	 */
	
	public function addLimit($field, $relation, $phrase)
	{
		if ( ! is_array($phrase) )
		{
			$phrase = array($phrase);
		}
		
		foreach ( $phrase as $value )
		{
			$term = new Xerxes_Model_Search_LimitTerm($field, $relation, $value);
			array_push($this->limits , $term);
		}
	}
	
	/**
	 * Check the spelling of the search terms
	 */
	
	public function checkSpelling()
	{
		$registry = Xerxes_Framework_Registry::getInstance();
		
		$configYahooID = $registry->getConfig( "YAHOO_ID", false );
		
		$spell_return = array(); // we'll return this one
		
		if ( $configYahooID != null )
		{
			for ( $x = 0; $x < count($this->terms); $x++ )
			{
				$term = $this->terms[$x];
				
				$query = "select * from search.spelling where query=\"" . $term->phrase . "\" and appid=\"$configYahooID\"";
				$url = "http://query.yahooapis.com/v1/public/yql?q=" . urlencode($query);
				$response = Xerxes_Framework_Parser::request($url);
				
				if ( $response != "" )
				{
					$objSpelling = new DOMDocument();
					$objSpelling->loadXML($response);
					
					if ( $objSpelling->getElementsByTagName("suggestion")->item(0) != null )
					{
						$suggestion = $objSpelling->getElementsByTagName("suggestion")->item(0)->nodeValue;
		
						$term->spell_correct = $suggestion;
						$spell_return[$term->id] = $term->spell_correct;
					}				
					
					// also put it here so we can return it
					
					$this->terms[$x] = $term;
				}
			}
		}
		
		return $spell_return;
	}
	
	/**
	 * Return an md5 hash of the main search parameters, bascially to identify the search
	 */
	
	public function getHash()
	{
		// get the search params
		
		$params = $this->extractSearchParams();
		
		// and sort them alphabetically
		
		ksort($params);
		
		$query_normalized = "";
		
		// now put them back together in a normalized form
		
		foreach ( $params as $key => $value )
		{
			if ( is_array($value) )
			{
				foreach ($value as $part)
				{
					$query_normalized .= "&amp;$key=" . urlencode($part);
				}
			}
			else
			{
				$query_normalized .= "&amp;$key=" . urlencode($value);
			}
		}
		
		// give me the hash!
		
		return md5($query_normalized);
	}
	
	/**
	 * Get 'limit' params out of the URL, sub-class defines this
	 * 
	 * @return array
	 */	
	
	protected function extractLimitParams()
	{
		if ( $this->limit_fields_regex != "" )
		{
			return $this->request->getParams($this->limit_fields_regex);
		}
		else
		{
			return array();
		}
	}

	/**
	 * Get 'search' params out of the URL
	 * 
	 * @return array
	 */		
	
	public function extractSearchParams()
	{
		if ( $this->search_fields_regex != "" )
		{
			return $this->request->getParams($this->search_fields_regex);
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Get 'limit' params out of the URL, organized into groupings for the 
	 * query object to parse
	 * 
	 * @return array
	 */	
	
	protected function extractLimitGroupings()
	{
		$arrFinal = array();
		
		if ( $this->limit_fields_regex != "" )
		{
			foreach ( $this->extractLimitParams() as $key => $value )
			{
				if ( $value == "" )
				{
					continue;
				}
				
				$key = urldecode($key);
				
				if ( strstr($key, "_relation") )
				{
					continue;
				}
				
				$arrTerm = array();
				
				$arrTerm["field"] = $key;
				$arrTerm["relation"] = "=";
				$arrTerm["value"] = $value;
				
				$relation = $this->request->getProperty($key . "_relation");
				
				if ( $relation != null )
				{
					$arrTerm["relation"] = $relation;
				}
				
				array_push($arrFinal, $arrTerm);
			}
		}
		
		return $arrFinal;
	}	
	
	/**
	 * Get 'search' params out of the URL, organized into groupings for the 
	 * query object to parse
	 * 
	 * @return array
	 */		
	
	protected function extractSearchGroupings()
	{
		$arrFinal = array();
		
		foreach ( $this->request->getAllProperties() as $key => $value )
		{
			$key = urldecode($key);
			
			// if we see 'query' as the start of a param, check if there are corresponding
			// entries for field and boolean; these will have a number after them
			// if coming from an advanced search form
				
			if ( preg_match("/^query/", $key) )
			{
				if ( $value == "" )
				{
					continue;
				}			
				
				$arrTerm = array();
				$arrTerm["id"] = $key;
				$arrTerm["relation"] = "=";
				
				$id = str_replace("query", "", $key);
				
				$boolean_id = "";
			
				if ( is_numeric($id) )
				{
					$boolean_id = $id - 1;
				}
				
				$arrTerm["query"] = $value;
				$arrTerm["field"] = $this->request->getProperty("field$id");
				
				// boolean only counts if this is not the first query term
				
				if ( count($arrFinal) > 0 )
				{
					$arrTerm["boolean"] = $this->request->getProperty("boolean" . ( $boolean_id ) );
				}
				else
				{
					$arrTerm["boolean"] = "";
				}
				
				array_push($arrFinal, $arrTerm);
			}
		}
		
		return $arrFinal;
	}

	/**
	 * Extract both query and limit params from the URL
	 * @return array
	 */
	
	public function getAllSearchParams()
	{
		$limits = $this->extractLimitParams();
		$search = $this->extractSearchParams();
		
		return array_merge($search, $limits);
	}
	
	/**
	 * Change the case or add truncation to a search based on config
	 * 
	 * @param string $phrase		the search phrase
	 * @param string $field			field to search on
	 * 
	 * @return string 				altereted phrase, or original as supplied if field has no definitions
	 */

	public function alterQuery($phrase, $field)
	{
		$phrase = trim($phrase);
		
		$case = $this->config->getFieldAttribute($field, "case");
		$trunc = $this->config->getFieldAttribute($field, "truncate");

		switch($case)
		{
			case "upper":
				$phrase = strtoupper($phrase);
				break;
			case "lower":
				$phrase = strtolower($phrase);
				break;			
		}

		switch($trunc)
		{
			case "left":
				$phrase = "*" . $phrase;
				break;
			case "right":
				$phrase = $phrase . "*";
				break;
			case "both":
				$phrase = "*" . $phrase . "*";
				break;	
		}
		
		return $phrase;
	}
}
