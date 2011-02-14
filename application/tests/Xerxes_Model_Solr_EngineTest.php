<?php

require_once 'PHPUnit/Framework/TestCase.php';

require_once '../lib/framework/Parser.php';
require_once '../lib/framework/Registry.php';
require_once '../lib/framework/Languages.php';
require_once '../lib/framework/Response.php';

require_once '../lib/Xerxes/Marc.php';
require_once '../lib/Xerxes/Record.php';
require_once '../lib/Xerxes/BxRecord.php';

require_once '../models/Search/Engine.php';
require_once '../models/Search/Config.php';
require_once '../models/Search/Query.php';
require_once '../models/Search/QueryTerm.php';
require_once '../models/Search/LimitTerm.php';
require_once '../models/Search/ResultSet.php';
require_once '../models/Search/Result.php';
require_once '../models/Search/Facet.php';
require_once '../models/Search/FacetGroup.php';
require_once '../models/Search/Facets.php';

require_once '../models/Solr/Engine.php';
require_once '../models/Solr/Config.php';



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
		
		$registry = Xerxes_Framework_Registry::getInstance();
		$registry->init();
		
		$server = "http://cowewpaq02.calstate.edu:8080/solr/monterey/";
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
		
		$this->toXML($results);
	}
	
	/**
	 * Tests Xerxes_Model_Solr_Engine->searchRetrieve()
	 */

	public function testSearchRetrieve() 
	{
		$search = new Xerxes_Model_Search_Query();
		$search->addTerm(1, null, "title", "=", "java"); 		
		
		$results = $this->Xerxes_Model_Solr_Engine->searchRetrieve($search, 1, 10, "date");
		
		/*
			// existing url
					
			$url = $this->currentParams();
					
			// now add the new one
					
			if ( $is_date == true ) // dates are different 
			{
				$facet->name = $decade_display[$key];
				$url["facet.date.$group_internal_name." . urlencode($key)] = $decade_display[$key];
			}
			else
			{
				$url["facet." . $group_internal_name] = $facet->name;									
			}
					
			$facet->url = $this->request->url_for($url);
		 */
	}
	
	private function toXML($results)
	{
		$response = new Xerxes_Framework_Response();
		$response->add($results, "results");
		file_put_contents("c:/test.xml", $response->toXML()->saveXML());
	}
}

class Xerxes_DataMap
{
	
}

