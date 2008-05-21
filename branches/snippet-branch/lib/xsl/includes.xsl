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
	<body class="xerxes">
	<xsl:if test="/metasearch">
		<xsl:attribute name="onLoad">loadFolders()</xsl:attribute>
	</xsl:if>
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
	TEMPLATE: header_div
  Contents of on-screen header. Generally overridden in local stylesheet.
-->
<xsl:template name="header_div" >
    <h2 style="margin-top: 0;"><a style="color:white" class="footer" href="{$base_url}">WELCOME TO <xsl:value-of select="/knowledge_base/config/application_name" /></a></h2>
    <p style="color:white">Header content. Customize by editing {Xerxes_app}/xsl/includes.xsl to
  override the template.</p>
</xsl:template>

<!-- 	
	TEMPLATE: footer_div
  Contents of on-screen header. Generally overridden in local stylesheet.
-->
<xsl:template name="footer_div" >
  Footer content. Customize by editing {Xerxes_app}/xsl/includes.xsl to
  override the template. 
</xsl:template>


<!--
  TEMPLATE: page_name
  A heading that can be used to label this page. Some views use this for
  their heading, others don't. 
  The "title" template always uses this to construct an html title. 
-->
<xsl:template name="page_name">
  <xsl:variable name="folder">
		<xsl:text>Saved Records</xsl:text>
	</xsl:variable>
  
	<xsl:choose>
		<xsl:when test="request/action = 'subject' or request/actions/action = 'subject'">
	     <xsl:text></xsl:text><xsl:value-of select="//category/@name" />
		</xsl:when>
		<xsl:when test="request/action = 'alphabetical'">
			<xsl:text>Databases A-Z</xsl:text>
		</xsl:when>
    <xsl:when test="request/base = 'databases' and request/action = 'find'">
      <xsl:text>Find a Database</xsl:text>
    </xsl:when>
		<xsl:when test="request/action = 'database'">
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
		<xsl:when test="request/action = 'hits'">
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Searching</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'results' or request/action = 'facet'">
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Results </xsl:text>
			( <xsl:value-of select="summary/range" /> )
		</xsl:when>
		<xsl:when test="request/action = 'record'">
			<xsl:value-of select="results/search/context" /><xsl:text>: </xsl:text>
			<xsl:value-of select="results/search/pair/query" /><xsl:text>: </xsl:text>
			<xsl:text>Record</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'home'">
			<xsl:value-of select="$folder" />
		</xsl:when>
		<xsl:when test="request/action = 'login'">
			<xsl:text>Login</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'logout'">
			<xsl:text>Logout</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'output_email'">
			<xsl:text>Email</xsl:text>
		</xsl:when>
		<xsl:when test="request/action = 'output_export_endnote'">
			<xsl:text>Download to Endnote</xsl:text>
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
		<xsl:when test="request/action = 'categories' or request/actions/action = 'categories'">
			<xsl:value-of select="$base" />
		</xsl:when>
		<xsl:when test="request/action = 'subject' or request/actions/action = 'subject'">
			<xsl:value-of select="$base" /><xsl:text>: </xsl:text><xsl:value-of select="//category/@name" />
		</xsl:when>
		<xsl:when test="request/action = 'alphabetical'">
			<xsl:value-of select="$base" /><xsl:text>: Databases A-Z</xsl:text>
		</xsl:when>
    <xsl:when test="request/base = 'databases' and request/action = 'find'">
      <xsl:value-of select="$base" /><xsl:text>: Find a Database</xsl:text>
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
    <xsl:otherwise>
      <xsl:value-of select="$base" />
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
	<xsl:variable name="return"			select="request/return" />
  <xsl:variable name="return_title" select="request/return_title" />
	<xsl:variable name="group"			select="request/group" />
	<xsl:variable name="resultset" 		select="request/resultset" />
	<xsl:variable name="start_record" 	select="request/startrecord" />
	<xsl:variable name="records_per_page" 	select="config/records_per_page" />
    
	<xsl:variable name="folder" select="navbar/element[@id = 'saved_records']/url" />
	
  
	
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
    <xsl:when test="request/base = 'databases' and request/action = 'find'">
      <xsl:call-template name="page_name" />
    </xsl:when>
		<xsl:when test="request/action = 'database'">
    
      <xsl:if test="$return != ''">          
          <a href="{$return}">
            <xsl:choose>
              <xsl:when test="$return_title != ''">
                <xsl:value-of select="$return_title" />
              </xsl:when>
              <xsl:otherwise><xsl:text>Databases</xsl:text></xsl:otherwise>
            </xsl:choose>
          </a> &gt;
      </xsl:if>
    
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
    <xsl:when test="request/base = 'embed' and request/action = 'gen_subject'">
      <a>
        <xsl:attribute name="href">
          <xsl:value-of select="//category/url" />
        </xsl:attribute>
        <xsl:value-of select="//category/@name" />
      </a>
      &gt;
      <span class="breadcrumbHere">Create Snippet</span>
    </xsl:when>
    <xsl:when test="request/base = 'embed' and request/action = 'gen_database'">
      <a>
        <xsl:attribute name="href">
            <xsl:value-of select="//database/url" />
        </xsl:attribute>
        <xsl:value-of select="//title_display" />
      </a>
      &gt;
      <span class="breadcrumbHere">Create Snippet</span>
    </xsl:when>
    <xsl:otherwise>
      <span class="breadcrumbHere"><xsl:call-template name="page_name" /></span>
    </xsl:otherwise>
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
				<a>
        <xsl:attribute name="href"><xsl:value-of select="navbar/element[@id = 'logout']/url" /></xsl:attribute>
        Log-out</a>
			</xsl:when>
			<xsl:otherwise>
				<a>
        <xsl:attribute name="href"><xsl:value-of select="navbar/element[@id = 'login']/url" /></xsl:attribute>
        Log-in</a>
			</xsl:otherwise>
			</xsl:choose>
		</span>
		|
		<span class="sessionAction">
			<img src="{$base_include}/images/folder.gif" name="folder" width="17" height="15" border="0" id="folder" alt="folder"/>
			<xsl:text> </xsl:text>
			<a>
      <xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='saved_records']/url" /></xsl:attribute>
      My Saved Records</a>
		</span>	
	</div>
