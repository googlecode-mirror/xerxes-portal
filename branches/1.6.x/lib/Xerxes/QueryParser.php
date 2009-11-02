<?php

	/**
	 * Parse queries for better searching via Metalib
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @uses Xerxes_Parser
	 */

	class Xerxes_QueryParser
	{
		private $url;					// url request to server
		private $xml;					// xml as string
		private $arrTips = array();		// search tips
		
		const BAD_PARENS = 1;
		const SMALL_WORDS = 2;
		
		
		/**
		 * Checks the spelling of the supplied query and offers spelling suggestion
		 *
		 * @param string $strQuery		supplied query
		 * @param string $strYahooId	yahoo access id
		 * @param string $strAltLocation an alternate location for the Yahoo spell check, in case you need to balanace
		 * @return string				spelling suggesting
		 */
		
		public function checkSpelling( $strQuery, $strYahooId, $strAltLocation = "" )
		{
			if ( $strAltLocation != "" )
			{
				$this->url = $strAltLocation . "?appid=" . $strYahooId . "&query=" . urlencode($strQuery);
			}
			else
			{
				$this->url = "http://api.search.yahoo.com/WebSearchService/V1/spellingSuggestion?appid=" . 
				$strYahooId . "&query=" . urlencode($strQuery);
			}
			
			$strResponse = Xerxes_Parser::request($this->url);
				
			$objSpelling = new DOMDocument();
			$objSpelling->loadXML($strResponse);
				
			if ( $objSpelling->getElementsByTagName("Result")->item(0) != null )
			{
				return $objSpelling->getElementsByTagName("Result")->item(0)->nodeValue;
			}
			else
			{
				return "";
			}
		}
		
		/**
		 * Strips out ANDs and parentheses, while supporting a single OR or NOT entered 
		 * into the first search box
		 *
		 * @param string $strField		first field to search on
		 * @param string $strTerm		first set of search terms
		 * @param string $strBoolean	[optional] boolean operator
		 * @param string $strField2		[optional] second field to search on
		 * @param string $strTerm2		[optional] second set of search terms
		 * @param boolean $bolFix		[optional] whether to split query on first OR or NOT, default is false
		 * @return string				normalized query
		 */
		
		public function normalizeMetalibQuery ( $strField, $strTerm, $strBoolean = "", $strField2 = "", $strTerm2 = "", $bolFix = false )
		{
			$strQuery = "";		// final normalized query
			
			// this strips parens and bare ANDs in the query
			
			$strTerm = $this->fixTerms($strTerm);
			
			if ( $strTerm2 != "" )
			{
				$strTerm2 = $this->fixTerms($strTerm2);
				$strQuery = "$strField=($strTerm) $strBoolean $strField2=($strTerm2)";
			}
			else
			{
				// if there was only one search field/term and there was an OR or NOT
				// in the search phrase itself, split the query into two seperate 
				// fielded searches, since this will improve the chance of the search 
				// working correctly
				
				// since the fixTerms -> normalizeArray function drops the query to 
				// lowercase and then uppercases the bare boolean operators this is
				// only catching an actual OR or NOT not in quotes
				
				if ( $bolFix == true )
				{
					$strQuery = preg_replace("/(OR|NOT)/", ") $1 $strField=(", $strTerm, 1);
					$strQuery = trim($strQuery);
					$strQuery = $strField . "=(" . $strQuery . ")";	
				}
				else
				{
					$strQuery = "$strField=($strTerm)";	
				}
			}
			
			// spacing clean-up, seems to make a difference
			
			$strQuery = str_replace("( ", "(", $strQuery);
			$strQuery = str_replace(" )", ")", $strQuery);
			
			return $strQuery;
		}
		
		private function fixTerms($value)
		{
			// strip out parens, since metalib can't handle them
			
			if ( strstr($value, "(") || strstr($value, ")") )
			{
				array_push($this->arrTips, array(self::BAD_PARENS => "parentheses stripped from query"));
							
				$value = str_replace("(", "", $value);
				$value = str_replace(")", "", $value);
			}
			
			// split the query into parts
			
			$arrWords = $this->normalizeArray($value);
			
			// remove non-quoted ANDs since Metalib will handle automatic AND-ing;
			// but only if there is no quotes in the query, since quoted phrases
			// and-ed together need the boolean operator in there, or so it seems
			
			if ( ! strstr($value, "\"") )
			{
				for ( $x = 0; $x < count($arrWords); $x++ )
				{
					if ( $arrWords[$x] == "AND")
					{
						 $arrWords[$x] = null;
					}
				}
			}

			return implode(" ", $arrWords);
		}
		
		/**
		 * Converts the query to AND all terms, while preserving boolean operators
		 * and quoted phrases; return as array
		 *
		 * @param string $strQuery		original query
		 * @return array				query normalized
		 */
		
		public function normalizeArray ( $strQuery )
		{
			$bolQuote = false;			// flags the start and end of a quoted phrase
			$arrWords = array();		// the query broken into a word array
			$arrFinal = array();		// final array of words
			$strQuote = "";				// quoted phrase
			$arrSmall = array();
						
			// split words into an array
			
			$strQuery = strtolower($strQuery);
			
			$arrWords = explode(" ", $strQuery);
			
			// cycle thru each word in the query
			
			for ( $x = 0; $x < count($arrWords); $x++ )
			{
				if ( $bolQuote == true )
				{
					// we are inside of a quoted phrase
					
					$strQuote .= " " . $arrWords[$x];
					
					if ( strpos($arrWords[$x], "\"") !== false )
					{
						// the end of a quoted phrase
						
						$bolQuote = false;
						
						if ( $x + 1 < count($arrWords) )
						{
							if ( $arrWords[$x + 1] != "and" && 
							 	 $arrWords[$x + 1] != "or" &&
							 	 $arrWords[$x + 1] != "not")
							{
								// the next word is not a boolean operator,
								// so AND the current one
								
								array_push($arrFinal, $strQuote);
								array_push($arrFinal, "AND");
							}
							else
							{
								array_push($arrFinal, $strQuote);
							}
						}
						else
						{
							array_push($arrFinal, $strQuote);
						}
						
						$strQuote = "";
					}
				}
				elseif ( $bolQuote == false && strpos($arrWords[$x], "\"") !== false )
				{
					// this is the start of a quoted phrase
					
					$strQuote .= " " . $arrWords[$x];
					$bolQuote = true;
				}				
				elseif ( $arrWords[$x] == "and" || 
						 $arrWords[$x] == "or" || 
						 $arrWords[$x] == "not")
				{
					// the current word is a boolean operator
					array_push($arrFinal, strtoupper($arrWords[$x]) );
				}
				else
				{
					$arrSmallWords = array( 'of','a','the','and','an','or','nor','but','is','if','then','else',
						'when', 'at','from','by','on','off','for','in','out','over','to','into','with', 'as' );
						
					if ( in_array($arrWords[$x], $arrSmallWords) )
					{
						array_push($arrSmall, $arrWords[$x]);
					}
						
					if ( $x + 1 < count($arrWords) )
					{
						if ( $arrWords[$x + 1] != "and" && 
						 	 $arrWords[$x + 1] != "or" &&
						 	 $arrWords[$x + 1] != "not")
						{
							// the next word is not a boolean operator,
							// so AND the current one
							array_push($arrFinal, $arrWords[$x]);
							array_push($arrFinal, "AND");
						}
						else
						{
							array_push($arrFinal, $arrWords[$x]);
						}
					}
					else
					{
						array_push($arrFinal, $arrWords[$x]);
					}
				}
			}		
			
			if ( count($arrSmall) > 0 )
			{
				array_push($this->arrTips, array(self::SMALL_WORDS => "'" . implode("', '", $arrSmall) . "'"));
			}
			
			return $arrFinal;
		}
		
		/**
		 * Search tips based on an analysis of the query
		 * 
		 * @return array
		 */
		
		public function getTips()
		{
			return $this->arrTips;
		}
		
	}


?>