<?php

/**
 * Parses and holds basic configuration information from the config file
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package  Xerxes_Framework
 */

class Xerxes_Framework_Registry
{
	protected static $xml = ""; // simple xml object copy
	protected static $config_file = "config/config";
	private static $usergroups = array(); // user groups
	private static $authentication_sources = array();
	private static $arrConfig = null; // configuration settings
	private static $arrPass = array(); // values to pass on to the view
	private static $instance; // singleton pattern

	protected function __construct()
	{
	}
	
	/**
	 * Get an instance of the class; Singleton to ensure correct data
	 *
	 * @return Xerxes_Framework_Registry
	 */
	
	public static function getInstance()
	{
		if ( empty( self::$instance ) )
		{
			self::$instance = new Xerxes_Framework_Registry( );
			self::init();
		}
		
		return self::$instance;
	}
	
	/**
	 * Initialize the object by picking up and processing the config xml file
	 * 
	 * @exception 	will throw exception if no configuration file can be found
	 */
	
	protected static function init()
	{
		if ( self::$arrConfig == null )
		{
			$file = "";
			$file_xml = self::$config_file . ".xml";
			$file_php = self::$config_file . ".php";
			
			self::$arrConfig = array ();
			
			// check if the config file has an .xml or .php extension
			
			if ( file_exists( $file_xml ) )
			{
				$file = $file_xml;
			}
			elseif ( file_exists($file_php) )
			{
				$file = $file_php;
			}
			else
			{
				throw new Exception( "could not find configuration file" );
			}
			
			self::$authentication_sources["guest"] = "guest";
			
			// get it!

			$xml = simplexml_load_file( $file );
			self::$xml = $xml;
			
			foreach ( $xml->configuration->config as $config )
			{
				$name = Xerxes_Framework_Parser::strtoupper( $config["name"] );
				$lang = (string) $config["lang"];
				
				if ( $lang != "" )
				{
					$name .= "_$lang";
				}
				
				if ( $config["xml"] == "true" ) 
				{
					// special XML config, already parsed as SimpleXML, leave it that way.
					$value = $config;          
				}
				else 
				{
					//simple string
					     
					$value = trim( ( string ) $config );
            
					// convert simple xml-encoded values to something easier 
					// for the client code to digest

					$value = str_replace( "&lt;", "<", $value );
					$value = str_replace( "&gt;", ">", $value );
					$value = str_replace( "&amp;", "&", $value );
				}
        
				// special logic for authentication_source because we can
				// have more than one. 

				if ( $name == "AUTHENTICATION_SOURCE" )
				{
					self::$authentication_sources[( string ) $config["id"]] = $value;

					// and don't overwrite the first one in our standard config array

					if ( ! empty( self::$arrConfig["AUTHENTICATION_SOURCE"] ) )
					{
						$value = "";
					}
				}
					
				if (! empty($value) )
				{
					// add it to the config array

					self::$arrConfig[$name] = $value;
					
					// types that are listed as 'pass' will be forwarded
					// on to the xml layer for use in the view
					
					if ( ( string ) $config["pass"] == "true" )
					{
						self::$arrPass[Xerxes_Framework_Parser::strtolower( $name )] = $value;
					}
				}
			}
				
			// get group information out of config.xml too
			// we just store actual simplexml elements in the 
			// self::$usergroups array.
				
			$groups = $xml->configuration->groups->group;
				
			if ( $groups != false )
			{
				foreach ( $groups as $group )
				{
					$id = ( string ) $group["id"];
					self::$usergroups[$id] = $group;
				}
			}
		}
	}
	
	/**
	 * Get a parsed configuration entry
	 *
	 * @param string $name			name of the configuration setting
	 * @param bool $bolRequired		[optional] whether function should throw exception if no value found
	 * @param mixed $default		[optional] a default value for the constant if none found
	 * @param string $lang			[optional] must include language attribute 
	 * @return mixed  Can return a String or a SimpleXMLElement, depending on whether it was XML config value. 
	 */
	
	public function getConfig($name, $bolRequired = false, $default = null, $lang = "")
	{
		$name = Xerxes_Framework_Parser::strtoupper( $name );
		
		if ( $lang != "" )
		{
			$name .= "_$lang";
		}
		
		if ( self::$arrConfig == null )
		{
			return null;
		} 
		
		if ( array_key_exists( $name, self::$arrConfig ) )
		{
			if ( self::$arrConfig[$name] == "true" )
			{
				return true;
			} 
			elseif ( self::$arrConfig[$name] == "false" )
			{
				return false;
			}
			elseif ( self::$arrConfig[$name] == "" || self::$arrConfig[$name] == null)
			{
				// let this fall to the code below
			} 
			else
			{
				return self::$arrConfig[$name];
			}
		} 

		if ( $bolRequired == true )
		{
			throw new Exception( "required configuration entry $name missing" );
		}
			
		if ( $default != null )
		{
			return $default;
		} 
		else
		{
				return null;
		}
	}
	
