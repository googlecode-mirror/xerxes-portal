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
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_WorldCatRecord extends Xerxes_Record
{
	protected $source = "worldcat";
	
	public function map()
	{
		parent::map();

		$this->oclc_number = $this->control_number;
		
		// blank all links
		
		$this->links = array();
	}
	
	public function getOpenURL($strResolver, $strReferer = null, $param_delimiter = "&")
	{
		$url = parent::getOpenURL($strResolver, $strReferer, $param_delimiter);
		
		// always ignore dates for journals and books, since worldcat is describing
		// the item as a whole, not any specific issue or part
		
		return $url . "&sfx.ignore_date_threshold=1";
	}
	
}

?>