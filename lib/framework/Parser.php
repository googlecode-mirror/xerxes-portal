<?php

	/**
	 * Utility class for basic parsing functions
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package  Xerxes_Framework
	 */ 

	class Xerxes_Parser
	{
		/**
		 * Simple XSLT transformation function
		 * 
		 * @param mixed $xml			DOMDocument or string containing xml
		 * @param string $strXslt		physical path to xslt document 
		 * @param array $arrParams		[optional] array of parameters to pass to stylesheet
		 * @return string				newly formatted document
		 * @static 
		 */ 
					
		public static function transform ( $xml, $strXslt, $arrParams = null )
		{
			if ( is_string($xml) )
			{
				// load xml document from string	
				$objXml = new DOMDocument();
				$objXml->loadXML($xml);
				$xml = $objXml;
			}
	
			// load xslt document from location
			$objXsl = new DOMDocument();
			$objXsl->load($strXslt);
				
			// create XSLT Processor
			$objProcessor = new XsltProcessor();
			$objProcessor->registerPhpFunctions();
			
			if ($arrParams != null)
			{
				// add in parameters
				foreach ($arrParams as $key => $value)
				{
					$objProcessor->setParameter(null, $key, $value);
				}
			}
				
			// transform
			$objXsl = $objProcessor->importStylesheet($objXsl);
				
			return $objProcessor->transformToXml($xml);
		}

		/**
		 * Simple function to convert text to title case
		 * Drops titles in ALL CAPS to lowercase first; honors initial capitalized 
		 * letter and words that should not be capitalized in a title
		 * 
		 * @param string $strInput	title to be converted
		 * @return string			converted title
		 * @static 
		 */ 

		private function toTitleCase( $strInput )
		{
			// NOTE: if you make a change to this function, make a corresponding change 
			// in the Xerxes_Parser class, since this one here is a duplicate function 
			// allowing Xerxes_Record to be a stand-alone class
			
			
			
			
			$arrMatches = "";			// matches from regular expression
			$arrSmallWords = "";		// words that shouldn't be capitalized if they aren't the first word.
			$arrWords = "";				// individual words in input
			$strFinal = "";				// final string to return
			$strLetter = "";			// first letter of subtitle, if any
						
			// if there are no lowercase letters (and its sufficiently long a title to 
			// not just be an aconym or something) then this is likely a title stupdily
			// entered into a database in ALL CAPS, so drop it entirely to 
			// lower-case first

			$iMatch = preg_match("/[a-z]/", $strInput);

			if ($iMatch == 0 && strlen($strInput) > 10)
			{
				$strInput = strtolower($strInput);
			}
			
			// array of small words
			
			$arrSmallWords = array( 'of','a','the','and','an','or','nor','but','is','if','then','else',
				'when', 'at','from','by','on','off','for','in','out','over','to','into','with', 'as' );
				
			// split the string into separate words
			
			$arrWords = explode(' ', $strInput);
			
			foreach ($arrWords as $key => $word)
			{ 
					// if this word is the first, or it's not one of our small words, capitalise it 
					
					if ( $key == 0 || !in_array( strtolower($word), $arrSmallWords) )
					{
						$arrWords[$key] = ucwords($word);
					}
					elseif ( in_array( strtolower($word), $arrSmallWords) )
					{
						$arrWords[$key] = strtolower($word);
					}
			} 
			
			// join the words back into a string
			
			$strFinal = implode(' ', $arrWords);
			
			// catch subtitles
			
			if ( preg_match("/: ([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/: ([a-z])/", ": " . $strLetter, $strFinal );
			}

			// catch words that start with double quotes
			
			if ( preg_match("/\"([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/\"[a-z]/", "\"" . $strLetter, $strFinal );
			}
			
			// catch words that start with a single quote
			// need to be a little more cautious here and make sure there is a space before the quote when
			// inside the title to ensure this isn't a quote for a contraction or for possisive; seperate
			// case to handle when the quote is the first word
			
			if ( preg_match("/ '([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/ '[a-z]/", " '" . $strLetter, $strFinal );
			}
			
			if ( preg_match("/^'([a-z])/", $strFinal, $arrMatches) )
			{
				$strLetter = ucwords($arrMatches[1]);
				$strFinal = preg_replace("/^'[a-z]/", "'" . $strLetter, $strFinal );
			}
			
			return $strFinal;
		}

		/**
		 * Simple function to strip off the previous part of a string
		 * from the start of the term to the beginning, including the term itself
		 * 
		 * @param string $strExpression		whole string to search 
		 * @param string $strRemove			term to match and remove left of from 
		 * @return string 					chopped string
		 * @static
		 */

		public static function removeLeft ( $strExpression, $strRemove ) 
		{		
			$iStartPos = 0;		// start position of removing term
			$iStopPos = 0;		// end position of removing term
			$strRight = "";		// right remainder of the srtring to return
			
			// if it really is there
			if ( strpos($strExpression, $strRemove) !== false )
			{
				// find the starting position of string to remove
				$iStartPos = strpos($strExpression, $strRemove);
				
				// find the end position of string to remove
				$iStopPos = $iStartPos + strlen($strRemove);
				
				// return everything after that
				$strRight = substr($strExpression, $iStopPos, strlen($strExpression) - $iStopPos);
				
				return $strRight;
			} 
			else 
			{
				return $strExpression;
			}
		}

		/**
		 * Simple function to strip off the remainder of a string
		 * from the start of the term to the end of the string, including the term itself
		 * 
		 * @param string $strExpression		whole string to search 
		 * @param string $strRemove			term to match and remove right of from 
		 * @return string chopped string
		 * @static 
		 */ 

		public static function removeRight ( $strExpression, $strRemove ) 
		{		
			$iStartPos = 0;		// start position of removing term
			$strLeft = "";		// left portion of to return

			// if it really is there
			if ( strpos( $strExpression, $strRemove) !== false ) 
			{

				// find the starting position of to remove
				$iStartPos = strpos( $strExpression, $strRemove);
				
				// get everything before that
				$strLeft = substr( $strExpression, 0, $iStartPos);
								
				return $strLeft;
			} 
			else 
			{
				return $strExpression;
			}
		}
		
		/**
		 * Clean data for inclusion in an XML document, escaping illegal
		 * characters
		 *
		 * @param string $string data to be cleaned
		 * @return string cleaned data
		 * @static 
		 */
		
		public static function escapeXml( $string )
		{
			// NOTE: if you make a change to this function, make a corresponding change 
			// in the Xerxes_Record class, since it has a duplicate function 
			// to allow it be as a stand-alone class 
			
			$string = str_replace('&', '&amp;', $string);
	        $string = str_replace('<', '&lt;', $string);
	        $string = str_replace('>', '&gt;', $string);
	        $string = str_replace('\'', '&#39;', $string);
	        $string = str_replace('"', '&quot;', $string);
	        
	        $string = str_replace("&amp;#", "&#", $string);
			$string = str_replace("&amp;amp;", "&amp;", $string);

	        
	        return $string;
		}
		
	}
	
?>
