<?php

// load in xerxes classes

require_once '../lib/framework/autoload.php';

import("Xerxes_Controller", "controllers");
import("Xerxes_Framework", "lib/framework");
import("Xerxes_Model", "models");
import("Xerxes", "lib/Xerxes");


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

class Xerxes_DataMap
{
	
}


// load in tests

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'application/tests/Xerxes_Model_Solr_EngineTest.php';

/**
 * Static test suite.
 */

class testsSuite extends PHPUnit_Framework_TestSuite {
	
	/**
	 * Constructs the test suite handler.
	 */

	public function __construct() 
	{
		$this->setName ( 'testsSuite' );
		$this->addTestSuite ( 'Xerxes_Model_Solr_EngineTest' );
	}
	
	/**
	 * Creates the suite.
	 */
	
	public static function suite() 
	{
		return new self ( );
	}
}