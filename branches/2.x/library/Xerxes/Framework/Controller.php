<?php

/**
 * Defines the basic functions for controllers to interface with the FrontController
 * 
 * @abstract
 * @author David Walker
 * @copyright 2011 California State University
 * @version $Id: Controller.php 1798 2011-03-09 18:37:31Z dwalker@calstate.edu $
 * @package Xerxes_Framework
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 */

abstract class Xerxes_Framework_Controller
{
	protected $request;		// the global request object
	protected $registry;	// the global registry object
	protected $response;	// the global response object
	
	public function __construct()
	{
		// map the request and registry objects here to memeber variables
		// so any subclassed command can get at them easily
		
		$this->request = Xerxes_Framework_Request::getInstance();
		$this->registry = Xerxes_Framework_Registry::getInstance();
		$this->response = Xerxes_Framework_Response::getInstance();
		
		$this->response->add("request", $this->request);
		$this->response->add("config", $this->registry);
	}
	
	/**
	 * Checks if the user is within local IP range or has logged in,
	 * failure stops the flow and redirects user to a login page
	 */
	
	protected function restrict()
	{
		if ( $this->request->isCommandLine() != true )
		{
			$restrict = new Xerxes_Framework_Restrict();
			$restrict->checkIP();
		}
	}

	/**
	 * Checks of the user has logged in, failure stops the flow and 
	 * redirects user to a login page
	 */
	
	protected function requireLogin()
	{
		if ( $this->request->isCommandLine() != true )
		{
			$restrict = new Xerxes_Framework_Restrict();
			$restrict->checkLogin();
		}		
	}
	
	/**
	 * Require that this action only be run via the command line, in order 
	 * to prevent web execution of potentially long-running tasks
	 */
	
	protected function limitToCLI()
	{
		// if this action is set to only be run via the 
			
		if ( ! $this->request->isCommandLine() )
		{
			throw new Exception( "cannot run command from web" );
		}
	}
}
