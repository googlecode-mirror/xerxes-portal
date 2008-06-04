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
	Configuration values used throughout the templates. Can over-ride
  distribution settings if desired. 
-->

<!-- Don't want username in your logout link? Put whatever you want
     here -->
<!-- <xsl:variable name="logout_text">Log-out</xsl:variable> -->


<!-- Individual named template overrides -->

<xsl:template name="header_div">
    <h2 style="margin-top: 0;"><a style="color:white" class="footer" href="{$base_url}">
    <img src="{$base_url}/images/jhsearch-banner.jpg" >
      <xsl:attribute name="alt">    
        <xsl:value-of select="/*/config/application_name" />
      </xsl:attribute>
    </img>            
    </a></h2>
</xsl:template>

<xsl:template name="footer_div">
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

<!-- CSU demo examples
<xsl:template name="header_div">
		<a href="{$base_url}"><img src="{$base_include}/images/title.gif" alt="california state university, xerxes library" border="0" /></a>	
</xsl:template>

<xsl:template name="footer_div">
		<img src="{$base_include}/images/seal.gif" width="147" height="149" />
</xsl:template>

-->

<!-- Over-ride categories_sidebar if you'd like to put something in the sidebar on the home page. Here's an example of giving the user their login/authentication details.

<xsl:template name="categories_sidebar">
<div id="sidebar_content">
  <xsl:call-template name="session_auth_info" />
</div>1
</xsl:template>
-->

</xsl:stylesheet>