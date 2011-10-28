<?php

class Xerxes_Controller_Primo extends Xerxes_Controller_Search
{
	protected $id = "primo";
	
	public function init()
	{
		$this->config = Xerxes_Model_Primo_Config::getInstance();
		$this->query = new Xerxes_Model_Search_Query($this->request, $this->config);
		$this->engine = new Xerxes_Model_Primo_Engine($this->config);
		
		$this->response->add("config_local", $this->config);
		
		parent::init();
	}
}
