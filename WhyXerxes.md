Thinking about installing Xerxes?  Here are some of the benefits Xerxes provides over the standard Metalib interface.




# Categories and databases #

## More intuitive method for selecting categories and databases ##

Probably the most complex and confusing feature of Metalib is the "Metasearch" module.  It simply offers too many options, all crammed into a single screen.  New users easily overlook the crowded pull-down menus on the left, often just skipping right to the search box.

Xerxes, on the other hand, alleviates this complexity by dividing the steps of selected categories and choosing databases into two different screens.  Instead of hiding the categories away in a pull-down menu, as Metalib does, Xerxes [prominently displays the categories on the home page](http://library.calstate.edu/media/png/xerxes-why-databases-categories.png), so that users can more easily notice and select their area of study.

Once users have chosen a category, they are, in most Xerxes implementations, presented with the [databases organized into "most useful" and "also useful" subcategories](http://library.calstate.edu/media/png/xerxes-why-databases-subject.png), with the former group pre-selected.  Users can, and often do, go straight to the search box and enter their search terms.  Since the most useful databases for that discipline are pre-selected, these databases often produce useful results.  But more advanced users can also easily select or un-select databases as they see fit.

## A single workflow for selecting categories and databases ##

Recognizing the complexity of the Metasearch module, Ex Libris developed the "Quick Set" module to provide a simpler workflow for searching.

But the Quick Set module suffers from the opposite problems of the Metasearch module: The quick sets tend to be broad, high-level subject categories, which can, in turn, produce unfocused search results.  The module provides no options for choosing narrower subcategories or selecting specific databases.

Having two different modules for selecting categories and databases (Quick Set and Metasearch), in turn, requires that the library set-up and maintain two different sets of subject lists and database assignments.

By giving users a simple way to select a category and then search, Xerxes achieves the _goal_ of the Quick Set module.  But it also achieves the _goal_ of the Metasearch module in giving users the option to select specific databases.  And it does all of that using a single workflow instead of two different workflows in two different modules.  Less for users to learn.  Less for the library to maintain.

## Simpler deep-linking ##

Metalb URLs contain session-specific information that prevents them from being persistent links.  This makes it difficult for librarians or faculty to link into Metalib from their own web pages.  To compensate, Metalib even has a management module for "generating" deep-links, so the library might give their users instructions on how to link into the system.

Xerxes creates simple, stable URLs.  You want to link to a subject category in Xerxes?  [Just cut-and-paste the URL from your browser](http://library.calstate.edu/media/png/xerxes-why-databases-deep.png) into your web page.  Done.

## Powerful embedding feature ##

Xerxes goes one step further than deep-linking.  It provides an easy way for faculty and librarians to actually [embed the search box and databases for a category into an external website](http://library.calstate.edu/media/png/xerxes-why-databases-embed-full.png), like Lib Guides or a learning management system.  Users can customize how this looks before adding the code to their external site.

## Add librarian contact info and related resources to categories ##

With Xerxes, you can use the Metalib KB to add librarian contact info to each category.  Simply create a "librarian" IRD in Metalib (just create an IRD with a type of "librarian"), and assign it to a category.  Xerxes will, in turn, display that as actual contact information in the subject.

Xerxes also allows libraries to [designate that specific subcategories display in the sidebar of the subject page](http://library.calstate.edu/media/png/xerxes-why-databases-sidebar.png).  This is a handy mechanism for adding related websites to each subject without overwhelming the display.

# Search results #

## Doesn’t break the back button ##

One major frustration for users of Metalib is that they cannot use their browser back button.  If you perform a search in Metalib, and then chose a search result, you can’t navigate back to the brief results using your back button.  Try it yourself.  Breaking the back button is a fundamental design mistake.

In Xerxes, the back button behaves as you would expect.   This is simple stuff.  But it makes a big difference in the usability of the system.

## Handles sessions better ##

In Metalib, if a user waits too long between actions -- perhaps because they are actually reading an article from one of their results -- Metalib will time-out the session, forcing the user to re-do their search.  This is incredibly frustrating, since it may take you upwards of a minute to get back to your place in the search results.

Xerxes has longer session time-outs, for starters.  But, even if the session ends, the user can log back in, and Xerxes puts them right back in the search results where they were.   Again, this is a simple change in behavior that makes a big difference to users.

## Spell checker ##

Xerxes taps into the Yahoo in order to offer [spelling corrections](http://library.calstate.edu/media/png/xerxes-why-search-spell.png), including those for proper names and places.  Your users need this.

## Peer-reviewed flag, full-text indicators, and abstracts in the brief results ##

Xerxes highlights search results that are from peer-reviewed journals, and also highlights when search results have full-text (either natively from that database, or in another database via SFX).  Moreover, it provides this information at the brief results level, where users value it the most.

Xerxes has also always displayed a portion of the abstract at the brief results level -- again, a feature users say they highly value.

## Format indicator ##

Usability studies have shown that users of metasearch systems would like a clear indication of the format of each search result -- that is, whether it is a book, article, video, or something else.  Xerxes provides a [detailed format designation](http://library.calstate.edu/media/png/xerxes-why-search-format.png) for each result, including such designations as book review, thesis, pamphlet, and so on.

## Language indicator ##

Many databases include abstracts written in English for articles actually published in another language.  It's very easy for users to not see the language note saying an article is published in, say, Japanese -- even in most native interfaces.

Xerxes prominently [notifies users when an article is not in English](http://library.calstate.edu/media/png/xerxes-why-search_language.png).  Feel free to change this behavior too, if you prefer.

## Better OpenURL linking ##

Xerxes supplements, and even corrects, the OpenURL that Metalib generates from the metadata in the bibliographic record.  That makes for fuller, more accurate OpenURLs, which benefits SFX and interlibrary loan.

## Recommendations via bX ##

Xerxes will display [related articles](http://library.calstate.edu/media/png/xerxes-why-search-bx.png) using Ex Libris’ bX recommendation service in the full record display.

## Citation style help ##

Xerxes offers in the full record display the bibliographic data [formatted in three common citation styles](http://library.calstate.edu/media/png/xerxes-why-serch-citation.png).  This is a quick and dirty way for users to add the record to their bibliography.

# Saving and export options #

## More intuitive export options ##

Probably the second most confusing module in Metalib is the “My Research” module.

Metalib makes it easy to save records.  It makes it easy to select or delete saved records.  But new users are left scratching their heads as to how they actually do anything with their saved records.  The reason is because the My Research module is just not well designed.  Options for exporting the records are buried.  And, even if the user stumbles across it, it’s limited mostly to emailing the records to yourself.

## Export groups of records to citation management systems ##

In addition to emailing records to yourself, Xerxes provides a full set of [export options to Refworks, Endnote, Zotero, etc.](http://library.calstate.edu/media/png/xerxes-why-save-refworks.png)

Future version of Xerxes will also include options to format saved records in various citation styles directly in Xerxes.

## Tagging ##

Users can organize their records in groups, or simply add a note to a record, using a simple but powerful [tagging mechanism](http://library.calstate.edu/media/png/xerxes-why-save-labels.png).  This allows students to keep their History 201 research separate from their  Psychology 202 research.

# General #

## Xerxes is more flexible, customizable, and maintainable than the Metlalib interface. ##

You can change anything and everything about the Xerxes interface.  You can even change how Xerxes behaves, or add new features of your own.  No need to mess around with hundreds of HTML fragment files, as in Metalib.  No core functionality is off limits.

But we’ve also designed Xerxes so you can keep your customizations compact and separate from the main distribution code.  That makes local customizations more maintainable.

## Immunity to Metalib upgrades ##

The Xerxes interface is completely separate from Metalib, and that makes it immune to Metalib upgrades.   You can upgrade Metalib as frequently as you like to gain KB and software updates.  But you don’t have to worry about a Metalib upgrade changing your interface or wiping out your customizations.  With Xerxes, you control when you want to change the interface.

## Mobile interface ##

The latest version of Xerxes recognizes mobile devices and adjust its display, giving users slimmed-down versions of the [search](http://library.calstate.edu/media/png/xerxes-why-mobile-categories.png) and [results](http://library.calstate.edu/media/png/xerxes-why-mobile-results.png) pages specifically designed for the smaller display.