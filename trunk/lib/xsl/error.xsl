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
	
	<!-- @todo i18n for error message? -->
	
	<h1 class="error"><xsl:value-of select="//error/heading" /></h1>
            
	<!-- do not show database errors to users -->
	
	<xsl:choose>
		<xsl:when test="message[@type = 'PDOException']">
			<p><xsl:copy-of select="$text_error_pdo_exception" /></p>
		</xsl:when>              
		<xsl:otherwise>
			<p><xsl:value-of select="message"/></p>              
		</xsl:otherwise>
	</xsl:choose>
	
	<!-- databases excluded from the search -->
	
	<xsl:if test="//excluded_dbs/database">
		<p><xsl:copy-of select="$text_error_databases_permission" /></p>
		<ul>
			<xsl:for-each select="//excluded_dbs/database">
				<li>
					<strong><xsl:value-of select="title_display"/></strong>:
					<xsl:choose>
						<xsl:when test="group_restriction">
							<xsl:call-template name="db_restriction_display" />
						</xsl:when>
						<xsl:when test="subscription = '1'">
							<xsl:copy-of select="$text_error_databases_registered" />
						</xsl:when>
					</xsl:choose>
				</li>
			</xsl:for-each>
		</ul>
	</xsl:if>

	
</xsl:template>
</xsl:stylesheet>