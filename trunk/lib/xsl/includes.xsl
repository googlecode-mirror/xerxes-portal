<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">

<!-- 
	GLOBAL VARIABLES
	Configuration values used throughout the templates
-->

	<xsl:variable name="base_url"		select="//base_url" />
  <xsl:variable name="app_name"   select="/*/config/application_name" />
	<xsl:variable name="rewrite" 		select="//config/rewrite" />
	<xsl:variable name="search_limit"	select="//config/search_limit" />
	<xsl:variable name="link_target"	select="//config/link_target" />
	<xsl:variable name="base_include">
		<xsl:choose>
			<xsl:when test="//request/server/https">
				<xsl:text>https://</xsl:text><xsl:value-of select="substring-after($base_url, 'http://')" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$base_url" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	
<!-- 
	TEXT LABELS 
	These are global variables that provide the text for the system.  We'll be slowly
	replacing the text in the templates with these starting with version 1.3.  Variable
	names should follow the pattern of: text_{location}_{unique-name}
-->
	
	<xsl:variable name="text_breadcrumb_seperator"> &gt; </xsl:variable>
	
	<xsl:variable name="text_header_logout">
		Log-out
		<xsl:text> </xsl:text>
    <xsl:choose>
      <xsl:when test="//request/authorization_info/affiliated[@user_account = 'true']">
        <xsl:value-of select="//request/session/username" />
      </xsl:when>
      <xsl:when test="//session/role = 'guest'">
        Guest
      </xsl:when>
    </xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="text_header_savedrecords">My Saved Records</xsl:variable>

 <xsl:variable name="text_link_resolver_available">Full text available</xsl:variable>
 <xsl:variable name="text_link_resolver_check">Check for availability</xsl:variable>

 <!-- Other configurable variables -->
 
 <xsl:variable name="app_mini_icon_url"><xsl:value-of select="$base_url" />/images/famfamfam/page_find.png</xsl:variable>

<!-- 	
	TEMPLATE: SURROUND
	This is the master template that defines the overall design for the application; place
	here the header, footer and other design elements which all pages should contain.
-->

<xsl:template name="surround">

	<html xmlns="http://www.w3.org/1999/xhtml" lang="eng">
	<head>
	<title><xsl:call-template name="title" /></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<xsl:call-template name="header" />
	<base href="{$base_include}/" />
	<link href="{$base_include}/css/xerxes.css" rel="stylesheet" type="text/css" />
	<link href="{$base_include}/css/xerxes-print.css" rel="stylesheet" type="text/css" media="print" />
  <!--opensearch autodiscovery -->
  <xsl:if test="/*/category">
     <xsl:variable name="subject_name" select="/*/category[1]/@name" />
     <xsl:variable name="subject_id" select="/*/category[1]/@normalized" />
     <link rel="search"
       type="application/opensearchdescription+xml" 
       href="{$base_url}?base=databases&amp;action=subject-opensearch&amp;subject={$subject_id}"
       title="{$app_name} {$subject_name} search" />
  </xsl:if>
	</head>
	<body>
	<xsl:if test="request/action = 'subject'">
		<xsl:attribute name="onLoad">document.forms.form1.query.focus()</xsl:attribute>
	</xsl:if>
	<div id="xerxes_outer_wrapper">
	<div id="header">
		<xsl:call-template name="header_div" />
	</div>
	<div id="breadcrumb">
		<div class="trail">
			<xsl:call-template name="breadcrumb" />
		</div>

		<xsl:call-template name="metasearch_options" />

	</div>

	<xsl:call-template name="main" />

	<div id="footer">
		<xsl:call-template name="footer_div" />
	</div>
	</div>
	</body>
	</html>
	
</xsl:template>

<!-- 	
	TEMPLATE: HEADER DIV
	Contents of on-screen header. Generally overridden in local stylesheet.
-->

<xsl:template name="header_div" >
	<h2><a style="color:white" class="footer" href="{$base_url}"><xsl:value-of select="/knowledge_base/config/application_name" /></a></h2>
	<p style="color:white">Header content. Customize by editing {Xerxes_app}/xsl/includes.xsl to override the template.</p>
</xsl:template>

<!-- 	
	TEMPLATE: FOOTER DIV
	Contents of on-screen header. Generally overridden in local stylesheet.
-->

<xsl:template name="footer_div" >
	Footer content. Customize by editing {Xerxes_app}/xsl/includes.xsl to
	override the template. 
</xsl:template>


<!--
	TEMPLATE: PAGE NAME
	A heading that can be used to label this page. Some views use this for
	their heading, others don't. The "title" template always uses this to construct an html title. 
-->

<xsl:template name="page_name">
	<xsl:variable name="folder">
		<xsl:text>Saved Records</xsl:text>
	</xsl:variable>
	
	<xsl:choose>
		<!-- mango -->
		
		<xsl:when test="request/base = 'books' and request/action = 'results'">
			<xsl:text>Results: </xsl:text>
			<xsl:value-of select="//request/query" />
			<xsl:if test="//request/startRecord">
				( <xsl:value-of select="//request/startRecord" /> )
			</xsl:if>
		</xsl:when>
		<xsl:when test="request/base = 'books' and request/action = 'record'">
			<xsl:value-of select="//results/records/record/xerxes_record/title_normalized" />
		</xsl:when>
		
		<!-- xerxes -->
		
		<xsl:when test="request/base = 'databases' and (request/action = 'subject' or request/actions/action = 'subject')">
			<xsl:text></xsl:text><xsl:value-of select="//category/@name" />
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'alphabetical'">
			<xsl:text>Databases A-Z</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'find'">
			<xsl:text>Find a Database</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'database'">
			<xsl:text></xsl:text><xsl:value-of select="//title_display" />
		</xsl:when>
		<xsl:when test="request/base = 'embed' and request/action = 'gen_subject'">
			<xsl:text>Create Snippet for: </xsl:text> 
		<xsl:value-of select="//category/@name" />
		</xsl:when>
		<xsl:when test="request/base = 'embed' and request/action = 'gen_database'">
			<xsl:text>Create Snippet for: </xsl:text>
			<xsl:value-of select="//title_display" />
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'hits'">
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Searching</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and ( request/action = 'results' or request/action = 'facet')">
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Results </xsl:text>
			( <xsl:value-of select="summary/range" /> )
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'record'">
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Record</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'home'">
			<xsl:value-of select="$folder" />
		</xsl:when>
		<xsl:when test="request/action = 'login'">
			<xsl:text>Login</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'logout'">
			<xsl:text>Logout</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_email'">
			<xsl:text>Email</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_export_endnote'">
			<xsl:text>Download to Endnote</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_export_text'">
			<xsl:value-of select="$folder" /><xsl:text>: Download to Text File</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_refworks'">
			<xsl:text>Export to Refworks</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'full'">
			<xsl:value-of select="$folder" /><xsl:text>: Record</xsl:text>
		</xsl:when>
	</xsl:choose>
	
