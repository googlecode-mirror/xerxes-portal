<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround">
		<xsl:with-param name="surround_template">none</xsl:with-param>
		<xsl:with-param name="sidebar">none</xsl:with-param>
	</xsl:call-template>
</xsl:template>

<xsl:template name="main">
	
	<xsl:variable name="back" select="request/server/http_referer" />
	
	<xsl:variable name="context">
		<xsl:choose>
			<xsl:when test="/*/request/context and /*/request/context != ''">
				<xsl:value-of select="/*/request/context" />
			</xsl:when>
			<xsl:otherwise><xsl:value-of select="text_folder_tags_edit_return_to_records" /></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<h1><xsl:value-of select="text_folder_tags_edit_updated" /></h1>
	<p><xsl:value-of select="text_folder_tags_edit_return" /><a href="{$back}"><xsl:value-of select="$context" /></a></p>
	
</xsl:template>
</xsl:stylesheet>
