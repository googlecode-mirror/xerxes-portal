<?php

class Xerxes_BxRecord_Document extends Xerxes_Marc_Document 
{
	protected $record_type = "Xerxes_BxRecord";

	protected function parse(DOMDocument $objDocument)
	{
		$objXPath = new DOMXPath($objDocument);
		$objXPath->registerNamespace("ctx", "info:ofi/fmt:xml:xsd:ctx");
		
		$objRecords = $objXPath->query("//ctx:context-object");
		$this->_length = $objRecords->length;
		
		foreach ( $objRecords as $objRecord )
		{
			$record = new $this->record_type();
			$record->loadXML($objRecord);
			array_push($this->_records, $record);
		}
	}
}

/**
 * Extract properties for books, articles, and dissertations from MARC-XML record 
 * with special handling for Metalib X-Server response
 * 
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id: BxRecord.php 1009 2009-11-30 21:34:21Z dwalker@calstate.edu $
 * @package Xerxes
 */

class Xerxes_BxRecord extends Xerxes_Record
{
	protected $database_name = "bX";
	
	public function map()
	{
		parent::map();
	}
}

?>