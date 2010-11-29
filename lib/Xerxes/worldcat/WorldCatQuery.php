<?php

/**
 * Worldcat Query
 * 
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_WorldCatQuery extends Xerxes_Framework_Search_Query 
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