<?php

/**
 * Worldcat Config
 *
 * @author David Walker
 * @copyright 2008 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_WorldCatConfig extends Xerxes_Framework_Registry
{
	protected $config_file = "config/books/config";
	private static $instance; // singleton pattern
	
	public static function getInstance()
	{
		if ( empty( self::$instance ) )
		{
			self::$instance = new Xerxes_WorldCatConfig( );
		}
		
		return self::$instance;
	}
}

?>