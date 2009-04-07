<?php

	/**
	 * Parse queries for better searching via Metalib
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
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
		 * Converts the query to AND all terms, while preserving boolean operators
		 * and quoted phrases
		 *
		 * @param string $strField		field to search on
		 * @param string $strQuery		original query
		 * @return string				normalized query
		 */
		
		public function normalize ( $strField, $strQuery )
		{
			$strFinal = "";			// final string to return
			$arrWords = array();	// the query broken into a word array
			
			// strip out parens, since metalib can't handle them
			
			if ( strstr($strQuery, "(") || strstr($strQuery, ")") )
			{
				array_push($this->arrTips, 
					array(self::BAD_PARENS => "parentheses stripped from query"));
				
				$strQuery = str_replace("(", "", $strQuery);
				$strQuery = str_replace(")", "", $strQuery);
			}
			
			$arrWords = $this->normalizeArray($strQuery);
			
			$strFinal = implode(" ", $arrWords);
			
			// split the query into two seperate fielded searches, since this
			// seems to improve the chance of the search working correctly
	
			$strFinal = preg_replace("/(AND|OR|NOT)/", ") $1 $strField=(", $strFinal, 1);
			
			$strFinal = trim($strFinal);
			$strFinal = $strField . "=(" . $strFinal . ")";		
			
			$strFinal = str_replace("( ", "(", $strFinal);
			$strFinal = str_replace(" )", ")", $strFinal);
			
			return $strFinal;
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
							if ( strtolower($arrWords[$x + 1]) != "and" && 
							 	 strtolower($arrWords[$x + 1]) != "or" &&
							 	 strtolower($arrWords[$x + 1]) != "not")
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
				elseif ( strtolower($arrWords[$x]) == "and" || 
						 strtolower($arrWords[$x]) == "or" || 
						 strtolower($arrWords[$x]) == "not")
				{
					// the current word is a boolean operator
					array_push($arrFinal, strtoupper($arrWords[$x]) );
				}
				else
				{
					$arrSmallWords = array( 'of','a','the','and','an','or','nor','but','is','if','then','else',
						'when', 'at','from','by','on','off','for','in','out','over','to','into','with', 'as' );
						
					if ( in_array(strtolower($arrWords[$x]), $arrSmallWords) )
					{
						array_push($arrSmall, strtolower($arrWords[$x]));
					}
						
					if ( $x + 1 < count($arrWords) )
					{
						if ( strtolower($arrWords[$x + 1]) != "and" && 
						 	 strtolower($arrWords[$x + 1]) != "or" &&
						 	 strtolower($arrWords[$x + 1]) != "not")
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