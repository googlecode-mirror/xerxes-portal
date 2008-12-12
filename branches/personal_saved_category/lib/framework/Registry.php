<?php

/**
 * Parses and holds basic configuration information from the config file
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version 1.1
 * @package  Xerxes_Framework
 */

class Xerxes_Framework_Registry
{
	private $xml = ""; // simple xml object copy
	private $file = "config/config.xml"; // settings configuration file
	private $php = "config/config.php"; // settings configuration in a php file
	private $usergroups = array ( ); // user groups
  private $authentication_sources = array();
	private $arrConfig = null; // configuration settings
	private $arrPass = array ( ); // values to pass on to the view
	private static $instance; // singleton pattern
	

	private function __construct()
	{
	}
	
	/**
	 * Get an instance of the file; Singleton to ensure correct data
	 *
	 * @return Xerxes_Framework_Registry
	 */
	
	public static function getInstance()
	{
		if ( empty( self::$instance ) )
		{
			self::$instance = new Xerxes_Framework_Registry( );
		}
		
		return self::$instance;
	}
	
	/**
	 * Initialize the object by picking up and storing the config xml file
	 * 
	 * @exception 	will throw exception if no configuration file can be found
	 */
	
	public function init()
	{
		// after the initial parsing of the xml file, we'll save
		// this object in session (?), so make sure we only parse the
		// file if the configuration array is missing
		

		if ( $this->arrConfig == null )
		{
			$bolUsePHP = false;
			
			$this->arrConfig = array ( );
			
			// there is no xml file
			

			if ( ! file_exists( $this->file ) )
			{
				// assume we'll use the php file, unless that doesn't exist either,
				// in which case we need to throw an error
				

				$bolUsePHP = true;
				
				if ( ! file_exists( $this->php ) )
				{
					throw new Exception( "could not find configuration file" );
				}
			}
			
			// use the php file
			

			if ( $bolUsePHP == true )
			{
				require_once ($this->php);
				
				$arrPass = Xerxes_Config::$pass;
				
				$class = new ReflectionClass( 'Xerxes_Config' );
				
				// add it to the config array
				

				foreach ( $class->getConstants() as $key => $text )
				{
					$name = strtoupper( $key );
					$value = trim( $text );
					
					if ( $value != "" )
					{
						//  add it to the config array
						

						$this->arrConfig[$name] = $value;
						
						// entires that are listed in the 'pass' array will be forwarded
						// on to the xml layer for use in the view
						

						if ( in_array( $key, $arrPass ) )
						{
							$this->arrPass[strtolower( $name )] = $value;
						}
					}
				}
			} else
			{
				// use the xml file	
				
				$xml = simplexml_load_file( $this->file );
				$this->xml = $xml;
				
				foreach ( $xml->configuration->config as $config )
				{
					$name = strtoupper( $config["name"] );
					$value = trim( ( string ) $config );
					
					// convert simple xml-encoded values to something easier 
					// for the client code to digest
					

					$value = str_replace( "&lt;", "<", $value );
					$value = str_replace( "&gt;", ">", $value );
					$value = str_replace( "&amp;", "&", $value );
					
          // special logic for authentication_source because we can
          // have more than one. 
          if ( $name == "AUTHENTICATION_SOURCE") {
            $this->authentication_sources[ (string) $config["id"] ] = $value;
            // And don't overwrite the first one in our standard config array
            if (! empty($this->arrConfig["AUTHENTICATION_SOURCE"])) {
              $value = "";
            }
          }
        
					if ( $value != "" )
					{
    
						// add it to the config array

						$this->arrConfig[$name] = $value;
						
						// types that are listed as 'pass' will be forwarded
						// on to the xml layer for use in the view
						

						if ( ( string ) $config["pass"] == "true" )
						{
							$this->arrPass[strtolower( $name )] = $value;
						}
					}
				}
        
				
				// get group information out of config.xml too
				// we just store actual SimpleXML elements in the 
				// $this->usergroups array.

				$groups = $xml->configuration->groups->group;
				
				if ( $groups != false )
				{
					foreach ( $groups as $group )
					{
						$id = ( string ) $group["id"];
						$this->usergroups[$id] = $group;
					}
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
	 * @return mixed
	 */
	
	public function getConfig($name, $bolRequired = false, $default = null)
	{
		$name = strtoupper( $name );
		
		if ( $this->arrConfig == null )
		{
			return null;
		} elseif ( array_key_exists( $name, $this->arrConfig ) )
		{
			if ( $this->arrConfig[$name] == "true" )
			{
				return true;
			} elseif ( $this->arrConfig[$name] == "false" )
			{
				return false;
			} else
			{
				return $this->arrConfig[$name];
			}
		} else
		{
			if ( $bolRequired == true )
			{
				throw new Exception( "required configuration entry $name missing" );
			}
			
			if ( $default != null )
			{
				return $default;
			} else
			{
				return null;
			}
		}
	}
	
	/**
	 * Get all confuguration settings as array
	 *
	 * @return array
	 */
	
	public function getAllConfigs()
	{
		return $this->arrConfig;
	}
	
	/**
	 * Get all configuration settings that should be passed to the XML and the XSLT
	 *
	 * @return unknown
	 */
	
	public function getPass()
	{
		return $this->arrPass;
	}
	
	/**
	 * Set a value for a configuration, from code rather than the file
	 *
	 * @param string $key		configuration setting name
	 * @param mixed $value		value
	 * @param bool $bolPass		[optional] whether value should be passed to XML (default false)
	 */
	
	public function setConfig($key, $value, $bolPass = false)
	{
		$this->arrConfig[strtoupper( $key )] = $value;
		
		if ( $bolPass == true )
		{
			$this->arrPass[strtolower( $key )] = $value;
		}
	}
	
	/**
	 * Get a value from the PHP configuration file
	 *
	 * @param string $class			class name enclosing the configuration settings
	 * @param string $key			configuration constant name
	 * @return mixed				configuration entry value
	 */
	
	private function getHidden($class, $key)
	{
		$value = "";
		$configName = "$class::$key";
		
		if ( ! defined( $configName ) )
			throw new Exception( "php config file lacks entry for $key" );
		
		$value = constant( $configName );
		
		if ( is_bool( $value ) )
		{
			if ( $value == true )
			{
				return "true";
			} else
			{
				return "false";
			}
		} else
		{
			return $value;
		}
	}
	
	public function userGroups()
	{
		if ( $this->usergroups != null )
		{
			return array_keys( $this->usergroups );
		} else
		{
			return null;
		}
	}
	
	public function getGroupDisplayName($id)
	{
		if ( array_key_exists( $id, $this->usergroups ) )
		{
			$group = $this->usergroups[$id];
			return ( string ) $group->display_name;
		} else
		{
			return $id;
		}
	}
	
	public function getGroupLocalIpRanges($id)
	{
		if ( array_key_exists( $id, $this->usergroups ) )
		{
			$group = $this->usergroups[$id];
			return ( string ) $group->local_ip_range;
		} else
		{
			return $id;
		}
	}
	
	// returns a simple xml object from the config
	
	public function getGroupXml($id)
	{
		if ( array_key_exists( $id, $this->usergroups ) )
		{
			return $this->usergroups[$id];
		} 
		else
		{
			return null;
		}
	}
  
  // Gets an authentication source by id. If id is null or no such
  // source can be found, returns first authentication source in config file.
  // If not even that, returns "demo". 
  public function getAuthenticationSource($id) {
    $source = null;
    if (! empty($id)) $source = $this->authentication_sources[$id];
    if ($source == null) {
      $source = $this->getConfig("AUTHENTICATION_SOURCE");
    }
    if ($source == null) {
      $source = "demo";
    }
    return $source;
  }
	
	public function getXML()
	{
		return $this->xml;
	}
}
?>