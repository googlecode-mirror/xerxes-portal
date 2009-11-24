<?php

/**
 * Metalib Search framework
 *
 * @author David Walker
 * @copyright 2009 California State University
 * @link http://xerxes.calstate.edu
 * @license http://www.gnu.org/licenses/
 * @version $Id$
 * @package Xerxes
 */

class Xerxes_MetalibSearch extends Xerxes_Framework_Search 
{
	protected $worldcat;
	
	public function __construct()
	{
		parent::__construct();

		$key = "";
		$this->search_object = new WorldCat($key);
	}
	
	public function search()
	{
		$query = $this->request->getProperty("query");
		$this->query->addTerm("", "", $query);
		$this->query->checkSpelling();

		print_r($this->query); exit;
	}
	
	public function results()
	{	
		$xml = $this->search_object->searchRetrieve("xml");
		$this->results = $this->convertToXerxesRecords($xml);
				
		return $this->toXML();
	}
	
	public function record()
	{
		$xml = $this->search_object->record("49851745");
		$this->results = $this->convertToXerxesRecords($xml);
		
		return $this->toXML();
	}
}
?>