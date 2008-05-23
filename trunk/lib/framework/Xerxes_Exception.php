<?php

class Xerxes_Exception extends Exception {
  public $heading;
  
  public function __construct($message, $heading = "Sorry, there was an error")
  {
    parent::__construct($message);
    $this->heading = $heading;
  }

  
  public function heading() {
    return $this->heading;
  }
  public function setHeading($h) {
    return $this->heading = $h;
  }
}

?>
