Each "page" in Xerxes is created using PHP code and XSLT.

The PHP code basically performs one or more tasks -- for example, fetching search results from Metalib -- and rendering it as XML.  In Xerxes, we call these "commands."  The XSLT, in turn, transforms that XML into HTML for display in the browser.  We call this the "view."

## Controller Map ##

In Xerxes, you can freely combine multiple commands together to fetch data or perform other actions, and, use multiple different views to display that data.  A single file -- lib/config/actions.xml – controls the mapping of commands and views to create each "page."

The parameters `base` and `action` in each Xerxes URL corresponds to the `<section>` and `<action>` XML blocks in actions.xml, respectively.  The `<action>` block further defines which commands and view to use to create the page.  See the comments in actions.xml for more information.

## Customizing commands ##

If you need to add a new command, or customize one of the existing commands, rather than edit it directly, you can copy it into your `demo` folder, edit it there, and Xerxes will use the new one in place of the one in the commands directories.  This allows you to keep your customizations separate from the distribution code.

If you want to change the command `databases/DatabasesSubject.php`, for example, simply create a new folder called `commands` in your demo directory.  Then create a folder called `databases` in the new `commands` folder, and copy `DatabasesSubject` there.  In other words, you’re mimicking the directory structure in the main `commands` area, just now in your local demo folder.

## Changing actions.xml ##

If you need to make changes to actions.xml, you can edit the one in demo/config so that it _overrides_ specific actions in the distro file.  This makes actions.xml now behave like the XSLT and commands where local copies override distribution ones.

Some examples:

1. In this one, we're overriding the subject page so that it includes an additional, locally developed command, called `DatabasesSacStateLinks`.  What you see here is the entirety of the local actions.xml file.

We'll simply set-up a `<section name="databases">`  and `<action name="subject">` to uniquely identify this action, and then the guts of the action is copied from the main actions.xml, with the additional command added.

```
<xerxes> 
 <commands> 
  <section name="databases"> 
   <action name="subject"> 
    <command>DatabasesSubject</command> 
    <command>DatabasesSacStateLinks</command> 
    <pathParamMap> 
     <mapEntry pathIndex="2" property="subject"/> 
    </pathParamMap> 
    <view>xsl/databases_subject.xsl</view> 
   </action> 
  </section> 
 </commands> 
</xerxes> 
```

2. In this one, we're  overriding the 'restricted' attribute on the databases section.  This basically forces everyone off-campus to login before viewing even the categories/databases pages.

Unlike actions, which need to be copied in whole, you can override section attributes by adding only the ones you want to change in your local file.  You don't need to copy all of the section's attributes.

```
<xerxes> 
 <commands> 
  <section name="databases" restricted="true"  /> 
 </commands> 
</xerxes> 
```

3. Here, we're defining a different action for the 'home' page, in this case an action that generates both the categories list and the alphabetical database list together at the same time.

As with the other examples, this is the entirety of the local actions.xml file:

```
<xerxes> 
 <commands>   
  <default> 
   <section>databases</section> 
   <action>combined</action> 
  </default> 
 </commands> 
</xerxes> 
```