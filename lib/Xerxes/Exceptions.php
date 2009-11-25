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

class Xerxes_NotFoundException extends Xerxes_Exception {
    //New default heading. 
    public function __construct($message, $heading = "Not Found")
    {
      parent::__construct($message, $heading);
      $this->heading = $heading;
    }   
}

class Xerxes_AccessDeniedException extends Xerxes_Exception { 
  
    //New default heading. 
    public function __construct($message, $heading = "Access Denied")
    {
      parent::__construct($message, $heading);
      $this->heading = $heading;
    }    
}

class Xerxes_DatabasesDeniedException extends Xerxes_AccessDeniedException {
    protected $deniedDatabases = array();
    
    //New arg 
    public function __construct($message = "Not authorized to search certain databases.", $heading = "Access Denied" , $arrDenied = array())
    {
      parent::__construct($message, $heading);
      $this->heading = $heading;
      $this->deniedDatabases = $arrDenied;
    }   
    
    /* array of Xerxes_Data_Database objects please.  */
    public function setDeniedDatabases(Array $dbs) {
      $this->deniedDatabases = $dbs;
    }
    public function addDeniedDatabase(Xerxes_Data_Database $db) {
      $this->deniedDatabases[] = $db;
    }    
    public function deniedDatabases() {
      return $this->deniedDatabases;
    } 
}

?>
