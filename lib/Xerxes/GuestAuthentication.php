<?php

	/**
	 * Guest Authentication
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version 1.1
	 * @package Xerxes
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 */

	class Xerxes_GuestAuthentication extends Xerxes_Framework_Authenticate 
	{
		/**
		 * Just register the user with a role of guest
		 */
		
		public function onLogin()
		{
			$this->role = "guest";
			$this->user->username = "guest@" . session_id();
			$this->register();
				
			return true;
		}
	}

?>