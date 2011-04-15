<?php

/**
 * Save or delete a record 
 * 
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_SearchSave extends Xerxes_Command_Search
{
	public function doExecute()
	{
		$search = $this->getSearchObject();
		$search->saveDelete();
	}
}

?>
