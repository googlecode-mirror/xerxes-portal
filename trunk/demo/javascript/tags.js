	/**
	 * FUNCTIONS FOR MANGING TAGS
   *
   * CALLER REQUIREMENTS:
   *  For autocomplete, you need a div on your page id="tag_suggestions",
   *  class="autocomplete".  It can (and should) be set to css display:none.
   *  The autocompleter will take it's suggestions from things on the page
   *  wrapped with <span class="label_list_item"></span>. These can be in a
   *  hidden div, or displayed, just on the page somewhere. 
	 */

	addEvent(window, 'load', findTagElements);
  addEvent(window, 'load', loadTagSuggestions);
  addEvent(window, 'load', addAutoCompleters);

	function findTagElements()
	{		
		var i;
		
		var forms = document.getElementsByTagName('form');
		var inputs = document.getElementsByTagName('input');
		var links = document.getElementsByTagName('a');
		
		// add onSubmit event to 'tags' forms
		
		for ( i = 0; i < forms.length; i++)
		{
			if( /tags/.test(forms[i].className) )
			{
				forms[i].onsubmit = function () {
					return updateTags(this);
				}
			}
		}
		
		// disable all the 'tagsSubmit' buttons
		
		for ( i = 0; i < inputs.length; i++)
		{
			if( /tagsSubmit/.test(inputs[i].className) )
			{
				inputs[i].disabled = true;
			}
		}
		
		// add onFocus event to 'tagsInput' text boxes to 
		// activate the submit button again
    // Add js local autocompleter to tag input boxes. 
		for ( i = 0; i < inputs.length; i++)
		{
			if( /tagsInput/.test(inputs[i].className) )
			{			
				inputs[i].onfocus = function () {
					activateButton(this)
				}
				inputs[i].onkeypress = function () {
					activateButton(this)
				}
				inputs[i].onblur = function () {
					deactivateButton(this)
				}
			}
		}
		
		// add onClick event for 'deleteRecord' links
		// ****** this needs to be moved into the saves.js ********* 
		
		for ( i = 0; i < links.length; i++)
		{
			if ( /deleteRecord/.test(links[i].className) )
			{			
				links[i].onclick = function () {
					return sureDelete()
				}
			}
		}	
		
	}	


  
  function loadTagSuggestions() {
    // don't ever create a new array, always use the array that's there, so
    // the autocompleter will keep using it--if we had assigned the variable to a new
    // array, then we would lose the connection with the existing autocompleters..

		// create new array only if we don't already have one
    if (typeof(window["tag_suggestions"]) == "undefined") tag_suggestions = new Array();

    //remove all elements from the array
    tag_suggestions.splice(0, tag_suggestions.length);

    // re-add current list
    var list = $$('.label_list_item');
		for( i=0; i<list.length; i++) {
			tag_suggestions.push( list[i].innerHTML );
		}  
}

  function addAutoCompleters() {
    //make sure the tag suggestions global variable is defined,
    //so we can share the same array reference that loadTagSuggestions
    //will use. 
    if (typeof(window["tag_suggestions"]) == "undefined") tag_suggestions = new Array();
    
     inputs = $$('.tagsInput');
     for ( i = 0; i < inputs.length ; i++ ) {
       addAutoCompleterToID( inputs[i].id );
		 }
	}
  function addAutoCompleterToID(id) {
     new Autocompleter.Local(id, 'tag_suggestions', tag_suggestions, {'partialSearch': false, 'tokens': [',']});
  }
	
	function updateTags(form)
	{
		// we'll switch the action here to one different from the one set in html
		// so that we can handle ajax-originated requests differently.  server will
		// perform same command, but view will spit back the newly calculated totals
		// which we will update in the interface
		
		$(form).action.value = "tags_edit_ajax";
		
		$(form).request(
		{
			onSuccess: function(transport)
			{			
				// update the shadow copy of the tags to the new value
				// and show the newly calculated totals on the side nav
				
				form.tagsShaddow.value = form.tags.value;
				$('labelsMaster').update(transport.responseText);

				// highlight that something happended
				highlightTagUpdate(form.submitButton);

        //reload autocompleter suggestions 
        loadTagSuggestions(); 
			},
			
			onFailure: function(transport)
			{
				alert('Sorry, there was an error, your labels could not be updated.');
			} 
		});
		
		return false;
	}
	
	function activateButton(inputTags)
	{
		// find the submit button in this same form block
		// and turn off the disabled
		
		var submitID = inputTags.id.replace("tags", "submit");
		
		$(submitID).value = "Update";
		$(submitID).disabled = false;
	}

	function deactivateButton(inputTags)
	{
		// find the hidden shadow field and submit button in thus
		// same form block
		
		var submitID = inputTags.id.replace("tags", "submit");
		var shadowID = inputTags.id.replace("tags", "shadow");
		
		var oldValue = trim($(shadowID).value);
		var newValue = trim(inputTags.value);
		
		// if the user made no changes, turn the submit button back off
		
		if ( oldValue == newValue )
		{
			$(submitID).disabled = true;
		}
	}

	function highlightTagUpdate(button)
	{
		// highlight the button
		
		new Effect.Highlight(button);
		new Effect.Highlight('labelsMaster');
		
		// then disable it again
		
		$(button.id).value = "Updated";
		$(button.id).disabled = true;
	}

	function trim(str, chars)
	{
		return ltrim(rtrim(str, chars), chars);
	}
	
	function ltrim(str, chars)
	{
		chars = chars || "\\s";
		return str.replace(new RegExp("^[" + chars + "]+", "g"), "");
	}
	
	function rtrim(str, chars)
	{
		chars = chars || "\\s";
		return str.replace(new RegExp("[" + chars + "]+$", "g"), "");
	}