</xsl:template>

<!-- 	
	TEMPLATE: SEARCH_BOX
	Search box that appears in the 'hits' and 'results' page, as well as databases_subject.xsl. 
-->


<xsl:template name="search_box">
	
	<div class="searchBox">
    
			<xsl:variable name="query" select="//search/pair[1]/query" />
				<label for="field">Search</label><xsl:text> </xsl:text>
				<select id="field" name="field">
          <option value="WRD">all fields</option>	
          <option value="WTI">
            <xsl:if test="//search/pair[1]/field = 'WTI'">
              <xsl:attribute name="selected">seleted</xsl:attribute>
            </xsl:if>
            title
          </option>
          <option value="WAU">
            <xsl:if test="//search/pair[1]/field = 'WAU'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            author              
          </option>
          <option value="WSU">
            <xsl:if test="//search/pair[1]/field = 'WSU'">
              <xsl:attribute name="selected">selected</xsl:attribute>
            </xsl:if>
            subject
          </option>          				  
				</select>
				<xsl:text> </xsl:text><label for="query">for</label><xsl:text> </xsl:text>
				<input id="query" name="query" type="text" size="32" value="{$query}" /><xsl:text> </xsl:text>


      <input type="submit" name="Submit" value="GO" />

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

<!-- TEMPLATE: subject_databases_list
  used to list databases, generally on a search form, on databases_subject.xsl,
  and embed_subject.xsl -->
