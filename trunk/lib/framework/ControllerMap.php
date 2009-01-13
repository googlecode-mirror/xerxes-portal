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
		private $path_map = null;			// xerxes_framework_pathmap object.
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
		 * @param string $strSection		'base' in the url or cli paramaters, corresponds to 'section' in xml
		 * @param string $strActin			'action' in the url or cli paramaters, corresponds to 'action' in the xml
		 * @param Xerxes_Framework_Request @xerxes_request The operative xerxes request object, used for getting properties from path in action specific ways.
		 */
		
		public function setAction( $strSection, $strAction, $xerxes_request  )
		{
			// get include files and directories for the entire application
			// as well as those for specific sections
			
			$includes = $this->xml->xpath("//commands/include|//section[@name='$strSection']/include");
			
			if ( $includes != false )
			{
				foreach ( $includes as $include )
				{
					array_push($this->arrIncludes, (string) $include );
				}
			}
			
			// get global commands that should be included with every request
			
			$global_commands = $this->xml->commands->global->command;
			
			if ( $global_commands != false )
			{
				foreach ( $global_commands as $global_command )
				{
					$arrGlobalCommand = array((string) $global_command["directory"], (string) $global_command["namespace"], (string) $global_command);
						
					$this->addCommand($arrGlobalCommand);
				}
			}
			
			// set a default section if none supplied
			
			if ( $strSection == "" )
			{
				$strSection = (string) $this->xml->commands->default->section;
				$strAction = (string) $this->xml->commands->default->action;
				
				$this->addRequest("base", $strSection);
				$this->addRequest("action", $strAction);
				
				if ($strSection == null || $strAction == null )
				{
					throw new Exception("no default action defined");
				}
			}
			
			$strRestricted = "";		// string to be converted to bool
			$strLogin = "";				// string to be converted to bool
			$strDirectory = "";			// directory of the command class
			$strNamespace = "";			// namespace of the command class
			
			// make sure a section is defined
			
			$sections = $this->xml->xpath("commands/section[@name='$strSection']");
			
			if ( $sections == false )
			{
				throw new Exception("no section defined for '$strSection'");
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
				
				// if no action is supplied, then simply grab the first command
				// entry; you may well pay for this later!
				
				$xpath = "";
				
				if ( $strAction == "")
				{
					$xpath = "action[position() = 1]";
				}
				else
				{
					$xpath = "action[@name='$strAction']";
				}

				$actions = array();
				$actions = $section->xpath($xpath);
				
				// if action was empty, we'll also need to grab the name out of the 
				// resulting xpath query
				
				if ( $strAction == "")
				{
					$action = (string) $actions[0]["name"];
					$this->addRequest("action", $action);
				}
				
				// didn't find anything, so let's just set some defaults to allow for 
				// simple convention
				
				if ( $actions == false )
				{
					// command follows the name of the section plus name of the action, 
					// with the first letter of each capitalized.  if there is a dash or 
					// underscore, remove those and also capitalize the fist letter
					 
					$strDefaultCommand = strtoupper(substr($strDirectory,0,1) ) . substr($strDirectory,1);
					
					$arrActionParts = split("-|_", $strAction);
					
					foreach ( $arrActionParts as $strActionPart )
					{
						$strDefaultCommand .= strtoupper(substr($strActionPart,0,1) ) . substr($strActionPart,1);
					}
					
					$arrCommand = array($strDirectory, $strNamespace, $strDefaultCommand);
					$this->addCommand($arrCommand);
					
					// view is similar but remains lower-case, and flip any dashes to underscore
					
					$strActionFile = str_replace("-", "_", $strAction);
					
					$this->strViewFile = "xsl/" . $strDirectory . "_" . $strActionFile . ".xsl";
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
					
					// commands
					
					foreach ( $action->command as $command )
					{
						$strLocalCommandDirectory = $strCommandDirectory;
						$strLocalCommandNamespace = $strCommandNamespace;
						
						if ( $command["directory"] != null ) $strLocalCommandDirectory = (string) $command["directory"];
						if ( $command["namespace"] != null ) $strLocalCommandNamespace = (string) $command["namespace"];
						
						// add it to the list of commands
						
						$arrCommand = array($strLocalCommandDirectory, $strLocalCommandNamespace, (string) $command);
						
						$this->addCommand($arrCommand);
					}
					
					// request data
					
					foreach ( $action->request as $request )
					{
						$this->addRequest((string) $request["name"], (string) $request);
					}
					
					// view
					
					// by default we'll take the first view file in the action
					
					$this->strViewFile = (string) $action->view;
          $type = (string) $action->view["type"];
					if ( $type != null ) $this->strViewType = $type;
          
					// if there is a format={format-name} in the request and a seperate
					// <view fomat="{format-name}"> that matches it, we'll take that as the
					// view
					
					$format = $xerxes_request->getProperty("format");
					
					if ( $format != null )
					{
						foreach ( $action->view as $view )
						{
							if ( $view["format"] == $format)
							{
								$this->strViewFile = $view;
                $this->strViewType = $view["type"];
							}
						}
					}
				}
			}
			
			// set the strings to boolean
			
			if ( $strRestricted == "true") $this->bolRestricted = true;
			if ( $strLogin == "true") $this->bolLogin = true;
			
			// add any predefined values to the request object from ControllerMap
			
			foreach ( $this->getRequests() as $key => $value )
			{
				$xerxes_request->setProperty($key, $value);
			}
		}
		
		/**
		 * Path Mapping object
		 *
		 * @return Xerxes_Framework_PathMap
		 */	
		
		public function path_map_obj()
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
  
	/**
	 * Keeps track of mapping path components to query properties on an
	 * action by action basis. Used only when pretty uris are turned on.
	 * Usually used by ControllerMap, and not accessed directly by any 
	 * other code. Gets mappings from actions.xml.
	 * 
	 * Caches answer for length of life of ControllerMap/PathMap, but that's
	 * currently just life of a request. This works well enough it looks like. 
	 * 
	 * @author Jonathan Rochkind
	 * @copyright 2008 Johns Hopkins University
	 * @version 1.1
	 * @package  Xerxes_Framework
	 * @license http://www.gnu.org/licenses/
	 *
	 */
	
	class Xerxes_Framework_PathMap
	{
		private $actions_xml = null; 		// simplexml object containing instructions for the actions
		private $mapsByProperty = array();	// array keyed by section name or "section/action"
											// value is an array mapping properties (key) to path indexes (value) 
		private $mapsByIndex = array();		// array keyed by section name or "section/action"
											// value is an array mapping path indexes (key) to properties (value) 
		
		/**
		 * Constructor
		 * 
		 * @param SimpleXML $actions_xml_arg		SimpleXML object of actions.xml directives
		 */
		
		public function __construct($actions_xml_arg)
		{ 
			$this->actions_xml = $actions_xml_arg;
		}
		
		/**
		 * Retrieve an array of paramater-name-to-path-index mappings for a given action
		 *
		 * @param string $section		the section to find the action
		 * @param string $action		the specific action being called
		 * @return array				array in form of [paramater_name] => position_in_path
		 */
		
		public function propertyToIndexMap($section, $action)
		{
			$key_name = "$section/$action";
			
			if (! array_key_exists($key_name, $this->mapsByProperty) )
			{
				$this->buildMapForAction( $section, $action );
			}
			
			return $this->mapsByProperty[$key_name];
		}

		/**
		 * Retrieve an individual path-index for a given action's paramater-name
		 *
		 * @param string $section			the section to find the action
		 * @param string $action			the specific action being called
		 * @param string $property_name		property name
		 * @return mixed					[int] path index number or [null] if no mapping exists
		 */
		
		public function indexForProperty($section, $action, $property_name)
		{
			$map = $this->propertyToIndexMap($section, $action);
					 
			if ( array_key_exists($property_name, $map) )
			{
				return $map[$property_name];
			}
			else
			{
				return null;
			}
		}
		
		/**
		 * Retrieve an array of path-index-to-parameter-name mappings for a given action
		 *
		 * @param string $section		the section to find the action
		 * @param string $action		the specific action being called
		 * @return array				array in form of [position_in_path] => paramater_name
		 */
		
		public function indexToPropertyMap($section, $action)
		{
			$key_name = "$section/$action";
			
			if (! array_key_exists($key_name, $this->mapsByIndex ) )
			{
				$this->buildMapForAction( $section, $action );
			}
			
			return $this->mapsByIndex[$key_name];
		}
		
		/**
		 * Retrieve an individual paramater-name for a given action's path-index
		 *
		 * @param string $section		the section to find the action
		 * @param string $action		the specific action being called
		 * @param int $path_index		the 0-based numbered index of the path
		 * @return mixed				[string] paramater name or [null] if no mapping exists
		 */
		
		public function propertyForIndex($section, $action, $path_index)
		{
			$map = $this->indexToPropertyMap($section, $action);

			if ( array_key_exists($path_index, $map) )
			{
				return $map[$path_index];
			}
			else
			{
				return null;
			}
		}
		
		/**
		 * Does the actual work of extracting the pathIndex mappings
		 *
		 * @param string $section		the section to find the action
		 * @param string $action		the specific action being called
		 * @return null					extracts the values to member variables
		 */
		
		private function buildMapForAction($section, $action)
		{
			$map_xml = null;
			$key_name = "$section/$action";

			// if no configed path param, empty array will be stored, good.
			
			$this->mapsByProperty[$key_name] = array();
			$this->mapsByIndex[$key_name] = array();
					 
			// section may supply a default param-map for the section. 

			$section_paths = $this->actions_xml->xpath("commands/section[@name='$section']/pathParamMap");
			
			// action may provide a param-map as well
			
			$action_paths = $this->actions_xml->xpath("commands/section[@name='$section']/action[@name='$action']/pathParamMap");
			
			// assign the default section map if present
			
			if ( $section_paths != false )
			{
				$map_xml = $section_paths[0];
				
				foreach ($map_xml->mapEntry as $map_entry)
				{
					$iIndex = (integer) $map_entry['pathIndex'];
					$strProperty = (string) $map_entry['property'];
					
					$this->mapsByProperty[$key_name][$strProperty] = $iIndex;
					$this->mapsByIndex[$key_name][$iIndex] = $strProperty;
				}
			}
			
			// and add the local map on top if also present. Important that
			// action overrides section, not replaces! 
			
			if ( $action_paths != false )
			{
				$map_xml = $action_paths[0];
				
				foreach ($map_xml->mapEntry as $map_entry)
				{
					$iIndex = (integer) $map_entry['pathIndex'];
					$strProperty = (string) $map_entry['property'];
					
          //Remove conflicting session declerations
          $oldProperty = $this->propertyForIndex($section, $action, $iIndex);
          if ($oldProperty)     
            unset($this->mapsByProperty[$key_name][$oldProperty]);
          $oldIndex = $this->indexForProperty($section, $action, $strProperty);
          if ($oldIndex)
            unset($this->mapsByIndex[$key_name][$oldIndex]);
          
          //And add new ones
					$this->mapsByProperty[$key_name][$strProperty] = $iIndex;
					$this->mapsByIndex[$key_name][$iIndex] = $strProperty;
				}
			}
		}
	}


?>
