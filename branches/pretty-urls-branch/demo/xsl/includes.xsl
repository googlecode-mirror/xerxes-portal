<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 This file includes most of the elements that you will want to change immediately
 for the Xerxes interface: surrounding design, titles, and breadcumbs.
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">

<xsl:output method="html" encoding="utf-8" indent="yes" />

<!-- 
	GLOBAL VARIABLES
	Configuration values used throughout the templates
-->

	<xsl:variable name="base_url"		select="//base_url" />
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
	TEMPLATE: SURROUND
	This is the master template that defines the overall design for the application; place
	here the header, footer and other design elements which all pages should contain.
-->

<xsl:template name="surround">

	<html xmlns="http://www.w3.org/1999/xhtml" lang="eng">
	<head>
	<title><xsl:call-template name="title" /></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <base href="{$base_include}/" />
	<link href="{$base_include}/css/xerxes.css" rel="stylesheet" type="text/css" />
	<link href="{$base_include}/css/xerxes-print.css" rel="stylesheet" type="text/css" media="print" />
	<xsl:call-template name="header" />
	</head>
	<body>
	<xsl:if test="/metasearch">
		<xsl:attribute name="onLoad">loadFolders()</xsl:attribute>
	</xsl:if>
	<xsl:if test="request/action = 'subject'">
		<xsl:attribute name="onLoad">document.forms.form1.query.focus()</xsl:attribute>
	</xsl:if>
	
	<div id="header">
		<a href="{$base_url}"><img src="{$base_include}/images/title.gif" alt="california state university, xerxes library" border="0" /></a>
	</div>
	<div id="breadcrumb">
		<div class="trail">
			<xsl:call-template name="breadcrumb" />
		</div>

		<xsl:call-template name="metasearch_options" />

	</div>

	<xsl:call-template name="main" />

	<div id="footer">
		<img src="{$base_include}/images/seal.gif" width="147" height="149" />
	</div>
	</body>
	</html>
	
</xsl:template>

<!-- 	
	TEMPLATE: TITLE
	Sets the title ( the one that appears in the browser title bar)
	for each page based on the action
-->

