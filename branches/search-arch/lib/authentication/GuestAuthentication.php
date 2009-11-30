<?php

	/**
	 * Guest Authentication
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @version $Id: GuestAuthentication.php 974 2009-10-28 20:54:47Z dwalker@calstate.edu $
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