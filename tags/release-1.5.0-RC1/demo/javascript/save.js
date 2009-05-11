/**
* FUNCTIONS FOR SAVING RECORDS
*/

/* CALLER REQUIREMENTS:
set a global js variable called numSavedRecords to the current number of records
in saved records area. Used to determine when to toggle saved records icon to
indicate presence of records 

set a global js variable isTemporarySession (boolean), used to determine whether to
ajax add a tagging input on save or not. 
*/

addEvent(window, 'load', loadSaveStrings );	
addEvent(window, 'load', addAjaxToSaveLinks);
addEvent(window, 'load', addDatabaseLimitChecks );
addEvent(window, 'load', addClearCheck );
  
function clearCheckedDatabases()
{
	var field = document.export_form.record;

	for (i = 0; i < field.length; i++)
	{
		field[i].checked = false ;
	}
}

function addClearCheck()
{
	if ( $("clear_databases") )
	{
		$("clear_databases").onclick = function() {
			return clearCheckedDatabases()
		}
	}
}
  
function addDatabaseLimitChecks() {
	$$('.subjectDatabaseCheckbox').each( function(checkbox) {
		checkbox.onclick = function() {databaseLimitCheckbox(this)};
	});
	
	$$('.metasearchForm').each( function(form) {
		form.onsubmit = function () {return databaseLimit(this)};
	});
}
  
  
function loadSaveStrings() {
	
	// string variables. Can be set in global js vars in calling context
	// if desired, to over-ride/customize
	
	if (typeof(window['save_action_label']) == "undefined") {
		save_action_label = 'Save this record';
	}     
	
	if (typeof(window['saved_permanent_label']) == "undefined") {
		saved_permanent_label = 'Record saved';
	}
	
	if (typeof(window['saved_temporary_label']) == "undefined") {
		saved_temporary_label = 'Temporarily saved';
	}
	
	if (typeof(window['saved_temporary_login_label']) == "undefined") {
		saved_temporary_login_label = 'login to save permanently';
	}
}
	
/**
 * Add onClick event to save records
 */

