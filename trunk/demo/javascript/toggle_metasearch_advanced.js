	/**
	 * Functions for toggling metasearch advanced/simple mode without
	 * a trip to the server.
	 *
	 *
	 * @author Jonathan Rochkind
	 * @copyright 2009 Johns Hopkins University
	 * @link http://xerxes.calstate.edu
	 * @license http://www.gnu.org/licenses/
	 * @version $Id$
	 * @package Xerxes
	 */
	 
	 
	addEvent(window, 'load', addToggle); 
	
	function addToggle()
	{ 
		$$('.searchBox_toggle').each( function(toggle) {
        toggle.onclick = function () { 
          return toggleSearchMode($(this).up('form'));
        }
    });
	}

	function toggleSearchMode(myForm) {

		if ( isAdvancedMode(myForm) ) {
      hideAdvancedFeatures(myForm);
		}
		else {
      showAdvancedFeatures(myForm);
		}
		
		return false;
	}
  
  /* By looking at the form, figure out if we're in advanced mode or not. */
  function isAdvancedMode(myForm) {    
      return Boolean(myForm.down(".find_operator1") && myForm.down(".find_operator1").getStyle('display') != 'none' );    
  }
	
	function showAdvancedFeatures(myForm) {
		// Add options for advanced, preserve selection.
    myField = myForm.down(".field");
    

    
		selected = myField.value;
		myField.insert('<option value="ISSN">' + jsDisplayConstants['text_searchbox_field_issn'] + '</option>');
		myField.insert('<option value="ISBN">' + jsDisplayConstants['text_searchbox_field_isbn'] + '</option>');
		myField.insert('<option value="WYR">' +  jsDisplayConstants['text_searchbox_field_year'] + '</option>');
		myField.value = selected;
		

			
		myForm.down(".find_operator1").enable();
		myForm.down(".find_operator1").show();
		

    
		// ada labels
		myForm.down(".find_operator1label").show();
		myForm.down(".field2label").show();
    


		myForm.down(".searchBox_advanced_newline").show();
		

    
		myForm.down(".query2").enable();
    
		myForm.down(".field2").enable();
		myForm.down(".searchBox_advanced_pair").show();
		
		myForm.down(".searchBox_toggle").update(jsDisplayConstants['text_searchbox_options_fewer']);
		

    
	}
	
	function hideAdvancedFeatures(myForm) {
    
		// Remove advanced options from first field. 
		options = myForm.down(".field").options;
		//reverse iterate since we're removing in the middle
		for(i=options.length - 1; i > 0 ;i--){
			o = options[i];
			value = o.value
			if ( (value == "WYR") || 
				 (value == "ISSN") || 
				 (value == "ISBN") ) {
			myForm.down(".field").removeChild(o);
			}
		}
		
		myForm.down(".find_operator1").hide();
		myForm.down(".find_operator1").disable();
		
		myForm.down(".searchBox_advanced_newline").hide();
		
		// ada labels
		myForm.down(".find_operator1label").hide();
		myForm.down(".field2label").hide();

		myForm.down(".searchBox_advanced_pair").hide();
		myForm.down(".query2").disable();
		myForm.down(".field2").disable();		
		
		myForm.down(".searchBox_toggle").update(jsDisplayConstants['text_searchbox_options_more']);
	}

