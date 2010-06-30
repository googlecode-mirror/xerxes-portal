<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2010 California State University
 version: $Id$
 package: Worldcat
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">

<xsl:import href="../includes.xsl" />
<xsl:import href="worldcat.xsl" />

<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_worldcat" />
	<xsl:choose>
		<xsl:when test="//request/action = 'advanced'">
			Advanced Search
		</xsl:when>
		<xsl:otherwise>
			<xsl:copy-of select="$text_worldcat_name" />
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<xsl:template name="main">

		<h1><xsl:value-of select="$text_worldcat_name" /></h1>
		
		<xsl:call-template name="generic_searchbox" />
	
</xsl:template>

</xsl:stylesheet>
