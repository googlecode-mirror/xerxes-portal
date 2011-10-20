<?php

/**
 * Utility class for basic parsing functions
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: Parser.php 1821 2011-03-10 19:16:26Z dwalker@calstate.edu $
 * @package  Xerxes_Framework
 */ 

class Xerxes_Framework_Parser
{
	/**
	 * Simple XSLT transformation function
	 * 
	 * @param mixed $xml			DOMDocument or string containing xml
	 * @param string $strXsltPath	Relative file path to xslt document. Will look in both library location and 
	 * 								local app location for documents, and combine them so local overrides library 
	 * 								templates, if neccesary. 
	 * @param array $arrParams		[optional] array of parameters to pass to stylesheet
	 * @param bool $bolDoc			[optional] return result as DOMDocument (default false)
	 * @param array $arrInclude		[optional] additional stylesheets that should be included in the transform
	 * @return mixed				newly formatted document as string or DOMDocument
	 * @static 
	 */ 
				
	public static function transform ( $xml, $strXsltPath, $arrParams = null, $bolDoc = false, $arrInclude = array() )
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
		
		$objProcessor->importStylesheet($objXsl);
			
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
	
	public static function generateBaseXsl($strXsltRelPath, $arrInclude = array())
	{
		$arrImports = array(); // files to be imported
		
		$objRegistry = Xerxes_Framework_Registry::getInstance();
		
		
		### first, set up the paths to the distro and local directories
	
		// the 'local' xsl lives here
		
		$local_xsl_dir = $objRegistry->getConfig("LOCAL_DIRECTORY", true) . "/views/";
		$local_path =  $local_xsl_dir . $strXsltRelPath;
		      
		// the 'distro' xsl lives here
	
		$distro_xsl_dir = XERXES_APPLICATION_PATH . "views/";
		$distro_path =  $distro_xsl_dir . $strXsltRelPath;
		
		

		### check to make sure at least one of the files exists
		
		$distro_exists = file_exists($distro_path);
		$local_exists = file_exists($local_path);

		// if we don't have either a local or a distro copy, that's a problem.
		
		if (! ( $local_exists || $distro_exists) )
		{
			// throw new Exception("No xsl stylesheet found: $local_path || $distro_path");
			throw new Exception("No xsl stylesheet found: $strXsltRelPath");
		}			
		

		
		### now create the skeleton XSLT file that will hold references to both
		### the distro and the local files
		
		$generated_xsl = new DOMDocument();
		$generated_xsl->load( $distro_xsl_dir . "xsl/dynamic_skeleton.xsl");
		
		// prepend imports to this, to put them at the top of the file. 
	
		$importInsertionPoint = $generated_xsl->documentElement->firstChild;
		
		
		### add a reference to the distro file

		if ( $distro_exists == true )
		{	
			array_push($arrImports, $distro_path);
		}
		else
		{
			// if no distro, need to directly import (distro) includes.xsl, since we're not
			// importing a file that will reference it
			
			array_push($arrImports, $distro_xsl_dir . "xsl/includes.xsl");
		}

		
		### language file
		
		$request = Xerxes_Framework_Request::getInstance();
		$language = $request->getProperty("lang");
		
		if ( $language == "" )
		{
			$language = $objRegistry->defaultLanguage();
		}
		
		// english file is included by default (as a fallback)
		
		array_push($arrInclude, "xsl/labels/eng.xsl");
		
		// if language is set to something other than english
		// then include that file to override the english labels
		
		if ( $language != "eng" ) {
			array_push($arrInclude, "xsl/labels/$language.xsl");
		}
		
		### add a refence for files programatically added (including the language file above)
		
		if ( $arrInclude != null )
		{
			foreach ( $arrInclude as $strInclude )
			{
				// but only if a distro copy exists
				
				if ( file_exists($distro_xsl_dir . $strInclude) )
				{
					array_push($arrImports, $distro_xsl_dir . $strInclude);
				}
				
				// see if there is a local version, and include it too
				
				if ( file_exists($local_xsl_dir . $strInclude) )
				{
					array_push($arrImports, $local_xsl_dir . $strInclude);
				}
			}
		}
		
			
		### add a refence to the local file
		
		if ( $local_exists )
		{
			self::addIncludeReference( $generated_xsl, $local_path);
		}
		

		### if the distro file  xsl:includes or xsl:imports other files
		### check if there is a corresponding local file, and import it too
		
		// We import instead of include in case the local stylesheet does erroneously 
		// 'include', to avoid a conflict. We import LAST to make sure it takes 
		// precedence over distro. 
		
		if ( $distro_exists )
		{
			$distroXml = simplexml_load_file ( $distro_path );
		
			$distroXml->registerXPathNamespace ( 'xsl', 'http://www.w3.org/1999/XSL/Transform' );
			
			// find anything include'd or import'ed in original base file
			
			$array_merged = array_merge ( $distroXml->xpath( "//xsl:include" ), $distroXml->xpath ( "//xsl:import" ) );
			
			foreach ( $array_merged as $extra )
			{
				// path to local copy
				
				$local_candidate = $local_xsl_dir . dirname ( $strXsltRelPath ) . '/' . $extra['href'];
				
				// path to distro copy as a check
				
				$distro_check = $distro_xsl_dir . dirname ( $strXsltRelPath ) . '/' . $extra['href'];
				
				// make sure local copy exists, and they are both not pointing at the same file 
				
				if ( file_exists ( $local_candidate ) && realpath($distro_check) != realpath($local_candidate) )
				{
					array_push($arrImports, $local_candidate);
				}
			}
		}
		
		### make sure we've got a reference to the local includes too
		
		array_push($arrImports, $local_xsl_dir . "xsl/includes.xsl");
		
		// now make sure no dupes
		
		$arrImports = array_unique($arrImports);
		
		
		### now the actual mechanics of the import
		
		foreach ( $arrImports as $import )
		{
			self::addImportReference ( $generated_xsl, $import, $importInsertionPoint );
		}
		
		// header("Content-type: text/xml"); echo $generated_xsl->saveXML(); exit;
		
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
		$absoluteFilePath = str_replace('\\', '/', $absoluteFilePath); // darn windows
		
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

	public static function toSentenceCase($strInput)
	{						
		if ( strlen($strInput) > 1 )
		{
			// drop everything
			
			$strInput = Xerxes_Framework_Parser::strtolower($strInput);
			
			// capitalize the first letter
			
			$strInput = Xerxes_Framework_Parser::strtoupper(substr($strInput, 0, 1)) . substr($strInput, 1);
			
			// and the start of a subtitle
			
			$strInput = self::capitalizeSubtitle($strInput);
		}
		
		return $strInput;
	}
	
	private static function capitalizeSubtitle($strFinal)
	{
		$arrMatches = array();
		
		if ( preg_match("/: ([a-z])/", $strFinal, $arrMatches) )
		{
			$strLetter = ucwords($arrMatches[1]);
			$strFinal = preg_replace("/: ([a-z])/", ": " . $strLetter, $strFinal );
		}
		
		return $strFinal;
	}
	
	
	/**
	 * Determine whether the url is part of a group of domains
	 * 
	 * @param string $strURL	the url to test
	 * @param string $strDomain	a comma-separated list of domains
	 *
	 * @return bool				true if in domain, false otherwise
	 */
	
	public static function withinDomain($strURL, $strDomain)
	{
		$bolPassed = false;
		
		if ( strlen($strURL) > 4 )
		{
			// only do it if it's an absolute url, local are fine
				
			if ( substr($strURL, 0, 4) == "http" )
			{
				$arrAllowed = explode(",", $strDomain);
				
				// if any in our list match
				
				$bolPassed = false;
				
				foreach ( $arrAllowed as $strAllowed )
				{
					$strAllowed = trim(str_replace(".", "\\.", $strAllowed));
					$strAllowed = trim(str_replace("*", "[^.]*", $strAllowed));
					
					if ( preg_match('/^http[s]{0,1}:\/\/' . $strAllowed .'.*/', $strURL) )
					{
						$bolPassed = true;
					}
				}
			}
		}
		
		return $bolPassed;
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
		$strRight = "";		// right remainder of the string to return
		
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
		$string = str_replace('&', '&amp;', $string);
		$string = str_replace('<', '&lt;', $string);
		$string = str_replace('>', '&gt;', $string);
		$string = str_replace('\'', '&#39;', $string);
		$string = str_replace('"', '&quot;', $string);
		
		$string = str_replace("&amp;#", "&#", $string);
		$string = str_replace("&amp;amp;", "&amp;", $string);
		
		// trying to catch unterminated entity references
		
		$string = preg_replace('/(&#[a-hA-H0-9]{2,5})\s/', "$1; ", $string);
		
		return $string;
	}
	
	/**
	 * use multi-byte string lower case if available
	 * 
	 * @param string $string the string to drop to lower case
	 */
	
	public static function strtolower($string)
	{
		if ( function_exists("mb_strtolower") )
		{
			return mb_strtolower($string, "UTF-8");
		}
		else
		{
			return strtolower($string);
		}
	}

	
	/**
	 * use multi-byte string upper case if available
	 * 
	 * @param string $string the string to raise to upper case
	 */

	public static function strtoupper($string)
	{
		if ( function_exists("mb_strtoupper") )
		{
			return mb_strtoupper($string, "UTF-8");
		}
		else
		{
			return strtoupper($string);
		}
	}
	
	public static function preg_replace($pattern, $replacement, $subject)
	{
		if ( function_exists("mb_ereg_replace") )
		{
			// preg strings have / at the start and end, so we need to take those
			// off for this mb_ereg one (annoying!) for it to work correctly
			 
			$pattern = substr($pattern,1);
			$pattern = substr($pattern,0,-1);
			
			return mb_ereg_replace($pattern, $replacement, $subject);
		}
		else
		{
			return preg_replace($pattern, $replacement, $subject);
		}			
	}
	
	public static function number_format($number, $decimals = 0)
	{
		$number = (int) preg_replace('/\D/', '', $number);
		
		$localeconv = localeconv();
		
		if ( $localeconv['thousands_sep'] == "" )
		{
			$localeconv['thousands_sep'] = ",";
		}
		
		return number_format($number, $decimals, $localeconv['decimal_point'], $localeconv['thousands_sep']);
	}
	
	
	/**
	 * Send a request as either GET or POST
	 *
	 * @param string $url			url you want to send the request to
	 * @param int $timeout			[optional] seconds to wait before timing out
	 * @param string $data			[optional] data to POST to the above url
	 * @param string $headers		[optional] http headers
	 * @param bool $bolEncode		[optional] whether to encode the posted data, true by default
	 * @return string				the response from the server
	 */
	
	public static function request($url, $timeout = null, $data = null, $headers = null, $bolEncode = true)
	{
		return Xerxes_Framework_HTTP::request($url, $timeout, $data, $headers, $bolEncode);
	}
	
	/**
	 * Convert string, DOMNode to DOMDocument
	 */
	
	public static function convertToDOMDocument($xml)
	{
		if ( $xml instanceof DOMDocument )
		{
			return $xml;
		}
		elseif ( is_string($xml) )
		{
			$document = new DOMDocument();
			$document->loadXML($xml);
			
			return $document;
		}
		elseif ( $xml instanceof DOMNode )
		{
			// we'll convert this node to a DOMDocument
				
			// first import it into an intermediate doc, 
			// so we can also import namespace definitions as well as nodes
				
			$intermediate = new DOMDocument();
			$intermediate->loadXML("<wrapper />");
				
			$import = $intermediate->importNode($xml, true);
			$our_node = $intermediate->documentElement->appendChild($import);
				
			// now get just our xml, minus the wrapper
				
			$document = new DOMDocument();
			$document->loadXML($intermediate->saveXML($our_node));
			
			return $document;
		}	
		else
		{
			throw new Exception("param 1 must be of type string, DOMNode, or DOMDocument");
		}
	}
}

/**
 * Utility class for parsing some XML
 */

class Xerxes_Framework_Parser_XML extends DOMElement 
{
	private $node;
	
	public function __construct(DOMElement $node)
	{
		$this->node = $node;
	}
	
	protected function getElement($name)
	{
		$elements = $this->node->getElementsByTagName($name);
		
		if ( count($elements) > 0 )
		{
			$node = $elements->item(0);
			return new Xerxes_Framework_Parser_XML($node);
		}
		else
		{
			return null;
		}
	}
	
	protected function getValue($name)
	{
		$element = $this->getElement($name);
		
		if ( $element != null )
		{
			return $element->nodeValue;
		}
		else
		{
			return null;
		}
	}
	
	protected function getValues($name)
	{
		$values = array();
		
		$elements = $this->node->getElementsByTagName($name);
		
		foreach ( $elements as $node )
		{
			array_push($values, $node->nodeValue);
		}
		
		return $values;
	}		
}