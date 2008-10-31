<?php

	/**
	 * Defines the basic functions for commands to interface with the FrontController
	 * 
	 * @abstract
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.4
	 * @package Xerxes_Framework
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */

	abstract class Xerxes_Framework_Command
	{
		private $status = 0;	// this is unused
		protected $request;		// the request object
		protected $registry;	// the config registry object
		
		final public function __construct() { }
		
		public function execute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// map the request and registry objects here to memeber variables
			// so any subclassed command can get at them easily
			
			$this->request = $objRequest;
			$this->registry = $objRegistry;
			
			$this->status = $this->doExecute( $objRequest, $objRegistry );
		}
			
		public function getStatus()
		{
			return $this->status;
		}
		
		// doExecute() abstract function is disable in 1.4 so subclassed commands can either
		// include the request and registry objects in their function definitions (old method)
		// or leave that empty and grab those from the memeber variables above (new method)
		// should uncomment the one below after we get everything cleaned up!
		
		// abstract function doExecute();
		
		
	}


?>