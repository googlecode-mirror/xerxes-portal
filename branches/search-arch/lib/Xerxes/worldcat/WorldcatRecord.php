<?php

class Xerxes_WorldCatRecord_Document extends Xerxes_Marc_Document 
{
	protected $record_type = "Xerxes_WorldCatRecord";
}

/**
 * Extract properties for books, articles, and dissertations from the WorldCat API
 * 
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: WorldcatRecord.php 1009 2009-11-30 21:34:21Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_WorldCatRecord extends Xerxes_Record
{
	protected $source = "WORLDCAT_API";
	protected $result_set = "worldcat";
	protected $record_number;
	
	public function map()
	{
		parent::map();

		$this->record_number = $this->control_number;
		$this->oclc_number = $this->control_number;
	}
	
	public function getResultSet()
	{
		return $this->result_set;
	}

	public function getRecordNumber()
	{
		return $this->record_number;
	}	
	
}

?>