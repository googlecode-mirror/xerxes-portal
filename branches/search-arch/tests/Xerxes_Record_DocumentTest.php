<?php
require_once '../lib/Xerxes/Marc.php';
require_once '../lib/Xerxes/Record.php';

/**
 * Xerxes_Record_Document test case.
 */
class Xerxes_Record_DocumentTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @var Xerxes_Record_Document
	 */
	private $Xerxes_Record_Document;
	private $dir;
	
	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp ();

		$this->dir = dirname(__FILE__);
		
		$this->Xerxes_Record_Document = new Xerxes_Record_Document();		
		$this->Xerxes_Record_Document->load($this->dir. "/data/worldcat-1.xml");
	}
	
	/**
	 * This tests if we can do the basic MARC parsing
	 */
	
	public function testWorldCatMarc()
	{		
		$record = $this->Xerxes_Record_Document->record(1);
		
		// basic field parsing
		
		$this->assertEquals( (string) $record->leader(), "00000cam a2200000Ia 4500");
		$this->assertEquals( (string) $record->controlfield("001"), "47278976");
		$this->assertEquals( (string) $record->datafield("245")->subfield("a"), "Programming web services with XML-RPC /");
		$this->assertEquals( (string) $record->datafield("245"), "Programming web services with XML-RPC / Simon St. Laurent, Joe Johnston, Edd Dumbill.");
		
		$title = $record->datafield("245")->subfield("a");
		$this->assertEquals( (string) $title, "Programming web services with XML-RPC /");
		
		// field-lists
		
		$datafield_list = $record->datafield("6XX");
		$arrFields = $record->fieldArray("6XX", "a"); 
		$domnode_list = $record->xpath("//marc:datafield[substring(@tag,1,1)=6]/marc:subfield[@code='a']");
		
		$this->assertEquals( $datafield_list->length(), 5);
		$this->assertEquals( count($arrFields), 5);
		$this->assertEquals( $domnode_list->length, 5);
		
		$this->assertEquals( (string) $datafield_list->item(0)->subfield("a"), "XML (Document markup language)");
		$this->assertEquals( (string) $arrFields[0], "XML (Document markup language)");
		$this->assertEquals( (string) $domnode_list->item(0)->nodeValue, "XML (Document markup language)");
	}
	
	/**
	 * This tests basic book parsing
	 */
	
	public function testWorldCatBook()
	{
		$record = $this->Xerxes_Record_Document->record(1);
		
		$this->assertEquals( $record->getMainTitle(), "Programming web services with XML-RPC");
		$this->assertEquals( $record->getTitle(true), "Programming Web Services with XML-RPC");
		$this->assertEquals( $record->getPrimaryAuthor(), "Simon St. Laurant");
		$this->assertEquals( $record->getControlNumber(), "47278976");
		
		$objXml = new DOMDocument();
		$objXml->load($this->dir . "/data/xerxes-worldcat-1.xml");
		
		$record->toXML()->save($this->dir . "/data/new.xml");
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		$this->Xerxes_Record_Document = null;
		parent::tearDown();
	}
}