	/**
	 * Get all confuguration settings as array
	 *
	 * @return array
	 */
	
	public function getAllConfigs()
	{
		return self::$arrConfig;
	}
	
	/**
	 * Get all configuration settings that should be passed to the XML and the XSLT
	 *
	 * @return unknown
	 */
	
	public function getPass()
	{
		return self::$arrPass;
	}
	
	/**
	 * Set a value for a configuration, from code rather than the file
	 *
	 * @param string $key		configuration setting name
	 * @param mixed $value		value. Generally String or SimpleXMLElement. 
	 * @param bool $bolPass		[optional] whether value should be passed to XML (default false)
	 */
	
	public function setConfig($key, $value, $bolPass = false)
	{
		self::$arrConfig[Xerxes_Framework_Parser::strtoupper( $key )] = $value;
		
		if ( $bolPass == true )
		{
			self::$arrPass[Xerxes_Framework_Parser::strtolower( $key )] = $value;
		}
	}
	
	public function userGroups()
	{
		if ( self::$usergroups != null )
		{
			return array_keys( self::$usergroups );
		} 
		else
		{
			return null;
		}
	}
	
	public function getGroupDisplayName($id)
	{
		if ( array_key_exists( $id, self::$usergroups ) )
		{
			$group = self::$usergroups[$id];
			return ( string ) $group->display_name;
		} 
		else
		{
			return $id;
		}
	}
	
	public function getGroupLocalIpRanges($id)
	{
		if ( array_key_exists( $id, self::$usergroups ) )
		{
			$group = self::$usergroups[$id];
			return ( string ) $group->local_ip_range;
		} 
		else
		{
			return $id;
		}
	}
	
	// returns a simple xml object from the config

	public function getGroupXml($id)
	{
		if ( array_key_exists( $id, self::$usergroups ) )
		{
			return self::$usergroups[$id];
		} 
		else
		{
			return null;
		}
	}
	
	public function getLocale()
	{
		$code = self::getConfig("XERXES_LANGUAGE", true);
		
		$language = self::$xml->xpath("//language[@code='$code']");
		
		if ( $language === false || count($language) == 0 )
		{
			return "C";
		}
		else
		{
			return (string) $language[0]["locale"];	
		}
	}
	
	// Gets an authentication source by id. If id is null or no such
	// source can be found, returns first authentication source in config file.
	// If not even that, returns "demo".
	
	public function getAuthenticationSource($id)
	{
		$source = null;
		
		if ( ! empty( $id ) )
		{
			// if $id was set, make sure calling code didn't ask for the main auth, 
			// since that has no 'id'; but code below will return the right name
			
			if ( $id != self::getConfig( "AUTHENTICATION_SOURCE" ) )
			{
				$source = self::$authentication_sources[$id];
			}
		}
		
		if ( $source == null )
		{
			$source = self::getConfig( "AUTHENTICATION_SOURCE" );
		}
		
		if ( $source == null )
		{
			$source = "demo";
		}
		
		return $source;
	}
	
	public function getXML()
	{
		return self::$xml;
	}
	
	public function publicXML()
	{
		// pass any configuration options defined as type=pass to the xml

		$objConfigXml = new DOMDocument( );
		$objConfigXml->loadXML( "<config />" );
			
		foreach ( self::getPass() as $key => $value )
		{
			if ($value instanceof SimpleXMLElement) 
			{
				// just spit it back out again as XML
									
				$objElement = $objConfigXml->createElement($key);
				$objConfigXml->documentElement->appendChild( $objElement );
				
				foreach ($value->children() as $child) 
				{
					// need to convert to DOMDocument.
					$domValue = dom_import_simplexml($child);
					$domValue =  $objConfigXml->importNode($domValue, true);
					$objElement->appendChild($domValue);
				}                                
			}
			else 
			{
				// simple string value
				
				$objElement = $objConfigXml->createElement( $key, Xerxes_Framework_Parser::escapeXml($value) );
				$objConfigXml->documentElement->appendChild( $objElement );
			}
		}
		
		return $objConfigXml;
	}
}
?>