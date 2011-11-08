<?php

class Xerxes_Controller_Ebsco extends Xerxes_Controller_Search
{
	protected $id = "ebsco";
	
	public function init()
	{
		$this->engine = new Xerxes_Model_Ebsco_Engine();
		parent::init();
	}
}
