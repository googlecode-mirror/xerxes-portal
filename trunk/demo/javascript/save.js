<!--

/**
 * Adds and subtracts records for the save records feature.  All actions are performed
 * server-side, including management of the cookie
 *
 * drops a persistent cookie called 'saves' to control overall saved items. 
 * Because users click on the same image to add and 'un-add' items, this function must 
 * handle both activities using a single onClick event.
 *
 * global variables dateSearch and xerxesRoot set by include.xsl since this is set server-side
 *
 * @param string resultSet		metalib result set id
 * @param string recordNumber	record number
 * @return bool					false if browser is AJAX capable, true if it is not
 */


function saveRecord( groupID, resultSet, recordNumber )
{
	var url = xerxesRoot + "/?base=metasearch&action=save-delete&group=" + groupID + "&resultSet=" + 
		resultSet + "&startRecord=" + recordNumber;
	var iRecordsCookie = recordsInCookie();
	
	if (!window.XMLHttpRequest && !window.ActiveXObject)
	{	
		// for non-ajax browsers, pass thru so link will
		// execute, sending user to a redirect
		
		return true;	
	}
	else
	{
		// perform save or delete action on the server
		
		if (window.XMLHttpRequest)
		{		
			// mozilla, ie 7
			req = new XMLHttpRequest();
		}
		else if (window.ActiveXObject)
		{
			// internet explorer 5-6
			req = new ActiveXObject("Microsoft.XMLHTTP");
		}
	
		req.onreadystatechange = processRequest;
		req.open("GET", url, true);
		req.send(null);
				
		// change the item folder image
		
		var objImage = document.getElementById('folder_' + resultSet + recordNumber).src;
		
		if ( objImage.indexOf("folder_on.gif") != -1 )
		{
			iRecordsCookie--;
			document.getElementById('folder_' + resultSet + recordNumber).src = "images/folder.gif";
		}
		else
		{
			iRecordsCookie++;
			document.getElementById('folder_' + resultSet + recordNumber).src = "images/folder_on.gif"
		}
	
		// change the master folder image
		
		if ( iRecordsCookie == 0 )
		{
			document.getElementById('folder').src = "images/folder.gif";
		}
		else
		{
			document.getElementById('folder').src ="images/folder_on.gif";
		}

		// prevents link from executing
		
		return false;
	}
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
 * Simple AJAX method 
 */

function processRequest() {
	
	if (req.readyState == 4)
	{
		if (req.status != 200)
		{
			alert (req.responseText);
		}
	}
}

/**
 * On any given page, sets the individual folder items and the 
 * 'my records' images and text to 'on' if already selected. 
 */

function loadFolders()
{
	var objCookie = getCookie("saves");
	var savedRecordsCookie = recordsInCookie();
	
	if ( savedRecordsCookie > 0 ) 
	{
		// set the master 'my records' image
		
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
					document.getElementById('folder_' + resultSet + recordNumber).src = "images/folder_on.gif";
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
 * Simple alert message to confirm deletion of 
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

//-->