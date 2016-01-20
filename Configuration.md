## alternate\_fulltext\_harvest\_address ##

The URL that Xerxes should point its SFX 'availability populate' action at, if different from [link\_resolver\_address](Configuration#link_resolver_address.md).

  * default value: none
  * available since: 1.5

This is particularly useful if you you use a different front-end application to SFX -- e.g., Umlaut -- and have [link\_resolver\_address](Configuration#link_resolver_address.md) configured for that.

## alternate\_yahoo\_location ##

Enter here the full URL of an alternate 'relay' script for the Yahoo Spell Check API.

  * default value: none
  * available since: 1.4

Since the Yahoo API has a daily limit based on IP address, this allows you to route some spell checking through another server in order to distribute the load.  This is particularly useful if you have multiple institutions hosted on the same server.

## application\_name ##

The 'name' of your Xerxes application.

  * default value: `Xerxes Demo`
  * available since: 1.1

By default, Xerxes only displays this name in the browser title bar, as a prefix to each page name.  This can simply be the name of your university or library.

## application\_sid ##

The source identifier (sid) that Xerxes will include in each OpenURL.

  * default value: `calstate.edu:xerxes`
  * available since: 1.1

This can be set to any value, although following the pattern above is recommended.  Use this value to distinguish Xerxes requests in SFX, both for the purpose of display logic rules, and in statistical reports.  Xerxes will also append the full name of the database to the sid.

## ~~authentication\_page~~ ##

**Removed in 1.5**.  Previously used to specify a URL to the authentication page.  This was no longer deemed necessary.

## authentication\_source ##

The source against which you will authenticate users

  * default value: `demo`
  * available since: 1.1

Valid values include:

  * **ldap** for a simple bind to an ldap-enabled directory server, including Active Directory
  * **innovative** for authentication against the Innovative Patron API
  * **cas** for authentication against a Central Authentication Source server
  * **demo** for a simple demo set-up when you don't want to configure a directory
  * **shibboleth** for Shibboleth authentication using a local configured shibd.
  * **custom** to use the script in demo/config/authentication/custom.php, where you can write your own authentication code

### authentication\_source id=other ###

Specify additional authentication\_sources Xerxes can accept. Set 'id' to one of the authentication sources mentioned above.  You can send users to these alternate login pages by adding `&authentication_source=id` to the login page URL, changing 'id' to the value you entered here.

## ~~authorization\_source~~ ##

**Removed in 1.5**.  Previously allowed you to set 'innovative' as secondary check to _authorize_ users against the III Patron API.  If you need this type of authentication logic, program it into `demo/config/authentication/custom.php`

## base\_web\_path ##

The base web server path for your Xerxes installation.

  * default value: `/demo`
  * available since: 1.2

This should reflect the path (URI) to your Xerxes instance from the root of your website.  _DO NOT_ add a trailing slash. If you have installed at web root, leave this value empty.

## bx\_token ##

The authentication token given to you by Ex Libris for the bX recommender service, if you have licensed it.

  * default value: none
  * available since: 1.6

Adding this value will cause Xerxes to begin displaying bX recommendations in the full record page.  Do not confuse this value with the _registration_ token for bX.

## ~~cas\_version~~ ##

**Removed in 1.6**.  Xerxes will use the cas\_validate URL itself to determined how to handle validation.

The version of CAS you're running.

  * default value: none
  * available since: 1.1

Enter either '1.0' or '2.0'.  If you are using a version higher than 2.0, just enter '2.0'.

## cas\_login ##

Full URL to the CAS login service.

  * default value: none
  * available since: 1.1

## cas\_validate ##

Full URL to the CAS validate service.

  * default value: none
  * available since: 1.1

## categories\_quicksearch ##

The 'normalized ID' of the category that should be used for the 'Quick Search' box on the Xerxes home page.

  * default value: `quick-search`
  * available since: 1.4

The normalized ID is a value that Xerxes constructs from the name of the category.  If you simply create a category called 'Quick Search' in Metalib, Xerxes will display this on the homepage.

If you want to use a different category -- for example, 'General Resources' -- and have Xerxes up and running, simply click on the category you want to use and look in the browser address bar.  The normalized ID is either the last part of the URL (if you have rewrite turned on) or is the value of `&subject=` if you do not.

## categories\_num\_columns ##

The number of columns for the list of subjects on the home page.

  * default value: `3`
  * available since: 1.5

Enter either '2' or '3'. Setting this to `2` can be useful if you are working in a smaller, fixed-width design, where two columns can allow for more readable text. If you want more than three columns, you'll need to customize the `databases_categories.xsl` page itself.


## chunk\_kb\_pull ##

Whether Xerxes should do the Metalib KB pull in smaller 'chunks'.

  * default value: `false`
  * available since: 1.6

_This config must be added to config.xml_.  This is only necessary if Metalib times out during the KB pull.  This can happen if your Metalib KB is large and the server it runs on is not particularly fast.

## database\_connection ##

The [PDO connection string](http://us.php.net/manual/en/pdo.construct.php) to the database.

  * default value: `mysql:host=localhost;dbname=xerxes`
  * available since: 1.1

If you are using MySQL and called your database/schema 'xerxes', then the default value in config.xml is all you need.  If you called it something else, then change just the 'dbname' portion of the string.

## database\_list\_searchable ##

Whether the alphabetical database list should include a search box, allowing users to search for databases by name or descriptive information.

  * default value: `false`
  * available since: 1.5

Caution: users often mistakenly think that this searches the actual databases themselves (i.e., for articles and books), rather than merely a search of their descriptions.  This is off by default for a reason.  Consider whether you really need it.

## database\_password ##

The password of the account that is set-up to access the (MySQL) database.

  * default value: none
  * available since: 1.1

## databases\_type\_exclude\_az ##

A list of database types that Xerxes should exclude from the A-Z list

  * default value: none
  * available since: 1.6

This is useful if you've created IRDs that are for websites, research guides, librarian contacts, or other resources that you want included (as secondary resources) in the subject pages, but want to keep out of the databases A-Z list page, for whatever reason. See also [subcategories\_sidebar](Configuration#subcategories_sidebar.md).

## database\_username ##

The username of the account that is set-up to access the (MySQL) database.

  * default value: none
  * available since: 1.1

## db\_description\_allow\_tags ##

A white list of HTML tags _NOT_ to strip in database descriptions.

  * default value: `b,i,strong,em,a`
  * available since: 1.4

Only used if [db\_description\_html](Configuration#db_description_html.md) is set to `strip`. Tags listed here (comma separated) will be allowed, while all others are stripped.  Do not include angle brackets.

## db\_description\_html ##

How Xerxes should handle HTML tags in the database description.

  * default value: `escape`
  * available since: 1.4

Possible values:

  * **escape** escapes all angle brackets, so any HTML will show up as source.  You should use this if only if you don't include ANY html in database descriptions, so angle brackets included for another purpose will still be displayed as intended.
  * **allow** allows all HTML through as code. NOT recommended. Suggest you use strip with some tags in db\_description\_allow\_tags instead.
  * **strip** strips out all HTML tags. May be used in combination with [db\_description\_allow\_tags](Configuration#db_description_allow_tags.md) to allow for certain exceptions.

## default\_collection\_name ##

The default name that should be used for new collections in 'My Saved Databases'.

  * default value: `My Saved Databases`
  * available since: 1.5

If you change this value _after_ you have already deployed Xerxes with 'My Saved Databases' functionality, users may see somewhat confusing behavior (although no errors). Try it yourself to see. You may want to change the names of all existing collections with the older names by using direct SQL.

## default\_collection\_section\_name ##

The default name that should be used for new _sections_ in 'My Saved Databases'.

  * default value: `Databases`
  * available since: 1.5

See [note above](Configuration#default_collection_name.md) if you change this after deployment.

## demo\_users ##

List of users who can access the system without being in the local directory server.

  * default value: none
  * available since: 1.1

Enter as `username:password`, and separate multiple entries by comma.  You can use the demo login in _addition_ to your regular login by adding `demo` as one of the   ["other" login options](Configuration#authentication_source.md) and directing users to that form with the query string parameter mentioned above.

## directory\_server ##

The directory server against which you will do the LDAP authentication.

  * default value: none
  * available since: 1.1

Required if [authentication\_source](Configuration#authentication_source.md) is set to `ldap`.

## display\_errors ##

Set this to `true` to see the full stack trace error from PHP in the page instead of the Xerxes error page.

  * default value: `false`
  * available since: 1.1

## document ##

Specifies a width for the interface.

  * default value: `doc3`
  * available since: 1.5

Xerxes uses the [Yahoo Grids CSS framework](http://developer.yahoo.com/yui/grids/) for basic layout.  The value here represents the 'id' of the main `<`div`>` of the layout.  Pre-set widths in the Yahoo framework, include:

  * **doc** -- 750px
  * **doc2** -- 950px
  * **doc3** -- 100%
  * **doc4** -- 974px,

If you want to use a different width, simply leave the value as `doc3`, and in your local.css file, manually set the width.  For example, set to 800px like this:

```
#doc3 { 
  width: 800px;
}
```

## domain ##

The URL for the directory server in an ldap bind.

  * default value: none
  * available since: 1.1

Required if [authentication\_source](Configuration#authentication_source.md) is set to `ldap`.  Enter in the form of 'ldaps://ldap.example.com/'

## email\_from ##

The 'from' address users will see when they email records to themselves.

  * default value: none
  * available since: 1.1

If left blank, the 'from' address will be the server name, or some other default depending on how you've set-up email on your server.

## ezp\_exp\_domain\_avoid ##

Domains that should be excluded as "Domain" config in the EZProxy export, even if ordinary rules would include them.

  * default value: `youruniv.edu` (as an example)
  * available since: 1.4

See the [EZProxy Export page](EzProxyExport.md).

## ezp\_exp\_resourceid\_omit ##

Particular databases that should be excluded from the EZProxy export, even if ordinary rules would include them.

  * default value: none
  * available since: 1.4

See the [EZProxy Export page](EzProxyExport.md).

## ezp\_exp\_default\_group ##

"Group" statement to output for ordinary default KB resources.

  * default value: `EzP_Group`
  * available since: 1.4

See the [EZProxy Export page](EzProxyExport.md).

## facets ##

Whether Xerxes should include facets in the brief metasearch results page.

  * default value: `false`
  * available since: 1.1

Make sure you've turned this option on in Metalib as well, or you'll get an error.

## fix\_ampersands ##

Whether Xerxes should _un-escape_ ampersands before sending the HTML to the browser

  * default value: `true`
  * available since: 1.5

_This config must be added to config.xml_.  Un-escaping the ampersands will better render accented letters and other non-Roman characters in the interface; but leaves the HTML (slightly) invalid.  Only set this to `false` if you care more about W3C validation than usability.

## fulltext\_ignore\_sources ##

Suppress full-text links in the specified databases.

  * default value: none
  * available since: 1.7

Enter the configuration code or metalib ID of the databases that should not show full-text links.  Separate multiple entries with a comma.

For example: `JSTOR_XML, CAL10458`.  In this example, `JSTOR_XML` would cover all JSTOR databases.  While `CAL10458` would cover one specific database (this one happens to be Project Muse in our test instance).  So you can mix and match source names and IDs.

## google\_analytics ##

A google analytics http://www.google.com/analytics id, which looks like "UA-xxxxxxx-xx".

  * default value: none

If configured, then Xerxes will output javascript to track user's on google analytics under your account. Requires 'pass=true' in config:

```
<config name="google_analytics" pass="true">UA-xxxxxx-xx</config>
```


## group ##

Metalib user groups (aka 'secondary affiliations').

  * `<group id="metalib_code">` : group code id used in Metalib, ordinarily in all caps.
  * `<display_name>` : What to call this group of users
  * `<local_ip_range>`: IP range(s) associated with this group.  Users coming from this ip range will be able to search this group's resources without first having to login. Enter as either 144.37.`*`.`*`, with `*` as wildcard, or as 144.37.0.0-144.37.255.255, seperate mutliple entries by comma
  * `<ezp_exp_group>` For use with EZProxy export function, ezproxy config group to output for this Metalib secondary affiliation. See [EZProxy Export page](EzProxyExport.md).

Example:
```
  <groups>
    <group id="SCIENCE">
      <display_name>Science Library</display_name>
      <local_ip_range>201.2.4.*</local_ip_range>
    </group>
  </groups>
```

## harvest\_memory\_limit ##

The amount of memory PHP can consume for the Metalib KB and SFX full-text 'harvesting' tasks.

  * default value: `500M`
  * available since: 1.5

_This config must be added to config.xml_.  You'll likely never need to increase it, since the default value is very large.

## hits\_thousands\_seperator ##

The character that delimits thousands in the database hit count.

  * default value: `,`
  * available since: 1.5

_This config must be added to config.xml_.  Useful if you want thousands to be separated by a period or other local, non-English convention.

## holdings\_links ##

Individual databases that should show a link to 'holdings'. Enter as:

```
<config name="holdings_links" pass="true" xml="true">
  <database metalib_id="JHU0001" />
  <database metalib_id="JHU0002" />
</config>
```

To show these links for _any_ database that has them, see [show\_all\_holdings\_links](Configuration#show_all_holdings_links.md).

About this type of link: Metalib IRDs have a field for 'Link to Records in Native Interface'.  In most cases, this is used to link to a library catalog so the user can check availability.

If you have filled out this field in the IRD, Xerxes will display it, regardless of any other configuration settings in Metalib. Xerxes does not currently support real-time z39.50 look-up of holdings, as in the standard Metalib interface.  Xerxes also does not support the 'alternative' (off-campus) versions of these links.

## http\_proxy\_server ##

The adress of a HTTP proxy tunnel (including port).

  * default value: none
  * available since: 1.5

Set this if you need to route all outgoing Xerxes requests through a proxy.  Do not confuse this with [proxy\_server](Configuration#proxy_server.md) (e.g., ezproxy).

## http\_proxy\_username ##

Username to authenticate again the HTTP Proxy.

  * default value: none
  * available since: 1.5

## http\_proxy\_password ##

Password to authenticate again the HTTP Proxy.

  * default value: none
  * available since: 1.5

## http\_use\_curl ##

Should Xerxes use the PHP CURL libraries for outgoing requests instead of file\_get\_contents().

  * default value: `false`
  * available since: 1.5

Requires that the PHP CURL libraries be installed on the server.  This is useful if you've disable remote file access in php.ini.

## immediately\_show\_merged\_results ##

Whether users should immediately see the merged results after the search is complete.

  * default value: `true`
  * available since: 1.5

Set this to `false` if you want Xerxes to leave the user at the search status page, requiring them to choose the merged set or an individual database.  The default `metasearch_hits.xsl` file is not set-up for this scenario.  Contact David about customizing for this alternate behavior.

## innovative\_patron\_api ##

Full URL to your Millenium server, together with the the Patron API port

  * default value: none
  * available since: 1.1

Required if [authentication\_source](Configuration#authentication_source.md) is set to `innovative`.  Enter as `http://catalog.example.edu:4500/` _INCLUDE_ the trailing slash '/' at the end of the URL.

## innovative\_patron\_types ##

Patron types allowed to access Xerxes from off-campus.

  * default value: none
  * available since: 1.1

This is a white list of patron types _allowed_ to use this system from off-campus. Separate multiple entries by comma.  Leave blank if all users are allowed.  Useful if you have guests and other non-affiliated users in your patron database.


## ip\_address ##

IP adress associated with this Xerxes implementation.

  * default value: none
  * available since: 1.1

This is typically the IP address of the server running Xerxes, or at least one that is within your campus IP Range.  This is particularly important in a consortium, or in a multi-campus Xerxes instance.  Metalib needs this to associate the correct knowledgebase with this Xerxes implementation.

## ~~ldap\_cannonicalize\_user~~ ##

**Removed in 1.4**.  If your LDAP authentication requires anything beyond a simple bind, you should implement it as a 'custom' authentication script.

## ~~ldap\_seach\_base~~ ##

**Removed in 1.4**.  If your LDAP authentication requires anything beyond a simple bind, you should implement it as a 'custom' authentication script.

## ~~ldap\_seach\_filter~~ ##

**Removed in 1.4**.  If your LDAP authentication requires anything beyond a simple bind, you should implement it as a 'custom' authentication script.

## ~~ldap\_seach\_uid~~ ##

**Removed in 1.4**.  If your LDAP authentication requires anything beyond a simple bind, you should implement it as a 'custom' authentication script.

## ~~ldap\_super\_pass~~ ##

**Removed in 1.4**.  If your LDAP authentication requires anything beyond a simple bind, you should implement it as a 'custom' authentication script.

## ~~ldap\_super\_user~~ ##

**Removed in 1.4**.  If your LDAP authentication requires anything beyond a simple bind, you should implement it as a 'custom' authentication script.

## limit\_context\_url ##

Limit context\_url to specified domains.

  * default value: none
  * available since: 1.6

If left blank, any external search form can specify any URL in the `context_url` parameter when creating a Xerxes search. Xerxes will use this URL in the breadcrumbs, usually as a link back to the external search form.

Use this entry to allow only specified domains in `context_url`.  Separate multiple entries with commas.  Do not include `http://`.  You can use an asterisk as a wildcard to represent single-level subdomains.

For example: `*.calstate.edu` or `*.*.calstate.edu`.


## link\_resolver\_address ##

Base URL of your link resolver.

  * default value: none
  * available since: 1.1

## link\_target ##

Behavior of links to external sites for full-text, holdings, and OpenURL links.

  * default value: `_blank`
  * available since: 1.1

You can change this value to any valid HTML anchor target attribute value, for example:

  * ****self** -- causes the browser to open the links in the same window
  * blank** -- causes the browser to open the links in a new window.

## link\_target\_databases ##

Same as [link\_target](Configuration#link_target.md), but for links to databases on the subject, alphabetical listing, and full database record pages.

  * default value: none
  * available since: 1.7

## local\_ip\_range ##

IP range(s) associated with your campus.

  * default value: none
  * available since: 1.1

Users within these ranges will not be prompted for a login when searching Xerxes.  Enter as either 144.37.`*`.`*`, with `*` as wildcard, or as 144.37.0.0-144.37.255.255.  Separate multiple entries by comma.

## logout\_url ##

The URL Xerxes will send users after they logout.

  * default value: none
  * available since: 1.1

Could be set to, for example, your library home page.  If left blank, then Xerxes returns users to its own homepage.

## marc\_fields\_brief ##

MARC 9XX fields Xerxes should include in the brief results.

  * default value: none
  * available since: 1.1

Prior to 1.5, specified the fields to include.  Since 1.5, Xerxes includes all control and datafields 0XX-8XX by default in the brief results.  But since some database vendors (e.g., Ebsco and OCLC) include large amounts of data in locally-defined 9XX fields, incurring noticeable slow-downs in the brief results, Xerxes excludes them.  Unless, that is, you specify them here.

Enter MARCfields as a five character string consisting of 3 digit field code and 2 characters for ind1 and ind2.  Use # as a wildcard -- e.g., `948##` will fetch the 948, `9####` will fetch all 9XX fields.

## maximum\_record\_export\_limit ##

Maximum number of records allowed in an export.

  * default value: 1,000
  * available since: 1.3

Setting this to a very large value could slow the system down, leading to a time-out.

## metalib\_address ##

Full URL to your Metalib server, including the port.

  * default value: none
  * available since: 1.1

Enter as `http://metalib.example.edu:8331`.  _DO NOT_ include the /X in the metalib address.

## metalib\_username ##

A valid Metalib Management (/M) interface username associated with the Metalib institute you want to use with Xerxes.

  * default value: none
  * available since: 1.1

## metalib\_password ##

The password of the above metalib\_username.

  * default value: none
  * available since: 1.1

## metalib\_institute ##

The Metalib institute you wish to associate with this Xerxes instance.

  * default value: none
  * available since: 1.1

## normalize\_query ##

This is **experimental**.  Allow users to enter a limited number of Boolen OR or NOT operators in the search box itself.

  * default value: none
  * available since: 1.1

Setting this to `true` instructs Xerxes to (slightly) modify the user's query, sending it to Metalib in such a way as to support a limited number of Boolen OR or NOT operators in the search box itself.  Metalib does not naively support this, instead requiring that the drop-down be used, and then only supporting one OR or NOT.

This is marked as experimental, since it is not guaranteed to work across all databases.  It seems to work well with Z39.50 resources, but might be interpreted in different ways by databases that use XML gateways or screen scrappers.

## original\_record\_links ##

Specify those databases that should present a link to the record in the native interface.  Enter as:

```
<config name="original_record_links" pass="true" xml="true">
  <database metalib_id="JHU0001" />
  <database metalib_id="JHU0002" />
</config>

```

The IRDs in the list must have a 'Link to Records in Native Interface'.

Xerxes will always show a direct link to the native full-text if it is available.  This option, in contrast, tells Xerxes to show a link to the record in the native interface whether full-text is available or not.

The link appears in the full record page, together with other link options.  The link will appear on the brief results page _ONLY_ if (1) there is no full text link available, (2) there is no OpenURL link available, which is true if you set the IRD in Metalib to not show OpenURL links for this resource, (3) if the "original\_record" link is both present _AND_ results in a link.

## proxy\_server ##

Enter the URL of your library proxy server.

  * default value: none
  * available since: 1.1

The value is different based on your system:

  * **EZProxy**: Include the entire starting-point URL prefix, e.g., `http://ezproxy.library.edu/login?qurl=`. Xerxes will look to URL-encode the full-text link, so it is preferable to use `qurl` param

  * **WAM Proxy**: Enter your catalog server information with '{WAM}' as the domain/port wildcard;  e.g.,   http://{WAM}.catalog.library.edu

If left blank, no proxy server will be used.

## records\_per\_page ##

Enter the maximum number of records Xerxes should show per-page in the brief metasearch results.

  * default value: `10`
  * available since: 1.1

The default is 10, mostly for performance reasons, since a larger number will create a bigger XML response from Metalib, which will travel across the network more slowly.  If Metalib is on the same network, has fast hardware, and you expect relatively light usage, consider making this higher.  Users will prefer having more records per page.

## refworks\_address ##

The full URL to the Refworks import script.

  * default value: `http://www.refworks.com/express/ExpressImport.asp`
  * available since: 1.3

_This config must be added to config.xml_. Only needed as a stop-gap in the unlikely event Refworks changes the address, and we've not updated (or you've not upgraded) Xerxes to pick-up the change.

## reverse\_proxy ##

Whether you are running Xerxes behind a reverse-proxy server

  * default value: `false`
  * available since: 1.6

This instructs Xerxes to look for the server name and user's IP address in HTTP\_X\_FORWARDED headers.  This entry is necessary as a security precaution in the event you are _not_ running Xerxes behind a reverse-proxy, in which case the headers can be spoofed by the client.

## rewrite ##

Whether Xerxes should create shorter, more stable-looking URLs.

  * default value: `false`
  * available since: 1.1

Although turning this on will create the URLs, for your Web server to _handle_ them correctly, you need either mod\_rewrite, if you are using Apache, one of the various mod\_rewite-like ISAPI modules, if you are using IIS. See the demo/.htaccess file for specifications of the rewrite scheme.

## rdbms ##

The relational database management system (RDBMS) you are using with Xerxes.

  * default value: `mysql`
  * available since: 1.6

_This config must be added to config.xml_.  This is necessary only if you are using Xerxes with Microsoft SQL Server.  In that case, set the value to `mssql`.  We need this to unambiguously distinguish the database (the connection string alone will not do that, since we're using the ODBC driver) in order to support some MS-SQL-specific statements.

## saved\_records\_per\_page ##

Maximum number of records to show on the saved records page.

  * default value: `10`
  * available since: 1.1

This can go much higher than [records\_per\_page](Configuration#records_per_Page.md), since the records are coming out of the local database, and thus much faster.

## search\_limit ##

The number of databases that can be searched simultaneously.

  * default value: `10`
  * available since: 1.1

This should correspond to the limit you have configured in Metalib.

## search\_progress\_cap ##

Number of seconds before Xerxes stops the search and merges available records.

  * default value: `34`
  * available since: 1.1

Note that this will not automatically change the animated search progress image, setting this higher will require a change in that display.

## secure\_login ##

Whether Xerxes will force all logins through HTTPS.

  * default value: `false`
  * available since: 1.1

This is useful only for the local authentication options -- where Xerxes provides the login page itself -- and is largely unnecessary for remote authentication though CAS and Shibboleth.

## ~~sfx\_resolver\_address~~ ##

**Renamed in 1.5**.  See [alternate\_fulltext\_harvest\_address](Configuration#alternate_fulltext_harvest_address.md)

## ~~shib\_username\_header~~ ##

**Removed in 1.6**. If necessary, configure with a local Shibboleth module sub-class in config/authentication/shibboleth.php instead.

The HTTP header that Xerxes will expect to find the Shiboleth username in.

  * default value: `REMOTE_USER`
  * available since: 1.2


## show\_all\_holdings\_links ##

Show links to holdings for all databases that have them.

  * default value: `true`
  * available since: 1.5

See [holdings\_link](Configuration#holdings_links.md) for more information on 'holdings' links, and to specify that only specific databases show them.

## show\_db\_detail\_search ##

Whether to show a search box on the individual database description page.

  * default value: `true`
  * available since: 1.2

## ~~show\_search\_box~~ ##

**Renamed in 1.5**.  See [database\_list\_searchable](Configuration#database_list_searchable.md)

## sort\_order\_primary ##

The initial sort orders for merged results.

  * default value: `rank`
  * available since: 1.1

This will be the default sort order when the user is first dropped into the merged results; valid values include:

  * rank
  * title
  * author
  * year
  * database


## sort\_order\_secondary ##

The _secondary_ sort criteria for sorting merged results.

  * default value: `title`
  * available since: 1.1

Valid values are the same as for [sort\_order\_primary](Configuration#sort_order_primary.md).  In the event that two or more records have the same value for the primary sort, this will further sort the items.

Default here is `title`, as in Metalib, since setting it to `year` causes unusual reordering of the results in Metalib if users (manually) change their sort to something else and then go _back_ to rank.

## subcategories\_include ##

Subcategories that should be included in the subject page.

  * default value: none
  * available since: 1.5

This is a white list of subcategories that should be _included_.  Xerxes will show a subcategory if it matches any value, or part of the value, entered here.  So the entry 'Articles' will match subcategories named 'Article databases' and 'Most useful article databases'. If left blank (the default), Xerxes will include all subcategories.

This can be useful if you are transitioning from the standard Metalib interface to Xerxes, and want to follow the common Xerxes pattern of 'most useful' and 'also useful' subcategories, while keeping a topical list in the standard Metalib interface.

## subcategories\_sidebar ##

Subcategories that should be shown in the sidebar of the subject page, rather than the main part of the page.

  * default value: none
  * available since: 1.6

List is case-sensitive; separate multiple entries with a comma.

This option is useful if you want to assign subject guides and/or a librarian contact to subjects.  You can manage the guides and librarian information in the Metalib KB, as IRDs, and then create subcategories in each category, and assign the IRDs as desired.  Enter the subcategory names here, and Xerxes will remove them from the main part of the page and put them in the sidebar.  Order is determined by the order of the subcategories in Metalib.

## template ##

The position (left or right) and width of the sidebar.

  * default value: `yui-t6`
  * available since: 1.5

Valid values include:

  * **yui-t1** -- 160 on left
  * **yui-t2** -- 180 on left
  * **xer-t5** -- 240 on left
  * **yui-t3** -- 300 on left
  * **yui-t4** -- 180 on right
  * **yui-t5** -- 240 on right
  * **yui-t6** -- 300 on right

Like [document](Configuration#document.md) this controls default behavior in the YUI CSS Grids framework.  If you'd like to create a new option, see `demo/css/xerxes-blue.css` for an example (.xer-t5).

## umlaut\_base ##

Enable Umlaut functionalities in Xerxes, if you have an umlaut resolver.

  * default value: none
  * available since: 1.6

Enter the Umlaut 'true' base, without the /resolve, no trailing slash. For example:  `http://findit.library.jhu.edu`

## yahoo\_id ##

The ID for the account that will access the Yahoo Spell Check API.

  * default value: `calstate`
  * available since: 1.1

It doesn't hurt to leave this as `calstate`, since the usage limits are based on IP address, but better to get your own key from Yahoo

## xerxes\_brief\_include\_marc ##

Whether to include the original MARC-XML record in the XML response for the brief results page.

  * default value: `false`
  * available since: 1.1

This is useful if you need to customized the brief results template to show data that Xerxes does not map to its own internal format.

## xerxes\_full\_include\_marc ##

Whether to include the original MARC-XML record in the XML response for the full record page.

  * default value: `false`
  * available since: 1.1

This is useful if you need to customized `xsl/record.xsl` to show data that Xerxes does not map to its own internal format.