</xsl:template>

<!-- 	
	TEMPLATE: TITLE
	Sets the title ( the one that appears in the browser title bar)
	Takes the 'header' and adds more stuff to it.
-->

<xsl:template name="title">
	<xsl:variable name="page_title"><xsl:call-template name="page_name" /></xsl:variable>
	<xsl:value-of select="config/application_name" />
	<xsl:if test="$page_title != ''">
		<xsl:text>: </xsl:text>
		<xsl:value-of select="$page_title" />
	</xsl:if>
</xsl:template>


<xsl:template name="title_old">
	
	<xsl:variable name="base" select="config/application_name" />
	
	<xsl:choose>
		<xsl:when test="request/base = 'databases' and (request/action = 'categories' or request/actions/action = 'categories')">
			<xsl:value-of select="$base" />
		</xsl:when>
		<xsl:when test="request/base = 'databases' and (request/action = 'subject' or request/actions/action = 'subject')">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text><xsl:value-of select="//category/@name" />
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'alphabetical'">
			<xsl:value-of select="$base" /><xsl:text>: Databases A-Z</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'find'">
			<xsl:value-of select="$base" /><xsl:text>: Find a Database</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'database'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text><xsl:value-of select="//title_display" />
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'hits'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Searching</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and (request/action = 'results' or request/action = 'facet')">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Results </xsl:text>
			( <xsl:value-of select="summary/range" /> )
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'record'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Record</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'home'">
			<xsl:value-of select="$folder" />
		</xsl:when>
		<xsl:when test="request/action = 'login'">
			<xsl:value-of select="$base" /><xsl:text>: Login</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'logout'">
			<xsl:value-of select="$base" /><xsl:text>: Logout</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_email'">
			<xsl:value-of select="$base" /><xsl:text>: Email</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_export_endnote'">
			<xsl:value-of select="$folder" /><xsl:text>: Download to Endnote</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_export_text'">
			<xsl:value-of select="$folder" /><xsl:text>: Download to Text File</xsl:text>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'full'">
			<xsl:value-of select="$folder" /><xsl:text>: Record</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$base" />
		</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

<!-- 
	TEMPLATE: BREADCRUMB START
	The initial elements of the breadcrumbs, often included external links or name changes
	that are convenient to seperate out here so as not to have to customize the entire the 
	breadcrumb template below 
-->

<xsl:template name="breadcrumb_start">

	<xsl:choose>
		<xsl:when test="request/action = 'categories'">
			<span class="breadcrumbHere">Home</span>
		</xsl:when>
		<xsl:otherwise>
			<a href="{$base_url}">Home</a> <xsl:value-of select="$text_breadcrumb_seperator" />  
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>

<!-- 	
	TEMPLATE: BREADCRUMB
	Sets a base set of breadcrumbs (if the application lives in a specific part of the library
	website, for example) and the specific breadcrumb titles for each page.
-->

<xsl:template name="breadcrumb">

	<xsl:variable name="context_url" 	select="results/search/context_url" />
	<xsl:variable name="username"		select="request/session/username" />
	<xsl:variable name="return"		select="request/return" />
	<xsl:variable name="return_title" 	select="request/return_title" />
	<xsl:variable name="group"		select="request/group" />
	<xsl:variable name="resultset" 		select="request/resultset" />
	<xsl:variable name="start_record" 	select="request/startrecord" />
	<xsl:variable name="records_per_page" 	select="config/records_per_page" />
	<xsl:variable name="folder" select="navbar/element[@id = 'saved_records']/url" />
	
	<xsl:call-template name="breadcrumb_start" />
	
	<xsl:choose>

		<!-- rss and mango -->
		
		<xsl:when test="request/base = 'books' and request/action = 'results'">
			<span class="breadcrumbHere">Results</span>
		</xsl:when>
		<xsl:when test="request/base = 'books' and request/action = 'record'">
			<span class="breadcrumbHere">Record</span>
		</xsl:when>
		<xsl:when test="request/base = 'rss'">
			<span class="breadcrumbHere">RSS prototype</span>
		</xsl:when>
		
		<!-- metasearch -->
		
		<xsl:when test="request/action = 'login'">
			<span class="breadcrumbHere">Login</span>
		</xsl:when>
		<xsl:when test="request/action = 'logout'">
			<span class="breadcrumbHere">Logout</span>
		</xsl:when>
		<xsl:when test="request/base = 'databases' and (request/action = 'subject' or request/actions/action = 'subject')">
			<span class="breadcrumbHere"><xsl:value-of select="//category/@name" /></span>
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'alphabetical'">
			<span class="breadcrumbHere">Databases A-Z</span>
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'find'">
			<xsl:call-template name="page_name" />
		</xsl:when>
		<xsl:when test="request/base = 'databases' and request/action = 'database'">
			<xsl:if test="$return != ''">			 
				<a href="{$return}">
					<xsl:choose>
						<xsl:when test="$return_title != ''">
						 <xsl:value-of select="$return_title" />
						</xsl:when>
						<xsl:otherwise><xsl:text>Databases</xsl:text></xsl:otherwise>
					</xsl:choose>
				</a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			</xsl:if>
			<span class="breadcrumbHere"><xsl:value-of select="//title_display" /></span>
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'hits'">
			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<span class="breadcrumbHere">Searching</span>
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'results'">
			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<span class="breadcrumbHere"><xsl:value-of select="results/database" /></span>
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'facet'">
			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<a href="{$return}"><xsl:value-of select="results/database" /></a> <xsl:value-of select="$text_breadcrumb_seperator" />
			<span class="breadcrumbHere"><xsl:value-of select="results/facet_name" /></span>
		</xsl:when>
		<xsl:when test="request/base = 'metasearch' and request/action = 'record'">
			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			
			<xsl:choose>
				<xsl:when test="$return != ''">
					<a href="{$return}">Results</a> <xsl:value-of select="$text_breadcrumb_seperator" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="parent_resultset">
						<xsl:value-of select="$base_url" />
						<xsl:text>/?base=metasearch&amp;action=results&amp;group=</xsl:text><xsl:value-of select="$group" />
						<xsl:text>&amp;resultSet=</xsl:text><xsl:value-of select="$resultset" />
						<xsl:text>&amp;startRecord=</xsl:text>
						<xsl:value-of select="(floor( ($start_record	- 1 ) div $records_per_page) * $records_per_page) + 1" />
					</xsl:variable>
					
					<a href="{$parent_resultset}">Results</a> <xsl:value-of select="$text_breadcrumb_seperator" />
				</xsl:otherwise>
			</xsl:choose>
			<span class="breadcrumbHere">Record</span>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'home'">
			<xsl:choose>
				<xsl:when test="request/label or request/type">
					<a href="{$folder}">My Saved Records</a> <xsl:value-of select="$text_breadcrumb_seperator" />
					<span class="breadcrumbHere"><xsl:value-of select="request/label|request/type" /></span>
				</xsl:when>
				<xsl:otherwise>
					<span class="breadcrumbHere">My Saved Records</span>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_email'">
			<a href="{$folder}">My Saved Records</a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<span class="breadcrumbHere">Email</span>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_export_endnote'">
			<a href="{$folder}">My Saved Records</a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<span class="breadcrumbHere">Download to Endnote</span>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_refworks'">
			<a href="{$folder}">My Saved Records</a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<span class="breadcrumbHere">Export to Refworks</span>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'output_export_text'">
			<a href="{$folder}">My Saved Records</a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<span class="breadcrumbHere">Download to Text File</span>
		</xsl:when>
		<xsl:when test="request/base = 'folder' and request/action = 'full'">
			<a href="{$folder}">My Saved Records</a> <xsl:value-of select="$text_breadcrumb_seperator" /> 
			<span class="breadcrumbHere">Record</span>
		</xsl:when>	
		
		<xsl:when test="request/base = 'embed' and request/action = 'gen_subject'">
			<a>
				<xsl:attribute name="href">
				 <xsl:value-of select="//category/url" />
				</xsl:attribute>
				<xsl:value-of select="//category/@name" />
			</a>
			<xsl:value-of select="$text_breadcrumb_seperator" />
			<span class="breadcrumbHere">Create Snippet</span>
		</xsl:when>
		<xsl:when test="request/base = 'embed' and request/action = 'gen_database'">
			<a>
				<xsl:attribute name="href">
					<xsl:value-of select="//database/url" />
				</xsl:attribute>
				<xsl:value-of select="//title_display" />
			</a>
			<xsl:value-of select="$text_breadcrumb_seperator" />
			<span class="breadcrumbHere">Create Snippet</span>
		</xsl:when>
		<xsl:otherwise>
			<span class="breadcrumbHere"><xsl:call-template name="page_name" /></span>
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>

