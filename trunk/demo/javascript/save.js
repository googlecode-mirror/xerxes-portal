	/**
	 * FUNCTIONS FOR SAVING RECORDS
	 */

  /* CALLER REQUIREMENTS:
       set a global js variable called numSavedRecords to the current number of records
       in saved records area. Used to determine when to toggle saved records icon to
       indicate presence of records */
	
	addEvent(window, 'load', addAjaxToSaveLinks);
	
	/**
	 * Add onClick event to save records
	 */
	
	function addAjaxToSaveLinks()
	{
		// add onClick event to save the record
		
		var links = document.getElementsByTagName('a');
		
		for ( i = 0; i < links.length; i++)
		{
			if ( /saveThisRecord/.test(links[i].className) )
			{		
				links[i].onclick = function () {
					return updateRecord(this.id)
				}
			}
		}
	}
	
	/**
	 * legacy function name and parameter list for backwards compatability < 1.3,
	 * should use updateRecord(id) instead
	 */
	
	function saveRecord(groupID,resultSet,recordNumber)
	{
		return updateRecord( "link_" + resultSet + ":" + recordNumber );
	}
	
	/**
	 * Add or delete a record from the user's folder
	 */
	
	function updateRecord( id )
	{

		arrID = id.split(/_|:/);
		resultSet = arrID[1]; 
		recordNumber = arrID[2];
					
    // Should be set by main page in global js variable, if not we set.
    if (typeof(window["numSavedRecords"]) == "undefined") {
       numSavedRecords = 0;
    }
		var url = $(id).readAttribute('href');
		
		// do it! Update our icons only after success please! Then we're only
    // telling them they have saved a record if they really have! Hooray
    // for javascript closures. 
		
		new Ajax.Request(url, {"onFailure": function(ajaxRequest) {
		alert('Sorry, an error occured, your record was not saved.');
          },
			"onSuccess": function (ajaxRequest) {
	    if ( $(id).hasClassName("saved") )
			{
  	    numSavedRecords--;
    	  $('folder_' + resultSet + recordNumber).src = "images/folder.gif";
      	$(id).update("Save this record");
      	$(id).removeClassName("saved");
			}
    	else
    	{
      	numSavedRecords++;
      	$('folder_' + resultSet + recordNumber).src = "images/folder_on.gif";
      	$(id).update("Record saved");
      	$(id).addClassName("saved");
    	}
      // Change master folder image
      if ( numSavedRecords > 0 ) {
			  $('folder').src = 'images/folder_on.gif';
      }
			else {
				$('folder').src = 'images/folder.gif';
			}
		}});	

		return false;
	}
	
	
	/**
	 * Checks to see if user has selected more databases than is allowed or none
	 *
	 * @note global variable iSearchable set by include.xsl since this is set server-side
	 * @param form1		form submitting the request
	 * @return bool		true if number of selected databases is less, false otherwise
	 */
	
	function databaseLimit(form1)
	{
		var iTotal = 0;
		var iMax = form1.database.length;
		
		if ( iMax == "" || iMax == undefined)
		{
			iMax = 1;
		}
	
		for (var x = 0; x < iMax; x++)
		{
			if (eval("document.forms.form1.database[" + x + "].checked") == true)
			{
				iTotal++;
			}
		}
		
		if ( iTotal == 0 )
		{
			alert("Please select databases to search");
			return false;
		}
		else if ( iTotal > iSearchable )
		{
			alert("Sorry, you can only search up to " + iSearchable + " databases at one time");
			return false;
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Simple alert message to confirm deletion of saved records
	 *
	 * @param form1		form submitting the request
	 * @return bool		true if number of selected databases is less, false otherwise
	 */
	
	function sureDelete()
	{
		if ( confirm("Are you sure you want to delete this record?") )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	
	function disableExportSelect()
	{
		
	}
	
