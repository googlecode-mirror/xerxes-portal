<?php
require_once '../lib/Xerxes/Marc.php';
require_once '../lib/Xerxes/Record.php';
require_once '../lib/Xerxes/MetalibRecord.php';

/**
 * Xerxes_MetalibRecord test case.
 */
class Xerxes_MetalibRecordTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @var Xerxes_MetalibRecord
	 */
	private $Xerxes_MetalibRecord_Document;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp ();
		
		$this->dir = dirname(__FILE__);
		
		$this->Xerxes_MetalibRecord_Document = new Xerxes_MetalibRecord_Document();
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Xerxes_MetalibRecord_Document = null;
		
		parent::tearDown ();
	}
	
	public function testMetalibFields()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-academic-search-article.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		$this->assertEquals( $record->getMetalibID(), "CAL00129");
		$this->assertEquals( $record->getResultSet(), "038776");
		$this->assertEquals( $record->getRecordNumber(), "000015");
		$this->assertEquals( $record->getDatabaseName(), "Academic Search Premier");
		$this->assertEquals( $record->getSource(), "EBSCO_APH");
	}

	public function testAcademicSearch()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-academic-search-article.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		$this->assertTrue( $record->hasFullText() );
		
		$arrFullTxt = $record->getFullText(true);
		
		$this->assertEquals( count($arrFullTxt), 1);
		$this->assertEquals( $arrFullTxt[0][2], "pdf");
		$this->assertEquals( $record->getDOI(), "", null, "record has 024 but not a doi pattern");
		
		// file_put_contents("2.xml", $record->toXML()->saveXML());
	}
	
	public function testCSA()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-csa-socabs-article.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		print_r($record);
		
	}

}