<!-- 	
	TEMPLATE: METASEARCH OPTIONS
	Defines a set of options that should appear on all the metasearch pages, such as 
	the link to the saved records feature, log-in or log-out link, etc.
-->

<xsl:template name="metasearch_options">
	
	<xsl:variable name="return" 		select="php:function('urlencode', string(request/server/request_uri))" />
	
	<xsl:comment>
		<xsl:value-of select="request/session/username" /> 
		( <xsl:value-of select="request/session/role" /> )
	</xsl:comment>

	<div class="sessionOptions" title="login and saved records links">
		<xsl:if test="request/base != 'authenticate'">
		<span class="sessionAction">
			<xsl:choose>
			<xsl:when test="request/session/role and request/session/role != 'local'">
				<a>
			<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id = 'logout']/url" /></xsl:attribute>
			<xsl:value-of select="$text_header_logout" />
			</a>
			</xsl:when>
			<xsl:otherwise>
				<a>
			<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id = 'login']/url" /></xsl:attribute>
			Log-in</a>
			</xsl:otherwise>
			</xsl:choose>
		</span>
		|
		</xsl:if>
		<span class="sessionAction">
			<img name="folder" width="17" height="15" border="0" id="folder" alt="">
			<xsl:attribute name="src">
				<xsl:choose>
				<xsl:when test="navbar/element[@id='saved_records']/@numSessionSavedRecords &gt; 0"><xsl:value-of select="$base_include" />/images/folder_on.gif</xsl:when>
				<xsl:otherwise><xsl:value-of select="$base_include"/>/images/folder.gif</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			</img>
			<xsl:text> </xsl:text>
			<a>
		<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='saved_records']/url" /></xsl:attribute>
			<xsl:value-of select="$text_header_savedrecords" />
		</a>
		</span>
	</div>
</xsl:template>

<!-- 	
	TEMPLATE: SEARCH BOX
	Search box that appears in the 'hits' and 'results' page, as well as databases_subject.xsl. 
-->
<xsl:template name="search_box">

	<!-- split contents into seperate template to make partial AJAX loading easier -->
	<div class="searchBox" id="searchBox">
	
		<!-- "base" url used for switching search modes. Defaults to just our current url, but for embed purposes 
		may be provided differently. -->
	
		<xsl:param name="full_page_url" select="//request/server/request_uri"/>
	
		<!-- pull out any already existing query entries -->
		
		<xsl:variable name="query" select="//results/search/pair[@position = '1']/query" />
		<xsl:variable name="query2" select="//results/search/pair[@position = '2']/query" />
		
		<xsl:variable name="find_operator" select="//results/search/operator[@position = '1']" />
		
		<xsl:variable name="field" select="//results/search/pair[@position ='1']/field"/>
		<xsl:variable name="field2" select="//results/search/pair[2]/field"/>
		
		<xsl:variable name="advanced_mode" select="(//request/metasearch_input_mode = 'advanced') or ($query2 != '') or $field = 'ISSN' or $field = 'ISBN' or $field = 'WYR' " />
		
		<!-- javascript to ajaxy toggle the advanced search input mode. Have to set advancedMode global for toggle scripts. -->
		<script type="text/javascript">
			advancedMode = <xsl:value-of select="$advanced_mode" />;
		</script>
		<script src="{$base_include}/javascript/toggle_metasearch_advanced.js" language="javascript" type="text/javascript"></script>
		
		<div id="searchLabel">
			<label for="field">Search</label>
		</div>
		
		<div id="searchInputs">
			<xsl:call-template name="metasearch_input_pair">
				<xsl:with-param name="field_selected" select="$field" />
				<xsl:with-param name="query_entered" select="$query" />
				<xsl:with-param name="advanced_mode" select="$advanced_mode" />
			</xsl:call-template>

			<!-- advanced search stuff is output even if we are in simple mode, but with display:none. 
			Javascriptiness may easily toggle without reload that way. -->
			<xsl:text> </xsl:text>
			<select id="find_operator1" name="find_operator1">
				<xsl:if test="not($advanced_mode)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<option value="AND">
					<xsl:if test="$find_operator = 'AND'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
					And
				</option>
				<option value="OR">
					<xsl:if test="$find_operator = 'OR'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
				Or
				</option>
				<option value="NOT">
					<xsl:if test="$find_operator = 'NOT'">
						<xsl:attribute name="selected">selected</xsl:attribute>
					</xsl:if>
				Without
				</option>
			</select>
		
			<br id="searchBox_advanced_newline">
				<xsl:if test="not($advanced_mode)">
					<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
			</br>
			<span id="searchBox_advanced_pair">
				<xsl:if test="not($advanced_mode)">
				<xsl:attribute name="style">display:none;</xsl:attribute>
				</xsl:if>
				<xsl:call-template name="metasearch_input_pair">
					<xsl:with-param name="field_selected" select="$field2" />
					<xsl:with-param name="query_entered" select="$query2" />
					<xsl:with-param name="advanced_mode" select="true()" />
					<xsl:with-param name="input_name_suffix" select="2" />
				</xsl:call-template>
				<xsl:text> </xsl:text>
			</span>
			<input type="submit" name="Submit" value="GO" />
		</div>
		<xsl:if test="results/search/spelling != ''">
			<xsl:variable name="spell_url" select="results/search/spelling_url" />
			<p class="errorSpelling">Did you mean: <a href="{$spell_url}"><xsl:value-of select="//spelling" /></a></p>
		</xsl:if>
	
		<div id="metasearch_input_toggle">
			<xsl:choose>
			<xsl:when test="$advanced_mode">
				<a id="searchBox_toggle" onClick="toggleSearchMode(); return false;">
				<xsl:attribute name="href">
					<xsl:value-of select="php:functionString('Xerxes_Framework_Request::setParamInUrl', $full_page_url, 'metasearch_input_mode', 'simple')"/>
				</xsl:attribute>
				Fewer options
				</a>
			</xsl:when>
			<xsl:otherwise>
				<a id="searchBox_toggle" onClick="toggleSearchMode(); return false;">
				<xsl:attribute name="href">
					<xsl:value-of select="php:functionString('Xerxes_Framework_Request::setParamInUrl', $full_page_url, 'metasearch_input_mode', 'advanced')"/>
				</xsl:attribute>
				More options
				</a>
			</xsl:otherwise>
		</xsl:choose>
		</div>
	</div>
	
	<xsl:for-each select="//base_info">
		<xsl:if test="base_001">
			<xsl:variable name="database" select="base_001" />
			<input type="hidden" name="database" value="{$database}" />
		</xsl:if>
	</xsl:for-each>
	
