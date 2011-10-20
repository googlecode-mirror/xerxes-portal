<?php

/**
 * MARC Subfield
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Marc_SubField extends Xerxes_Marc_Field 
{
	public $code;
	public $value;
	
	public function __construct(DOMNode $objNode = null )
	{
		if ( $objNode != null )
		{
			$this->code = $objNode->getAttribute("code");
			$this->value = $objNode->nodeValue;
		}
	}
}