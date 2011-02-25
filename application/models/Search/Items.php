<?php

/**
 * Search Items
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: Facet.php 1658 2011-02-15 23:02:54Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_Model_Search_Items
{
	public $items = array();
	
	/**
	 * Add an item to this group of items
	 * 
	 * @param Xerxes_Model_Search_Item or Xerxes_Model_Search_Holding $item
	 */
	
	public function addItem($item)
	{
		if ( ! $item instanceof Xerxes_Model_Search_Holding && 
		     ! $item instanceof Xerxes_Model_Search_Item )
		{
			throw new Exception("parameter must be instance of Xerxes_Model_Search_Holding or " .
				"Xerxes_Model_Search_Item");
		}
		
		array_push($this->items, $item);
	}
	
	/**
	 * Get all items
	 */
	
	public function getItems()
	{
		return $this->items;
	}
	
	/**
	 * The number of items
	 */
	
	public function length()
	{
		return count($this->items);
	}
}