</xsl:template>

<!--
	TEMPLATE: METASEARCH INPUT PAIR
	Two search box form
-->

<xsl:template name="metasearch_input_pair">
	<xsl:param name="field_selected" />
	<xsl:param name="query_entered" />
	<xsl:param name="advanced_search" select="false" />
	<xsl:param name="input_name_suffix" select ="''" />
	
	<select id="field{$input_name_suffix}" name="field{$input_name_suffix}">
		<option value="WRD">all fields</option>
		<option value="WTI">
		<xsl:if test="$field_selected = 'WTI'">
			<xsl:attribute name="selected">seleted</xsl:attribute>
		</xsl:if>
		title
		</option>
		<option value="WAU">
		<xsl:if test="$field_selected = 'WAU'">
			<xsl:attribute name="selected">selected</xsl:attribute>
		</xsl:if>
		author
		</option>
		<option value="WSU">
		<xsl:if test="$field_selected = 'WSU'">
			<xsl:attribute name="selected">selected</xsl:attribute>
		</xsl:if>
		subject
		</option>
		
		<!-- Include advanced mode options? We don't just try to hide,
		doesn't work in IE, javascript will need to actually add/remove. -->
		
		<xsl:if test="$advanced_mode">
			<option value="ISSN">
			<xsl:if test="$field_selected = 'ISSN'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			ISSN
			</option>
			<option value="ISBN">
			<xsl:if test="$field_selected = 'ISBN'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			ISBN
			</option>
			<option value="WYR">
			<xsl:if test="$field_selected = 'WYR'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			year
			</option>
		</xsl:if>
	</select>
	<xsl:text> </xsl:text><label for="query{$input_name_suffix}">for</label><xsl:text> </xsl:text>
	<input id="query{$input_name_suffix}" name="query{$input_name_suffix}" type="text" size="32" value="{$query_entered}" />
	
</xsl:template>

<!--
	TEMPLATE: TAG INPUT
	tab/label input form used to enter labels/tags for saved record, on both folder page and search results
	page (for saved records only) one of record (usually) or id (unusually) are required. 
	parameter: record  =>  XSL node representing a savedRecord with a child <id> and optional children <tags>
	parameter: id => pass a string id instead of a record in nodeset. Used for the 'template' form for ajax 
	label input adder. 
-->

<xsl:template name="tag_input">
	<xsl:param name="record" select="." />
	<xsl:param name="id" select="$record/id" /> 
	<xsl:param name="context" select="'the saved records page'" />

	<div class="folderLabels" id="tag_input_div-{$id}">
		<form action="./" method="get" class="tags">
			<!-- note that if this event is fired with ajax, the javascript changes
			the action element here to 'tags_edit_ajax' so the server knows to display a 
			different view, which the javascript captures and uses to updates the totals above. -->
			
			<input type="hidden" name="base" value="folder" />
			<input type="hidden" name="action" value="tags_edit" />
			<input type="hidden" name="record" value="{$id}" />
			<input type="hidden" name="context" value="{$context}" />
			
			<xsl:variable name="tag_list">
				<xsl:for-each select="$record/tag">
					<xsl:value-of select="text()" />
					<xsl:if test="following-sibling::tag">
						<xsl:text>, </xsl:text>
					</xsl:if>
				</xsl:for-each>
			</xsl:variable>
			
			<input type="hidden" name="tagsShaddow" id="shadow-{$id}" value="{$tag_list}" />
			
			<label for="tags-{$id}">Labels: </label>
			
			<input type="text" name="tags" id="tags-{$id}" class="tagsInput" value="{$tag_list}" />
			
			<span class="folderLabelsSubmit">
				<input id="submit-{$id}" type="submit" name="submitButton" value="Update" class="tagsSubmit" />
			</span>
		</form>
	</div>
							
</xsl:template>


<!--
	TEMPLATE: SUBJECT DATABASES LIST
	used to list databases, generally on a search form, on databases_subject.xsl,
	and embed_subject.xsl 
-->

