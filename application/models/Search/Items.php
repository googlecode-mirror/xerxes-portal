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

class Xerxes_Record_Items
{
	private $items = array();
	
	public function addItem($item)
	{
		array_push($this->items, $item);
	}
	
	public function getItems()
	{
		return $this->items;
	}
	
	public function length()
	{
		return count($this->items);
	}
}
