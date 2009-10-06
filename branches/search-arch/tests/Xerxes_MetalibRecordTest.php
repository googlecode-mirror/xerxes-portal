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
		
		// record has 024, but is not a doi, so test to make sure this is blank
		
		$this->assertEquals( $record->getDOI(), "");
	}
	
	public function testCSA()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-csa-socabs-article.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		$this->assertEquals( $record->getDOI(), "10.1017/S0144686X08007940");
		
		// test author 100 -> 700 mapping
		
		$this->assertEquals( count($record->getAuthors()), 3);
		
		// test subject clean-up
		
		$arrSubjects = array("New Zealand", "Retirement", "Goals", "Young Adults", "Aging");
		$this->assertEquals( $record->getSubjects(), $arrSubjects);		
	}
	
	public function testPsycInfoBookChapter()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-psycinfo-book-chapter.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		$this->assertEquals( $record->getFormat(), "Book Chapter");
	}

	public function testEricDocument()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-eric-document.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		$this->assertEquals( $record->getFormat(), "Report");
		$this->assertEquals( $record->hasFullText(), true);
	}

	public function testJSTORBookReview()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-jstor-book-review.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		$link = $record->getFullText();
		
		$this->assertEquals( $link[0][2], "pdf");
		$this->assertEquals( $record->getFormat(), "Book Review");
		$this->assertEquals( $record->getTitle(true), "Teachings of the Prophet Joseph Smith Joseph Smith Joseph Fielding Smith");
	}
	
	public function testGaleArticle()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-gale-article.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		// title note clean-up
		
		$this->assertEquals( $record->getTitle(true), "Virtual Program Counter  Prediction: Very Low Cost Ndirect Branch Prediction Using Conditional Branch Prediction Hardware");
	
		// has 856, but link is original record
		
		$this->assertFalse( $record->hasFullText());
	}
	
	public function testFactiva()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-factiva.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		// should create full-text link
		
		$this->assertTrue( $record->hasFullText());
	}
	
	public function testOCLCDissAbs()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-oclc-dissabs.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		
		$this->assertEquals( $record->getInstitution(), "University of Alberta (Canada)");
		$this->assertEquals( $record->getDegree(), "M.Sc.");
		$this->assertEquals( $record->getFormat(), "Thesis");
	}

	public function testOCLCPapers()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-oclc-papers.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		$this->assertEquals( $record->getFormat(), "Conference Paper");
	}

	public function testOCLCProceedings()
	{
		$this->Xerxes_MetalibRecord_Document->load($this->dir. "/data/metalib-oclc-proceedings.xml");
		$record = $this->Xerxes_MetalibRecord_Document->record(1);
		$this->assertEquals( $record->getFormat(), "Conference Proceeding");

		// testing 245$p
		
		$this->assertEquals( $record->getTitle(true), "Engineering for Climatic Change");		
	}	
}

