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
		 * @param string $strXsltPath	Relative file path to xslt document. Will look in both library location and 
		 * 								local app location for documents, and combine them so local over-rides library 
		 * 								templates, if neccesary. 
		 * @param array $arrParams		[optional] array of parameters to pass to stylesheet
		 * @param bool $bolDoc			[optional] return result as DOMDocument (default false)
		 * @param array $arrInclude		[optional] additional stylesheets that should be included in the transform
		 * @return mixed				newly formatted document as string or DOMDocument
		 * @static 
		 */ 
					
		public static function transform ( $xml, $strXsltPath, $arrParams = null, $bolDoc = false, $arrInclude = "" )
		{
			if ( $strXsltPath == "") throw new Exception("no stylesheet supplied");
			
			if ( is_string($xml) )
			{
				// load xml document from string	
				$objXml = new DOMDocument();
				$objXml->loadXML($xml);
				$xml = $objXml;
			}
			
			$objXsl = self::generateBaseXsl($strXsltPath, $arrInclude);
			
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
				
			if ($bolDoc == true)
			{
				return $objProcessor->transformToDoc($xml);
			}
			else 
			{
				return $objProcessor->transformToXml($xml);
			}
		}

		/**
		 * Dynamically create our 'base' stylesheet, combining distro and local
		 * stylesheets, as available, using includes and imports into our
		 * 'base'.  Base uses the distro file xsl/dynamic_skeleton.xsl to
		 * begin with. 
		 * 
		 * @param string $strXsltRelPath 	Relative path to a stylesheet, generally beginning with "xsl/". 
		 * 									Stylesheet may exist in library location, app location, or both. 
		 * @param array $arrInclude			[optional] additional stylesheets that should be included in the request
		 * @return DomDocument 		A DomDocument holding the generated XSLT stylesheet.
		 * @static
		*/
		
		public static function generateBaseXsl($strXsltRelPath, $arrInclude = "")
		{
			$objRegistry = Xerxes_Framework_Registry::getInstance(); 
		
			// the relative path passed in should, starting from our working
			// dir, give us the localized xsl if it exists.
			
			$local_xsl_dir = $objRegistry->getConfig("APP_DIRECTORY", true);
			$local_path =  $local_xsl_dir . "/" . $strXsltRelPath;
			
			// applying that relative url with a base of xerxes library install
			// gives us our distro file, if it exists. 
		
			$distro_xsl_dir = $objRegistry->getConfig("PATH_PARENT_DIRECTORY", true) . '/lib/';
			$distro_path = $distro_xsl_dir . $strXsltRelPath;
			
			$generated_xsl = new DOMDocument();
			$generated_xsl->load( $distro_xsl_dir . "xsl/dynamic_skeleton.xsl");
			
			// pre-pend imports to this, to put them at the top of the file. 
		
			$importInsertionPoint = $generated_xsl->documentElement->firstChild;
	
			// add actual stylesheets to 'base' stylesheet. local are included,
			// distro are imported. this will ensure local overrides distro.
			
			// import the distro
      
			if ( file_exists($distro_path) )
			{	
				self::addImportReference($generated_xsl, $distro_path, $importInsertionPoint);
			}
			else
			{
				// need to directly import distro includes.xsl, since we're not
				// importing a distro file that will reference it. 
				
				self::addImportReference( $generated_xsl, $distro_xsl_dir . "xsl/includes.xsl", $importInsertionPoint );
			}
			
			// additional xsl that should be included
			
			if ( $arrInclude != "" )
			{
				foreach ( $arrInclude as $strInclude )
				{
					self::addImportReference( $generated_xsl, $distro_xsl_dir . $strInclude, $importInsertionPoint );
				}
			}
			
			// include local
			
			if ( file_exists($local_path) )
			{
				self::addIncludeReference( $generated_xsl, $local_path);
			}
			
			// if actions.xml specified a view and we don't have a local or 
			// a distro copy, that's a problem.
			
			if (! ( file_exists($local_path) || file_exists($distro_path)))
			{
				throw new Exception("No xsl stylesheet found: $strXsltRelPath");
			}
			
			// add any locally overridden subsidiary 'included' type files if
			// neccesary. Right now, that's just includes.xsl.
			// includes.xsl still needs manually xsl:include'd in the distro source,
			// but local source shouldn't, we will import local includes.xsl
			// dynamically here. We import instead of include in case the local
			// stylesheet does erroneously 'include', to avoid a conflict. We
			// import LAST to make sure it takes precedence over distro. 
			
			$extra_xsl_names = array("xsl/includes.xsl");
			
			foreach ($extra_xsl_names as $rel_path )
			{
				$abs_local_path = $local_xsl_dir . '/' . $rel_path;
				
				if ( file_exists( $abs_local_path ))
				{
					self::addImportReference($generated_xsl, $abs_local_path, $importInsertionPoint);
				}
			}
			
			return $generated_xsl;
		}
		
		/**
		 * Internal function used to add another import statement to a supplied
		 * XSLT stylesheet. An insertPoint is also passed in--a reference to a 
		 * particular DOMElement which the 'import' will be added right before.
		 * Ordering of imports matters. 
		 * 
		 * @param DomDocument $xsltStylesheet	stylesheet to be modified
		 * @param string $absoluteFilePath 		abs filepath of stylesheet to be imported
		 * @param DomElement $insertPoint 		DOM Element to insert before. 
		 */ 
		
		private static function addImportReference($xsltStylesheet, $absoluteFilePath, $insertPoint)
		{
			$import_element = $xsltStylesheet->createElementNS("http://www.w3.org/1999/XSL/Transform", "xsl:import");
			$import_element->setAttribute("href", $absoluteFilePath);
			$xsltStylesheet->documentElement->insertBefore( $import_element, $insertPoint);
			
			return $xsltStylesheet;
		}
		
		/**
		 * Internal function used to add another inlude statement to a supplied
		 * XSLT stylesheet. Include will be added at end of stylesheet. 
		 * 
		 * @param DomDocument $xsltStylesheet	stylesheet to be modified
		 * @param string $absoluteFilePath abs filepath of stylesheet to be imported
		 */
		
		private static function addIncludeReference($xsltStylesheet, $absoluteFilePath)
		{
			$include_element = $xsltStylesheet->createElementNS("http://www.w3.org/1999/XSL/Transform", "xsl:include");
			$include_element->setAttribute("href", $absoluteFilePath);
			$xsltStylesheet->documentElement->appendChild( $include_element );
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

		public static function toTitleCase( $strInput )
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
		
		/**
		 * Send a request as either GET or POST
		 *
		 * @param string $url			url you want to send the request to
		 * @param string $data			[optional] data to POST to the above url
		 * @param string $content_type	[optional] content-type in the post, 'application/x-www-form-urlencoded' by default
		 * @param bool $bolEncode		[optional] whether to encode the posted data, true by default
		 * @return string				the response from the server
		 */
		
		public static function request($url, $data = null, $content_type = null, $bolEncode = true)
		{
			$objRegistry = Xerxes_Framework_Registry::getInstance();
			
			$proxy = $objRegistry->getConfig("HTTP_PROXY_SERVER", false);
			$curl = $objRegistry->getConfig("HTTP_USE_CURL", false, false);
			
			### GET REQUEST (NON-PROXY)
			
			if ( $data == null && $proxy == null )
			{
				return file_get_contents($url);
			}
			
			// these for POST requests
			
			$host = ""; // just the server host name
			$port = 80; // just the port number
			$path = ""; // just the uri path

			if ( $data != null )
			{
				if ( $content_type == null )
				{
					$content_type = "application/x-www-form-urlencoded";
				}
				
				// split the host from the path
				
				$arrMatches = array();
				
				if ( preg_match("/http:\/\/([^\/]*)(\/.*)/", $url, $arrMatches) != false )
				{
					$host = $arrMatches[1];
					$path = $arrMatches[2];
				}
				
				// extract the port number, if present
				
				if ( strstr($host, ":") )
				{
					$port = (int) self::removeLeft($host, ":");
					$host = self::removeRight($host, ":");
				}
				
				// regular POST requests will need to have the data urlencoded, but some special 
				// POST requests, like 'text/xml' to Solr, should not, so client code should 
				// set to false
				
				if ( $bolEncode == true )
				{
					$data = urlencode($data);
				}				
			}

			### POST OR GET USING AN HTTP PROXY or need to use CURL
			
			if ( $proxy != null || $curl != null )
			{				
				$response = ""; // the response
				$ch = curl_init(); // curl object
					
				// basic curl settings
				
				curl_setopt($ch, CURLOPT_URL, $url); // the url we're sending the request to
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // this returns the response to a variable		
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // this tells curl to follow 'location:' headers
				curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // but don't follow more than 10 'location:' redirects
				
				// this is a post request
				
				if ( $data != null )
				{
					// we do it this way, as opposed to a more typical curl post,
					// in case this is a custom HTTP POST request

					$header[] = "Host: $host\r\n";
					$header[] = "Content-type: $content_type\r\n";
					$header[] = "Content-length: " . strlen($data) . "\r\n";
					$header[] = $data;
						
					curl_setopt( $ch, CURLOPT_HTTPHEADER, $header ); 
					curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
				}
				
				// proxy settings
				
				if ( $proxy != null )
				{
					curl_setopt($ch, CURLOPT_PROXY, $proxy);
	
					// proxy username and password, if necessary
					
					$username = $objRegistry->getConfig("HTTP_PROXY_USERNAME", false);
					$password = $objRegistry->getConfig("HTTP_PROXY_PASSWORD", false);				
					
					if ( $username != null && $password != null )
					{
						curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$username:$password");
					}
				}
				
				// return the response
	
				$response = curl_exec($ch);
				$responseInfo = curl_getinfo($ch);
				curl_close($ch);

				if ( $response === false || $responseInfo["http_code"] != 200 )
				{
					throw new Exception("Error in response, " . $responseInfo["http_code"] . " " . $response );
				}
				
				return $response;
			}

			### POST REQUEST (NON-PROXY)
			
			else
			{
				$buf = ""; // the response
				$fp = fsockopen($host, $port); // file pointer object
				
				if ( ! $fp )
				{
					throw new Exception("could not connect to server");
				}
				
				fputs($fp, "POST $path HTTP/1.1\r\n");
				fputs($fp, "Host: $host\r\n");
				fputs($fp, "Content-type: $content_type\r\n");
				fputs($fp, "Content-length: " . strlen($data) . "\r\n");
				fputs($fp, "Connection: close\r\n\r\n");
				fputs($fp, $data);
				
				while (!feof($fp))
				{
					$buf .= fgets($fp,128);
				}
				
				fclose($fp);
				
				if ( ! strstr($buf, "200 OK") )
				{
					throw new Exception("Error in response, $buf");
				}
				
				return $buf;					
			}
		}
	}
	
?>
