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
	public $field_internal;
	public $relation;
	public $phrase;
	public $spell_correct;
	
	/**
	 * Constructor
	 * 
	 * @param string $id				a unique identifier for this term
	 * @param string $boolen			a boolean operator (AND, OR, NOT) that joins this term to the query
	 * @param string $field				field id?
	 * @param string $field_internal	internal field name
	 * @param string $relation			relation operator (=, >, <)  
	 * @param string $phrase			value
	 */
	
	public function __construct($id, $boolean, $field, $field_internal, $relation, $phrase)
	{
		$this->id = $id;
		$this->boolean = $boolean;
		$this->field = $field;
		$this->field_internal = $field_internal;
		$this->relation = $relation;
		$this->phrase = $phrase;		
	}
}
