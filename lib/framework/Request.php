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
	private $method = ""; // request method: GET, POST, COMMAND
	private $arrParams = array ( ); // request paramaters
	private $arrSession = array ( ); // session array for command line, unused right now
	private $arrCookieSetParams = array ( ); // cookies that will be set with response. value is array of args to php set_cookie. 
	private $xml = null; // main xml document for holding data from commands
	private $strRedirect = ""; // redirect url
	private $path_elements = null; // http path tranlsated into array of elements.

	/**
	 * Process the incoming request paramaters, cookie values, url path if pretty-uri on
	 */
	
	public function __construct()
	{
		if ( array_key_exists( "REQUEST_METHOD", $_SERVER ) )
		{
			// request has come in from GET or POST

			$this->method = $_SERVER['REQUEST_METHOD'];
			
			// now extract remaining params in query string. 

			if ( $_SERVER['QUERY_STRING'] != "" )
			{
				// querystring can be delimited either with ampersand
				// or semicolon
				

				$arrParams = preg_split( "/&|;/", $_SERVER['QUERY_STRING'] );
				
				foreach ( $arrParams as $strParam )
				{
					// split out key and value on equal sign
					

					$iEqual = strpos( $strParam, "=" );
					
					if ( $iEqual !== false )
					{
						$strKey = substr( $strParam, 0, $iEqual );
						$strValue = substr( $strParam, $iEqual + 1 );
						
						$this->setProperty( $strKey, urldecode( $strValue ) );
					}
				}
			}
			
			foreach ( $_POST as $key => $value )
			{
				$this->setProperty( $key, $value );
			}
			foreach ( $_COOKIE as $key => $value )
			{
				$this->setProperty( $key, $value );
			}
			
			// if pretty-urls is turned on, extract params from uri. 

			$objRegistry = Xerxes_Framework_Registry::getInstance();
			
			if ( $objRegistry->getConfig( "REWRITE", false ) )
			{
				$this->extractParamsFromPath();
			}
		} 
		else
		{
			// request has come in from the command line

			$this->method = "COMMAND";
			
			foreach ( $_SERVER['argv'] as $arg )
			{
				if ( strpos( $arg, "=" ) )
				{
					list ( $key, $val ) = explode( "=", $arg );
					$this->setProperty( $key, $val );
				}
			}
		}
		
		### iis fixes
		
		if ( isset($_SERVER) )
		{
			// to make this consistent with apache
			
			if ( array_key_exists('HTTPS', $_SERVER) )
			{
				if ( $_SERVER['HTTPS'] == "off" )
				{
					unset($_SERVER['HTTPS']);
				}
			}
			
			// since it doesn't hold value for request_uri
	
			if ( ! isset( $_SERVER['REQUEST_URI'] ) )
			{
				if ( ! isset( $_SERVER['QUERY_STRING'] ) )
				{
					$_SERVER['REQUEST_URI'] = $_SERVER["SCRIPT_NAME"];
				} 
				else
				{
					$_SERVER['REQUEST_URI'] = $_SERVER["SCRIPT_NAME"] . '?' . $_SERVER['QUERY_STRING'];
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
		if ( $this->method == "COMMAND" )
		{
			return true;
		} 
		else
		{
			return false;
		}
	}
	
	/**
	 * Extract params from pretty-urls when turned on in config. Requires base url to be set in config.
	 * will get from $_SERVER['REQUEST_URI'], first stripping base url.
	 */
	
	public function extractParamsFromPath()
	{
		$this->mapPathToProperty( 0, "base" );
		$this->mapPathToProperty( 1, "action" );
		
		// if the action has any specific parmaters it defines beyond base and action
		// they are extracted here

		$objMap = Xerxes_Framework_ControllerMap::getInstance()->path_map_obj();
		$map = $objMap->indexToPropertyMap( $this->getProperty( "base" ), $this->getProperty( "action" ) );
		
		foreach ( $map as $index => $property )
		{
			$this->mapPathToProperty( $index, $property );
		}
	}
	
	/**
	 * Take the http request path and translate it to an array. 
	 * will get from $_SERVER['REQUEST_URI'], first stripping base url.
	 * If path was just "/", array will be empty. 
	 *
	 * @return array		array of path elements
	 */
	
	private function pathElements()
	{
		// lazy load

		if ( ! $this->path_elements )
		{
			$objRegistry = Xerxes_Framework_Registry::getInstance();
			
			$request_uri = $this->getServer( 'REQUEST_URI' );
			
			// get the path by stripping off base url + querystring

			$configBase = $objRegistry->getConfig( 'BASE_WEB_PATH', false, "" );
      # remove base path, which might be simply '/'
      if (substr($request_uri, 0, strlen($configBase) +1) == $configBase . "/") {
        #$request_uri = str_replace( $configBase . "/", "", $request_uri );
        $request_uri = substr_replace( $request_uri, '', 0, strlen($configBase) + 1);
      }
      # remove query string
			$request_uri = Xerxes_Parser::removeRight( $request_uri, "?" );
			
			// now get the elements

			$path_elements = explode( '/', $request_uri );
			
			for ( $x = 0 ; $x < count( $path_elements ) ; $x ++ )
			{
				$path_elements[$x] = urldecode( $path_elements[$x] );
			}
			
			// for an empty path, we'll have one empty string element, get rid of it.

			if ( strlen( $path_elements[0] ) == 0 )
			{
				unset( $path_elements[0] );
			}
			
			$this->path_elements = $path_elements;
		}
		
		return $this->path_elements;
	}
	
	/**
	 * Maps and inserts the path elements into the request parameters
	 *
	 * @param int $path_index			the numbered path element
	 * @param string $property_name		the property name
	 */
	
	public function mapPathToProperty($path_index, $property_name)
	{
		$path_elements = $this->pathElements();
		
		if ( array_key_exists( $path_index, $path_elements ) )
		{
			$this->setProperty( ( string ) $property_name, ( string ) $path_elements[$path_index] );
		}
	}
	
	/**
	 * Add a value to the request parameters
	 *
	 * @param string $key		key to identify the value
	 * @param string $value		value to add
	 * @param bool $bolArray	[optional] set to true will ensure property is set as array
	 */
	
	public function setProperty($key, $value, $bolArray = false)
	{
		if ( array_key_exists( $key, $this->arrParams ) )
		{
			// if there is an existing element, then we always push in the
			// the new value into an array, first converting the exising value
			// to an array if it is not already one 

			if ( ! is_array( $this->arrParams[$key] ) )
			{
				$this->arrParams[$key] = array ($this->arrParams[$key] );
			}
			
			array_push( $this->arrParams[$key], $value );
		} 
		elseif ( $bolArray == true )
		{
			// no existing value in property, but the calling code says 
			// this *must* be added as an array, so make it an array, if not one already

			if ( ! is_array( $value ) )
			{
				$value = array ($value );
			}
			
			$this->arrParams[$key] = $value;
		} 
		else
		{
			$this->arrParams[$key] = $value;
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
			

			if ( $bolArray == true && ! is_array( $this->arrParams[$key] ) )
			{
				return array ($this->arrParams[$key] );
			} 

			// the opposite: if the the value is not requested as array but is,
			// take just the first value in the array

			elseif ( $bolArray == false && is_array( $this->arrParams[$key] ) )
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
		if ( array_key_exists( $key, $_SERVER ) )
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
		if ( array_key_exists( $key, $_SESSION ) )
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
			error_log( "document element name was null" );
		} 
		else
		{
			$this->xml = new DOMDocument( );
			$this->xml->loadXML( "<$strName />" );
		}
	}
	
	/**
	 * Add an XML DOMDocument to the master xml
	 *
	 * @param DOMDocument $objData
	 */
	
	public function addDocument(DOMDocument $objData)
	{
		if ( ! $this->xml instanceof DOMDocument )
		{
			$this->xml = new DOMDocument( );
			$this->xml->loadXML( "<xerxes />" );
		}
		
		if ( $objData != null )
		{
			if ( $objData->documentElement != null )
			{
				$objImport = $this->xml->importNode( $objData->documentElement, true );
				$this->xml->documentElement->appendChild( $objImport );
			}
		}
	}
	
	/**
	 * Include some cookies in response
	 *
	 * Parameters match those of the PHP function setCookie.
	 * Except if path is left blank, will be set to Xerxes base dir.
	 */
	
	public function setCookie($name)
	{
		$cookieParams = func_get_args();
		if ( ! $cookieParams[3] )
		{
			// No path? PHP default is current path, which won't work well in pretty url style.
			 
			$cookieParams[3] = $config->getConfig( 'BASE_WEB_PATH', false, "." ) . "/";
			
			// pad other args if neccesary
			if ( ! $cookieParams[2] )
			{
				// expire, use 0 to pad
				$cookieParams[2] = 0;
			}
			if ( ! $cookieParams[1] )
			{
				// value, use blank to pad. Why would you want a blank value? who knows. 
				$cookieParams[1] = "";
			}
		}
		
		$this->arrCookieSetParams[$name] = $cookieParams;
	}
	
	// called by frontcontroller, nobody else should need it.
	
	public function cookieSetParams()
	{
		return $this->arrCookieSetParams;
	}
		
	/**
	 * Take a url, and set a particular key/value in the url parameters.
	 * Replace existing value if neccesary; works whether or not url
	 * to begin with has a query string component. 
	 */
	
	public static function setParamInUrl($url, $key, $value)
	{
		$queryPos = strpos( $url, '?' );
		$queryHash = "";
		
		if ( $queryPos )
		{
			$base = substr( $url, 0, $queryPos + 1 );
			$queryString = substr( $url, $queryPos + 1 );
			parse_str( $queryString, $queryHash );
		} 
		else
		{
			$base = $url . '?';
			$queryHash = array ( );
		}
		
		$queryHash[$key] = $value;
		
		return $base . http_build_query( $queryHash );
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
	 * @param string $xpath				[optional] xpath expression to the element(s)
	 * @param array $arrNamespaces		[optional] key / value pair of url / namespace reference for the xpath
	 * @param string $strReturnType		[optional] return query results as 'DOMNODELIST' or 'ARRAY', otherwise as 'sting'
	 * @return mixed					if no paramaters set, returns entire xml as DOMDocument
	 * 									otherwise returns a string, unless value supplied in return type
	 */
	
	public function getData($xpath = null, $arrNamespaces = null, $strReturnType = null)
	{
		if ( $xpath == null && $arrNamespaces == null && $strReturnType == null )
		{
			return $this->xml;
		}
		
		$strReturnType = strtoupper( $strReturnType );
		
		if ( $strReturnType != null && $strReturnType != "DOMNODELIST" && $strReturnType != "ARRAY" )
		{
			throw new Exception( "unsupported return type" );
		}
		
		$objXPath = new DOMXPath( $this->xml );
		
		if ( $arrNamespaces != null )
		{
			foreach ( $arrNamespaces as $prefix => $identifier )
			{
				$objXPath->registerNamespace( $prefix, $identifier );
			}
		}
		
		$objNodes = $objXPath->query( $xpath );
		
		if ( $objNodes == null )
		{
			// no value found
			

			return null;
		} 
		elseif ( $strReturnType == "DOMNODELIST" )
		{
			// return nodelist
			

			return $objNodes;
		} 
		elseif ( $strReturnType == "ARRAY" )
		{
			// return nodelist as array, for convenience
			

			$arrReturn = array ( );
			
			foreach ( $objNodes as $objNode )
			{
				array_push( $arrReturn, $objNode->nodeValue );
			}
			
			return $arrReturn;
		} 
		else
		{
			// just grab the value, if you know it is the 
			// only one or first one in the query
			

			if ( $objNodes->item( 0 ) != null )
			{
				return $objNodes->item( 0 )->nodeValue;
			} 
			else
			{
				return null;
			}
		}
	}
	
	/**
	 * Generate a url for a given action. Used by commands to generate action
	 * urls, rather than calculating manually. This method returns different
	 * urls depending on whether rewrite is on in config. Will use
	 * configured base_web_path if available, which is best.	
	 * 
	 * @param array $properties	Keys are xerxes request properties, values are 
	 * the values. For an action url, "base" and "action" are required as keys.
	 * Properties will be put in path or query string of url, if pretty-urls are turned on
	 * in config.xml, as per action configuration in actions.xml.
	 * @param boolean $full Optional, force full absolute url with hostname. 
	 *
	 * @return string url 
	 */
	
	public function url_for($properties, $full = false, $force_secure = false)
	{
		if ( ! array_key_exists( "base", $properties ) )
		{
			throw new Exception( "no base/section supplied in url_for." );
		}
    if ( $force_secure ) {
      $full = true;
    }
		
		$config = Xerxes_Framework_Registry::getInstance();
		
		$base_path = $config->getConfig( 'BASE_WEB_PATH', false, "" ) . "/";
		
		// should we generate full absolute urls with hostname? indicated by a
		// request property, set automatically by snippet embed controllers. 
		

		if ( $this->getProperty( "gen_full_urls" ) == 'true' || $full )
		{
			$base_path = $config->getConfig( 'BASE_URL', true ) . "/";
      if ( $force_secure ) {
        $base_path = ereg_replace("^http\:\/\/", "https://", $base_path);  
      }
		}
		
		$extra_path = "";
		$query_string = "";
		
		$base = $properties["base"];
		$action = null;
		
		if ( array_key_exists( "action", $properties ) )
		{
			$action = $properties["action"];
		}
		
		if ( $config->getConfig( 'REWRITE', false ) )
		{
			// base in path

			$extra_path_arr[0] = urlencode( $base );
			unset( $properties["base"] );
			
			// action in path

			if ( array_key_exists( "action", $properties ) )
			{
				$extra_path_arr[1] = urlencode( $action );
				unset( $properties["action"] );
			}
			
			// action-specific stuff

			foreach ( array_keys( $properties ) as $property )
			{
				$controller_map = Xerxes_Framework_ControllerMap::getInstance();
				$index = $controller_map->path_map_obj()->indexForProperty( $base, $action, $property );
				
				if ( $index )
				{
					$extra_path_arr[$index] = urlencode( $properties[$property] );
					unset( $properties[$property] );
				}
			}
		  // Need to resort since we may have added indexes in a weird order. Silly PHP. 
			ksort($extra_path_arr); 	
			$extra_path = implode( "/", $extra_path_arr );
		}
		
		// everything else, which may be everything if it's ugly uris,

		$query_string = http_build_query( $properties, '', '&amp;' );
		$assembled_path = $base_path . $extra_path;
		
		if ( $query_string )
		{
			$assembled_path = $assembled_path . '?' . $query_string;
		}
		
		return $assembled_path;
	}
	
	/**
	 * Check if the user has explicitly logged in
	 *
	 * @return bool		true if user is named and logged in, otherwise false
	 */
	
	public function hasLoggedInUser()
	{
		return Xerxes_Framework_Restrict::isAuthenticatedUser( $this );
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
		$objRegistry = Xerxes_Framework_Registry::getInstance();
		
		// add the url parameters and session and server global arrays
		// to the master xml document

		$objXml = new DOMDocument( );
		$objXml->loadXML( "<request />" );
		
		// session and server global arrays will have parent elements
		// but querystring and cookie params will be at the root of request

		$this->addElement( $objXml, $objXml->documentElement, $this->arrParams );
		
		// add the session global array

		$objSession = $objXml->createElement( "session" );
		$objXml->documentElement->appendChild( $objSession );
		$this->addElement( $objXml, $objSession, $_SESSION );
		
		// we might add some calculated thigns to xml that aren't actually
		// stored in session.
		
		// okay, yeah, we already have group memberships listed from the session,
		// but it doesn't have all the data we need, plus we need to stick
		// group memberships by virtue of IP address. 

		$objAuth = $objXml->createElement( "authorization_info" );
		$objXml->documentElement->appendChild( $objAuth );
		
		// are they an affiliated user at all, meaning either logged in or
		// ip recognized?

		$authUser = Xerxes_Framework_Restrict::isAuthenticatedUser( $this );
		$authIP = Xerxes_Framework_Restrict::isIpAddrInRanges( $this->getServer( 'REMOTE_ADDR' ), $objRegistry->getConfig( "local_ip_range" ) );
		$objElement = $objXml->createElement( "affiliated", ($authUser || $authIP) ? "true" : "false" );
		$objElement->setAttribute( "user_account", $authUser ? "true" : "false" );
		$objElement->setAttribute( "ip_addr", $authIP ? "true" : "false" );
		$objAuth->appendChild( $objElement );
		
		// now each group
		
		$arrGroups = $objRegistry->userGroups();
		
		if ( $arrGroups != null )
		{
			foreach ( $objRegistry->userGroups() as $group )
			{
				$authUser = array_key_exists( "user_groups", $_SESSION ) && is_array( $_SESSION["user_groups"] ) && in_array( $group, $_SESSION["user_groups"] );
				$authIP = Xerxes_Framework_Restrict::isIpAddrInRanges( $this->getServer( 'REMOTE_ADDR' ), $objRegistry->getGroupLocalIpRanges( $group ) );
				$objElement = $objXml->createElement( "group", ($authUser || $authIP) ? "true" : "false" );
				$objElement->setAttribute( "id", $group );
				$objElement->setAttribute( "display_name", $objRegistry->getGroupDisplayName( $group ) );
				$objElement->setAttribute( "user_account", $authUser ? "true" : "false" );
				$objElement->setAttribute( "ip_addr", $authIP ? "true" : "false" );
				$objAuth->appendChild( $objElement );
			}
		}
		
		// add the server global array, but only if the request
		// asks for it, for security purposes

		if ( $bolHideServer == true )
		{
			$objServer = $objXml->createElement( "server" );
			$objXml->documentElement->appendChild( $objServer );
			$this->addElement( $objXml, $objServer, $_SERVER );
		}
		
		// add to the master xml document
		
		$this->addDocument( $objXml );
		
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
			// need to make sure the xml element has a valid name
			// and not something crazy with spaces or commas, etc.

			$strSafeKey = strtolower( preg_replace( '/\W/', '_', $key ) );
			
			if ( is_array( $value ) )
			{
				foreach ( $value as $strKey => $strValue )
				{
					$objElement = $objXml->createElement( $strSafeKey );
					$objElement->setAttribute( "key", $strKey );
					$objAppend->appendChild( $objElement );

					if ( is_array( $strValue ) )
					{
						// multi-dimensional arrays will be recursively added
						$this->addElement($objXml, $objElement, $strValue);
					}
					else
					{
						$objElement->nodeValue = Xerxes_Parser::escapeXml( $strValue );
					}
				}
			}
			else
			{
				$objElement = $objXml->createElement( $strSafeKey, Xerxes_Parser::escapeXml( $value ) );
				$objAppend->appendChild( $objElement );
			}
		}
	}

}

?>
