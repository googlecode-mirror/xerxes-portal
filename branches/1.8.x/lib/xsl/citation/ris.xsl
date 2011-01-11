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
<xsl:include href="utils.xsl" />
<xsl:output method="text" encoding="utf-8"/>

<xsl:template match="/">

	<xsl:for-each select="//xerxes_record">
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>TY  - </xsl:text>
		<xsl:choose>
			<xsl:when test="format = 'Article'">
				<xsl:text>JOUR</xsl:text>
			</xsl:when>
			<xsl:when test="format = 'Book'">
				<xsl:text>BOOK</xsl:text>
			</xsl:when>
			<xsl:when test="format = 'Thesis' or format = 'Dissertation'">
				<xsl:text>THES</xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>GEN</xsl:text>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>T1  - </xsl:text><xsl:value-of select="title_normalized" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>T3  - </xsl:text><xsl:value-of select="series_title" />
		
		<xsl:for-each select="authors/author">
			<xsl:text>&#013;&#010;</xsl:text><xsl:text>A1  - </xsl:text>
			<xsl:call-template name="author">
				<xsl:with-param name="type">last</xsl:with-param>
			</xsl:call-template>
		</xsl:for-each>
		
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>Y1  - </xsl:text><xsl:value-of select="year" /><xsl:text>///</xsl:text>
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>N2  - </xsl:text><xsl:value-of select="abstract" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>JF  - </xsl:text><xsl:value-of select="journal_title" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>VL  - </xsl:text><xsl:value-of select="volume" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>IS  - </xsl:text><xsl:value-of select="issue" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>SP  - </xsl:text><xsl:value-of select="start_page" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>EP  - </xsl:text><xsl:value-of select="end_page" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>CY  - </xsl:text><xsl:value-of select="place" />
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>PB  - </xsl:text><xsl:value-of select="publisher" />
		
		<xsl:for-each select="standard_numbers">
			<xsl:for-each select="issn|isbn">
				<xsl:text>&#013;&#010;</xsl:text><xsl:text>SN  - </xsl:text><xsl:value-of select="text()" />
			</xsl:for-each>
		</xsl:for-each>
		
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>DO  - </xsl:text><xsl:value-of select="doi" />
		
		<xsl:if test="full_text_html != ''">			
			<xsl:text>&#013;&#010;</xsl:text><xsl:text>L1  - </xsl:text>
			
		</xsl:if>
		<xsl:if test="full_text_pdf != ''">
			<xsl:text>&#013;&#010;</xsl:text><xsl:text>L2  - </xsl:text>
				
		</xsl:if>
		<xsl:if test="full_text != ''">
			<xsl:text>&#013;&#010;</xsl:text><xsl:text>L1  - </xsl:text>
			
		</xsl:if>
		<xsl:text>&#013;&#010;</xsl:text><xsl:text>ER  - </xsl:text>
		<xsl:text>&#013;&#010;</xsl:text>
		<xsl:text>&#013;&#010;</xsl:text>
		<xsl:text>&#013;&#010;</xsl:text>

	</xsl:for-each>
		
</xsl:template>
</xsl:stylesheet>