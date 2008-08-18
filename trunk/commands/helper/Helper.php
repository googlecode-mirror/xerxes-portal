<?php

/**
 * Base class for helper commands
 *
 */

abstract class Xerxes_Command_Helper extends Xerxes_Framework_Command
{
	// this is just waiting for some common code between
	// the database command child classes

  

  # Functions for saving saved record state from a result set in session
  # This is used for knowing whether to add or delete on a 'toggle' command
  # (MetasearchSaveDelete), and also used for knowing whether to display
  # a result line as saved or not. 
  public static function markSaved($objRecord) {
      $key = self::savedRecordKey($objRecord->getResultSet(), $objRecord->getRecordNumber()); 
		  $_SESSION['resultsSaved'][$key]['xerxes_record_id'] = $objRecord->id; 

  }
  public static function unmarkSaved($strResultSet, $strRecordNumber) {
    $key = self::savedRecordKey($strResultSet, $strRecordNumber);

    if ( array_key_exists("resultsSaved", $_SESSION) &&
         array_key_exists($key, $_SESSION["resultsSaved"])) {
				unset($_SESSION['resultsSaved'][$key]);
		}
  }
	public static function isMarkedSaved($strResultSet, $strRecordNumber) {
    $key = self::savedRecordKey($strResultSet, $strRecordNumber);
		return ( array_key_exists("resultsSaved", $_SESSION) &&
             array_key_exists($key, $_SESSION["resultsSaved"]));

  }
  public static function numMarkedSaved() {
    $num = 0;
    if ( array_key_exists("resultsSaved", $_SESSION) ) {
			$num = count($_SESSION["resultsSaved"]);
		}    
		return $num;
  }  
  public static function savedRecordKey($strResultSet, $strRecordNumber) {
		# key based on result set and record number in search results. Save id
		# of saved xerxes_record. Normalize number strings remove initial 0s. 
    $key = (string) (int) $strResultSet . ":" . (string) (int) $strRecordNumber;
    return $key;
  } 



}

?>
