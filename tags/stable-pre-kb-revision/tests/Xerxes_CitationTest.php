<?php

require_once '../lib/Xerxes/Marc.php';
require_once '../lib/Xerxes/Record.php';
require_once '../lib/Xerxes/Citation.php';

/**
 * Xerxes_Citation test case.
 */
class Xerxes_CitationTest extends PHPUnit_Framework_TestCase
{
	
	private $Xerxes_Citation;
	private $dir;
	
	protected function setUp()
	{
		parent::setUp ();

		$this->dir = dirname(__FILE__);
		$this->Xerxes_Citation = new Xerxes_Citation();
	}
	
	public function testAPA()
	{
		$this->Xerxes_Citation->loadStyle($this->dir . "/data/citation/apa.xml");

		$doc = new Xerxes_Record_Document();
		$doc->load($this->dir. "/data/worldcat-book.xml");
		$record = $doc->record(1);
		
		$this->Xerxes_Citation->process($record);
		
		
	}
	
	protected function tearDown()
	{
		$this->Xerxes_Citation = null;
		parent::tearDown ();
	}
}
