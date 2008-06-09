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

	Footer content. Customize by editing {Xerxes_app}/xsl/includes.xsl to
	override the template. 

    <p><a>
    <xsl:attribute name="href"><xsl:value-of select="navbar/element[@id='database_list']/url" /></xsl:attribute>
    [Database List (A-Z)]</a>
    
    <xsl:if test="request/base = 'databases' and request/action = 'subject'">
      <xsl:text> | </xsl:text>
      <xsl:variable name="subject" select="//category/@normalized" />
       <xsl:text> </xsl:text>
       <a href="./embed/gen_subject/{$subject}">[Generate Snippet]</a>
    </xsl:if>
    
    <xsl:if test="request/base = 'databases' and request/action = 'database'">
      <xsl:text> | </xsl:text>
      <xsl:variable name="id" select="//database[1]/metalib_id" />
       <xsl:text> </xsl:text>
       <a href="./embed/gen_database/{$id}">[Generate Snippet]</a>
    </xsl:if>
    
    </p>
</xsl:template>

<!-- 
	Over-ride categories_sidebar if you'd like to put something in the sidebar on the home page. 
	Here's an example of giving the user their login/authentication details. -->

<!--

<xsl:template name="categories_sidebar">

	<div id="sidebar_content">
		<xsl:call-template name="session_auth_info" />
	</div>

</xsl:template>

-->

</xsl:stylesheet>
