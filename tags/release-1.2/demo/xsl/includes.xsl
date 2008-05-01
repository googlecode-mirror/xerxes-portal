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



<!-- 	
	TEMPLATE: SURROUND
	This is the master template that defines the overall design for the application; place
	here the header, footer and other design elements which all pages should contain.
-->


<xsl:template name="header_div">
    <h2 style="margin-top: 0;"><a style="color:white" class="footer" href="{$base_url}">WELCOME TO XERXES</a></h2>
    <p style="color:white">Header content. Customize by editing {Xerxes_app}/xsl/includes.xsl to
  override the template.</p>
</xsl:template>

<xsl:template name="footer_div">
    <p>Footer content. Customize by editing {Xerxes_app}/xsl/includes.xsl to
  override the template.</p>
</xsl:template>

<!-- CSU demo examples
<xsl:template name="header_div">
		<a href="{$base_url}"><img src="{$base_include}/images/title.gif" alt="california state university, xerxes library" border="0" /></a>	
</xsl:template>

<xsl:template name="footer_div">
		<img src="{$base_include}/images/seal.gif" width="147" height="149" />
</xsl:template>

-->












</xsl:stylesheet>