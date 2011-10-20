<?php

/**
 * Bx Record
 * 
 * @author David Walker
 * @copyright 2011 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: BxRecord.php 1637 2011-02-14 23:38:56Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_BxRecord extends Xerxes_Record_Bibliographic
{
	protected $database_name = "bX";
	
	protected function map()
	{
		parent::map();
	}
}
