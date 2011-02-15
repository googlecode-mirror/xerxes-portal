<?php

/**
 * Search Facets
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

/**
 * Data structure for facets
 */

class Xerxes_Model_Search_Facets
{
	public $groups = array();
	
	/**
	 * Add a facet grouping
	 * 
	 * @param Xerxes_Model_Search_FacetGroup $group
	 */
	
	public function addGroup(Xerxes_Model_Search_FacetGroup $group)
	{
		array_push($this->groups, $group);
	}
	
	/**
	 * Return facet groups
	 * 
	 * @return array of Xerxes_Model_Search_FacetGroup's
	 */	
	
	public function getGroups()
	{
		return $this->groups;
	}	
}
