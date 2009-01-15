/** FUNCTIONS FOR USER-CREATED COLLECTIONS
  * (aka user-created categories)
  *
  *  Adds confirm JS to delete functions on collection edit form.
  *  Also adds AJAXy goodness to the db save dialog. 
  **/
  
  default_new_subject = 'My Collection';
  default_new_subcategory = 'Databases';
  
  addEvent(window, 'load', addConfirmDialogs);
  addEvent(window, 'load', addDynamicSectionChoice);
  
  function addSelectOption(selectObj, text, value, isSelected, id) 
  {
    if (selectObj != null && selectObj.options != null)
    {
        opt = new Option(text, value, false, isSelected); 
        selectObj.options[selectObj.options.length] = opt;            
        if (id != null) opt.id = id;          
    }
  }
  
  function addConfirmDialogs() {
    var deleteCollectionLinks = $$('a.deleteCollection');    
    for ( i = 0; i < deleteCollectionLinks.length; i++)
		{
				deleteCollectionLinks[i].onclick = function () {
					return confirm("Are you sure you want to delete this collection?");
        }
    }
    
    var deleteSectionLinks = $$('a.deleteSection');
    for ( i = 0; i < deleteSectionLinks.length; i++)
		{
				deleteSectionLinks[i].onclick = function () {
					return confirm("Are you sure you want to delete this section?");
        }
    }
    
  }
  
  function changedCategoryChoice() {
    // Disable it while we're working so the user doesn't interact
    // with an incomplete form. 
    $('save_database').disable();
    
    if (this.getValue() == 'NEW') {
      // Fill out subcategory selection with default values for a new category.
      
      $('new_subject_name').value = default_new_subject;
      // Load subcategory select options
      $('subcategory').options.length = 0;
      addSelectOption($('subcategory'), "New section...", "NEW");
      $('new_subcategory_name').value = default_new_subcategory;
    }
    else {
      // Load the subcategory selection with appropriate values
      // for the selected category. 
      
      $('new_subject_name').value = '';
      new Ajax.Request('./?format=json&base=collections&action=subject&username=jrochki1&subject=' + this.getValue(), 
        { onSuccess: function(transport) {
            var responseData = transport.responseText.evalJSON(true);
            
            $('subcategory').options.length = 0;
            if ( responseData.category.subcategory ) {
              responseData.category.subcategory.each( 
                function(subcat) {
                  addSelectOption($('subcategory'), subcat.name, subcat.id);
                }
              );
            }

            // Add the option for creating a new subcategory, in some cases
            // may be the only one there. 
            addSelectOption($('subcategory'), "New section...", "NEW");
            // If it was the only one and is selected, fill out text box. 
            if ($('subcategory').getValue() == "NEW") {
              $('new_subcategory_name').value = default_new_subcategory;
            }
            else {
              $('new_subcategory_name').value = '';
            }
          }
        }
      );
 

    }
    
    // Now that we're done, we can make sure it's displayed and enabled. 
    $('subcategory_choice').show();
    $('save_database').enable();
    
    // This needs to be done after enable. 
    if ( this.getValue() == 'NEW') {
      $('new_subject_name').select();
    }
    


  }
  
  function addDynamicSectionChoice() {
    if ($('subject')) {            
      
      // Change action to save_complete, instead of just step 2. 
      $('action_input').value = 'save_complete';

      // Some input checking to avoid blank new names. 
      $('save_database').onsubmit = function() {
        if ( $('subject').getValue() == "NEW" && trim($('new_subject_name').getValue()) == "") {
          $('new_subject_name').value = default_new_subject;
        }
        if ($('subcategory').getValue() == "NEW" &&
         trim($('new_subcategory_name').getValue()) == "") {
          $('new_subcategory_name').value = default_new_subcategory;
        } 
      };
      
      $('subject').onchange = changedCategoryChoice;
      
      $('new_subject_name').onkeypress = function () {
        if ($('subject').value != 'NEW' && $('new_subject_name').value != '') {
          $('subject').value = 'NEW';
          $('subject').onchange();
        }
      };
      
      $('subcategory').onchange = function() {
        if (this.getValue() == "NEW") {
          $('new_subcategory_name').value = default_new_subcategory;
          $('new_subcategory_name').select();
        }
        else {
          $('new_subcategory_name').value = "";
        }
      }
      
      $('new_subcategory_name').onkeypress = function () { 
        if ($('subcategory').getValue() != 'NEW' && $('new_subcategory_name').getValue() != '') {
          $('subcategory').value = 'NEW';          
        }
      };
    
      
      //Add a "Create new" option to the select
      if ($('new_collection')) {
        $('new_collection').update('New collection...');
      }
      else {
        var existingSelection = $('subject').value
        addSelectOption( $('subject'), "New collection...", "NEW", null, "new_collection");
        $('subject').value = existingSelection;
      }
      
      
      // Call it ourselves for initial load
      $('subject').onchange();
    }
  }
  
  
