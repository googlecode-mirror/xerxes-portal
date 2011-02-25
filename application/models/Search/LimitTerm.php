<?php

/**
 * Search Limit Term
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Search_LimitTerm
{
	public $field;
	public $relation;
	public $value;
	
	/**
	 * Constructor
	 * 
	 * @param string $field			field name
	 * @param string $relation		operator ('=', '>', etc.)
	 * @param string $value			value
	 */
	
	public function __construct($field, $relation, $value)
	{
		$this->field = $field;
		$this->relation = $relation;
		$this->value = $value;		
	}
}
