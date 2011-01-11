<?php

/**
 * Authentication hook called on every request
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_AuthenticateCheck extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		$this->authentication->onEveryRequest();
		return 1;
	}
}

?>