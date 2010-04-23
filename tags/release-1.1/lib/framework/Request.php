<?php

	/**
	 * Process parameter in the request, either from URL or command line, and store
	 * XML produced by the command classes
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes_Framework
	 * @uses Xerxes_Parser
	 */

	class Xerxes_Framework_Request
	{
		private $method = "";				// request method: GET, POST, COMMAND
		private $arrParams = array();		// request paramaters
		private $arrSession = array();		// session array for command line, unused right now
		private $xml = null;				// main xml document for holding data from commands
		private $strRedirect = "";			// redirect url
		
		/**
		 * Process the incloming request paramaters and cookie values
		 */
		
		public function __construct()
		{
			if ( array_key_exists("REQUEST_METHOD", $_SERVER) )
			{
				$this->method = $_SERVER['REQUEST_METHOD'];
				
				// request has come in from GET or POST
				
				if ( $_SERVER['QUERY_STRING'] != "" )
				{
					// querystring can be delimited either with ampersand
					// or semicolon
					
					$arrParams = preg_split("/&|;/", $_SERVER['QUERY_STRING']);
					
					foreach ( $arrParams as $strParam )
					{
						// split out key and value on equal sign
						
						$iEqual = strpos($strParam,"=");
						
						if ( $iEqual !== false )
						{
							$strKey = substr($strParam, 0, $iEqual);
							$strValue = substr($strParam, $iEqual + 1);
							
							if ( array_key_exists($strKey,$this->arrParams) )
							{
								// if there are multiple params of the same name,
								// make sure we add them as array
									
								if ( ! is_array( $this->arrParams[$strKey]) )
								{
									$this->arrParams[$strKey] = array($this->arrParams[$strKey]);
								}
																		
								array_push( $this->arrParams[$strKey], $strValue );
							}
							else
							{
								$this->arrParams[$strKey] = urldecode($strValue);
							}
						}
					}
				}
				foreach ( $_POST as $key => $value )
				{
					$this->arrParams[$key] = $value;
				}
				foreach ( $_COOKIE as $key => $value )
				{
					$this->arrParams[$key] = $value;
				}
			}
			else
			{			
				// request has come in from the command line
				
				$this->method = "COMMAND";
				
				foreach ( $_SERVER['argv'] as $arg )
				{
					if ( strpos($arg, "=") )
					{
						list($key,$val) = explode("=",$arg);
						$this->setProperty($key, $val);
					}
				}
			}			
		}
		
		/**
		 * Whether the request came in on the command line
		 *
		 * @return bool		true if came in on the cli
		 */
		
		public function isCommandLine()
		{
			if ( $this->method == "COMMAND")
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		/**
		 * Add a value to the request parameters
		 *
		 * @param string $key		key to identify the value
		 * @param string $val		value to add
		 * @param bool $bolArray	[optional] set to true will convert property to array and push value into it
		 */
		
		public function setProperty($key, $val, $bolArray = false)
		{
			if ( $bolArray == true )
			{
				if ( array_key_exists($key, $this->arrParams) && ! is_array($this->arrParams[$key]) )
				{
					$this->arrParams[$key] = array($this->arrParams[$key]);
				}
				
				array_push($this->arrParams[$key], $val);
			}
			else
			{
				$this->arrParams[$key] = $val;
			}
		}

		/**
		 * Retrieve a value from the request parameters
		 *
		 * @param string $key		key that identify the value
		 * @param bool $bolArray	[optional] whether value should be returned as an array, even if only one value
		 * @return mixed 			[string or array] value if available, otherwise null
		 */
		
		public function getProperty($key, $bolArray = false)
		{
			if ( array_key_exists( $key, $this->arrParams ) )
			{
				// if the value is requested as array, but is not array, make it one!
				
				if ( $bolArray == true && ! is_array($this->arrParams[$key]))
				{
					return array($this->arrParams[$key]);
				}
				
				// the opposite: if the the value is not requested as array but is,
				// take just the first value in the array
				
				elseif ( $bolArray == false && is_array($this->arrParams[$key]))
				{
					return $this->arrParams[$key][0];
				}
				else
				{
					return $this->arrParams[$key];
				}
			}
			else
			{
				return null;
			}
		}
		
		/**
		 * Retrieve all request paramaters as array
		 *
		 * @return array
		 */

		public function getAllProperties()
		{
			return $this->arrParams;
		}
		
		/**
		 * Get a value from the $_SERVER global array
		 *
		 * @param string $key	server variable
		 * @return mixed
		 */
		
		public function getServer($key)
		{
			// IIS fix, since it doesn't hold value for request_uri
			
			if ( $key == "REQUEST_URI")
			{				
				if (!isset($_SERVER['REQUEST_URI']))
				{
				    if (!isset($_SERVER['QUERY_STRING']))
				    {
				        $_SERVER['REQUEST_URI'] = $_SERVER["SCRIPT_NAME"];
				    }
				    else
				    {
				        $_SERVER['REQUEST_URI'] = $_SERVER["SCRIPT_NAME"] .'?'.
				        $_SERVER['QUERY_STRING'];
				    }
				}
			}
			
			if ( array_key_exists($key, $_SERVER) )
			{
				return $_SERVER[$key];
			}
			else
			{
				return null;
			}
		}
		
		/**
		 * Get a value stored in the session
		 *
		 * @param string $key	variable name
		 * @return mixed		stored variable value
		 */
		
		public function getSession($key)
		{
			if ( array_key_exists($key, $_SESSION) )
			{
				return $_SESSION[$key];
			}
			else
			{
				return null;
			}
		}
		
		/**
		 * Save a value in session state
		 *
		 * @param string $key		name of the variable
		 * @param mixed $value		value of the variable
		 */
		
		public function setSession($key, $value)
		{
			$_SESSION[$key] = $value;
		}
		
		/**
		 * Set the name of the document element for the stored xml; if this
		 * is not set before adding a document via addDocument, the document
		 * element will be set to 'xerxes'
		 *
		 * @param string $strName	document element name
		 */
		
		public function setDocumentElement($strName)
		{
			if ( $strName == null )
			{
				error_log("document element name was null");	
			}
			else
			{
				$this->xml = new DOMDocument();
				$this->xml->loadXML("<$strName />");
			}
		}
		
		/**
		 * Add an XML DOMDocument to the master xml
		 *
		 * @param DOMDocument $objData
		 */
		
		public function addDocument(DOMDocument $objData)
		{
			if (! $this->xml instanceof DOMDocument )
			{
				$this->xml = new DOMDocument();
				$this->xml->loadXML("<xerxes />");
			}
			
			if ( $objData != null )
			{
				if ( $objData->documentElement != null )
				{
					$objImport = $this->xml->importNode($objData->documentElement, true);
					$this->xml->documentElement->appendChild($objImport);
				}
			}
		}
		
		/**
		 * Set the URL for redirect
		 *
		 * @param string $url
		 */
		
		public function setRedirect($url)
		{
			$this->strRedirect = $url;
		}
		
		/**
		 * Get the URL to redirect user
		 *
		 * @return unknown
		 */
		
		public function getRedirect()
		{
			return $this->strRedirect;
		}
		
		/**
		 * Extract a value from the master xml
		 *
		 * @param string $xpath				xpath expression to the element(s)
		 * @param array $arrNamespaces		key / value pair of url / namespace reference for the xpath
		 * @param string $strReturnType		[optional] return query results as 'DOMNODELIST' or 'ARRAY', otherwise as sting
		 * @return mixed					if no value in return type, then single value returned as string					
		 */
		
		public function getData($xpath, $arrNamespaces = null, $strReturnType = null)
		{
			$strReturnType = strtoupper($strReturnType);
			
			if ( $strReturnType != null && $strReturnType != "DOMNODELIST" && $strReturnType != "ARRAY")
			{
				throw new Exception("unsupported return type");
			}
			
			$objXPath = new DOMXPath($this->xml);
			
			if ( $arrNamespaces != null )
			{
				foreach ( $arrNamespaces as $prefix => $identifier )
				{
					$objXPath->registerNamespace($prefix, $identifier);
				}
			}
			
			$objNodes = $objXPath->query($xpath);
			
			if ( $objNodes == null )
			{
				// no value found
				
				return null;
			}
			elseif ( $strReturnType == "DOMNODELIST")
			{
				// return nodelist
				
				return $objNodes;
			}
			elseif ( $strReturnType == "ARRAY")
			{
				// return nodelist as array, for convenience
				
				$arrReturn = array();
				
				foreach  ( $objNodes as $objNode )
				{
					array_push( $arrReturn, $objNode->nodeValue );
				}
				
				return $arrReturn;
			}
			else
			{
				// just grab the value, if you know it is the 
				// only one or first one in the query
				
				return $objNodes->item(0)->nodeValue;
			}
		}

		/**
		 * Retrieve master XML and all request paramaters
		 * 
		 * @param bool $bolHideServer	[optional]	true will exclude the server variables from the response, default false
		 *
		 * @return DOMDocument
		 */
				
		public function toXML($bolHideServer = false)
		{
			// add the url parameters and session and server global arrays
			// to the master xml document
			
			$objXml = new DOMDocument();
			$objXml->loadXML("<request />");
			
			// session and server global arrays will have parent elements
			// but querystring and cookie params will be at the root of request
			
			$this->addElement($objXml, $objXml->documentElement, $this->arrParams);	
			
			// add the session global array
			
			$objSession = $objXml->createElement("session");			
			$objXml->documentElement->appendChild($objSession);
			$this->addElement($objXml, $objSession, $_SESSION);
			
			// add the server global array, but only if the request
			// asks for it, for security purposes
			
			if ( $bolHideServer == true )
			{
				$objServer = $objXml->createElement("server");
				$objXml->documentElement->appendChild($objServer);
				$this->addElement($objXml, $objServer, $_SERVER);
			}
			
			// add to the master xml document
			
			$this->addDocument($objXml);
			
			// once added, now return the master xml document
			
			return $this->xml;
		}
		
		/**
		 * Add global array as xml to request xml document
		 *
		 * @param DOMDocument $objXml		[by reference] request xml document
		 * @param DOMNode $objAppend		[by reference] node to append values to
		 * @param array $arrValues			global array
		 */
		
		private function addElement(&$objXml, &$objAppend, $arrValues)
		{
			foreach ( $arrValues as $key => $value )
			{
				if ( is_array($value) )
				{
					foreach ($value as $strKey => $strValue)
					{
						$objElement = $objXml->createElement(strtolower($key), Xerxes_Parser::escapeXml($strValue));
						$objElement->setAttribute("key", $strKey);
						$objAppend->appendChild($objElement);
					}
				}
				else
				{
					$objElement = $objXml->createElement(strtolower($key), Xerxes_Parser::escapeXml($value));
					$objAppend->appendChild($objElement);
				}
			}
		}
	}

?>