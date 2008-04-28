<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:include href="utils.xsl" />
<xsl:include href="../includes.xsl" />
<xsl:output method="text" encoding="utf-8"/>

<xsl:template match="/folder">

	<xsl:for-each select="//xerxes_record">
	
		<xsl:if test="title_normalized">
			<xsl:text>Title: </xsl:text>
			<xsl:value-of select="title_normalized" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="format">
			<xsl:text>Format: </xsl:text>
			<xsl:value-of select="format" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="authors/author">
			<xsl:text>Authors: </xsl:text>
			<xsl:for-each select="authors/author">
				<xsl:call-template name="author"><xsl:with-param name="type" value="last" /></xsl:call-template>
				<xsl:if test="following-sibling::author">
					<xsl:text>; </xsl:text>
				</xsl:if>
			</xsl:for-each>
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="journal">
			<xsl:text>Original Citation: </xsl:text>
			<xsl:value-of select="journal" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="journal_title">
			<xsl:text>Journal Title: </xsl:text>
			<xsl:value-of select="journal_title" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="volume">
			<xsl:text>Volume: </xsl:text>
			<xsl:value-of select="volume" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="issue">
			<xsl:text>Issue: </xsl:text>
			<xsl:value-of select="issue" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="start_page">
			<xsl:text>Start Page: </xsl:text>
			<xsl:value-of select="start_page" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="end_page">
			<xsl:text>End Page: </xsl:text>
			<xsl:value-of select="end_page" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="place">
			<xsl:text>Place: </xsl:text>
			<xsl:value-of select="place" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="publisher">
			<xsl:text>Publisher: </xsl:text>
			<xsl:value-of select="publisher" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="year">
			<xsl:text>Year: </xsl:text>
			<xsl:value-of select="year" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="abstract">
			<xsl:text>Summary: </xsl:text>
			<xsl:value-of select="abstract" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="subjects/subject">
			<xsl:text>Subjects: </xsl:text>
			<xsl:for-each select="subjects/subject">
				<xsl:value-of select="text()" />
				<xsl:if test="following-sibling::subject">
					<xsl:text>; </xsl:text>
				</xsl:if>
			</xsl:for-each>
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>	
		<xsl:if test="language">
			<xsl:text>Language: </xsl:text>
			<xsl:value-of select="language" />
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		<xsl:if test="note">
			<xsl:text>Notes: </xsl:text>
			<xsl:for-each select="note">
				<xsl:value-of select="text()" />
				<xsl:if test="following-sibling::subject">
					<xsl:text>; </xsl:text>
				</xsl:if>
			</xsl:for-each>
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:if>
		

		<xsl:for-each select="links/link[@type != 'none']">
			<xsl:choose>
				<xsl:when test="@type = 'html'">			
					<xsl:text>Full-text in HTML: </xsl:text>
				</xsl:when>	
				<xsl:when test="@type = 'pdf'">			
					<xsl:text>Full-text in PDF: </xsl:text>
				</xsl:when>	
				<xsl:otherwise>
					<xsl:text>Full-text: </xsl:text>
				</xsl:otherwise>
			</xsl:choose>
			
			<xsl:call-template name="fulltext">
				<xsl:with-param name="rewrite"><xsl:value-of select="$rewrite" /></xsl:with-param>
				<xsl:with-param name="type"><xsl:value-of select="@type" /></xsl:with-param>
				<xsl:with-param name="id"><xsl:value-of select="../../../id" /></xsl:with-param>
				<xsl:with-param name="base_url"><xsl:value-of select="$base_url" /></xsl:with-param>
        <xsl:with-param name="url"><xsl:value-of select="../../../url" /></xsl:with-param>
				
			</xsl:call-template>
			
			<xsl:text>&#013;&#010;</xsl:text>
		</xsl:for-each>

		<xsl:choose>
			<xsl:when test="full_text_bool">
				<xsl:text>Check for additional availability: </xsl:text>
			</xsl:when>
			<xsl:otherwise>
				<xsl:text>Check for availability: </xsl:text>
			</xsl:otherwise>
		</xsl:choose>

		<xsl:call-template name="fulltext">
			<xsl:with-param name="rewrite"><xsl:value-of select="$rewrite" /></xsl:with-param>
			<xsl:with-param name="type">openurl</xsl:with-param>
			<xsl:with-param name="id"><xsl:value-of select="../id" /></xsl:with-param>
			<xsl:with-param name="base_url"><xsl:value-of select="$base_url" /></xsl:with-param>
			
		</xsl:call-template>
		
		<xsl:text>&#013;&#010;</xsl:text>
		<xsl:text>&#013;&#010;</xsl:text>
		<xsl:text>&#013;&#010;</xsl:text>
		
	</xsl:for-each>
	
</xsl:template>

</xsl:stylesheet>