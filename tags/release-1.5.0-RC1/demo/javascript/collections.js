/** FUNCTIONS FOR USER-CREATED COLLECTIONS
  * (aka user-created categories)
  *
  *  Adds confirm JS to delete functions on collection edit form.
  *  Also adds AJAXy goodness to the db save dialog. 
  **/
  
  
  addEvent(window, 'load', addConfirmDialogs);
  addEvent(window, 'load', addDynamicSectionChoice);
  addEvent(window, 'load', loadCollectionStrings);
  
  // Can be loaded by calling page in js global variables, but if not
  // we use some defaults. 
  function loadCollectionStrings() {
     if (typeof(window['collection_default_new_name']) == "undefined") {
      collection_default_new_name = 'My Saved Databases';
     }
     if (typeof(window['collection_default_new_section_name']) == "undefined") {     
      collection_default_new_section_name = 'Databases';
     }
  }
  
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
    // Disable the whole form while we're working so the user doesn't interact
    // with an incompletely setup form. 
    $('save_database').disable();
    
    // Load the subcategory selection with appropriate values
    // for the selected category. 
    
    new Ajax.Request('./?format=json&base=collections&action=subject&username=jrochki1&subject=' + this.getValue(), 
      { onSuccess: function(transport) {
          var responseData = transport.responseText.evalJSON(true);
          
          $('subcategory').options.length = 0;
          if ( responseData.category.subcategory && responseData.category.subcategory.length > 1  ) {
            // We're going to show subcat selection on this page,
            // and change the action to point to save_complete           
            $('action_input').value = 'save_complete';
            
            
            responseData.category.subcategory.each( 
              function(subcat) {
                addSelectOption($('subcategory'), subcat.name, subcat.id);
              }
            );
            $('subcategory').enable();
            $('subcategory_choice').show();
          }
          else {
            // Change the action back to 'save_choose_subheading', which will
            // do it in the background with 0 or 1 subheading choices. 
            $('action_input').value = 'save_choose_subheading';
            
            // And don't show the sub menu. 
            $('subcategory_choice').hide();
            $('subcategory').disable();
          }          
        }
      }
    );
    
    // Now that we're done, enable it again.     
    $('save_database').enable();        

  }
  
  /* Adds ajaxy javascript to the 'save database' dialog, to 
     dynamically change available sections based on selected collection */
  function addDynamicSectionChoice() {
    if ($('save_database')) {            
      
      
      $('subject').onchange = changedCategoryChoice;
                  
      // Call it ourselves for initial load
      $('subject').onchange();
    }
  }
  
  
