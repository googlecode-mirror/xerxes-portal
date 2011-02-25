<?php

/**
 * Search Items
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Search_Items
{
	public $items = array();
	public $holdings = array();
	
	/**
	 * Add an item to this group of items
	 * 
	 * @param Xerxes_Model_Search_Item $item
	 */
	
	public function addItem(Xerxes_Model_Search_Item $item)
	{
		array_push($this->items, $item);
	}

	/**
	 * Add (journal) holdings record to this group of items
	 * 
	 * @param Xerxes_Model_Search_Holding $holdings
	 */
	
	public function addHolding(Xerxes_Model_Search_Holding $holdings)
	{
		array_push($this->holdings, $holdings);
	}
	
	/**
	 * Get all items
	 */
	
	public function getItems()
	{
		return $this->items;
	}

	/**
	 * Get all holdings
	 */
	
	public function getHoldings()
	{
		return $this->holdings;
	}	
	
	/**
	 * The number of items
	 */
	
	public function length()
	{
		return count($this->items);
	}
}
