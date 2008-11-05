	/**
	* Functions for toggling metasearch advanced/simple mode without
	* a trip to the server.
	*
	* ASSUMPTION: Set global javascript variable "advancedMode" to boolean
	* true or false in parent page, depending on initially displayed mode. 
	*/
	
	addEvent(window, 'load', addToggle);
	
	function addToggle()
	{
		if ( $('searchBox_toggle') )
		{
			$('searchBox_toggle').onclick = function () {
				return toggleSearchMode();
			}
		}
	}

	function toggleSearchMode() {
		if ( advancedMode ) {
		hideAdvancedFeatures();
		advancedMode = false;
		}
		else {
		showAdvancedFeatures();
		advancedMode = true;
		}
		
		return false;

	}
	
	function showAdvancedFeatures() {
		// Add options for advanced, preserve selection. 
		selected = $("field").value;
		$("field").insert('<option value="ISSN">ISSN</option>');
		$("field").insert('<option value="ISBN">ISBN</option>');
		$("field").insert('<option value="WYR">year</option>');
		$("field").value = selected;
		
			
		$("find_operator1").enable();
		$("find_operator1").show();

		$("searchBox_advanced_newline").show();
		
		$("query2").enable();
		$("field2").enable();
		$("searchBox_advanced_pair").show();
		
		$("searchBox_toggle").update("Fewer options");
		
	}
	
	function hideAdvancedFeatures() {
		// Remove advanced options from first field. 
		options = $("field").options;
		//reverse iterate since we're removing in the middle
		for(i=options.length - 1; i > 0 ;i--){
			o = options[i];
			value = o.value
			if ( (value == "WYR") || 
				 (value == "ISSN") || 
				 (value == "ISBN") ) {
			$("field").removeChild(o);
			}
		}
		
		$("find_operator1").hide();
		$("find_operator1").disable();
		
		$("searchBox_advanced_newline").hide();
		
		$("searchBox_advanced_pair").hide();
		$("query2").disable();
		$("field2").disable();		
		
		$("searchBox_toggle").update("More options");
	}

