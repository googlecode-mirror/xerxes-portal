<?php

/**
 * Validate an external authentication request
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_AuthenticateValidate extends Xerxes_Command_Authenticate
{
	public function doExecute()
	{
		// validate the request
		
		$this->authentication->onCallBack();
	}
}

?>