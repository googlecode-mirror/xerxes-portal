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
	private $Xerxes_Model_Search_Query;
	
	/**
	 * Prepares the environment before running a test.
	 */
	
	protected function setUp() 
	{
		parent::setUp();
		
		// set the location
		
		chdir(dirname(__FILE__));
		
		// engine
		
		$Xerxes_Model_Solr_Config = Xerxes_Model_Solr_Config::getInstance();
		$this->Xerxes_Model_Solr_Engine = new Xerxes_Model_Solr_Engine($Xerxes_Model_Solr_Config);
		$this->Xerxes_Model_Search_Query = new Xerxes_Model_Search_Query(null, $Xerxes_Model_Solr_Config);
	}
	
	/**
	 * Cleans up the environment after running a test.
	 */
	
	protected function tearDown() 
	{
		$this->Xerxes_Model_Solr_Engine = null;
		$this->Xerxes_Model_Search_Query = null;
		parent::tearDown();
	}
	
	/**
	 * Tests Xerxes_Model_Solr_Engine->getHits()
	 */
	
	public function testGetHits() 
	{
		$this->Xerxes_Model_Search_Query->addTerm(1, null, "title", "=", "java"); 
		$total = $this->Xerxes_Model_Solr_Engine->getHits($this->Xerxes_Model_Search_Query);
		
		$this->assertEquals(218, $total);
	}
	
	/**
	 * Tests Xerxes_Model_Solr_Engine->getRecord()
	 */
	
	public function testGetRecord() 
	{
		$Xerxes_Model_Search_ResultSet = $this->Xerxes_Model_Solr_Engine->getRecord("38034");
		$Xerxes_Model_Search_Result = $Xerxes_Model_Search_ResultSet->getRecord(0);
		
		$Xerxes_Record = $Xerxes_Model_Search_Result->getXerxesRecord();
		
		$this->assertEquals("28889970", $Xerxes_Record->getOCLCNumber());
		$this->assertEquals("University of Illinois Press", $Xerxes_Record->getPublisher());
		
		$Xerxes_Model_Search_Result->addHoldings();
		$Xerxes_Model_Search_Holdings = $Xerxes_Model_Search_Result->getHoldings();
		
		$this->assertEquals(1, $Xerxes_Model_Search_Holdings->length());
		
		$items = $Xerxes_Model_Search_Holdings->getItems();
		$Xerxes_Model_Search_Item = $items[0];
		
		$this->assertEquals(true, $Xerxes_Model_Search_Item->getProperty("availability"));
		$this->assertEquals("Not Checked Out", $Xerxes_Model_Search_Item->getProperty("status"));
		$this->assertEquals("Book Stacks (2nd Floor)", $Xerxes_Model_Search_Item->getProperty("location"));
		$this->assertEquals("QA76.73.J39 H3734 2010", $Xerxes_Model_Search_Item->getProperty("callnumber"));
	}
	
	/**
	 * Tests Xerxes_Model_Solr_Engine->searchRetrieve()
	 */

	public function testSearchRetrieve() 
	{
		$this->Xerxes_Model_Search_Query->addTerm(1, null, "title", "=", "java"); 
		
		$Xerxes_Model_Search_ResultSet = $this->Xerxes_Model_Solr_Engine->searchRetrieve(
			$this->Xerxes_Model_Search_Query, 1, 10, "date"
		);
		
		$this->assertEquals(218, $Xerxes_Model_Search_ResultSet->getTotal());
		$this->assertEquals(10, count($Xerxes_Model_Search_ResultSet->getRecords()));
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
				
			case 'http://localhost/lookup/?action=status&id=38034':
				
				return file_get_contents('data/status-38034.txt');
				break;
		}
		
		echo $url; exit;
	}
}

class Xerxes_Framework_DataMap
{
	public function __call($name, $params)
	{
		if ( $name == 'select' )
		{
			$sql = $this->decompose($params[0]);
		}
	}
	
	private function decompose($param)
	{
		$final = "";
		
		if ( is_array($param) )
		{
			foreach ( $param as $key => $value )
			{
				$final .= " " . $key . " "  . $this->decompose($value);
			}
		}
		else
		{
			$final .= " " . $param;
		}
		
		$final = trim($final);
		$final = str_replace(' ', '_', $final);
		
		return $final;
	}
}