<xsl:template name="title">
	
	<xsl:variable name="base">
		<xsl:text>Xerxes Demo</xsl:text>
	</xsl:variable>
	<xsl:variable name="folder">
		<xsl:text>Xerxes Demo: Saved Records</xsl:text>
	</xsl:variable>
	
	<xsl:choose>
		<xsl:when test="request/action = 'categories' or request/actions/action = 'categories'">
			<xsl:value-of select="$base" />
		</xsl:when>
		<xsl:when test="request/action = 'subject' or request/actions/action = 'subject'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text><xsl:value-of select="//category/@name" />
		</xsl:when>
		<xsl:when test="request/action = 'alphabetical'">
			<xsl:value-of select="$base" /><xsl:text>: Databases A-Z</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'database'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text><xsl:value-of select="//title_display" />
		</xsl:when>
		<xsl:when test="request/action = 'hits'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Searching</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'results' or request/action = 'facet'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Results </xsl:text>
			( <xsl:value-of select="summary/range" /> )
		</xsl:when>
		<xsl:when test="request/action = 'record'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Record</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'home'">
			<xsl:value-of select="$folder" />
		</xsl:when>
		<xsl:when test="request/action = 'login'">
			<xsl:value-of select="$base" /><xsl:text>: Login</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'logout'">
			<xsl:value-of select="$base" /><xsl:text>: Logout</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'output_email'">
			<xsl:value-of select="$base" /><xsl:text>: Email</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'output_export_endnote'">
			<xsl:value-of select="$folder" /><xsl:text>: Download to Endnote</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'output_export_text'">
			<xsl:value-of select="$folder" /><xsl:text>: Download to Text File</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'full'">
			<xsl:value-of select="$folder" /><xsl:text>: Record</xsl:text>
		</xsl:when>
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
	<xsl:variable name="return"			select="request/return" />
	<xsl:variable name="group"			select="request/group" />
	<xsl:variable name="resultset" 		select="request/resultset" />
	<xsl:variable name="start_record" 	select="request/startrecord" />
	<xsl:variable name="records_per_page" 	select="config/records_per_page" />
    
	<xsl:variable name="folder">
		<xsl:choose>
			<xsl:when test="$rewrite = 'true'">
				<xsl:value-of select="$base_url" /><xsl:text>/folder/</xsl:text><xsl:value-of select="$username" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$base_url" /><xsl:text>/?base=folder&amp;username=</xsl:text><xsl:value-of select="$username" />
			</xsl:otherwise>
		</xsl:choose>	
	</xsl:variable>

	<xsl:choose>
		<xsl:when test="request/action = 'categories'">
			<span class="breadcrumbHere">Home</span>
		</xsl:when>
		<xsl:otherwise>
			<a href="{$base_url}">Home</a> &gt; 
		</xsl:otherwise>
	</xsl:choose>	
	
	<xsl:choose>
		<xsl:when test="request/action = 'login'">
			<span class="breadcrumbHere">Login</span>
		</xsl:when>
		<xsl:when test="request/action = 'logout'">
			<span class="breadcrumbHere">Logout</span>
		</xsl:when>
		<xsl:when test="request/action = 'subject' or request/actions/action = 'subject'">
			<span class="breadcrumbHere"><xsl:value-of select="//category/@name" /></span>
		</xsl:when>
		<xsl:when test="request/action = 'alphabetical'">
			<span class="breadcrumbHere">Databases A-Z</span>
		</xsl:when>
		<xsl:when test="request/action = 'database'">
			<span class="breadcrumbHere"><xsl:value-of select="//title_display" /></span>
		</xsl:when>
		<xsl:when test="request/action = 'hits'">
			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> &gt; 
			<span class="breadcrumbHere">Searching</span>
		</xsl:when>
		<xsl:when test="request/action = 'results'">
			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> &gt; 
			<span class="breadcrumbHere"><xsl:value-of select="results/database" /></span>
		</xsl:when>
		<xsl:when test="request/action = 'facet'">
			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> &gt; 
			<a href="{$return}"><xsl:value-of select="results/database" /></a> &gt;  
			<span class="breadcrumbHere"><xsl:value-of select="results/facet_name" /></span>
		</xsl:when>
		<xsl:when test="request/action = 'record'">
 			<a href="{$context_url}"><xsl:value-of select="results/search/context" /></a> &gt; 
			
			<xsl:choose>
				<xsl:when test="$return != ''">
					<a href="{$return}">Results</a> &gt;
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="parent_resultset">
						<xsl:value-of select="$base_url" />
						<xsl:text>/?base=metasearch&amp;action=results&amp;group=</xsl:text><xsl:value-of select="$group" />
						<xsl:text>&amp;resultSet=</xsl:text><xsl:value-of select="$resultset" />
						<xsl:text>&amp;startRecord=</xsl:text>
						<xsl:value-of select="(floor( ($start_record  - 1 ) div $records_per_page) * $records_per_page) + 1" />
					</xsl:variable>
					
					<a href="{$parent_resultset}">Results</a> &gt;
				</xsl:otherwise>
			</xsl:choose>
			<span class="breadcrumbHere">Record</span>
		</xsl:when>
		<xsl:when test="request/action = 'home'">
			<span class="breadcrumbHere">My Saved Records</span>
		</xsl:when>
		<xsl:when test="request/action = 'output_email'">
			<a href="{$folder}">My Saved Records</a> &gt; 
			<span class="breadcrumbHere">Email</span>
		</xsl:when>
		<xsl:when test="request/action = 'output_export_endnote'">
			<a href="{$folder}">My Saved Records</a> &gt; 
			<span class="breadcrumbHere">Download to Endnote</span>
		</xsl:when>
		<xsl:when test="request/action = 'output_export_text'">
			<a href="{$folder}">My Saved Records</a> &gt; 
			<span class="breadcrumbHere">Download to Text File</span>
		</xsl:when>
		<xsl:when test="request/action = 'full'">
			<a href="{$folder}">My Saved Records</a> &gt; 
			<span class="breadcrumbHere">Record</span>
		</xsl:when>
	</xsl:choose>

