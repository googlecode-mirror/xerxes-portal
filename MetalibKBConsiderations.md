For a variety of reasons, Xerxes displays the Metalib Knowledgebase differently than the standard Metalib interface.  It also allows you to to achieve some additional functionality beyond what Metalib normally provides by simply following some conventions in the Metalib KB.

## Changes in Categories Admin ##

After making any changes in Metalib's Categories Admin module -- such as creating or renaming a category, or assigning databases to a subcategory -- you need to click on the "ALL Subcategories" link at the top of the page.  This re-indexes your category assignments. The process takes just a few seconds to run.

If you don't perform this step, the Xerxes populate/databases action will not pick up your category changes. This is a bug in Metalib, and one you will find yourself tripping over more than once.

## Display of categories in Xerxes versus Metalib ##

Xerxes employs a different philosophy for arranging categories and subcategories than Metalib.  As you implement Xerxes, you will find that you may need to rethink (and, in turn, re-organize) your categories.

Metalib basically assumes that you have large, overarching categories (e.g., Social Sciences or Humanities) and then topical subcategories focused around disciplines (e.g., History).  The Metalib interface is set-up to support this model by displaying subcategories individually.

Xerxes, on the other hand, is set-up for a flat hierarchy where the categories are disciplines (e.g., History) and the subcategories convey either some sense of usefulness ("Most useful" and "Also useful") or format ("Books" and "Articles"), or both.  Xerxes inlines the display of subcategories, so users see them together on the same page, rather than individually, as in Metalib.

We think a flat, discipline-based hierarchy makes more sense to students, since they can more readily identify with the course they are taking -- I'm in History 301, so I'll choose the 'History' category -- then having to divine that the subject they want is contained within a super-grouping like Social Sciences or Humanities.

## Subcategories and Librarian contact information in the sidebar ##

You can have Xerxes display certain subcategories in the sidebar versus the main part of the subject page.  This is useful for adding supplementary information to a category, like librarian contact information, or links to subject guides and secondary websites.

Here's a [working example from Sacramento State](http://xerxes.calstate.edu/sacramento/databases/subject/nursing).

In this example, all of the research guides and the librarian contact are just IRDs in Metalib.  In each category, Sacramento has created the necessary subcategories for both, and assigned the IRDs as necessary.

The name and order of the subcategories is not important, so you can control the order in which they appear and the headings that display purely in Categories Admin.  Consistency is import, though, since you need to list the names of the subcategories that should display in the sidebar in a config.xml entry -- [subcategories\_sidebar](Configuration#subcategories_sidebar.md).

The one 'trick' is the Librarian contact.  What we've done here is created a new 'type' in Metalib called 'Librarian'.  You can create new types at any time in Metalib with a config file on  the Metalib side.  When Xerxes sees a type of 'Librarian' (in the sidebar) it knows to display the contact info as you see it in the example above.

The data you see in the example -- office, email, telephone, etc. -- is taken from the 'Library' tab of the IRD, where there are fields for all of this.  If you just swap the word 'library' for 'librarian' in your mind when setting this up in Metalib, it should all make sense, and make this seem a little less hacky.

There is also a new config.xml entry called [databases\_type\_exclude\_az](Configuration#databases_type_exclude_az.md) that allows you to exclude certain IRDs from the A-Z database listing based on their 'type'.  This could be useful for other reasons, but one thing it allows you to do is remove these Librarian IRDs that we've created from the A-Z list.


## Search-and-link databases that use HTTP POST ##

The Metalib X-Server currently contains a bug that prevents it from properly handling search-and-link databases that require HTTP POST (as opposed to GET) to link to the website's search results.

By necessity, Xerxes employs a workaround for this problem.  Basically, you're going to copy a field in the search configuration into the IRD, so that Xerxes can gain access to the data necessary to create the HTTP POST request itself.

For these resources, you'll need to go into the Metalib Management interface:

  * Locate the IRD.
  * On the `Subscription` tab, select the `Configuration Code`.
  * Once you are in the search configuration, chose the `Term Transformations` tab.
  * Copy the `URL Mask` field.

Now, usually the `URL Mask` field is disabled, which means you can't select and copy it directly.  Bummer.  So, you'll need to look at the HTML source code to get this information.

In your browser, go to `view` > `Source` (or in Firefox `Page source`), and then use `crtl+f` on your keyboard to bring-up the browser's search facility, and search for `TERM1`.

That should take you directly to an HTML tag that looks like this one from TOXNET:

> `<input name="REG_EXP_1" value="queryxxx=TERM1&revisesearch=/home/httpd/htdocs/html/TOXLINE.htm&second_search=1&database=toxline&and=1&Stemming=1&max=50000" size=65 maxlength="500" disabled=Y>`

Now copy the data in the `value` part of that input field:

> ` queryxxx=TERM1&revisesearch=/home/httpd/htdocs/html/TOXLINE.htm&second_search=1&database=toxline&and=1&Stemming=1&max=50000 `

Use your browser's back button to go back to the IRD, to the `Subscription` tab.  Paste the data into the `Alternative Link to Records in Native Interface` field.  Note that this is the **alternate** link to records, not the main one.

After you run the `databases/populate` command again, Xerxes should be able to correctly search and link to this resource.