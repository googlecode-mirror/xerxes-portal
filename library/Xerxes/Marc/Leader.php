<?php

/**
 * MARC Leader
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Marc_Leader extends Xerxes_Marc_ControlField 
{
	public $value;					// the entire leader
	
	public function __construct(DOMNode $objNode = null)
	{
		if ( $objNode != null )
		{
			$this->value = $objNode->nodeValue;
		}
	}
}