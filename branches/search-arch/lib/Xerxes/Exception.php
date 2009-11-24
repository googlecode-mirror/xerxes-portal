<?php

/**
 * exceptions
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: Exceptions.php 974 2009-10-28 20:54:47Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_Exception extends Exception
{
	public $heading;
	
	public function __construct($message, $heading = "Sorry, there was an error")
	{
		parent::__construct ( $message );
		$this->heading = $heading;
	}
	
	public function heading()
	{
		return $this->heading;
	}
	public function setHeading($h)
	{
		return $this->heading = $h;
	}
}

class Xerxes_Exception_NotFound extends Xerxes_Exception
{
	//New default heading. 
	public function __construct($message, $heading = "Not Found")
	{
		parent::__construct ( $message, $heading );
		$this->heading = $heading;
	}
}

class Xerxes_Exception_AccessDenied extends Xerxes_Exception
{
	
	//New default heading. 
	public function __construct($message, $heading = "Access Denied")
	{
		parent::__construct ( $message, $heading );
		$this->heading = $heading;
	}
}

class Xerxes_Exception_DatabasesDenied extends Xerxes_Exception_AccessDenied
{
	protected $deniedDatabases = array ();
	
	//New arg 
	public function __construct($message = "Not authorized to search certain databases.", $heading = "Access Denied", $arrDenied = array())
	{
		parent::__construct ( $message, $heading );
		$this->heading = $heading;
		$this->deniedDatabases = $arrDenied;
	}
	
	/* array of Xerxes_Data_Database objects please.  */
	public function setDeniedDatabases(Array $dbs)
	{
		$this->deniedDatabases = $dbs;
	}
	public function addDeniedDatabase(Xerxes_Data_Database $db)
	{
		$this->deniedDatabases [] = $db;
	}
	public function deniedDatabases()
	{
		return $this->deniedDatabases;
	}
}

?>
