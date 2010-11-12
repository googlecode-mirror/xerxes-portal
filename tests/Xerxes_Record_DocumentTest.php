<?php

require_once '../lib/framework/Parser.php';
require_once '../lib/framework/Languages.php';
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
	}
	
	/**
	 * This tests if we can do the basic MARC parsing
	 */
	
	public function testMarcRead()
	{		
		$this->Xerxes_Record_Document->load($this->dir. "/data/worldcat-book.xml");
		$record = $this->Xerxes_Record_Document->record(1);
		
		// basic field parsing
		
		$this->assertEquals( (string) $record->leader(), "00000cam a2200000Ia 4500");
		$this->assertEquals( (string) $record->controlfield("001"), "47278976");
		$this->assertEquals( (string) $record->datafield("245")->subfield("a"), "Programming web services with XML-RPC /");
		$this->assertEquals( (string) $record->datafield("245"), "Programming web services with XML-RPC / Simon St. Laurent, Joe Johnston, Edd Dumbill.");
		$title = $record->datafield("245")->subfield("a");
		$this->assertEquals( (string) $title, "Programming web services with XML-RPC /");
		$this->assertEquals( (string) $record->datafield("650")->subfield("a"), "XML (Document markup language)");
		
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

	public function testMarcWrite()
	{		
		$this->Xerxes_Record_Document->load($this->dir. "/data/worldcat-book.xml");
		$record = $this->Xerxes_Record_Document->record(1);
		
		$leader = $record->leader();
		$leader->value = "happy";
		$this->assertEquals( (string) $record->leader(), "happy");
		
		$control_field = $record->controlfield("001");
		$control_field->value = "days";
		$this->assertEquals( (string) $record->controlfield("001"), "days");
		
		$subfield = $record->datafield("245")->subfield("a");
		$subfield->value = "Happy days are here again";
		$this->assertEquals( (string) $record->datafield("245")->subfield("a"), "Happy days are here again");
	}	
	
	/**
	 * This tests basic book parsing
	 */
	
	public function testWorldCatBook()
	{
		$this->Xerxes_Record_Document->load($this->dir. "/data/worldcat-book.xml");
		$record = $this->Xerxes_Record_Document->record(1);
		
		$this->assertEquals( $record->getMainTitle(), "Programming web services with XML-RPC");
		$this->assertEquals( $record->getTitle(true), "Programming Web Services with XML-RPC");
		$this->assertEquals( $record->getPrimaryAuthor(), "Simon St. Laurant");
		
		$this->assertEquals( $record->getControlNumber(), "47278976");
		$this->assertEquals( $record->getISBN(), "0596001193");
		$this->assertEquals( count($record->getAllISBN()), 2);
		$this->assertEquals( $record->getCallNumber(), "QA76.76.H94 S717 2001");
		
		$this->assertEquals( $record->getDescription(), "213 p. : ill. ; 24 cm.");
		$this->assertEquals( $record->getPublisher(), "O'Reilly");
		$this->assertEquals( $record->getPlace(), "Beijing");
		$this->assertEquals( $record->getYear(), "2001");
		
	}

	public function testWorldCatThesis()
	{
		$this->Xerxes_Record_Document->load($this->dir. "/data/worldcat-thesis.xml");
		$record = $this->Xerxes_Record_Document->record(1);
		
		$this->assertEquals( $record->getFormat(), "Thesis");
		$this->assertEquals( $record->getDegree(), "M.Arch.");
		$this->assertEquals( $record->getInstitution(), "UCLA--Architecture.");
	}

	public function testParsing()
	{
		$this->Xerxes_Record_Document->load($this->dir. "/data/metalib-char-data2.xml");
		
		foreach ( $this->Xerxes_Record_Document->records() as $record )
		{
			$xml = $record->toXML()->saveXML();
		}
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

