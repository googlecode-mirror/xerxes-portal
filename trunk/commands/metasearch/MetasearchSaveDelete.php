<?php	
	
	/**
	 * Save or delete a record, depending on whether the record has previously been saved or not
	 * 
	 * @author David Walker
	 * @copyright 2008 California State University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version 1.1
	 * @package Xerxes
	 */
	
	class Xerxes_Command_MetasearchSaveDelete extends Xerxes_Command_Metasearch
	{
		/**
		 * Save or delete a record; comes in on a single 'onClick' event from the interface so
		 * we will make determination of whether this is save or delete from cookie; Request params
		 * should include: 'username' (in session) the username under which to save the record, 
		 * 'group' the search group number; 'resultSet' the result set from which
		 * the record came; and 'startRecord' the record to save, based on position in the resultset
		 *
		 * @param Xerxes_Framework_Request $objRequest
		 * @param Xerxes_Framework_Registry $objRegistry
		 * @return unknown
		 */
		public function doExecute( Xerxes_Framework_Request $objRequest, Xerxes_Framework_Registry $objRegistry )
		{
			// get properties from request
			
			$strUsername = $objRequest->getSession("username");
			$strGroup =	$objRequest->getProperty("group");
			$strResultSet =	$objRequest->getProperty("resultSet");
			$iStartRecord =	(int) $objRequest->getProperty("startRecord");

			// get the search start date
			
			$objSearchXml = $this->getCache($strGroup, "search", "SimpleXML");
			$strDate = (string) $objSearchXml->date;
			
			// construct a fully unique id for metalib record based on
			// date, resultset, and startrecord numbers			
			
			$strID = "";
			$strID = $strDate . ":";
			$strID .= $strResultSet . ":";
			$strID .= str_pad($iStartRecord, 6, "0", STR_PAD_LEFT);
		
			// the save and delete action come in on the same onClick event from the search results page,
			// so we have to check here to see if it is a delete or save based on the session. This is a
			// bit dangerous, since maybe the session got out of sync? Oh well, it'll do for now, since
			// that's what was done with the previous cookie implementation. 
	
			$objData = new Xerxes_DataMap( );
			
			$bolAdd = ! $this->isMarkedSaved( $strResultSet, $iStartRecord );
			
			$strInsertedId = null;
			
			if ( $bolAdd == true )
			{
				// add command
	
				// get record from metalib result set
				
				$objXerxesRecord = new Xerxes_Record( );
				$objXerxesRecord->loadXML( $this->getRecord( $objRequest, $objRegistry ) );
				
				// add to database
	
				$objData->addRecord( $strUsername, "metalib", $strID, $objXerxesRecord );
				$strInsertedId = $objXerxesRecord->id;
				
				// mark saved for feedback on search results
				
				$this->markSaved( $objXerxesRecord );
			} 
			else
			{
				// delete command
	
				$objData->deleteRecordBySource( $strUsername, "metalib", $strID );
				$this->unmarkSaved( $strResultSet, $iStartRecord );
			}
			
			// build a response
	
			$objXml = new DOMDocument( );
			$objXml->loadXML( "<results />" );
			
			if ( $bolAdd == false )
			{
				// flag this as being a delete comand in the view, in the event
				// user has javascript turned off and we need to show them an actual page
	
				$objDelete = $objXml->createElement( "delete", "1" );
				$objXml->documentElement->appendChild( $objDelete );
			} 
			else
			{
				// add inserted id for ajax response
				
				$objInsertedId = $objXml->createElement( "savedRecordID", $strInsertedId );
				$objXml->documentElement->appendChild( $objInsertedId );
			}
			
			$objRequest->addDocument( $objXml );
			
			return 1;
		}
	}

?>