<xsl:template name="subject_databases_list">

	<!-- default to true: -->
	<xsl:param name="should_show_checkboxes" select="true()" />
	<!-- specific subcategory only? Default to false meaning, no, all subcats. -->
	<xsl:param name="show_only_subcategory" select="false()" />
	
	<xsl:for-each select="category/subcategory[(not($show_only_subcategory )) or ($show_only_subcategory = '') or (@id = $show_only_subcategory)]">
		<fieldset class="subjectSubCategory">
		<legend><xsl:value-of select="@name" /></legend>
		
		<xsl:variable name="subcategory" select="position()" />
			<table summary="this table lists databases you can search" class="subjectCheckList">
			<xsl:for-each select="database">
			<xsl:variable name="id_meta" select="metalib_id" />
			<tr valign="top">
			<td>
			
				<!-- if the current session can't search this resource, should we show a lock icon? 
				We show lock icons for logged in with account users, on campus users, and guest users. Not for off campus not logged in users, because they might be able to search more resources than we can tell now. --> 
				
				<xsl:variable name="should_lock_nonsearchable" select=" (//request/authorization_info/affiliated = 'true' or //request/session/role = 'guest')" />
				
				<!-- how many database checkboxes were displayed in this subcategory, before now?
					Used for seeing if we've reached maximum for default selected dbs. Depends on 
					if we're locking non-searchable or not. -->
					
				<xsl:variable name="prev_checkbox_count">
				<xsl:choose>
					<xsl:when test="$should_lock_nonsearchable">
					<xsl:value-of select="count(preceding-sibling::database[searchable_by_user = '1'])" />
					</xsl:when>
					<xsl:otherwise>
					<xsl:value-of select="count(preceding-sibling::database[searchable = '1'])" />
					</xsl:otherwise>
				</xsl:choose>
				</xsl:variable>
				
				<!-- Show a checkbox, a disabled checkbox, or a lock icon. If it's a checkbox, default it to checked or not. -->
				
				<xsl:choose>
				<xsl:when test="not($should_show_checkboxes)">
				<xsl:text> </xsl:text>
				</xsl:when>
				<xsl:when test="searchable = 1">
					<xsl:choose>
					<xsl:when test="$should_lock_nonsearchable	and searchable_by_user != '1'" >
					<!-- if we have a logged in user (or a registered guest), but they can't search this, show them a lock. -->			
					<img src="{$base_url}/images/lock.gif" alt="restricted to campus users only" />
					</xsl:when>
					<xsl:otherwise>
					<!-- if no user logged in, or user logged in and they can
					search this, show them a checkbox. -->
					<xsl:element name="input">
						<xsl:attribute name="name">database</xsl:attribute>
						<xsl:attribute name="id"><xsl:value-of select="metalib_id" /></xsl:attribute>
						<xsl:attribute name="value"><xsl:value-of select="metalib_id" /></xsl:attribute>
						<xsl:attribute name="type">checkbox</xsl:attribute>
						<xsl:if test="$subcategory = 1 and $prev_checkbox_count &lt; //config/search_limit">
						<xsl:attribute name="checked">checked</xsl:attribute>
						</xsl:if>
					</xsl:element>
					</xsl:otherwise>
					</xsl:choose>
				</xsl:when>
				<xsl:otherwise>
					<img src="{$base_url}/images/link-out.gif" alt="Click database title to search individually" title="Click database title to search individually"/>
				</xsl:otherwise>
				</xsl:choose>
			</td>
			<td>
				<div class="subjectDatabaseTitle">
					<xsl:element name="label">
					<xsl:attribute name="for"><xsl:value-of select="metalib_id" /></xsl:attribute>
					
					<a>
					<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
						<xsl:value-of select="title_display" />
					</a>
					</xsl:element>
				</div>
					
				<div class="subjectDatabaseInfo">
					<a>
					<xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
					<img alt="more information" src="images/info.gif" >
						<xsl:attribute name="src"><xsl:value-of select="//config/base_url" />/images/info.gif</xsl:attribute>
					</img>
					</a>
				</div>
				
				<xsl:if test="group_restriction">
					<div class="subjectDatabaseRestriction"><xsl:call-template name="db_restriction_display" /></div>
				</xsl:if>
				
			</td>
		</tr>
		</xsl:for-each>
		</table>
		
		</fieldset>
	</xsl:for-each>
</xsl:template>

<!-- 
	TEMPLATE: DATABASES SEARCH BOX
	Search box that appears sometimes on databases_alphabetical.xsl. May
	appear other places eventually.
-->

<xsl:template name="databases_search_box">
	
	<!-- would be nice if the form action was rewrite aware, but couldn't figure
	out a way to do that that wasn't awful. -->
	
	<form method="GET" action="./">
		<div class="searchBox">
			<input type="hidden" name="base" value="databases" />
			<input type="hidden" name="action" value="find" />
			
			<label for="query">List databases matching: </label> 
			
			<input id="query" name="query" type="text" size="32">
				<xsl:attribute name="value"><xsl:value-of select="request/query" /></xsl:attribute>
			</input>
			<xsl:text> </xsl:text>
			<input type="submit" value="GO" />
		</div>
		
		<div class="databasesReturn">
			<xsl:if test="request/action != 'alphabetical'">
				<a>
				<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='database_list']/url" /></xsl:attribute>
				All Databases</a> &gt; Databases matching "<xsl:value-of select="request/query" />"
			</xsl:if>
		</div>
		
	</form>
	
</xsl:template>

<!-- 
	TEMPLATE: DB RESTRICTION DISPLAY
 	Show access rights for db, including group restrictions. Either pass in a parameter, or else it assumes that
	a <database> node is the XSL current() node. 
-->

<xsl:template name="db_restriction_display">
	<xsl:param name="database" select="current()" />

	<xsl:variable name="group_restrictions" select="$database/group_restriction" />
	
	<xsl:if test="$group_restrictions">
		Only available to
	</xsl:if>
	
	<xsl:for-each select="$group_restrictions">
		<xsl:value-of select="@display_name" />
		<xsl:choose>
			<xsl:when test="count(following-sibling::group_restriction) = 1">
			and
			</xsl:when>
			<xsl:when test="count(following-sibling::group_restriction) > 1">
			, 
			</xsl:when>
		</xsl:choose>
	</xsl:for-each>
	<xsl:if test="$group_restrictions">
	<xsl:text>  users</xsl:text>
	</xsl:if>
</xsl:template>


<!-- 	
	TEMPLATE: FOLDER BRIEF RESULTS
	Brief results list that appears on many of the export options pages.
-->

<xsl:template name="folder_brief_results">

	<xsl:variable name="username" 	select="request/session/username" />
	
	<table summary="">
	<xsl:for-each select="results/records/record">
		<tr>
		<td class="folderRecord">
			<input type="checkbox" name="record" value="{id}" id="record-{id}" />
		</td>
		<td class="folderRecord">
			<label for="record-{id}">
				<a href="{url_full}" class="resultsTitle"><xsl:value-of select="title" /></a><br />
				<xsl:value-of select="author" /> / <xsl:value-of select="format" /> / <xsl:value-of select="year" />
			</label>
		</td>
		</tr>
	</xsl:for-each>
	</table>
</xsl:template>

<!-- 
	TEMPLATE: FOLDER HEADER 
	Sets the name of the folder area, dynamically based on roles
-->

