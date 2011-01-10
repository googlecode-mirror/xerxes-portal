<?php

/**
 * Search parent class
 * 
 * @author David Walker
 * @copyright 2010 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Command_Search extends Xerxes_Framework_Command
{
	protected function getSearchObject()
	{
		$class = $this->request->getProperty("xerxes_search_object");
		
		// check to make sure it exists
		
		if (! class_exists($class) )
		{
			throw new Exception("could not find a search class named '$class'");
		}
		
		$search = new $class($this->request, $this->registry);
		
		// check to make sure it extends the search framework
		
		if ( ! $search instanceof Xerxes_Framework_Search )
		{
			throw new Exception("class '$class' must extend Xerxes_Framework_Search");
		}
		
		return $search;
	}
}

?>