<xsl:template name="subject_databases_list">
    <!-- default to true: -->
  <xsl:param name="should_show_checkboxes" select="true()" /> 
  
  <xsl:for-each select="category/subcategory">
    <fieldset class="subjectSubCategory">
    <legend><xsl:value-of select="@name" /></legend>
    
    <xsl:variable name="subcategory" select="position()" />
  
    <table summary="this table lists databases you can search" class="subjectCheckList">
    <xsl:for-each select="database">
      <xsl:variable name="id_meta" select="metalib_id" />
  
      <tr valign="top">		
        <td>
          <xsl:choose>
            <xsl:when test="not($should_show_checkboxes)">
            <xsl:text> </xsl:text>
            </xsl:when>
            <xsl:when test="searchable = 1">
              <xsl:choose>
                <xsl:when test="subscription = '1' and /knowledge_base/request/session/role = 'guest'">
                  <img src="{$base_url}/images/lock.gif" alt="restricted to campus users only" />
                </xsl:when>
                <xsl:otherwise>
                  <xsl:element name="input">
                    <xsl:attribute name="name">database</xsl:attribute>
                    <xsl:attribute name="id"><xsl:value-of select="metalib_id" /></xsl:attribute>
                    <xsl:attribute name="value"><xsl:value-of select="metalib_id" /></xsl:attribute>
                    <xsl:attribute name="type">checkbox</xsl:attribute>
                    <xsl:if test="$subcategory = 1 and searchable/@count &lt;= 10">
                      <xsl:attribute name="checked">checked</xsl:attribute>
                    </xsl:if>
                  </xsl:element>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:when>
            <xsl:otherwise>
              <img src="{$base_url}/images/link-out.gif" alt="non-searchable database" />
            </xsl:otherwise>
          </xsl:choose>
        </td>
        <td>
          <xsl:element name="label">
            <xsl:attribute name="for"><xsl:value-of select="metalib_id" /></xsl:attribute>
            
            <xsl:variable name="link_native_home" select="php:function('urlencode', string(link_native_home))" />
            
            <a>
            <xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
              <xsl:value-of select="title_display" />
            </a>
            &#160;<a>
              <xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
              <img alt="info" src="images/info.gif" >
                <xsl:attribute name="src"><xsl:value-of select="/*/config/base_url" />/images/info.gif</xsl:attribute>
              </img>
            </a>
          </xsl:element>
        </td>
      </tr>
    </xsl:for-each>	
    </table>
    
    </fieldset>
    
  </xsl:for-each>
</xsl:template>

<!-- 
  TEMPLATE: databases_search_box
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
    <input type="submit" value="GO" />
   <xsl:if test="request/action != 'alphabetical'">
     <hr /><a>
     <xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='database_list']/url" /></xsl:attribute>
     Show all Databases (A-Z)</a>
   </xsl:if>
  </div>
</form>
</xsl:template>


<!-- 	
	TEMPLATE: FOLDER_BRIEF_RESULTS
	Brief results list that appears on many of the export options pages.
-->

<xsl:template name="folder_brief_results">

  <xsl:variable name="username" 	select="request/session/username" />

	<table>
	<xsl:for-each select="results/records/record">	
		<xsl:variable name="id" select="id" />
    <xsl:variable name="url"    select="url" />

		<tr valign="top">
			<td class="folderRecord">
				<input type="checkbox" name="record" value="{$id}" />
			</td>
			<td align="left" class="folderRecord" width="100%">
				<a href="{$url}" class="resultsTitle">
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
	 
	 <div class="folderOutputs">
		 <table border="0" cellspacing="0" cellpadding="5">
			  <tr valign="top">
				<td class="folderOutputOptions">
					<img src="{$base_include}/images/folder_email.gif" alt="email" />
				</td>
				<td>
					<p><strong><a>
          <xsl:attribute name="href"><xsl:value-of select='export_functions/export_option[@id="email"]/url' /></xsl:attribute>
          Email</a></strong> records</p>
				</td>
				<td class="folderOutputOptions">
					<img src="{$base_include}/images/folder_download.gif" alt="download" />
				</td>
				<td>
					<p>Download to: </p>
					<ul>
					<li><strong><a>
          <xsl:attribute name="href"><xsl:value-of select='export_functions/export_option[@id="endnote"]/url' /></xsl:attribute>
          Endnote</a></strong></li>
					<li><strong><a>
          <xsl:attribute name="href"><xsl:value-of select='export_functions/export_option[@id="text"]/url' /></xsl:attribute>
          Text file</a></strong></li>
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
	
  <!-- prototype -->
  <script src="{$base_include}/javascript/prototype-1.6.0.2.js" language="javascript" type="text/javascript"></script>	
  
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
<!-- obsoleted. But left here for a second while until I'm SURE of that. 
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


</xsl:template> -->

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