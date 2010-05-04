<?php

/**
 * Return a group of results and display them to the user
 * 
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_SearchResults extends Xerxes_Command_Search
{
	public function doExecute()
	{
		$search = $this->getSearchObject();
		$search->results();
	}
}

?>
