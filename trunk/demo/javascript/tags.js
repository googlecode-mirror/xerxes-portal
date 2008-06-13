	/**
	 * FUNCTIONS FOR MANGING TAGS
	 */

	addEvent(window, 'load', findTagElements);

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
			},
			
			onFailure: function(transport)
			{
				alert(transport.responseText);
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