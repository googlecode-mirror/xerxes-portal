<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once '../lib/framework/autoload.php';

import("Xerxes_Framework", "lib/framework");
import("Xerxes_Model", "models");
import("Xerxes", "lib/Xerxes");

/**
 * Mock Object
 */

class Xerxes_Framework_HTTP
{
	public static function request($url)
	{
		switch ( $url )
		{
			case 'http://localhost/solr/select/?version=2.2&defType=dismax&qf=title&pf=title&q=java&start=0&rows=0&sort=score+desc%2CpublishDate+desc':
			
				return file_get_contents('data/solr_hits_java.xml');
				break;
				
			case 'http://localhost/solr/select/?version=2.2&q=id%3A38034':
			
				return file_get_contents('data/solr_record_38034.xml');
				break;
				
			case 'http://localhost/bx/recommender/openurl?token=not_a_real_token&url_ver=Z39.88-2004&rfr_id=info:sid/calstate.edu%3Axerxes&rft_id=info%3Aoclcnum%2F28889970&rft.genre=book&rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&rft.isbn=0252063929&rft.place=Champaign%2C+Ill.&rft.pub=University+of+Illinois+Press&rft.date=1994&rft.tpages=xii%2C+218+p.+%3B&rft.btitle=William+James%2C+public+philosopher+&rft.aulast=Cotkin&rft.aufirst=George&res_dat=source=global&threshold=0&maxRecords=10':
			
				return file_get_contents('data/bx_islam.xml');
		}
		
		echo $url; exit;
	}
}

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

