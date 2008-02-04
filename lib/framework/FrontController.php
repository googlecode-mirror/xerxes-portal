<?php

	/**
	 * Provides the framework for performing actions in the system
	 *
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package  Xerxes_Framework
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */

	class Xerxes_Framework_FrontController
	{	
		/**
		 * Fire-up the framework
		 */
		
		public static function execute()
		{			
			session_start();
			
			// we'll put all code in a try-catch block in order to show friendly error page
			// for any uncaught exceptions
			
			try
			{
				// include the framework files
				
				$this_directory = dirname(__FILE__);
				$base = basename(__FILE__);
				
				self::includeFiles($this_directory, $base);
				
				// initialize the configuration setting (Registry) and 
				// command-view mapping (ControllerMap) objects
				
				$objRegistry = Xerxes_Framework_Registry::getInstance(); $objRegistry->init();
				$objControllerMap = Xerxes_Framework_ControllerMap::getInstance(); $objControllerMap->init();
				
				$objRequest = new Xerxes_Framework_Request();	// stores data about the request and fetched results
				$objPage = new Xerxes_Framework_Page();			// assists with basic paging/navigation elements for the view
				$objError = new Xerxes_Framework_Error();		// functions for special logging or handling of errors
				
				
				####################
				#  DISPLAY ERRORS  #
				####################
				
				if ( $objRegistry->getConfig("DISPLAY_ERRORS") == true )
				{
					error_reporting(E_ALL);
					ini_set('display_errors', '1');
				}
				
				
				####################
				#     SET PATHS    #
				####################
				
				// the working directory is the instance, so any relative paths will
				// be executed in relation to the root directory of the instance
							
				// set the 'parent' directory to '../../' from here
				
				$path_to_parent = $this_directory; $path_to_parent = str_replace("\\", "/", $path_to_parent);
				$arrPath = explode("/", $path_to_parent); array_pop($arrPath); array_pop($arrPath);
				$path_to_parent = implode("/", $arrPath);
				
				// base url of the instance
				
				$web = "http://" . $objRequest->getServer('SERVER_NAME') . dirname($objRequest->getServer('PHP_SELF'));
				if ( substr($web, strlen($web) - 1, 1) == "/") $web = substr($web, 0, strlen($web) - 1);
				
				// register these values
				
				$objRegistry->setConfig("PATH_PARENT_DIRECTORY", $path_to_parent);
				$objRegistry->setConfig("BASE_URL", $web, true);
	
	
				####################
				#   INSTRUCTIONS   #
				####################
				
				// ControllerMap contains instructions for commands and views
				// based on the url parameters 'base' and 'action'
				
				$strBase = $objRequest->getProperty("base");
				$strAction = $objRequest->getProperty("action");
				
				$objControllerMap->setAction($strBase, $strAction);
	
				// add any predefined values to the request object from ControllerMap
				
				foreach ( $objControllerMap->getRequests() as $key => $value )
				{
					$objRequest->setProperty($key, $value, is_array($value));
				}
				
				
				####################
				#  ACCESS CONTROL  #
				####################
				
				// if this part of the application is restricted to a local ip range, or requires a named login, then the
				// Restrict class will check the user's ip address or if they have logged in; failure stops the flow 
				// and redirects user to a login page with the current request passed as 'return' paramater in the url			
							
				$configIP = $objRegistry->getConfig("LOCAL_IP_RANGE", false, null);
				$configAppName = $objRegistry->getConfig("APPLICATION_NAME", false, "xerxes");
				$configBaseURL = $objRegistry->getConfig("BASE_URL", true);
				$configAuthenticationPage = $objRegistry->getConfig("AUTHENTICATION_PAGE", false, "?base=authenticate&action=login");
				
				$objRestrict = new Xerxes_Framework_Restrict( $configIP, $configAppName, $configBaseURL, $configAuthenticationPage );
				
				// command line scripts will ignore access rules
				
				if ( $objRequest->isCommandLine() != true )
				{
					if ( $objControllerMap->isRestricted() == true )
					{
						if ( $objControllerMap->requiresLogin() == true )
						{
							// resource requires a valid named username
							$objRestrict->checkLogin($objRequest);
						}
						else
						{					
							// resource is resricted, but local ip range is okay
							$objRestrict->checkIP($objRequest);
						}
					}
				}
				
				// if this action is set to only be run via the command line, in order to prevent
				// web execution of potentially long-running tasks, then restrict it here
				
				if ( ! $objRequest->isCommandLine() && $objControllerMap->restrictToCLI() )
				{
					throw new Exception("cannot run command from web");
				}
	
					
				####################
				#     INCLUDES     #
				####################
				
				// files and directories that have been set to be included by the config file
				
				foreach ( $objControllerMap->getIncludes() as $path_to_include )
				{
					self::includeFiles("$path_to_parent/$path_to_include");
				}
				
				
				####################
				#       DATA       #
				####################
				
				// set-up the data by defining the root element
				
				$strDocumentElement = $objControllerMap->getDocumentElement();		
				$objRequest->setDocumentElement($strDocumentElement);
				
				// pass any configuration options defined as type=pass to the xml
				
				$objConfigXml = new DOMDocument();
				$objConfigXml->loadXML("<config />");
				
				foreach ( $objRegistry->getPass() as $key => $value )
				{
					$objElement = $objConfigXml->createElement($key, $value);
					$objConfigXml->documentElement->appendChild($objElement);
				}
				
				$objRequest->addDocument($objConfigXml);
				
				// the data will be built-up by calling one or more command classes
				// which will fetch their data based on other parameters supplied in
				// the request; returning that data as xml to a master xml dom document
				// inside the Xerxes_Framework_Request class, or in some cases specififying 
				// a url to redirect the user out
				
				foreach ( $objControllerMap->getCommands() as $arrCommand )
				{
					$strDirectory = $arrCommand[0];		// directory where the command class is located
					$strNamespace = $arrCommand[1];		// prefix namespace of the command class
					$strClassFile = $arrCommand[2];		// suffix name of the command class
					
					// first, include any parent class, assuming that the parent class will
					// follow the naming convention of having the same name as the directory
					
					$strParentClass = strtoupper(substr($strDirectory, 0, 1)) . substr($strDirectory, 1);
					
					if ( file_exists("$path_to_parent/commands/$strDirectory/$strParentClass.php") )
					{
						require_once("$path_to_parent/commands/$strDirectory/$strParentClass.php");
					}
					
					// if the specified command class exists in the commands folder, then
					// instantiate an object and execute it
					
					$strClass = $strNamespace . "_Command_" . $strClassFile;
					
					$objCommand = null;
					
					if ( file_exists("$path_to_parent/commands/$strDirectory/$strClassFile.php") )
					{
						require_once("$path_to_parent/commands/$strDirectory/$strClassFile.php");
						
						// instantiate the command class and execute it, but only
						// if it extends xerxes_framework_command
						
						$objCommand = new $strClass();
						
						if ( $objCommand instanceof Xerxes_Framework_Command )
						{
							$objCommand->execute($objRequest, $objRegistry);
						}
						else
						{
							throw new Exception("command classes must be instance of Xerxes_Framework_Command");
						}
					}
					else
					{
						throw new Exception("invalid command $strClass");
					}
				}
				
				####################
				#   COMMANDLINE    #
				####################
				
				// command line scripts should exit without calling a view? 
				
				if ( $objRequest->isCommandLine() )
				{
					exit;
				}
				
				
				####################
				#     REDIRECT     #
				####################
				
				
				// if the result of the command is a redirect, we will stop the 
				// flow and redirect the user out, unless overridden by the noRedirect
				// directive
				
				if ( $objRequest->getRedirect() != null && $objRequest->getProperty("noRediect") == null )
				{
					header("Location: " . $objRequest->getRedirect() );
					exit;
				}
				
				
				####################
				#       VIEW       #
				####################
				
				// get the xml from the request object, but exclude any server information
				// from being included if format=xml
	
				$bolShowServer = true; if ( $objRequest->getProperty("format") == "xml" ) $bolShowServer = false;
	
				$objXml = new DOMDocument();
				$objXml = $objRequest->toXML($bolShowServer);
							
				
				// RAW XML DISPLAY
				//
				// you can append 'format=xml' to the querystring to have this controller spit back
				// the response in plain xml, which can be useful in some cases, like maybe AJAX?
				
				if ( $objRequest->getProperty("format") == "xml" )
				{
					header('Content-type: text/xml');
					echo $objXml->saveXML();
				}
			
				// VIEW CODE
				//
				// ControllerMap contains instructions on what file to include for the view; typically
				// this will be an xslt file, but could be a php file if the xslt does not
				// provide enough flexibility; php page will inherit the xml dom document &
				// can go from there
						
				elseif ( file_exists( $objControllerMap->getView() ) )
				{
					if ( $objControllerMap->getViewType() != "xsl" && $objControllerMap->getViewType() != null )
					{
						require_once($objControllerMap->getView());
					}
					else
					{	
						echo $objPage->transform($objXml, $objControllerMap->getView(), null, true);
					}
				}
				else
				{
					throw new Exception("view file '" . $objControllerMap->getView() . "' does not exist");
				}
			}
			
			// we'll catch all exceptions here, but the Xerxes_Error class can perform actions
			// based on the specific type of error, such as PDOException
			
			catch ( Exception $e )
			{
				$objError->handle($e, $objRequest, $objRegistry);
			}
		}
		
		/**
		 * require_once() all php files or directories specified
		 *
		 * @param string $path		path to the file or directory
		 * @param string $exclude	a file to exclude from being included, usually this file
		 */
		
		private static function includeFiles($path, $exclude = null)
		{
			// check to see if this is a directory or a file
			
			if ( is_dir($path) )
			{
				// open a directory handle and grab all the php files 
				
				$directory = opendir($path);
				
				 while (($file = readdir($directory)) !== false)
				 {
				 	// make sure it is a php file, and exclude
				 	// any file specified by $exclude
				 	
				 	if ( strstr($file, ".php") && $file != $exclude)
				 	{
				 		require_once("$path/$file");
				 	}
				}
			}
			else
			{
				require_once($path);
			}
		}
	}

?>