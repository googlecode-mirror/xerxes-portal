<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2009 California State University
 version: 1.5
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
	Databases A-Z
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
	
	<p><strong><xsl:value-of select="count(databases/database)" /> databases</strong></p>
	
	<xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
	<xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
	
	<xsl:if test="$show_alpha_links">
	
		<div id="alphaLetters">
		
			<xsl:for-each select="databases/database">
			
				<xsl:variable name="letter" select="substring(translate(title_display,$lower,$upper), 1, 1)" />
				
				<xsl:if test="substring(translate(preceding-sibling::database[1]/title_display,$lower,$upper), 1, 1) !=  $letter">
					<a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#<xsl:value-of select="$letter" /></xsl:attribute> 
					<xsl:value-of select="$letter" /></a>
					<span class="letterSeperator"> | </span> 
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
								[ <a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#top</xsl:attribute>  Back to top</a> ]
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
				<a>
					<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
					<xsl:value-of select="title_display" />
				</a>
				
				<xsl:if test="title_display">
					&#160;
					<a href="{url}">

						<img alt="more information" title="more information" src="images/info.gif" id="iconInfo">
							<xsl:attribute name="src"><xsl:value-of select="/knowledge_base/config/base_url" />/images/info.gif</xsl:attribute>
						</img>						
						<xsl:text> </xsl:text>
						<img alt="searchable by {$app_name}" title="searchable by {$app_name}" id="iconSearchable"
							 src="{$base_url}/images/famfamfam/magnifier.png"/>
					</a>
					
					<xsl:if test="count(group_restriction) > 0" >
						<xsl:text> </xsl:text>(<xsl:call-template name="db_restriction_display" />)
					</xsl:if>
				</xsl:if>
			</div>
			
			<div class="resultsDescription">
				<xsl:value-of select="description" disable-output-escaping="yes"/>
			</div>
		</div>
		
	</xsl:for-each>
	
</xsl:template>
</xsl:stylesheet>