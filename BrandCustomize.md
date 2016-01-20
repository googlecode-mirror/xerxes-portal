After you get Xerxes installed and configured, you’ll want to brand the application to look like your library website, as well change the default labels to meet your preferences.

## Branding ##

The simplest way to brand the application is to open `demo/xsl/includes.xsl`, and place your library website header and footer HTML in the `header_div` and `footer_div` templates, respectively.

Accompanying CSS definitions for the header and footer can go in `demo/css/local.css`.

### Tips: ###

  * Anything you add to an XSLT file needs to be well-formed XML, so you may need to convert your library header and footer HTML to XHTML first.  Dreamweaver can do this easily; simply open the HTML file(s) and use file > convert > XHTML.

  * Importing your entire library or university stylesheet into Xerxes will almost invariably result in conflicts.  Try to narrow down the styles you add to only those necessary for the branding.

  * Wrap your library header and footer it in one or more div's with a unique class name, such as  `<div class="my_university_branding">` (obviously "my\_university\_branding" can be anything).

> Now take the CSS stylesheet that is meant to go with that branded content, and pre-pend every single CSS declaration with:

> `.my_university_branding (space)`

> Now your university branding CSS only applies to your specific branded content, and not to the rest of Xerxes. Put this branding CSS in a separate file, such as `library.css` and add `@import url("library.css");` to the _end_ of your local.css file.

> This won't usually get you 100% of the way there, but may get you 80 or 90% and save you some time, and clarify what the remaining merge issues are.


## Labels ##

Virtually all of the text labels in the system are defined as XSLT variables.  If you want to change one of the labels you see on a page, first locate the name of the variable in the XSLT and then _add_ it to your `demo/xsl/includes.xsl`.

### Tips: ###

  * Do NOT change the original variable(s) in `lib/xsl/labels/eng.xsl`. This is a handy file to reference, but you should change labels by copy and pasting them (essentially _redefining_ the variable) in your local `includes.xsl`.  You do NOT need to create a local `eng.xsl` file.

  * The label variables can contain XSLT code in addition to text.  If you want a label to be dynamic in some way, based on the underlying XML, you can use the same `xsl:if`, `xsl:choose`, and `xsl:for-each` constructs that any XSLT template might use.


## Interface Customization: XSLT ##

All of the Xerxes interface is written in XSLT.

If you're familiar with XSLT and want to change some aspect of the interface, then locate the file you want to change in `lib/xsl` and COPY it to `demo/xsl`.  If you need to make a change to `lib/xsl/includes.xsl`, then copy-and-paste ONLY the template that you wish to change into `demo/xsl/includes.xsl`.

Any files in `demo/xsl` will override its equivalent in `lib/xsl`.  By setting-up Xerxes this way, you keep your customizations clearly and cleanly separate from the distribution files, making future upgrades easier.

### Tips: ###

  * Get a book on XSLT.  There's a lot to the language beyond the surface, and you'll be better able to make customizations if you know some of the advanced stuff.

  * Try to customize as little as possible.  If you can get away with changing just one or two templates or variables to achieved the desired goal, do that instead of copying an entire file.

  * Before you make changes, join the [listserv](http://groups.google.com/group/xerxes-portal?pli=1) and tell us what you're trying to accomplish.  We are always happy to give advice on how best to implement changes.  Or we might even fold the changes into the distribution itself (so you don't have to maintain them) if we think others will want to do the same thing.

## Interface Customization: CSS ##

The main Xerxes styles are defined in a file called `xerxes-blue.css`.

Rather than make changes directly to this file, you should copy-and-paste the style definition you want to change into `local.css`.  Just like with the XSLT, adding a style to `local.css` overrides the main style in `xerxes-blue.css`, making it easier for you to upgrade Xerxes in the future.

### Tips: ###

  * You don't need to copy-and-paste the entire style into `local.css`.  Rather, just re-define the part of the style you want to change.