<?php

	/**
	 * Defines the basic functions for commands to interface with the FrontController
	 * 
	 * @abstract
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package Xerxes_Framework
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */

	abstract class Xerxes_Framework_Command
	{
		private $status = 0;				// status of the request
		
		final public function __construct() { }
		
		public function execute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			$this->status = $this->doExecute( $objRequest, $objRegistry );
		}
			
		public function getStatus()
		{
			return $this->status;
		}
		
		abstract function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry );
	}


?>