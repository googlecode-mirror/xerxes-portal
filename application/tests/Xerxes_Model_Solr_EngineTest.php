<?php

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Xerxes_Model_Solr_Engine test case.
 */

class Xerxes_Model_Solr_EngineTest extends PHPUnit_Framework_TestCase 
{
	/**
	 * @var Xerxes_Model_Solr_Engine
	 */
	
	private $Xerxes_Model_Solr_Engine;
	
	/**
	 * Prepares the environment before running a test.
	 */
	
	protected function setUp() 
	{
		parent::setUp();
		
		chdir(dirname(__FILE__));
		
		$server = "http://localhost/solr/";
		$this->Xerxes_Model_Solr_Engine = new Xerxes_Model_Solr_Engine($server);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	
	protected function tearDown() 
	{
		$this->Xerxes_Model_Solr_Engine = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Xerxes_Model_Solr_Engine->getHits()
	 */
	
	public function testGetHits() 
	{
		$search = new Xerxes_Model_Search_Query();
		$search->addTerm(1, null, "title", "=", "java"); 
		
		$total = $this->Xerxes_Model_Solr_Engine->getHits($search);
		$this->assertEquals(218, $total);
	}
	
	/**
	 * Tests Xerxes_Model_Solr_Engine->getRecord()
	 */
	
	public function testGetRecord() 
	{
		$results = $this->Xerxes_Model_Solr_Engine->getRecord("38034");
		$record = $results->getRecord(0)->getXerxesRecord();
		
		$this->assertEquals("28889970", $record->getOCLCNumber());
		$this->assertEquals("University of Illinois Press", $record->getPublisher());
	}
	
	/**
	 * Tests Xerxes_Model_Solr_Engine->searchRetrieve()
	 */

	public function testSearchRetrieve() 
	{
		$search = new Xerxes_Model_Search_Query();
		$search->addTerm(1, null, "title", "=", "java"); 
		
		$results = $this->Xerxes_Model_Solr_Engine->searchRetrieve($search, 1, 10, "date");
		$this->toXML($results);
	}
	
	private function toXML($results)
	{
		$response = Xerxes_Framework_Response::getInstance();
		$response->add($results, "results");
		file_put_contents("c:/test.xml", $response->toXML()->saveXML());
	}
}