<xsl:template name="folder_header">

	<xsl:variable name="return" 	select="php:function('urlencode', string(request/server/request_uri))" />
	
	<div class="folderHeaderArea">
	
	<h1>
		<xsl:choose>
			<xsl:when test="request/label or request/type">
				<a href="./?base=folder" class="folderHomeHeader"><xsl:call-template name="folder_header_label" /></a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="folder_header_label" />
			</xsl:otherwise>
		</xsl:choose>
		
		<xsl:if test="request/label">
			<xsl:text> / </xsl:text><xsl:value-of select="request/label" />
		</xsl:if>
		<xsl:if test="request/type">
			<xsl:text> / </xsl:text><xsl:value-of select="request/type" />
		</xsl:if>
	</h1>
	
	<xsl:if test="request/session/role = 'local'">
		<p>( <a href="{navbar/element[@id='login']/url}">Log-in</a> 
		to save the records beyond this session.)</p>
	</xsl:if>
		
	</div>

</xsl:template>

<!-- 
	TEMPLATE: FOLDER HEADER LABEL
	Whether this is 'temporary' or 'my' saved records
-->


<xsl:template name="folder_header_label">
	<xsl:choose>
		<xsl:when test="request/session/role = 'local' or request/session/role = 'guest'">
			<xsl:text>Temporary Saved Records</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			<xsl:text>My Saved Records</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<!-- 
	TEMPLATE: TAGS DISPLAY
	used by a couple of pages in the folder area for displaying tags
-->

<xsl:template name="tags_display">
	
	<h2>Labels</h2>
	<ul>
	<xsl:for-each select="tags/tag">
		<li>
		<xsl:choose>
			<xsl:when test="@label = //request/label">
				<strong><span class="label_list_item"><xsl:value-of select="@label" /></span></strong> ( <xsl:value-of select="@total" /> )
			</xsl:when>
			<xsl:otherwise>
				<a href="{@url}"><span class="label_list_item"><xsl:value-of select="@label" /></span></a> ( <xsl:value-of select="@total" /> )
			</xsl:otherwise>
		</xsl:choose>
		</li>
	</xsl:for-each>
	</ul>
	
</xsl:template>


<!-- 
	TEMPLATE: FOLDER EXPORT OPTIONS
	used within each export to provide selection of items
-->

<xsl:template name="folder_export_options">

	<div>
		
		<fieldset class="folderExportSet">
		<legend>Export</legend>
		
		<ul class="folderExportSelections">
			<li>
			
			<input type="radio" name="items" value="all" id="all" checked="checked" />
			
			<xsl:choose>
				<xsl:when test="//request/label">
					
					<label for="all">
					All of my saved records labeled <strong><xsl:value-of select="//request/label" /></strong>
					</label>
					<input type="hidden" name="label" value="{//request/label}" />

				</xsl:when>
				<xsl:when test="//request/type">

					<label for="all">
					All of my saved records that are <strong><xsl:value-of select="//request/type" />s</strong>
					</label>
					<input type="hidden" name="type" value="{//request/type}" />
				
				</xsl:when>
				<xsl:otherwise>
					<label for="all">All of my saved records</label>
				</xsl:otherwise>
			
			</xsl:choose>

			</li>
			
			<li>
				<input type="radio" name="items" value="new" id="new" />
				<label for="new">Only the records I have selected below.</label>
			</li>
	
		</ul>
		
		</fieldset>
		
	</div>

</xsl:template>


<!-- 	
	TEMPLATE: RESULTS RETURN
	provides a return mechanism on the saved records page to get back to the results
-->

<xsl:template name="results_return">

	<xsl:variable name="back" 	select="request/session/saved_return" />
	
	<xsl:if test="contains(request/session/saved_return,'action=hits') or 
			contains(request/session/saved_return,'action=results') or 
			contains(request/session/saved_return,'action=facet') or
			contains(request/session/saved_return,'action=record')">

		<div class="folderReturn">
			<img src="{$base_include}/images/back.gif" alt="" />
			<span class="folderReturnText">
				<a href="{$back}">Return to search results</a>
			</span>
		</div>
		
	</xsl:if>

</xsl:template>


<!-- 	
	TEMPLATE: HEADER
	header content, such as Javascript functions that should appear on specific page.
-->

<xsl:template name="header">
	
	<xsl:if test="request/action = 'hits' and results/progress &lt; 10">
		<meta http-equiv="refresh" content="6" />
	</xsl:if>
	<script src="{$base_include}/javascript/onload.js" language="javascript" type="text/javascript"></script>
	<script src="{$base_include}/javascript/prototype.js" language="javascript" type="text/javascript"></script>
	<script src="{$base_include}/javascript/scriptaculous/scriptaculous.js" language="javascript" type="text/javascript"></script>
	<script src="{$base_include}/javascript/tags.js" language="javascript" type="text/javascript"></script>
	<script type="text/javascript">
		// change numSessionSavedRecords to numSavedRecords if you prefer the folder icon to change
		// if there are any records at all in saved records. Also fix initial display in navbar.
		numSavedRecords = parseInt('0<xsl:value-of select="navbar/element[@id='saved_records']/@numSessionSavedRecords" />', 10);
		isTemporarySession = <xsl:choose><xsl:when test="request/session/role = 'guest' or request/session/role = 'local'">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>
	</script>
	<script src="{$base_include}/javascript/save.js" language="javascript" type="text/javascript"></script>
	
	<script language="javascript" type="text/javascript">
		var dateSearch = "<xsl:value-of select="results/search/date" />";
		var iSearchable = "<xsl:value-of select="$search_limit" />";
	</script>
	
	<!-- mango stuff -->
	
	<xsl:if test="request/base = 'books'">
		<script src="{$base_include}/javascript/availability.js" language="javascript" type="text/javascript"></script>
		<link href="{$base_include}/css/mango.css" rel="stylesheet" type="text/css" />
	</xsl:if>

</xsl:template>

<!--
	TEMPLATE: FULL TEXT LINKS
	Constructs proxied links for full-text links in the results, folder, and full record
	Assumes that you call from inside a xerxes_record element
-->

