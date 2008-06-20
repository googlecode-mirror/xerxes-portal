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

-->


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
	</p>

</xsl:template>

<!-- Override categories_sidebar if you'd like to put something in the sidebar on the home page.  -->

<xsl:template name="categories_sidebar">

	<div id="sidebar_content">
	
		<!-- Here's an example of giving the user their login/authentication details. -->
		
		<xsl:call-template name="session_auth_info" />		
	
		<!-- see, there is an a-z list too! -->
	
		<h2>Additional Options</h2>
		<ul>
			<li>
				<a>
				<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='database_list']/url" /></xsl:attribute>
				Database List (A-Z)
				</a>
			</li>
			<li>Example</li>
			<li>Example</li>
		</ul>
	
	</div>
	
	
</xsl:template>


</xsl:stylesheet>
