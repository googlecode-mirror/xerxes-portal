<?php

/**
 *  Abstract field object
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

abstract class Xerxes_Marc_Field
{
	protected $value;
	
	public function __toString()
	{
		return (string) $this->value;
	}
}