<xsl:template name="full_text_links">

	<xsl:param name="class" />
	
	<xsl:variable name="database_code" select="metalib_id" />
	
	<xsl:for-each select="links/link[@type != 'none']">
		
		<div class="{$class}">
		
			<xsl:variable name="url">
				<xsl:if test="url">
					<xsl:value-of select="php:function('urlencode', string(url))" />
				</xsl:if>
			</xsl:variable>
			
			<a>
				<xsl:attribute name="href">
					<xsl:value-of select="$base_url" /><xsl:text>/</xsl:text>
					<xsl:text>./?base=databases&amp;action=proxy</xsl:text>
					<xsl:text>&amp;database=</xsl:text><xsl:value-of select="$database_code" />
					<xsl:choose>
						<xsl:when test="$url != ''">
							<xsl:text>&amp;url=</xsl:text><xsl:value-of select="$url" />
						</xsl:when>
						<xsl:otherwise>
							<xsl:for-each select="param">
								<xsl:text>&amp;param=</xsl:text>
								<xsl:value-of select="@field" />
								<xsl:text>=</xsl:text>
								<xsl:value-of select="text()" />
							</xsl:for-each>
						</xsl:otherwise>
					</xsl:choose>
				</xsl:attribute>
				
				<xsl:attribute name="class">resultsFullText</xsl:attribute>
				<xsl:attribute name="target"><xsl:value-of select="$link_target" /></xsl:attribute>
			
				<xsl:choose>
					<xsl:when test="@type = 'pdf'">
						<img src="{$base_include}/images/pdf.gif" alt="" width="16" height="16" border="0" /> 
						Full-Text in PDF
					</xsl:when>
					<xsl:when test="@type = 'html'">
						<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" /> 
						Full-Text in HTML
					</xsl:when>
					<xsl:otherwise>
						<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" /> 
						Full-Text Available
					</xsl:otherwise>
				</xsl:choose>
			
			</a>
		
		</div>
		
	</xsl:for-each>
</xsl:template>

<!--
	TEMPLATE: CATEGORIES SIDEBAR
	Override in local includes.xsl if you'd like a sidebar on the home/categories page. 
	Put your content in a div with id="sidebar_content" if you'd like some style. 
-->

<xsl:template name="categories_sidebar">
</xsl:template>
<xsl:template name="categories_sidebar_alt">
</xsl:template>

<!--
	TEMPLATE: SESSION AUTH INFO
	Displays a user's authorization crednetials from login and IP.  Useful especially if you are using Metalib 
	usergroup/secondary affiliation access. jrochkind likes to display it on the front page in a sidebar.
-->

<xsl:template name="session_auth_info">
	<div id="sessionAuthInfo">

		<xsl:choose>
			<xsl:when test="//request/authorization_info/affiliated[@user_account = 'true']">
				<h2 class="sessionLoggedIn">Welcome, <xsl:value-of select="//session/user_properties[@key = 'username']" />.</h2>	 
			</xsl:when>
			<xsl:otherwise>
				<h2 class="sessionLoggedOut">Welcome</h2>	
			</xsl:otherwise>
		</xsl:choose>
		
		<div class="sessionAuthSection">
      <xsl:choose>
        <xsl:when test="//request/authorization_info/group[@user_account = 'true']">
          <h3>Your Affiliation: </h3>
          <ul>
            <xsl:for-each select="//request/authorization_info/group[@user_account = 'true']">
              <li><xsl:value-of select="@display_name" /></li>
            </xsl:for-each>
          </ul>
        </xsl:when>
        <xsl:when test="//session/role = 'guest'">
          <h3>Your Affiliation: Guest</h3>
        </xsl:when>
      </xsl:choose>
		</div>
		
		<div class="sessionAuthSection">
			<h3>Your Location: </h3>
			<ul>
			<xsl:choose>
				<xsl:when test="//request/authorization_info/group[@ip_addr = 'true']">
					<xsl:for-each select="//request/authorization_info/group[@ip_addr = 'true']">
						<li><xsl:value-of select="@display_name" /></li>
					</xsl:for-each>	
				</xsl:when>			
				<xsl:when test="//request/authorization_info/affiliated[@ip_addr = 'true']">
					<li>On-campus</li>
				</xsl:when>
				<xsl:otherwise>
					<li><strong>Off</strong> campus</li>
				</xsl:otherwise>
			</xsl:choose>
			</ul>
		</div>

	</div>
</xsl:template>


<!-- 
	TEMPLATE PAGING NAVIGATION	
	Provides the visual display for moving through a set of results
-->

<xsl:template name="paging_navigation">

	<xsl:if test="pager/page">
		<div class="resultsPager">

			<ul class="resultsPagerList">
			<xsl:for-each select="pager/page">
				<li>
				<xsl:variable name="link" select="@link" />
				<xsl:choose>
					<xsl:when test="@here = 'true'">
						<strong><xsl:value-of select="text()" /></strong>
					</xsl:when>
					<xsl:otherwise>
						<a href="{$link}">
						<xsl:choose>
							<xsl:when test="@type = 'next'">
								<xsl:attribute name="class">resultsPagerNext</xsl:attribute>
							</xsl:when>
							<xsl:otherwise>
								<xsl:attribute name="class">resultsPagerLink</xsl:attribute>
							</xsl:otherwise>
						</xsl:choose>
							<xsl:value-of select="text()" />
						</a>
					</xsl:otherwise>
				</xsl:choose>
				</li>
			</xsl:for-each>
			</ul>
		</div>
	</xsl:if>

</xsl:template>


<xsl:template name="mango-searchbox">

	<xsl:variable name="query"				select="request/query" />
	
	<form action="./" method="get">

		<input type="hidden" name="base" value="books" />
		<input type="hidden" name="action" value="search" />
		<input type="hidden" name="source" value="local" />

		<label for="field">Search</label><xsl:text> </xsl:text>
	
		<select id="field" name="field">
			<option value="">keyword</option>
			<option value="title">
			<xsl:if test="request/field = 'title'">
				<xsl:attribute name="selected">seleted</xsl:attribute>
			</xsl:if>
			title
			</option>
			<option value="author">
			<xsl:if test="request/field = 'author'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			author
			</option>
			<option value="subject">
			<xsl:if test="request/field = 'subject'">
				<xsl:attribute name="selected">selected</xsl:attribute>
			</xsl:if>
			subject
			</option>
		</select>
		<xsl:text> </xsl:text><label for="query">for</label><xsl:text> </xsl:text>
		<input id="query" name="query" type="text" size="32" value="{$query}" /><xsl:text> </xsl:text>
		
		<input type="submit" name="Submit" value="GO" />
	
	</form>	

</xsl:template>

<!-- 	
	TEMPLATE: REAL-TIME HOLDINGS LOOKUP
-->

<xsl:template name="holdings_lookup">
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	<xsl:param name="type" />
	
	<xsl:choose>
		<xsl:when test="$type = 'summary'">
			<xsl:call-template name="holdings_lookup_summary">
				<xsl:with-param name="isbn" select="$isbn" />
				<xsl:with-param name="oclc" select="$oclc" />
			</xsl:call-template>			
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="holdings_lookup_full">
				<xsl:with-param name="isbn" select="$isbn" />
				<xsl:with-param name="oclc" select="$oclc" />
			</xsl:call-template>
		</xsl:otherwise>
	</xsl:choose>
				
</xsl:template>

<!-- 	
	TEMPLATE: HOLDINGS LOOKUP SUMMARY
	A summary view of the holdings information
