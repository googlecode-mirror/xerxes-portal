	/**
	 * FUNCTIONS FOR SAVING RECORDS
	 */
	
	addEvent(window, 'load', loadFolders);
	addEvent(window, 'load', findFolders);
	
	/**
	 * Add onClick event to save records
	 */
	
	function findFolders()
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
					
		var iRecordsCookie = recordsInCookie();
		var url = $(id).readAttribute('href');

		// change the item folder image

		var objImage = $('folder_' + resultSet + recordNumber).src;
		
		if ( objImage.indexOf("folder_on.gif") != -1 )
		{
			iRecordsCookie--;
			$('folder_' + resultSet + recordNumber).src = "images/folder.gif";
			$(id).update("Save this record");
		}
		else
		{
			iRecordsCookie++;
			$('folder_' + resultSet + recordNumber).src = "images/folder_on.gif";
			$(id).update("Record saved");
		}
	
		// change the master folder image
		
		if ( iRecordsCookie == 0 )
		{
			$('folder').src = "images/folder.gif";
		}
		else
		{
			$('folder').src ="images/folder_on.gif";
		}
		
		// do it!
		
		new Ajax.Request(url);	

		return false;
	}
	
	/**
	 * Simple method to return number of records currently saved in cookie
	 *
	 * @return int 		number of records in the cookie
	 */
	
	function recordsInCookie()
	{	
		var test_pat = new RegExp("&","g");
		var strSaves = getCookie("saves");
		
		if (strSaves != null )
		{
			if ( strSaves.match(/&/) )
			{
				results = strSaves.match(test_pat);
				return results.length;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			return 0;
		}
	}
	
	/**
	 * Simple method to get specific cookie
	 *
	 * @return string		cookie data
	 */
	
	function getCookie( name )
	{
		var dc = document.cookie;
		var prefix = name + "=";
		var begin = dc.indexOf("; " + prefix);
		
		if (begin == -1)
		{
			begin = dc.indexOf(prefix);
			if (begin != 0) return null;
		}
		else
		{
			begin += 2;
		}
		
		var end = document.cookie.indexOf(";", begin);
		
		if (end == -1)
		{
			end = dc.length;
		}
		
		return unescape(dc.substring(begin + prefix.length, end));
	}
	
	/**
	 * On any given page, sets the individual folder items and the 
	 * 'my records' images and text to 'on' if already selected. 
	 */
	
	function loadFolders()
	{		
		// for records that are already saved
		
		var objCookie = getCookie("saves");
		var savedRecordsCookie = recordsInCookie();
		
		// set the master 'my records' image

		if ( savedRecordsCookie > 0 ) 
		{	
			document.getElementById('folder').src = "images/folder_on.gif";
			
			// set the individual folder images
		
			var arrRecords = objCookie.split("&");
			
			for ( var i = 0; i < arrRecords.length; i++ )
			{
				if ( arrRecords[i] != "" )
				{
					var arrResultSet = arrRecords[i].split(":");
					var resultSet = arrResultSet[1];
					var recordNumber = arrResultSet[2];
					
					if ( document.getElementById('folder_' + resultSet + recordNumber) )
					{
						$('folder_' + resultSet + recordNumber).src = "images/folder_on.gif";
						$('link_' + resultSet + ":" + recordNumber).update("Record saved");
					}
				}
			}
		}
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
	
