/** FUNCTIONS FOR USER-CREATED COLLECTIONS
  * (aka user-created categories)
  *
  *  Adds confirm JS to delete functions on collection edit form.
  **/
  
  addEvent(window, 'load', addConfirmDialogs);
  
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
