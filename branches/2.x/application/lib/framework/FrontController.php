<?php

/**
 * Provides the framework for performing actions in the system
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @version $Id$
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
		// registry
		
		$registry = Xerxes_Framework_Registry::getInstance();
		
		// controller map
		
		$controller_map = Xerxes_Framework_ControllerMap::getInstance();
			
		// set the version number, for interface or other places
		
		$registry->setConfig("XERXES_VERSION", $controller_map->getVersion(), true);
		
		// dynamically set the web path, if config says so, doesn't work on all webserver/php 
		// set-ups, so an explicit web path from config is preferred
		
		if ( $registry->getConfig( "base_web_path", false ) == '{dynamic}' )
		{
			if ( isset($_SERVER) )
			{
				$script_name = $_SERVER['SCRIPT_NAME'];
				$script_name = str_replace("/index.php", "", $script_name);
				$registry->setConfig( "base_web_path", $script_name);
			}
		}
		
		// give our session a name to keep sessions distinct between multiple
		// instances of xerxes on one server.  use base_path (preferably) or
		// application_name config directives.
		
		$path_key = preg_replace( '/\W/', '_', $registry->getConfig( "BASE_WEB_PATH", false ) );
		$session_name = "xerxessession_" . $path_key;
		
		session_name( $session_name );
		session_start();
		
		// processes the incoming request
		
		$request = Xerxes_Framework_Request::getInstance();
		
		// set-up the response
		
		$response = Xerxes_Framework_Response::getInstance();
		
		// we'll put the remaining code in a try-catch block in order to show friendly error page
		// for any uncaught exceptions
		
		try
		{
			####################
			#  DISPLAY ERRORS  #
			####################
			
			if ( $registry->getConfig( "DISPLAY_ERRORS" ) == true )
			{
				error_reporting( E_ALL );
				ini_set( 'display_errors', '1' );
			}
			
			####################
			#   DEFAULTS       #
			####################
			
			// labels
			
			$lang = $request->getParam("lang");
			$labels = Xerxes_Framework_Labels::getInstance($lang);

			// make sure application_name is passthrough, and has a value.

			$registry->setConfig( 
				"application_name", 
				$registry->getConfig( "APPLICATION_NAME", false, "Xerxes", $lang ), 
				true 
			);
			
			####################
			#     SET PATHS    #
			####################

			### reverse proxy
			
			// check to see if xerxes is running behind a reverse proxy and swap
			// host and remote ip here with their http_x_forwarded counterparts;
			// but only if configured for this, since client can spoof the header 
			// if xerxes is not, in fact, behind a reverse proxy
			
			if ( $registry->getConfig("REVERSE_PROXY", false, false ) == true )
			{
				$forward_host = $request->getServer('HTTP_X_FORWARDED_HOST');
				$forward_address = $request->getServer('HTTP_X_FORWARDED_FOR');
				
				if ( $forward_host != "" )
				{
					$request->setServer('SERVER_NAME', $forward_host);
				}
				
				// last ip address is the user's
				
				if ( $forward_address != "" )
				{
					$arrIP = explode(",", $forward_address);
					$request->setServer('REMOTE_ADDR', trim(array_pop($arrIP)));
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
			
			$base_path = $registry->getConfig( 'BASE_WEB_PATH', false, "" );
			$this_server_name = $request->getServer( 'SERVER_NAME' );
			
			// check for a non-standard port
						
			$port = $request->getServer( 'SERVER_PORT' );
			
			if ( $port == 80 || $port == 443 )
			{
			    $port = "";
			}
			else
			{
			    $port = ":" . $port;
			}
			
			$protocol = "http://";
			
			if ( $request->getServer("HTTPS") )
			{
				$protocol = "https://";
			}
			
			$web = $protocol . $this_server_name . $port;			
			
			// register these values
			
			$registry->setConfig("SERVER_URL", $web);
			$registry->setConfig("APP_DIRECTORY", $working_dir);
			$registry->setConfig("BASE_URL", $web . $base_path , true);

			####################
			#   INSTRUCTIONS   #
			####################
			
			// ControllerMap contains instructions for commands and views
			// based on the url parameters 'base' and 'action'
			
			$base = $request->getParam("base");
			$action = $request->getParam("action");
			
			$controller_map->setAction( $base, $action, $request );

			####################
			#  ACCESS CONTROL  #
			####################
			
			// if this part of the application is restricted to a local ip range, or 
			// requires a named login, then the Restrict class will check the user's 
			// ip address or if they have logged in; failure stops the flow and redirects 
			// user to a login page with the current request passed as 'return' paramater 
			// in the url

			$restrict = new Xerxes_Framework_Restrict();
			
			// command line scripts will ignore access rules

			if ( $request->isCommandLine() != true )
			{
				if ( $controller_map->isRestricted() == true )
				{
					if ( $controller_map->requiresLogin() == true )
					{
						// resource requires a valid named username
						$restrict->checkLogin();
					} 
					else
					{
						// resource is resricted, but local ip range is okay
						$restrict->checkIP();
					}
				}
				else
				{
					// go ahead and register local users, but don't prompt for login
					$restrict->checkIP(false);
				}
			}
			
			// if this action is set to only be run via the command line, in order to prevent
			// web execution of potentially long-running tasks, then restrict it here
			
			if ( ! $request->isCommandLine() && $controller_map->restrictToCLI() )
			{
				throw new Exception( "cannot run command from web" );
			}

			####################
			#       DATA       #
			####################
			
			$controller_name = "Xerxes_Controller_" . strtoupper(substr($base, 0, 1)) . substr($base,1); 
				
			$controller = new $controller_name();
			
			$controller->$action();
			
			header('Content-type: text/xml');
			echo $response->toXML()->saveXML();
			
			
			####################
			#     COOKIES      #
			####################

			// any cookies specified in the reuqest object? if so, set em now.

			$cookieSetParams = $request->cookieSetParams();
			
			foreach ( $cookieSetParams as $cookieParams )
			{
				set_cookie( $cookieParams[0], $cookieParams[1], $cookieParams[2], $cookieParams[3], 
					$cookieParams[4], $cookieParams[5] 
				);
			}
      
			####################
			#     REDIRECT     #
			####################

			// if the result of the command is a redirect, we will stop the 
			// flow and redirect the user out, unless overridden by the noRedirect
			// directive

			if ( $response->getRedirect() != null )
			{
				if ( $request->getParam( "noRedirect" ) == null )
				{
					header( "Location: " . $response->getRedirect() );
					exit();
				}
				else
				{
					// include in the resposne what the redirect would have been
					$request->setProperty( "redirect", $response->getRedirect() );
				}
			}
						
			// remove any flash message, intended for one display only.
				 
			$request->setSession( "flash_message", null );
		} 

		// we'll catch all exceptions here, but the Xerxes_Error class can perform actions
		// based on the specific type of error, such as PDOException

		catch ( Exception $e )
		{
			throw $e; exit;
			
			$error_handler = new Xerxes_Framework_Error();
			$error_handler->handle( $e );
		}
	}
}