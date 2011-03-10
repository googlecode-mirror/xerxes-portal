<?php

class Xerxes_Controller_Summon extends Xerxes_Controller_Search
{
	protected $id = "summon";
	
	public function __construct()
	{
		parent::__construct();
		
		$this->config = Xerxes_Model_Summon_Config::getInstance();
		$this->query = new Xerxes_Model_Search_Query($this->request, $this->config);
		$this->engine = new Xerxes_Model_Summon_Engine($this->config);
		
		$this->response->add("config_local", $this->config->toXML());
	}
}
