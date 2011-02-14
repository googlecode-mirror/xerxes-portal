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
	private $groups = array();
	
	public function addGroup($group)
	{
		array_push($this->groups, $group);
	}
	
	public function getGroups()
	{
		return $this->groups;
	}	
	
	public function toXML()
	{
		$xml = new DOMDocument();
		$xml->loadXML("<facets />");
		
		foreach ( $this->getGroups() as $group )
		{
			$group_node = $xml->createElement("group");
			$group_node->setAttribute("id", $group->id);
			$group_node->setAttribute("name", $group->name);
			$xml->documentElement->appendChild($group_node);
			
			foreach ( $group->getFacets() as $facet )
			{
				$facet_node = $xml->createElement("facet");
				
				foreach ( $facet as $key => $value )
				{ 
					if ( $value != "" )
					{
						$facet_node->setAttribute($key, $value);
					}
				}
				
				$group_node->appendChild($facet_node);				
			}
		}
		
		return $xml;
	}
}
