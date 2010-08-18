<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2009 California State University
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:import href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<!-- certain operational parameters, given in request. -->

<xsl:variable name="show_alpha_links" select="not(/knowledge_base/request/show_alpha_links) or /knowledge_base/request/show_alpha_links != 'false'" />

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="breadcrumb">
	
	<xsl:choose>
		<xsl:when test="//request/action != 'alphabetical'">
		
			<xsl:call-template name="breadcrumb_databases">
				<xsl:with-param name="condition">4</xsl:with-param>
			</xsl:call-template>
		
			<xsl:copy-of select="$text_databases_az_breadcrumb_matching" /> "<xsl:value-of select="//request/query" />"
		
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="breadcrumb_databases" />
			<xsl:call-template name="page_name" />
		</xsl:otherwise>
	</xsl:choose>
	
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="$text_databases_az_pagename" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
</xsl:template>

<xsl:template name="main">
	
	<a name="top" />
	
	<h1><xsl:call-template name="page_name" /></h1>
	
	<xsl:if test="$databases_searchable = 'true'">
		<xsl:call-template name="databases_search_box" />
	</xsl:if>
	
	<p><strong><xsl:value-of select="count(databases/database)" /><xsl:text> </xsl:text><xsl:copy-of select="$text_databases_az_databases" /></strong></p>
	
	<xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
	<xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
	
	<xsl:if test="$show_alpha_links">
	
		<div id="alphaLetters">
		
			<xsl:for-each select="databases/database">
			
				<xsl:variable name="letter" select="substring(translate(title_display,$lower,$upper), 1, 1)" />
				
				<xsl:if test="substring(translate(preceding-sibling::database[1]/title_display,$lower,$upper), 1, 1) !=  $letter">
					<a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#<xsl:value-of select="$letter" /></xsl:attribute> 
					<xsl:value-of select="$letter" /></a>
					<span class="letterSeperator"><xsl:copy-of select="$text_databases_az_letter_separator" /></span> 
				</xsl:if>
			
			</xsl:for-each>
		
		</div>
	</xsl:if>
	
	<xsl:for-each select="databases/database">
	
		<xsl:if test="$show_alpha_links" >
			<xsl:variable name="letter" select="substring(translate(title_display,$lower,$upper), 1, 1)" />
		
			<xsl:if test="substring(translate(preceding-sibling::database[1]/title_display,$lower,$upper), 1, 1) !=  $letter">
			
				<div class="alphaHeading">
					<div class="yui-g">
						<div class="yui-u first">
							<a name="{$letter}"><h2><xsl:value-of select="$letter" /></h2></a>
						</div>
						<div class="yui-u">
							<div class="alphaBack">
								[ <a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#top</xsl:attribute><xsl:copy-of select="$text_databases_az_backtop" /></a> ]
							</div>
						</div>
					</div>
				</div>
				
			</xsl:if>
		</xsl:if>
		
		<div class="result">
			<xsl:variable name="link_native_home" select="php:function('urlencode', string(link_native_home))" />
			<xsl:variable name="id_meta" select="metalib_id" />		
		
			<div class="resultsTitle">
				<a target="{$link_target_databases}">
					<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
					<xsl:value-of select="title_display" />
				</a>
				
				<xsl:if test="title_display">
					&#160;
					<a href="{url}">

						<img alt="more information" title="{$text_databases_az_hint_info}" src="images/info.gif" class="iconInfo miniIcon">
							<xsl:attribute name="src"><xsl:value-of select="/knowledge_base/config/base_url" />/images/info.gif</xsl:attribute>
						</img>						
						<xsl:text> </xsl:text>
            
						<xsl:if test="searchable">
						<img alt="searchable by {$app_name}" title="{$text_databases_az_hint_searchable}" 
							class="iconSearchable miniIcon" src="{$base_url}/images/famfamfam/magnifier.png"/>
						</xsl:if>
					</a>
					
					<xsl:if test="count(group_restriction) > 0" >
						<xsl:text> </xsl:text>(<xsl:call-template name="db_restriction_display" />)
					</xsl:if>
				</xsl:if>
			</div>
			<xsl:choose>
				<xsl:when test="$db_description_multilingual != 'false'">
					<div class="resultsDescription">
						<xsl:call-template name="n-th-item-in-list">
							<xsl:with-param name="list">
								<xsl:value-of select="description" disable-output-escaping="yes" />
							</xsl:with-param>
							<xsl:with-param name="delimiter">\n\n\n</xsl:with-param>
							<xsl:with-param name="index">
								<xsl:value-of select="$xerxes_language_position" />
							</xsl:with-param>
						</xsl:call-template>
					</div>
				</xsl:when>
				<xsl:otherwise>
					<div class="resultsDescription">
						<xsl:value-of select="description" disable-output-escaping="yes" />
					</div>
				</xsl:otherwise>
			</xsl:choose>
		</div>
		
	</xsl:for-each>
	
</xsl:template>
</xsl:stylesheet>