</xsl:template>

<!-- 	
	TEMPLATE: METASEARCH_OPTIONS
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
		<span class="sessionAction">
			<xsl:choose>
			<xsl:when test="request/session/role and request/session/role != 'local'">
				<a href="{$base_url}/?base=authenticate&amp;action=logout&amp;return={$return}">Log-out</a>
			</xsl:when>
			<xsl:otherwise>
				<a href="{$base_url}/?base=authenticate&amp;action=login&amp;return={$return}">Log-in</a>
			</xsl:otherwise>
			</xsl:choose>
		</span>
		|
		<span class="sessionAction">
			<img src="{$base_include}/images/folder.gif" name="folder" width="17" height="15" border="0" id="folder" alt="folder"/>
			<xsl:text> </xsl:text>
			<a href="{$base_url}/?base=folder&amp;return={$return}">My Saved Records</a>
		</span>	
	</div>
</xsl:template>

<!-- 	
	TEMPLATE: SEARCH_BOX
	Search box that appears in the 'hits' and 'results' page.  The one that lives in the 
	databases_subject.xsl template is seperate, but should look the same 
	( NOTE: maybe these should be combined? )
-->


<xsl:template name="search_box">
	
	<div class="searchBox">
		<xsl:for-each select="//pair">
			<xsl:variable name="query" select="query" />
				<label for="field">Search</label><xsl:text> </xsl:text>
				<select id="field" name="field">
					<option value="WRD">all fields</option>	
					<xsl:choose>
						<xsl:when test="field = 'WTI'">
							<option value="WTI" selected="selected">title</option>
						</xsl:when>
						<xsl:otherwise>
							<option value="WTI">title</option>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:choose>
						<xsl:when test="field = 'WAU'">
							<option value="WAU" selected="selected">author</option>
						</xsl:when>
						<xsl:otherwise>
							<option value="WAU">author</option>
						</xsl:otherwise>
					</xsl:choose>	  
					<xsl:choose>
						<xsl:when test="field = 'WSU'">
							<option value="WSU" selected="selected">subject</option>
						</xsl:when>
						<xsl:otherwise>
							<option value="WSU">subject</option>
						</xsl:otherwise>
					</xsl:choose>		  
				  
				  
				</select>
				<xsl:text> </xsl:text><label for="query">for</label><xsl:text> </xsl:text>
				<input id="query" name="query" type="text" size="32" value="{$query}" /><xsl:text> </xsl:text>
				<input type="submit" name="Submit" value="GO" />
		</xsl:for-each>

		<xsl:if test="results/search/spelling != ''">
			<xsl:variable name="spell_url" select="results/search/spelling_url" />
			<p class="errorSpelling">Did you mean: <a href="{$spell_url}"><xsl:value-of select="//spelling" /></a></p>
		</xsl:if>
	</div>
	
	<xsl:for-each select="//base_info">
		<xsl:if test="base_001">
			<xsl:variable name="database" select="base_001" />
			<input type="hidden" name="database" value="{$database}" />
		</xsl:if>
	</xsl:for-each>

</xsl:template>

<!-- 	
	TEMPLATE: FOLDER_BRIEF_RESULTS
	Brief results list that appears on many of the export options pages.
-->