-->

<xsl:template name="holdings_lookup_summary">
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />	

	<ul class="mangoAvailabilitySummary">
	
	<xsl:call-template name="holdings_full_text">
		<xsl:with-param name="element">li</xsl:with-param>
		<xsl:with-param name="class">resultsHoldings</xsl:with-param>
		<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
		<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
	</xsl:call-template>
	
	<xsl:choose>
		<xsl:when test="count(//lookup/summary/number[(@isbn = $isbn and $isbn != '') or (@oclc = $oclc and $oclc != '')]/location[@available != '0']) = 0">
			<xsl:if test="//request/source = 'local'">
				<li class="mangoAvailabilityMissing"><img src="images/book-out.gif" alt="" />&#160; No Copies Available</li>
			</xsl:if>
			<xsl:call-template name="ill_option">
				<xsl:with-param name="element">li</xsl:with-param>
				<xsl:with-param name="class">resultsHoldings</xsl:with-param>
				<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
				<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
			</xsl:call-template>
		</xsl:when>
		<xsl:otherwise>
			<xsl:for-each select="//lookup/summary/number[(@isbn = $isbn and $isbn != '') or (@oclc = $oclc and $oclc != '')]/location[@name != 'ONLINE']">
				<li>
					<xsl:choose>
						<xsl:when test="@available = '0'"><img src="images/book-out.gif" alt="" />&#160; No copies available at <xsl:value-of select="@name" /></xsl:when>
						<xsl:when test="@available = '1'"><img src="images/book.gif" alt="" />&#160; 1 copy available at <xsl:value-of select="@name" /></xsl:when>
						<xsl:otherwise><img src="images/book.gif" alt="" />&#160; <xsl:value-of select="@available" /> copies available at <xsl:value-of select="@name" /></xsl:otherwise>
					</xsl:choose>
				</li>
			</xsl:for-each>
		</xsl:otherwise>
	</xsl:choose>
	</ul>
	
</xsl:template>

<!-- 	
	TEMPLATE: HOLDINGS LOOKUP FULL
	A full table-view of the (print) holdings information, with full-text below
-->

<xsl:template name="holdings_lookup_full">
	<xsl:param name="isbn" />
	<xsl:param name="oclc" />
	
	<xsl:choose>
		<xsl:when test="//lookup/library[held = 1 and location != 'ONLINE' and ( isbn = $isbn  or oclc = $oclc)]">
		
			<div class="mangoAvailable">		
				<table id="holdingsTable">
				<tr>
					<th>Location</th>
					<th>Call Number</th>
					<th>Status</th>
				</tr>
				<xsl:for-each select="//lookup/library[held = 1 and location != 'ONLINE' and ( isbn = $isbn  or oclc = $oclc)]">
					<tr>
						<td><xsl:value-of select="location" /></td>
						<td><xsl:value-of select="call_number" /></td>
						<td><xsl:value-of select="status" /></td>
					</tr>
				</xsl:for-each>
				</table>
			</div>
		</xsl:when>
	</xsl:choose>		
			
	<xsl:call-template name="holdings_full_text">
		<xsl:with-param name="element">span</xsl:with-param>
		<xsl:with-param name="class">resultsAvailability</xsl:with-param>
		<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
		<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
	</xsl:call-template>

	<xsl:call-template name="ill_option">
		<xsl:with-param name="element">span</xsl:with-param>
		<xsl:with-param name="class">resultsAvailability</xsl:with-param>
		<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
		<xsl:with-param name="isbn"><xsl:value-of select="$isbn" /></xsl:with-param>
	</xsl:call-template>

	<xsl:call-template name="mango_save_record">
		<xsl:with-param name="element">span</xsl:with-param>
		<xsl:with-param name="class">resultsAvailability</xsl:with-param>
		<xsl:with-param name="oclc"><xsl:value-of select="$oclc" /></xsl:with-param>
	</xsl:call-template>

</xsl:template>

<!-- 	
	TEMPLATE: HOLDINGS FULL TEXT
	just the full-text on a holdings lookup
-->

<xsl:template name="holdings_full_text">
	<xsl:param name="element" />
	<xsl:param name="class" />
	<xsl:param name="oclc" />
	<xsl:param name="isbn" />
				
	<xsl:for-each select="//lookup/library[location = 'ONLINE' and ( isbn = $isbn  or oclc = $oclc)]">
		<xsl:element name="{$element}">
			<xsl:attribute name="class"><xsl:value-of select="$class" /></xsl:attribute>
			<a href="{link}" class="resultsFullText"  target="" >
				<img src="{$base_include}/images/html.gif" alt="" width="16" height="16" border="0" /> Full-Text Online
			</a>
		</xsl:element>
	</xsl:for-each>
		
</xsl:template>

<!-- 	
	TEMPLATE: ILL OPTION
	just the ill link on a holdings lookup
-->

<xsl:template name="ill_option">
	<xsl:param name="element" />
	<xsl:param name="class" />
	<xsl:param name="oclc" />
	<xsl:param name="isbn" />
	
	<xsl:variable name="source"  select="//request/source"/>

	<xsl:if test="not(//lookup/library[held = 1 and ( isbn = $isbn  or oclc = $oclc)]/@available = 'true')">
		<xsl:element name="{$element}">
			<xsl:attribute name="class"><xsl:value-of select="$class" /></xsl:attribute>
			<img src="images/ill.gif" alt="" class="mangoILL" /> 
			<a target="_blank" href="./?base=books&amp;action=ill&amp;oclc={$oclc}" style="color: green">
				<xsl:text>We'll get a copy for you </xsl:text>
				<xsl:choose>
					<xsl:when test="$source = 'regional'">
						<xsl:text> in 3-5 days</xsl:text>
					</xsl:when>
					<xsl:when test="$source = 'world'">
						<xsl:text> in 5-10 days</xsl:text>
					</xsl:when>
				</xsl:choose>
			
			</a>
		</xsl:element>
	</xsl:if>
		
</xsl:template>
		
<!-- 	
	TEMPLATE: MANGO SAVE RECORD
	for mango
-->

<xsl:template name="mango_save_record">
	<xsl:param name="element" />
	<xsl:param name="class" />
	<xsl:param name="oclc" />
	
	<!--
	
	<xsl:element name="{$element}">
		<xsl:attribute name="class"><xsl:value-of select="$class" /></xsl:attribute>
		<img id="folder_{$oclc}" src="images/folder.gif" width="17" height="15" alt="" border="0" />
		<xsl:text> </xsl:text>
		<a id="link_{$oclc}" href="" class="saveThisRecord resultsFullText">Save this record</a>
	</xsl:element>
	
	-->
		
</xsl:template>

</xsl:stylesheet>
