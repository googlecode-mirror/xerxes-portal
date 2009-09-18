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
	
	private static $parent_directory = "";
	
	public static function execute()
	{		
		// calculate current file, this directory

		$this_directory = dirname( __FILE__ );
		$base = basename( __FILE__ );
		
		// calculate root directory of the app ../../ from here

		$path_to_parent = $this_directory;
		$path_to_parent = str_replace( "\\", "/", $path_to_parent );
		$arrPath = explode( "/", $path_to_parent );
		
		array_pop( $arrPath );
		array_pop( $arrPath );
		
		$path_to_parent = implode( "/", $arrPath );
		
		// here so other framework files can reference it
		
		self::$parent_directory = $path_to_parent;
				
		// include the framework files
		
		self::includeFiles( $this_directory, $base );
		
		// initialize the configuration setting (Registry) and 
		// command-view mapping (ControllerMap) objects

		$objRegistry = Xerxes_Framework_Registry::getInstance();
		$objRegistry->init();

		$objControllerMap = Xerxes_Framework_ControllerMap::getInstance();
		$objControllerMap->init();
		
		// give our session a name to keep sessions distinct between multiple
		// instances of xerxes on one server.  use base_path (preferably) or
		// application_name config directives.
		
		$path_key = preg_replace( '/\W/', '_', $objRegistry->getConfig( "base_web_path", false ) );
		
		$session_name = "xerxessession_" . $path_key;
		
		session_name( $session_name );
		session_start();
		
		// utility classes
		
		$objRequest = new Xerxes_Framework_Request( ); // processes the incoming request
		$objPage = new Xerxes_Framework_Page($objRequest, $objRegistry); // assists with basic paging/navigation elements for the view
		$objError = new Xerxes_Framework_Error( ); // functions for special logging or handling of errors
		
		// print_r($_REQUEST); print_r($objRequest->getAllProperties()); exit;
		
		// we'll put the remaining code in a try-catch block in order to show friendly error page
		// for any uncaught exceptions
		
		try
		{
			####################
			#  DISPLAY ERRORS  #
			####################
			
			if ( $objRegistry->getConfig( "DISPLAY_ERRORS" ) == true )
			{
				error_reporting( E_ALL );
				ini_set( 'display_errors', '1' );
			}
			
			####################
			#   DEFAULTS       #
			####################
			
			// make sure application_name is passthrough, and has a value.

			$objRegistry->setConfig( "application_name", $objRegistry->getConfig( "APPLICATION_NAME", false, "Xerxes" ), true );
			
			####################
			#     SET PATHS    #
			####################

			### reverse proxy
			
			// check to see if xerxes is running behind a reverse proxy and swap
			// host and remote ip here with their http_x_forwarded counterparts;
			// but only if configured for this, since client can spoof the header 
			// if xerxes is not, in fact, behind a reverse proxy
			
			if ( $objRegistry->getConfig("REVERSE_PROXY", false, false ) == true )
			{
				$forward_host = $objRequest->getServer('HTTP_X_FORWARDED_HOST');
				$forward_address = $objRequest->getServer('HTTP_X_FORWARDED_FOR');
				
				if ( $forward_host != "" )
				{
					$objRequest->setServer('SERVER_NAME', $forward_host);
				}
				
				// last ip address is the user's
				
				if ( $forward_address != "" )
				{
					$arrIP = explode(",", $forward_address);
					$objRequest->setServer('REMOTE_ADDR', trim(array_pop($arrIP)));
				}		
			}
			
			// the working directory is the instance, so any relative paths will
			// be executed in relation to the root directory of the instance
						
			$working_dir = getcwd();
			$working_dir = str_replace( "\\", "/", $working_dir );
			
			// full web path
			//
			// NOTE :if you change this code  make sure you make a corresponding
			// change in lib/framework/Error.php, since there is redundant code
			// there in case something goes horribly wrong and we need to set the
			// web path for proper display of a (friendly) error page 
			
			$base_path = $objRegistry->getConfig( 'BASE_WEB_PATH', false, "" );
			$this_server_name = $objRequest->getServer( 'SERVER_NAME' );
			
			// check for a non-standard port
						
			$port = $objRequest->getServer( 'SERVER_PORT' );
			
			if ( $port == 80 || $port == 443 )
			{
			    $port = "";
			}
			else
			{
			    $port = ":" . $port;
			}
			
			$protocol = "http://";
			
			if ( $objRequest->getServer("HTTPS") )
			{
				$protocol = "https://";
			}
			
			$web = $protocol . $this_server_name . $port;			
			
			// register these values
			
			$objRegistry->setConfig( "SERVER_URL", $web );
			$objRegistry->setConfig( "PATH_PARENT_DIRECTORY", $path_to_parent );
			$objRegistry->setConfig( "APP_DIRECTORY", $working_dir );
			$objRegistry->setConfig( "BASE_URL", $web . $base_path , true );
			

			####################
			#   INSTRUCTIONS   #
			####################
			
			// ControllerMap contains instructions for commands and views
			// based on the url parameters 'base' and 'action'
			
			$strBase = $objRequest->getProperty( "base" );
			$strAction = $objRequest->getProperty( "action" );
			
			$objControllerMap->setAction( $strBase, $strAction, $objRequest );
			
			####################
			#  ACCESS CONTROL  #
			####################
			

			

			// if this part of the application is restricted to a local ip range, or requires a named login, then the
			// Restrict class will check the user's ip address or if they have logged in; failure stops the flow 
			// and redirects user to a login page with the current request passed as 'return' paramater in the url

			$configIP = $objRegistry->getConfig( "LOCAL_IP_RANGE", false, null );
			$configAppName = $objRegistry->getConfig( "BASE_WEB_PATH" );
			
			// If authentication page isn't configured, we want to just use the
			// default, properly generated by url_for. The weird api
			// of Xerxes_Framework_Restrict makes this weird.

			$configAuthenticationPage = $objRequest->url_for( array ("base" => "authenticate", "action" => "login" ) );
			
			$objRestrict = new Xerxes_Framework_Restrict( $configIP, $configAppName, $configAuthenticationPage );
			
			// command line scripts will ignore access rules

			if ( $objRequest->isCommandLine() != true )
			{
				if ( $objControllerMap->isRestricted() == true )
				{
					if ( $objControllerMap->requiresLogin() == true )
					{
						// resource requires a valid named username
						$objRestrict->checkLogin( $objRequest );
					} 
					else
					{
						// resource is resricted, but local ip range is okay
						$objRestrict->checkIP( $objRequest );
					}
				}
			}
			
			// if this action is set to only be run via the command line, in order to prevent
			// web execution of potentially long-running tasks, then restrict it here
			
			if ( ! $objRequest->isCommandLine() && $objControllerMap->restrictToCLI() )
			{
				throw new Exception( "cannot run command from web" );
			}

			####################
			#     INCLUDES     #
			####################

			// files and directories that have been set to be included by the config file
			
			foreach ( $objControllerMap->getIncludes() as $path_to_include )
			{
				self::includeFiles( $path_to_parent . "/$path_to_include" );
			}
			
			####################
			#       DATA       #
			####################
			
			// set-up the data by defining the root element
			
			$strDocumentElement = $objControllerMap->getDocumentElement();
			$objRequest->setDocumentElement( $strDocumentElement );
			
			// pass config values that should be made available to the XSLT
			
			$objRequest->addDocument( $objRegistry->publicXML() );
			
			// the data will be built-up by calling one or more command classes
			// which will fetch their data based on other parameters supplied in
			// the request; returning that data as xml to a master xml dom document
			// inside the Xerxes_Framework_Request class, or in some cases specififying 
			// a url to redirect the user out
			
			$commands = $objControllerMap->getCommands();
			
			foreach ( $commands as $arrCommand )
			{
				$strDirectory = $arrCommand[0]; // directory where the command class is located
				$strNamespace = $arrCommand[1]; // prefix namespace of the command class
				$strClassFile = $arrCommand[2]; // suffix name of the command class
				$strModule = $arrCommand[3]; // suffix name of the command class
							
				// directory where commands live
				
				$command_path = "$path_to_parent/commands/$strDirectory";
				
				// but modules live elsewhere!
				
				if ( $strModule != "" )
				{
					$command_path = "$path_to_parent/modules/$strDirectory/commands";
				}
				
				// allow for a local override, even
				
				$local_command_path = "commands/$strDirectory";
				
				// echo "<h3>$strClassFile</h3>";

				// first, include any parent class, assuming that the parent class will
				// follow the naming convention of having the same name as the directory

				$strParentClass = strtoupper( substr( $strDirectory, 0, 1 ) ) . substr( $strDirectory, 1 );
				
				if ( file_exists( "$local_command_path/$strParentClass.php" ) )
				{
					require_once ("$local_command_path/$strParentClass.php");
				}
				elseif ( file_exists( "$command_path/$strParentClass.php" ) )
				{
					require_once ("$command_path/$strParentClass.php");
				}
				
				// if the specified command class exists in the distro or local commands folder, then
				// instantiate an object and execute it

				$strClass = $strNamespace . "_Command_" . $strClassFile;
				
				$local_command = file_exists( "$local_command_path/$strClassFile.php" );
				
				if ( file_exists( "$command_path/$strClassFile.php" ) || $local_command )
				{
					// if the instance has a local version, take it!
					
					if ( $local_command )
					{
						require_once ("$local_command_path/$strClassFile.php");
					}
					else
					{
						require_once ("$command_path/$strClassFile.php");
					}
					
					// instantiate the command class and execute it, but only
					// if it extends xerxes_framework_command

					$objCommand = new $strClass( );
					
					if ( $objCommand instanceof Xerxes_Framework_Command )
					{
						$objCommand->execute( $objRequest, $objRegistry );
					} 
					else
					{
						throw new Exception( "command classes must be instance of Xerxes_Framework_Command" );
					}
				} 
				else
				{
					// if no command but a view was specified, then go ahead and show the view
					// minus any data, since the view is doin' its own thang
					
					if ( $objControllerMap->getView() == "" )
					{
						throw new Exception( "invalid command $strClass" );
					}
				}
			}
			
			####################
			#     COOKIES      #
			####################

			// any cookies specified in the reuqest object? if so, set em now.

			$cookieSetParams = $objRequest->cookieSetParams();
			foreach ( $cookieSetParams as $cookieParams )
			{
				set_cookie( $cookieParams[0], $cookieParams[1], $cookieParams[2], $cookieParams[3], $cookieParams[4], $cookieParams[5] );
			}
      
			
			####################
			#     REDIRECT     #
			####################

			// if the result of the command is a redirect, we will stop the 
			// flow and redirect the user out, unless overridden by the noRedirect
			// directive

			if ( $objRequest->getRedirect() != null )
			{
				if ( $objRequest->getProperty( "noRedirect" ) == null )
				{
					header( "Location: " . $objRequest->getRedirect() );
					exit();
				}
				else
				{
					// include in the resposne what the redirect would have been
					$objRequest->setProperty( "redirect", $objRequest->getRedirect() );
				}
			}
			
			####################
			#       VIEW       #
			####################

			// SET THE HTTP HEADER
			//
			// we'll set the content-type, and potentially other header elements, based on the paramater 'format';
			// format must correspond to one of the pre-defined format content-types in setHeader() or can be a user-
			// defined format set in action.xml
			

			$format = $objRequest->getProperty( "format" );
			
			if ( $objControllerMap->getFormat( $format ) != null )
			{
				header( $objControllerMap->getFormat( $format ) );
			} 
			else
			{
				self::setHeader( $format );
			}
			
			// get the xml from the request object, but exclude any server information
			// from being included if format=source
			
			$bolShowServer = true;
			
			if ( $format == "xerxes" )
			{
				$bolShowServer = false;
			}
			
			$objXml = new DOMDocument( );
			$objXml = $objRequest->toXML( $bolShowServer );
			
			// RAW XML DISPLAY
			//
			// you can append 'format=xerxes' to the querystring to have this controller spit back
			// the response in plain xml, which can be useful in some cases, like maybe AJAX?

			if ( $format == "xerxes" )
			{
				echo $objXml->saveXML();
			} 
			else
			{
				// VIEW CODE
				//
				// ControllerMap contains instructions on what file to include for the view; typically
				// this will be an xslt file, but could be a php file if the xslt does not
				// provide enough flexibility; php page will inherit the xml dom document and
				// can go from there
				
				if ( $objControllerMap->getView() == "" )
				{
					// No view specified, no view will be executed. 
					return;
				}
				
				// views will live either in the main lib/xsl directory, or if a module, then
				// in the module's equivalent; register it in the Registry for XSLT
				
				$distro_parent_folder = $objControllerMap->getViewFolder();
				$objRegistry->setConfig("XSL_PARENT_DIRECTORY", "$path_to_parent/$distro_parent_folder/");
				
				// PHP CODE
				
				if ( $objControllerMap->getViewType() != "xsl" && $objControllerMap->getViewType() != null )
				{
					$file = $objControllerMap->getView();
					
					$distro_file = $objRegistry->getConfig( "PATH_PARENT_DIRECTORY", true ) . "/$distro_parent_folder/$file";
					
					if ( file_exists( $file ) )
					{
						require_once ($file);
					} 
					elseif ( file_exists( $distro_file ) )
					{
						require_once ($distro_file);
					} 
					else
					{
						throw new Exception( "Could not find non-xsl view specified to include: $file" );
					}
				} 
				else
				{
					// XSLT CODE
					
					$output = $objPage->transform( $objXml, $objControllerMap->getView(), null, $objControllerMap->getCommonXSL() );
					
					// EMBEDED JAVASCRIPT DISPLAY
					//
					// you can append 'format=embed_html_js' to the querystring to output 
					// the content as a javascript source document with everything wrapped in 
					// document.write() statements

					if ( $format == "embed_html_js" )
					{
						// first escape any single quotes
						
						$output = str_replace( "'", "\\'", $output );
						
						// now break the html into lines and output with document.write('')

						$lines = explode( "\n", $output );
						$new_lines = array ("// Javascript output. " );
						
						foreach ( $lines as $line )
						{
							array_push( $new_lines, "document.write('" . $line . "');" );
						}
						
						$output = implode( "\n", $new_lines );
					}
					
					echo $output;
				}
				
				//remove the flash message, intended for one display only. 
				$objRequest->setSession( "flash_message", null );        
			}
		} 

		// we'll catch all exceptions here, but the Xerxes_Error class can perform actions
		// based on the specific type of error, such as PDOException

		catch ( Exception $e )
		{
			$objError->handle( $e, $objRequest, $objRegistry );
		}
	}
	
	/**
	 * Returns the root directory ../../ of xerxes
	 * 
	 * @return string path to application root
	 */
	
	public static function parentDirectory()
	{
		return self::$parent_directory;
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

		if ( is_dir( $path ) )
		{
			// open a directory handle and grab all the php files 
			
			$directory = opendir( $path );
			
			while ( ($file = readdir( $directory )) !== false )
			{
				// make sure it is a php file, and exclude
				// any file specified by $exclude
				

				if ( strstr( $file, ".php" ) && $file != $exclude )
				{
					require_once ("$path/$file");
				}
			}
		} 
		else
		{
			require_once ($path);
		}
	}
	
	private function setHeader($format)
	{
		$arrFormats = array 
		(
			// basic types
	
			"javascript" => "Content-type: application/javascript", 
			"json" => "Content-type: application/json", 
			"pdf" => "Content-type: application/pdf", 
			"text" => "Content-type: text/plain", 
			"xml" => "Content-type: text/xml", 
	
			// complex types
	
			"atom" => "Content-type: text/xml", 
			"bibliographic" => "Content-type: application/x-research-info-systems", 
			"embed_html_js" => "Content-type: application/javascript", 
			"ris" => "Content-type: text/plain", 
			"rss" => "Content-type: text/xml", 
			"xerxes" => "Content-type: text/xml", 
			"text-file" => "Content-Disposition: attachment; Content-type: text/plain; filename=download.txt", 
			"ris-file" => "Content-Disposition: attachment; Content-type: text/plain; filename=download.ris" 
		);
		
		if ( array_key_exists( $format, $arrFormats ) )
		{
			header( $arrFormats[$format] . "; charset=UTF-8" );
		}
	}
}

/**
 * We use this to catch the occassion where we're calling a class
 * before it has been loaded; a kind of last-case include
 *
 * @param string $name	the name of the class
 */

function __autoload($name)
{
	if ( strstr($name, "Xerxes_Framework_") )
	{
		$file = str_replace("Xerxes_Framework_", "", $name);
		require_once("$file.php");
	}
	elseif ( strstr($name, "Xerxes_") )
	{
		$file = str_replace("Xerxes_", "", $name);
		
		require_once(self::parent_directory . "lib/Xerxes/$file.php");
		
	}
}

?>
