<?php

require_once 'tests/Xerxes_MetalibRecordTest.php';
require_once 'tests/Xerxes_Record_DocumentTest.php';

/**
 * Static test suite.
 */
class testsSuite extends PHPUnit_Framework_TestSuite
{
	
	/**
	 * Constructs the test suite handler.
	 */
	public function __construct()
	{
		$this->setName ( 'testsSuite' );
		
		$this->addTestSuite ( 'Xerxes_MetalibRecordTest' );
		
		$this->addTestSuite ( 'Xerxes_Record_DocumentTest' );
		
		// load language file
		
		$objLanguage = Xerxes_Framework_Languages::getInstance();
		$objLanguage->init();
	}
	
	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		return new self ( );
	}
}

