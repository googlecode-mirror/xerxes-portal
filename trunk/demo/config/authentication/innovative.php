<?php

class Xerxes_InnovativePatron_Local extends Xerxes_InnovativePatron
{ 
	/**
	 * Implement code in this function to authorize the user and/or map
	 * the user's informtion from the Patron API
	 * 
	 * User has already been authenticated when this function is called. 
	 */
	
	protected function mapUserData()
	{
		/* EXAMPLE:

		$arrName = explode(",", $this->user_data["PATRN NAME"]);
		
		if ( count($arrName) == 2 )
		{
			$this->user->first_name = trim($arrName[1]);
			$this->user->last_name = trim($arrName[2]);
		}
		
		$this->user->email_addr = $this->user_data["EMAIL ADDR"];

		*/
		
	}
}
?>
