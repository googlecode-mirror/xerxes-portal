<?php

/**
 * This only here for backwards compatibility on older guest links
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_AuthenticateGuest extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		// redirect the user to the main login action with auth source = guest
		
		$arrURL = array(
			"base" => "authenticate",
			"action" => "login",
			"authentication_source" => "guest",
			"return" => $this->request->getProperty("return")
		);
		
		$url = $this->request->url_for($arrURL);
		$this->request->setRedirect($url);
		
		return 1;
	}
}

?>