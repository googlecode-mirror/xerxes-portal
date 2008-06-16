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
	LOCALIZED GLOBAL VARIABLES
	Override the value of any of the global variables in lib/xsl/includes.xsl
	for example, if you don't want username in your logout link? Put whatever you want here 
-->

<!-- <xsl:variable name="logout_text">Log-out</xsl:variable> -->



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
		<ul>
			<li>
				<a>
				<xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='database_list']/url" /></xsl:attribute>
				Database List (A-Z)
				</a>
			</li>
		</ul>
	</div>
</xsl:template>


</xsl:stylesheet>
