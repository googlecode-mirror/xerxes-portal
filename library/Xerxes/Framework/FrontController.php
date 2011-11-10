<?php

/**
 * Provides the framework for performing actions in the system
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @version $Id: FrontController.php 1850 2011-03-17 18:14:53Z dwalker@calstate.edu $
 * @package  Xerxes_Framework
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 */

require_once realpath(dirname(__FILE__) . '/autoload.php');

import("Xerxes_Controller", XERXES_APPLICATION_PATH . "/controllers");
import("Xerxes_Model", XERXES_APPLICATION_PATH . "/models");
import("Xerxes_View", XERXES_APPLICATION_PATH . "/views");

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
			// $labels = Xerxes_Framework_Labels::getInstance($lang);

			// make sure application_name is passthrough, and has a value.

			$registry->setConfig( 
				"application_name", 
				$registry->getConfig( "APPLICATION_NAME", false, "Xerxes", $lang ), 
				true 
			);
			
			####################
			#     SET PATHS    #
			####################

			// the working directory is the instance, so any relative paths will
			// be executed in relation to the root directory of the instance
						
			$working_dir = getcwd();
			$working_dir = str_replace( "\\", "/", $working_dir );
			
			// full web path
			
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
			$registry->setConfig("LOCAL_DIRECTORY", $working_dir);
			$registry->setConfig("BASE_URL", $web . $base_path , true);

			####################
			#   INSTRUCTIONS   #
			####################
			
			// ControllerMap contains instructions for commands and views
			// based on the url parameters 'base' and 'action'
			
			$base = $request->getParam("base");
			$action = $request->getParam("action", null, "index");
			
			$controller_map->setAction( $base, $action, $request );


			####################
			#       DATA       #
			####################
			
			// global action
			
			$global_controller = new Xerxes_Controller_Navigation();
			$global_controller->navbar();
			
			// specified action
			
			if ( $base == "" ) $base = "solr"; $request->setParam("base", "solr");
			if ( $action == "" ) $action = "index"; $request->setParam("action", "index");
			
			$controller_name = "Xerxes_Controller_" . strtoupper(substr($base, 0, 1)) . substr($base,1); 
			$controller = new $controller_name();

			$controller->$action();

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

			####################
			#      DISPLAY     #
			####################			
			
			$format = $request->getParam('format', false, 'html');
			echo $response->display($format);
						
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
