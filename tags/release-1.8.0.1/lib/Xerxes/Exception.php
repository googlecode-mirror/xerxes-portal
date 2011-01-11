<?php

/**
 * exceptions
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_Exception extends Exception
{
	public $heading;
	
	public function __construct($message, $heading = "text_error")
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
	
	protected function getLabelObject()
	{
		return ;
	}
}

class Xerxes_Exception_NotFound extends Xerxes_Exception
{
	public function __construct($message, $heading = "text_error_not_found")
	{
		parent::__construct ( $message, $heading );
	}
}

class Xerxes_Exception_AccessDenied extends Xerxes_Exception
{
	public function __construct($message, $heading = "text_error_access_denied")
	{
		parent::__construct ( $message, $heading );
	}
}

class Xerxes_Exception_DatabasesDenied extends Xerxes_Exception_AccessDenied
{
	protected $deniedDatabases = array ();
	
	public function __construct($message = "text_error_not_authorized_db", $heading = "text_error_access_denied", $arrDenied = array())
	{
		parent::__construct ( $message, $heading );
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
