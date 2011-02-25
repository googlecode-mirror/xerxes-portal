<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once 'loader.php';

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
		
		// set the location and environment
		
		chdir(dirname(__FILE__));
		
		// engine
		
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
		
		$this->assertEquals(218, $results->getTotal());
		$this->assertEquals(10, count($results->getRecords()));
		
		$this->toXML($results);
	}
	
	private function toXML($results)
	{
		$response = Xerxes_Framework_Response::getInstance();
		$response->add($results, "results");
		file_put_contents("c:/test.xml", $response->toXML()->saveXML());
	}
}

/**
 * Mock Objects
 */

class Xerxes_Framework_HTTP
{
	public static function request($url)
	{
		switch ( $url )
		{
			case 'http://localhost/solr/select/?version=2.2&defType=dismax&qf=title+title_sub%5E0.8+title_full%5E0.5+title_alt%5E0.5+title_preceding%5E0.3+title_succeeding%5E0.3+title_series%5E0.3+title_contents%5E0.3&pf=title+title_sub%5E0.8+title_full%5E0.5+title_alt%5E0.5+title_preceding%5E0.3+title_succeeding%5E0.3+title_series%5E0.3+title_contents%5E0.3&q=java&start=0&rows=0&sort=score+desc%2CpublishDate+desc':
				return file_get_contents('data/solr_hits_java.xml');
				break;
				
			case 'http://localhost/solr/select/?version=2.2&q=id%3A38034':
			
				return file_get_contents('data/solr_record_38034.xml');
				break;
				
			case 'http://localhost/bx/recommender/openurl?token=not_a_real_token&url_ver=Z39.88-2004&rfr_id=info:sid/calstate.edu%3Axerxes&rft_id=info%3Aoclcnum%2F28889970&rft.genre=book&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&rft.isbn=0252063929&rft.place=Champaign%2C+Ill.&rft.pub=University+of+Illinois+Press&rft.date=1994&rft.tpages=xii%2C+218+p.+%3B&rft.btitle=William+James%2C+public+philosopher+&rft.aulast=Cotkin&rft.aufirst=George&res_dat=source=global&threshold=0&maxRecords=10':
			
				return file_get_contents('data/bx_islam.xml');
				break;
				
			case 'http://localhost/solr/select/?version=2.2&defType=dismax&qf=title+title_sub%5E0.8+title_full%5E0.5+title_alt%5E0.5+title_preceding%5E0.3+title_succeeding%5E0.3+title_series%5E0.3+title_contents%5E0.3&pf=title+title_sub%5E0.8+title_full%5E0.5+title_alt%5E0.5+title_preceding%5E0.3+title_succeeding%5E0.3+title_series%5E0.3+title_contents%5E0.3&q=java&start=0&rows=10&sort=publishDate+desc&facet=true&facet.mincount=1&facet.field=format&facet.field=callnumber-first&facet.field=publishDate':
				
				return file_get_contents('data/solr_results_java.xml');
				break;
		}
		
		echo $url; exit;
	}
}

