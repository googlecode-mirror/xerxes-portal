<?php

require_once 'PHPUnit/Framework/TestCase.php';
require_once '../lib/framework/autoload.php';

import("Xerxes_Controller", "controllers");
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
				break;
				
			case 'http://localhost/solr/select/?version=2.2&defType=dismax&qf=title+title_sub%5E0.8+title_full%5E0.5+title_alt%5E0.5+title_preceding%5E0.3+title_succeeding%5E0.3+title_series%5E0.3+title_contents%5E0.3&pf=title+title_sub%5E0.8+title_full%5E0.5+title_alt%5E0.5+title_preceding%5E0.3+title_succeeding%5E0.3+title_series%5E0.3+title_contents%5E0.3&q=java&start=0&rows=10&sort=publishDate+desc&facet=true&facet.mincount=1&facet.field=format&facet.field=callnumber-first&facet.field=publishDate':
				
				return file_get_contents('data/solr_results_java.xml');
				break;
		}
		
		echo $url; exit;
	}
}

class Xerxes_DataMap
{
	
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
	
	private $controller_map;
	private $registry;
	private	$response;
	private $request;
	
	/**
	 * Prepares the environment before running a test.
	 */
	
	protected function setUp() 
	{
		parent::setUp();
		
		chdir(dirname(__FILE__));
		
		$this->registry = Xerxes_Framework_Registry::getInstance();
		$this->registry->init();

		$this->controller_map = Xerxes_Framework_ControllerMap::getInstance();
		$this->controller_map->init();
		
		$this->request = Xerxes_Framework_Request::getInstance();
		$this->request->init();
		
		$this->response = Xerxes_Framework_Response::getInstance();
		
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
		// mock request
		
		$this->request->setProperty("base", "solr");
		$this->request->setProperty("action", "results");
		$this->request->setProperty("query", "java");
		$this->request->setProperty("field", "title");
		
		$controller = new Xerxes_Controller_Search($this->request, $this->registry, $this->response );
		
		// search results
				
		$config = Xerxes_Model_Solr_Config::getInstance(); 
		$config->init();
		
		$query = new Xerxes_Model_Search_Query($this->request, $config);
		
		$results = $this->Xerxes_Model_Solr_Engine->searchRetrieve($query, 1, 10, "date");
		
		// link helper
		
		foreach ( $results->getRecords() as $result )
		{
			$xerxes_record = $result->getXerxesRecord();
			
			// full-record link
			
			$result->url = $controller->linkFullRecord($xerxes_record);
			$result->url_full = $result->url; // backwards compatibility
				
			// sms link

			$result->url_sms = $controller->linkSMS($xerxes_record);
				
			// save or delete link

			$result->url_save = $controller->linkSaveRecord($xerxes_record);
			$result->url_save_delete = $result->url_save; // backwards compatibility
		}

		$facets = $results->getFacets();
		
		if ( $facets != "" )
		{
			foreach ( $facets->getGroups() as $group )
			{
				foreach ( $group->getFacets() as $facet )
				{
					// existing url
						
					$url = $controller->currentParams($query);
							
					// now add the new one
							
					if ( $facet->is_date == true ) // dates are different 
					{
						$url["facet.date." . $group->name . "." . urlencode($facet->key)] = $facet->name;
					}
					else
					{
						$url["facet." . $group->name] = $facet->name;									
					}
							
					$facet->url = $this->request->url_for($url);
				}
			}
		}

		$this->toXML($results);
	}
	
	private function toXML($results)
	{
		$response = Xerxes_Framework_Response::getInstance();
		$response->add($results, "results");
		file_put_contents("c:/test.xml", $response->toXML()->saveXML());
	}
}

