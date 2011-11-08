<?php

class Xerxes_Controller_Summon extends Xerxes_Controller_Search
{
	protected $id = "summon";
	
	public function init()
	{
		$this->engine = new Xerxes_Model_Summon_Engine();
		parent::init();
	}
}