<xsl:template name="folder_brief_results">

	<xsl:variable name="username" 	select="request/session/username" />

	<xsl:variable name="full">
		<xsl:choose>
			<xsl:when test="$rewrite = 'true'">
				<xsl:value-of select="$username" /><xsl:text>/record/</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>./?base=folder&amp;action=full&amp;username=</xsl:text><xsl:value-of select="$username" /><xsl:text>&amp;record=</xsl:text>
			</xsl:otherwise>
		</xsl:choose>	
	</xsl:variable>

	<table>
	<xsl:for-each select="results/records/record">	
		<xsl:variable name="id" select="id" />
		<tr valign="top">
			<td class="folderRecord">
				<input type="checkbox" name="record" value="{$id}" />
			</td>
			<td align="left" class="folderRecord" width="100%">
				<a href="{$full}{$id}" class="resultsTitle">
					<xsl:value-of select="title" />
				</a>
				<br /><xsl:value-of select="author" /> / <xsl:value-of select="format" /> / <xsl:value-of select="year" />
				
			</td>
		</tr>
	</xsl:for-each>
	</table>

</xsl:template>

<!-- 	
	TEMPLATE: FOLDER_OPTIONS
	Export options from the 'My Saved Records' pages
-->

<xsl:template name="folder_options">
	
	<xsl:variable name="username" 	select="request/session/username" />

	<xsl:variable name="base">
		<xsl:choose>
			<xsl:when test="$rewrite = 'true'">
				<xsl:value-of select="$username" /><xsl:text>?</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>./?base=folder&amp;username=</xsl:text><xsl:value-of select="$username" /><xsl:text>&amp;</xsl:text>
			</xsl:otherwise>
		</xsl:choose>	
	</xsl:variable>
	 
	 <div class="folderOutputs">
		 <table border="0" cellspacing="0" cellpadding="5">
			  <tr valign="top">
				<td class="folderOutputOptions">
					<img src="{$base_include}/images/folder_email.gif" alt="email" />
				</td>
				<td>
					<p><strong><a href="{$base}action=output_email&amp;sortKeys=title">Email</a></strong> records</p>
				</td>
				<td class="folderOutputOptions">
					<img src="{$base_include}/images/folder_download.gif" alt="download" />
				</td>
				<td>
					<p>Download to: </p>
					<ul>
					<li><strong><a href="{$base}action=output_export_endnote&amp;sortKeys=title">Endnote</a></strong></li>
					<li><strong><a href="{$base}action=output_export_text&amp;sortKeys=title">Text file</a></strong></li>
					</ul>
				</td>
			  </tr>
		</table>
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
			<img src="{$base_include}/images/back.gif" />
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
	
	<script src="{$base_include}/javascript/save.js" language="javascript" type="text/javascript"></script>	
	<script language="javascript" type="text/javascript">

		var dateSearch = "<xsl:value-of select="results/search/date" />";
		var xerxesRoot = "<xsl:value-of select="$base_url" />";
		var iSearchable = "<xsl:value-of select="$search_limit" />";
	</script>

</xsl:template>


<!-- 	
	TEMPLATE: PROXY LINK
	Determines whether native link to database should be proxied or not
	based on subscription flag in the database record, used on a number of pages
-->

<xsl:template name="proxy_link">
	
	<xsl:variable name="link_native_home" select="php:function('urlencode', string(link_native_home))" />
	
	<xsl:choose>
		<xsl:when test="subscription = '1'">
			<xsl:value-of select="$base_url" />
			<xsl:text>/</xsl:text>
			<xsl:text>./?base=databases&amp;action=proxy</xsl:text>
			<xsl:text>&amp;url=</xsl:text>
			<xsl:value-of select="$link_native_home" />									
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="link_native_home" />
		</xsl:otherwise>									
	</xsl:choose>


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
						<img src="{$base_include}/images/pdf.gif" alt="full text pdf" width="16" height="16" border="0" /> Full-Text in PDF
					</xsl:when>
					<xsl:when test="@type = 'html'">
						<img src="{$base_include}/images/html.gif" alt="full text html" width="16" height="16" border="0" /> Full-Text in HTML
					</xsl:when>
					<xsl:otherwise>
						<img src="{$base_include}/images/html.gif" alt="full text online" width="16" height="16" border="0" /> Full-Text Available
					</xsl:otherwise>
				</xsl:choose>
			
			</a>
		
		</div>
		
	</xsl:for-each>
</xsl:template>

</xsl:stylesheet>