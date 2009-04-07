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

<!-- Turn off display of the Saved Databases/Collection feature, if you
     don't want it, uncomment this: -->
<!-- <xsl:variable name="show_collection_links" select="false()"/> -->
  
  
<!-- 
	LOCALIZED TEXT LABELS / GLOBAL VARIABLES
	Override the value of any of the global variables in lib/xsl/includes.xsl.  This also
	includes text labels used throughout the system.
-->

<!-- 

<xsl:variable name="text_header_logout">Log-out</xsl:variable>

<xsl:variable name="text_header_savedrecords">
	<xsl:choose>
	<xsl:when test="//request/session/role = 'local' or //request/session/role = 'guest'">Temporary Saved Records</xsl:when>
	<xsl:otherwise>My Saved Records</xsl:otherwise>
	</xsl:choose>
</xsl:variable>

  <xsl:variable name="text_link_resolver_available">Full-Text via Find It</xsl:variable>
  
  <xsl:variable name="text_link_resolver_check">Check availability via Find It</xsl:variable>
  
  
-->

<!-- want a custom mini icon for your app, used in OpenSearch and potentially
     other places?  Should be 16x16. 
     <xsl:variable name="app_mini_icon_url">http://university.edu/mini_logo.gif</xsl:variable> -->


<!-- Don't want searchable icon on database list? Uncomment. -->
<!-- <xsl:variable name="show_db_searchable_icon" select="false()" /> -->

<!-- Don't want a search box on the database detail page? Uncomment. -->
<!--   <xsl:variable name="show_db_detail_search" select="false()" /> -->

<!-- Want details provided on the page for the home page default search?
     Uncomment. -->
<!--  <xsl:variable name="homepage_search_details" select="true()" /> -->

<!-- Should the full metasearch form be used on the home page, 
     instead of a smaller simpler one?  If you'd like the full one,
     uncomment. -->
<!--  <xsl:variable name="homepage_use_simple_search" select="false()" /> -->

<!-- Header -->

<xsl:template name="header_div" >
	<div style="color: #fff; font-weight: bold; font-size: 130%; margin-bottom: 12px"><xsl:value-of select="//config/application_name" /></div>
	<div style="color:#efefef">Header content. Customize by editing {Xerxes_app}/xsl/includes.xsl to override the template.</div>
</xsl:template>

<!-- Footer -->

<xsl:template name="footer_div" >

	<p>Footer content. Customize by editing {Xerxes_app}/xsl/includes.xsl to
	override the template.</p>
	
	<p>
	<xsl:if test="request/base = 'databases' and request/action = 'subject'">
		<xsl:variable name="subject" select="//category/@normalized" />
		[ <a href="./embed/gen_subject/{$subject}">Generate Snippet</a> ]
	</xsl:if>
	
	<xsl:if test="request/base = 'databases' and request/action = 'database'">
		<xsl:variable name="id" select="//database[1]/metalib_id" />
		[ <a href="./embed/gen_database/{$id}">Generate Snippet</a> ]
	</xsl:if>
  
  <xsl:if test="request/base = 'collections' and (request/action = 'subject' or request/action = 'edit_form')">
    [ <a href="./collections/gen_embed/{//category[1]/@owned_by_user}/{//category[1]/@normalized}"> Generate Snippet </a> ]
  </xsl:if>
  
	</p>

</xsl:template>

<!-- 
	Override categories_sidebar if you'd like to put something in the sidebar on the home page. 

	session_auth_info provides an example of giving the user their login/authentication details.
	The 'additional options' list below also provides a link to the a-z database list.
-->

<!--
	
<xsl:template name="categories_sidebar">

	<div id="sidebar_content">
		
		<xsl:call-template name="session_auth_info" />
	
		<h2>Additional Options</h2>
		<ul>
			<li>
				<a>
				<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='database_list']/url" /></xsl:attribute>
				Database List (A-Z)
				</a>
			</li>
			<li>Ask a Librarian</li>
			<li>Example</li>
			<li>Another Example</li>
		</ul>
	
	</div>
	
</xsl:template>

-->

</xsl:stylesheet>
