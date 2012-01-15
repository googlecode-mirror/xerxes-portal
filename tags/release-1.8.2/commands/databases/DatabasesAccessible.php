<?php

/**
 * set the ada flag in session for non-ajax version of site
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @version $Id$
 * @package Xerxes
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @uses Xerxes_DataMap
 */

class Xerxes_Command_DatabasesAccessible extends Xerxes_Command_Helper
{
	public function doExecute()
	{
		$this->request->setSession("ada", "true");
		$this->request->setRedirect($this->request->getProperty("return"));
	}
}
?>
