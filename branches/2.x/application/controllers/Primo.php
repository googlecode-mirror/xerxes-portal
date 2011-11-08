<?php

class Xerxes_Controller_Primo extends Xerxes_Controller_Search
{
	protected $id = "primo";
	
	public function init()
	{
		$this->engine = new Xerxes_Model_Primo_Engine();
		parent::init();
	}
}
