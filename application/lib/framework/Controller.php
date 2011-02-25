<?php

/**
 * Defines the basic functions for controllers to interface with the FrontController
 * 
 * @abstract
 * @author David Walker
 * @copyright 2011 California State University
 * @version $Id$
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
	}
}
