<?php

	/**
	 * Parses the required configuration files and registers the appropriate commands and views
	 * for a given request
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package  Xerxes_Framework
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 *
	 */

	class Xerxes_Framework_ControllerMap
	{
		private $file = "config/actions.xml";	// actions configuration file
		
		private $xml = null;				// simplexml object containing instructions for the actions
		private static $instance;			// singleton pattern
	
		private $strDocumentElement = "";	// name of the document element for the xml
		private $bolRestricted = false;		// whether action should require authentication
		private $bolLogin = false;			// whether action requires an explicit login
		private $bolCLI = false;			// whether action should be restricted to command line interface
		
		private $arrCommands = array();		// list of commands
		private $arrRequest = array();		// list of request params
		private $arrIncludes = array();		// directories and files to include

		private $strViewType = "";			// the folder the file lives in, important if it is 'xsl'
		private $strViewFile = "";			// name of the file to use in view
				
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
			// this object in session, so make sure we only parse the
			// file if the xml is gone
			
			if ( ! $this->xml instanceof SimpleXMLElement )
			{
				if ( file_exists($this->file) )
				{
					$this->xml = simplexml_load_file($this->file);
				}
				else
				{
					throw new Exception("could not find configuration file");
				}
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
     * @param Xerxes_Framework_Request @xerxes_request The operative xerxes request object, used for getting properties from path in action specific ways.
		 */
		
		public function setAction( $section, $action, $xerxes_request  )
		{
			// get include files and directories for the entire application
			
			$includes = $this->xml->commands->include;
			
			if ( $includes != false )
			{
				foreach ( $includes as $include )
				{
					array_push($this->arrIncludes, (string) $include );
				}
			}
			
			// set a default section if none supplied
			
			if ( $section == "" )
			{
				$section = (string) $this->xml->commands->default->section;
				$action = (string) $this->xml->commands->default->action;
				
				$this->addRequest("base", $section);
				$this->addRequest("action", $action);
				
				if ($section == null || $action == null )
				{
					throw new Exception("no default action defined");
				}
			}
			
			$strRestricted = "";		// string to be converted to bool
			$strLogin = "";				// string to be converted to bool
			$strDirectory = "";			// directory of the command class
			$strNamespace = "";			// namespace of the command class
			
			// make sure a section is defined
			
			$sections = $this->xml->xpath("commands/section[@name='$section']");
			
			if ( $sections == false )
			{
				throw new Exception("no section defined for '$section'");
			}
			
			foreach ( $sections as $section )
			{
				// get the basic configurations that apply to the section, which may
				// be overriden by more specific entries in the actions
				
				$this->strDocumentElement = (string) $section["documentElement"];
				
				$strDirectory = (string) $section["directory"];
				$strNamespace = (string) $section["namespace"];
				$strRestricted = (string) $section["restricted"];
				$strLogin = (string) $section["login"];
				
				// get additionally defined includes
				
				$section_includes = $section->include;
				
				if ( $section_includes !== false )
				{
					foreach ( $section_includes as $include )
					{
						array_push($this->arrIncludes, (string) $include );
					}
				}
				
        // Section may supply a path to property map for the section. 
        $section_path_map = $section->pathParamMap;
        
				// if no action is supplied, then simply grab the first command
				// entry; you may well pay for this later!
				
				$xpath = "";
				$actions = array();
				
				if ( $action == "")
				{
					$xpath = "action[position() = 1]";
				}
				else
				{
					$xpath = "action[@name='$action']";
				}
						
				$actions = $section->xpath($xpath);
				
				if ( $action == "")
				{
					$action = (string) $actions[0]["name"];
					$this->addRequest("action", $action);				
				}
				
				foreach ( $actions as $action )
				{
					// take the section directory and namespace by default
					
					$strCommandDirectory = $strDirectory;
					$strCommandNamespace = $strNamespace;
					
					// override any section values with these
					
					if ( $action["documentElement"] != null ) $this->strDocumentElement = (string) $action["documentElement"];
					if ( $action["directory"] != null ) $strCommandDirectory = (string) $action["directory"];
					if ( $action["namespace"] != null ) $strCommandNamespace = (string) $action["namespace"];
					if ( $action["restricted"] != null ) $strRestricted = (string) $action["restricted"];
					if ( $action["login"] != null ) $strLogin = (string) $action["login"];
					
					// check to see if this command should be restricted to the command line
					
					if ( $action["cli"] != null )
					{
						$this->bolCLI = true;
					}
					
					// get additionally defined includes
				
					$action_includes = $action->include;
					
					if ( $action_includes !== false )
					{
						foreach ( $action_includes as $include )
						{
							array_push($this->arrIncludes, (string) $include );
						}
					}
          
          //If pretty uris are on, then additional command-specific properties
          // from path may be specified in the action config file. Execute
          // them.
          $obj_registry = Xerxes_Framework_Registry::getInstance();
          if ($obj_registry->getConfig("pretty_uris", false)) {
            $path_map = $section_path_map;
            if ( $action->pathParamMap ) {
              $path_map = $action->pathParamMap;
            }
            
            if ($path_map) {
              foreach ($path_map->mapEntry as $map_entry) {
                
                $xerxes_request->mapPathToProperty((string) $map_entry['pathIndex'], (string) $map_entry['property']);
              }
            }
          }
					                              
					// commands          
					
					foreach ( $action->command as $command )
					{
						if ( $command["directory"] != null ) $strCommandDirectory = (string) $command["directory"];
						if ( $command["namespace"] != null ) $strCommandNamespace = (string) $command["namespace"];						          
                        
						// add it to the list of commands
						
						$arrCommand = array($strCommandDirectory, $strCommandNamespace, (string) $command);
						
						$this->addCommand($arrCommand);
					}
					
					// request data
					
					foreach ( $action->request as $request )
					{
						$this->addRequest((string) $request["name"], (string) $request);
					}
					
					// view
					
					$this->strViewFile = (string) $action->view;
					$type = (string) $action->view["type"];
					if ( $type != null ) $this->strViewType = $type;
				}
			}
			
			// set the strings to boolean
			
			if ( $strRestricted == "true") $this->bolRestricted = true;
			if ( $strLogin == "true") $this->bolLogin = true;
      
      // add any predefined values to the request object from ControllerMap
				
      foreach ( $this->getRequests() as $key => $value )
      {
        $xerxes_request->setProperty($key, $value, is_array($value));
      }
		}
		
		/**
		 * Document element that the master xml should contain
		 *
		 * @return string
		 */
		
		public function getDocumentElement()
		{
			return $this->strDocumentElement;
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
		 * Get a list of directories or files that should be included for the request
		 *
		 * @return array
		 */
		
		public function getIncludes()
		{
			return array_unique($this->arrIncludes);
		}
		
		/**
		 * Add the command to the command array
		 *
		 * @param string $value		command name
		 */
		
		private function addCommand($value)
		{
			// make sure we don't accidentally add the same 
			// command twice by only adding unique values
			
			if ( ! in_array($value, $this->arrCommands) )
			{
				array_push($this->arrCommands, $value);               
			}
		}
		
		/**
		 * Add values to the request paramater
		 *
		 * @param string $key		paramater attribute
		 * @param string $value		paramater value
		 */
		
		private function addRequest($key, $value)
		{
			// make sure to add items to the array without
			// overriding earlier ones by converting existing
			// values to arrays and pushing the new value on
			
			if ( array_key_exists($key, $this->arrRequest) )
			{
				if ( is_array( $this->arrRequest[$key] ) )
				{
					array_push($this->arrRequest[$key], $value);
				}
				else
				{
					$this->arrRequest[$key] = array($this->arrRequest[$key], $value);
				}
			}
			else
			{
				$this->arrRequest[$key] = $value;
			}
		}
	}


?>