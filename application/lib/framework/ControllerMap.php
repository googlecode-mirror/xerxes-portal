<?php

/**
 * Parses the required configuration files and registers the appropriate commands and views
 * for a given request
 * 
 * @author David Walker
 * @copyright 2008 California State University
 * @version $Id$
 * @package  Xerxes_Framework
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 *
 */

class Xerxes_Framework_ControllerMap
{
	private $file = "config/actions.xml";	// actions configuration file
	
	public $xml = null;					// simplexml object containing instructions for the actions
	private $path_map = null;			// xerxes_framework_pathmap object.

	private $bolRestricted = false;		// whether action should require authentication
	private $bolLogin = false;			// whether action requires an explicit login
	private $bolCLI = false;			// whether action should be restricted to command line interface
	
	private $view_type = "";			// the folder the file lives in, important if it is 'xsl'
	private $view_file = "";			// name of the file to use in view
	private $version;					// xerxes version number
	
	private static $instance;			// singleton pattern	
	
	private function __construct() { }
	
	/**
	 * Get an instance of this class; this is singleton to ensure consistency 
	 *
	 * @return Xerxes_Framework_ControllerMap
	 */
	
	public static function getInstance()
	{
		if ( empty( self::$instance) )
		{
			self::$instance = new Xerxes_Framework_ControllerMap();
			$object = self::$instance;
			$object->init();
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
		// don't parse it twice
		
		if ( ! $this->xml instanceof SimpleXMLElement )
		{
			$distro = XERXES_APPLICATION_PATH . "lib/" . $this->file;	
			
			// distro actions.xml
			
			if ( file_exists($distro) )
			{
				$this->xml = simplexml_load_file($distro);
				$this->version = (string) $this->xml["version"];
			}
			else
			{
				throw new Exception("could not find configuration file");
			}

			// local actions.xml overrides, if any
			
			if ( file_exists($this->file) )
			{
				$local = simplexml_load_file($this->file);
				
				if ( $local === false )
				{
					throw new Exception("could not parse local actions.xml");
				}
				
				$this->addSections($this->xml, $local );
			}				
		}

		// header("Content-type: text/xml"); echo $this->xml->asXML(); exit;	
	}

	/**
	 * Adds sections from the local actions.xml file into the master one
	 */
	
	private function addSections( SimpleXMLElement $parent, SimpleXMLElement $local )
	{
		$master = dom_import_simplexml ( $parent );
		
		// global commands
		
		$global = $local->global;
		
		if ( count($global) > 0 )
		{
			$new = dom_import_simplexml ( $global );
			$import = $master->ownerDocument->importNode ( $new, true );
			$master->ownerDocument->documentElement->appendChild ( $import );				
		}

		// sections
		
		// import then in the commands element
		
		$ref = $master->getElementsByTagName( "commands" )->item ( 0 );
		
		if ($ref == null)
		{
			throw new Exception ( "could not find commands insertion node in actions.xml" );
		}

		foreach ( $local->commands->children() as $section )
		{
			$new = dom_import_simplexml ( $section );
			$import = $master->ownerDocument->importNode ( $new, true );
			$ref->appendChild ( $import );
		}
	}
		
	/**
	 * Process the action in the incoming request and parse the xml file to determine
	 * the necessary includes, command classes, and view to call. 
	 * Also translates path to properties in command-specific ways. 
	 * Adds properties to Xerxes Request object. 
	 *
	 * @param string $section		'base' in the url or cli paramaters, corresponds to 'section' in xml
	 * @param string $action		'action' in the url or cli paramaters, corresponds to 'action' in the xml
	 */
	
	public function setAction( $section, $action  )
	{
		
	}
	
	/**
	 * Path Mapping object
	 *
	 * @return Xerxes_Framework_PathMap
	 */	
	
	public function getPathMapObject()
	{
		if ( ! $this->path_map )
		{
			$this->path_map = new Xerxes_Framework_PathMap($this->xml);
		}
		
		return $this->path_map;
	}
	
	/**
	 * Get the header for a user-defined format
	 *
	 * @param string $format	name of the format
	 * @return stringq			http header to be output for format
	 */
	
	public function getFormat($format)
	{
		$formats = $this->xml->xpath("//formats/format[@name='$format']");
		
		if ( $formats != false )
		{
			return (string) $formats[0]["header"];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Whether this portion of the application should be restricted by IP adddress
	 *
	 * @return bool
	 */
		
	public function isRestricted()
	{
		return $this->bolRestricted;
	}
	
	/**
	 * Whether this part of the application required a valid, named login
	 *
	 * @return bool
	 */
	
	public function requiresLogin()
	{
		return $this->bolLogin;
	}
	
	/**
	 * Get the list of commands
	 *
	 * @return array	each array element consists of another array(directory, namespace, command name)
	 */
	
	public function getCommands()
	{
		return $this->arrCommands;
	}
	
	/**
	 * Get any parameters that should be included in the request 
	 *
	 * @return array
	 */
	
	public function getRequests()
	{
		return $this->arrRequest;
	}
	
	/**
	 * Get the file type of the view, either php or xml
	 *
	 * @return string
	 */
	
	public function getViewType()
	{
		return $this->strViewType; 
	}
	
	/**
	 * Whether this command should be restricted to command line interface
	 * only to prevent execution via the web
	 *
	 * @return bool		true if should be restricted
	 */
	
	public function restrictToCLI()
	{
		return $this->bolCLI;
	}
	
	/**
	 * Get the location of the view, relative to the root of the instance
	 *
	 * @return string
	 */
	
	public function getView()
	{
		return $this->strViewFile;
	}

	/**
	 * Get the Xerxes version number
	 */
	
	public function getVersion()
	{
		return $this->version;
	}
}
