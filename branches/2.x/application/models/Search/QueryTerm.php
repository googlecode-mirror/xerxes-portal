<?php

/**
 * Search Query Term
 *
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Model_Search_QueryTerm
{
	public $id;
	public $boolean;
	public $field;
	public $relation;
	public $phrase;
	public $spell_correct;
	
	/**
	 * Constructor
	 * 
	 * @param string $id			a unique identifier for this term
	 * @param string $boolen		a boolean operator (AND, OR, NOT) that joins this term to the query
	 * @param string $value			field to search one
	 * @param string $phrase		value
	 */
	
	public function __construct($id, $boolean, $field, $relation, $phrase)
	{
		$this->id = $id;
		$this->boolean = $boolean;
		$this->field = $field;
		$this->relation = $relation;
		$this->phrase = $phrase;		
	}
}