function addAjaxToSaveLinks()
{
	// add onClick event to save the record
	
	var links = document.getElementsByTagName('a');
	
	for ( i = 0; i < links.length; i++)
	{
		if ( /saveRecord/.test(links[i].className) )
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
		var arrID = id.split(/_|:/);
		var resultSet = arrID[1]; 
		var recordNumber = arrID[2];
	
		// Should be set by main page in global js variable, if not we set.
		if (typeof(window["numSavedRecords"]) == "undefined") {
			numSavedRecords = 0;
		}
		
		if (typeof(window["isTemporarySession"]) == "undefined") {
			 isTemporarySession = true;
		}
		
		var url = $(id).readAttribute('href');
		
		// we want an ajax-json response from Xerxes
		
		var base = url.split('?')[0]
		var queryParams = url.toQueryParams();	
		queryParams["format"] = "json";
		url = base + '?' + $H(queryParams).toQueryString();
	
		if ( $(id).hasClassName("disabled")) {
			return false;
		}
		
		// do it! update our icons only after success please! then we're only
		// telling them they have saved a record if they really have! hooray
		// for javascript closures.
		
		var workingText = "Saving...";
		if ( $(id).hasClassName("saved") ) workingText = "Removing...";
		
		$(id).update(workingText);
		$(id).addClassName("disabled");
		
		new Ajax.Request(url, {"onFailure": function(ajaxRequest) {
			alert('Sorry, an error occured, your record was not saved.');
			},
			"onSuccess": function (ajaxRequest) {

			// add tag input form. first need to get saved record id out
			// of ajax response. 
      
			
			var responseData = ajaxRequest.responseText.evalJSON(true);
			var savedID = responseData.savedRecordID;



      
			if ( $(id).hasClassName("saved") )
			{
				numSavedRecords--;
        $$('#saveRecordOption_' + resultSet+ '_' + recordNumber + ' .temporary_login_note').each(function(node) {
          node.remove();
        });
				$('folder_' + resultSet + recordNumber).src = "images/folder.gif";
				$(id).update( save_action_label );
				$(id).removeClassName("saved");


				// remove label input
				
				var label_input = $('label_' + resultSet + ':' + recordNumber);
				if (label_input) label_input.remove();
			}
			else
			{
				numSavedRecords++;
				$('folder_' + resultSet + recordNumber).src = "images/folder_on.gif";

        // Different label depending on whether they are logged in or not. 
        // We tell if they are logged in or not, as well as find the login
        // url, based on looking for 'login' link in the DOM. 
        if ($('login')) {
          var temporary_login_note = ' <span class="temporary_login_note"> ( <a  href="' + $('login').href +'">' + saved_temporary_login_label + ' </a> ) </span>';
                   
         // Put the login link back please         
         $(id).update( saved_temporary_label ); 
         $(id).insert({after: temporary_login_note  });
        }
        else {
          $(id).update( saved_permanent_label );
        }
				$(id).addClassName("saved");

				// add tag input
				
				if ( ! isTemporarySession && savedID)
				{					
					var input_div = $('template_tag_input').cloneNode(true);
					var new_form = input_div.down('form');
	
					// take the template for a tag input and set it up for this particular
					// record
	
					input_div.id = "label_" + resultSet + ":" + recordNumber; 
					new_form.record.value = savedID;
					new_form.tagsShaddow.id = 'shadow-' + savedID; 
					new_form.tags.id = 'tags-' + savedID;
					
					new_form.tags.onfocus = function () {
						activateButton(this)
					}
					new_form.tags.onkeypress = function () {
						activateButton(this)
					}
					new_form.tags.onblur = function () {
						deactivateButton(this)
					}
	
					new_form.submitButton.id = 'submit-' + savedID;
					new_form.submitButton.disabled = true;
					new_form.onsubmit = function () {
						return updateTags(this);
					}
				
					// add it to the page, now that it's all set up.
					
					var parentBlock = $(id).up('.recordActions');
					
					if (parentBlock) 
					{
						parentBlock.insert(input_div);
						
						// and add the autocompleter
	
						addAutoCompleterToID(new_form.tags.id);
						input_div.show();
					}
				}
			}
			
			$(id).removeClassName("disabled");
	
			// change master folder image
			
			if ( numSavedRecords > 0 ) {
				$('folder').src = 'images/folder_on.gif';
			}
			else {
				$('folder').src = 'images/folder.gif';
			}
		}
	});	
		return false;
	}
	
	/**
	 * Checks to see if user has selected more databases than is allowed or none
	 *
	 * @note global variable xerxes_iSearchable set by include.xsl since this is set server-side
	 * @param form1		form submitting the request
	 * @return bool		true if number of selected databases is less, false otherwise
	 */
	
	function databaseLimit(form1)
	{
		var iTotal = numChecked(form1)
		
    
    // Allow a hidden input with name="database" to get them by this check,
    // even if they've actually checked no checkboxes. 
    //Some forms have no checkboxes, instead having hidden field!
		if ( iTotal == 0 && $(form1).select('input[type="hidden"][name="database"]') == "") 
		{
			alert("Please select databases to search");
			return false;
		}
		else if ( iTotal > xerxes_iSearchable )
		{
			alert("Sorry, you can only search up to " + xerxes_iSearchable + " databases at one time");
			return false;
		}
		else
		{
			return true;
		}
	}
  
  /* How many checkboxes are checked in the form1 passed in as argument?
     Returns number. Assumes checkboxes have class 'subjectDatabaseCheckbox' */
  function numChecked(form1) {
    var iTotal = 0;
		
    $(form1).select('input.subjectDatabaseCheckbox').each( function(checkbox) {
          if ( checkbox.checked ) iTotal++;
    });
    
    return iTotal;
  }
  
  function databaseLimitCheckbox(checkbox) {
    var limit_reached = (numChecked(checkbox.form) > xerxes_iSearchable);
    
    if (limit_reached) {
      alert("Sorry, you can only search up to " + xerxes_iSearchable + " databases at one time");
			checkbox.checked = false;
      return false;
    }
    else {
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
	
