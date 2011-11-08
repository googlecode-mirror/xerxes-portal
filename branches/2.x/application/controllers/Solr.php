<?php

class Xerxes_Controller_Solr extends Xerxes_Controller_Search
{
	protected $id = "solr";
	
	public function init()
	{
		$this->engine = new Xerxes_Model_Solr_Engine();
		parent::init();
	}
}
