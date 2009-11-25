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

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="/*/databases/database/title_display" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_databases" />
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar" />
	<xsl:call-template name="snippet_sidebar" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="request_uri"	select="//request/server/request_uri" />
	
	<xsl:for-each select="/*/databases/database">

		<xsl:variable name="native_link" select="php:function('urlencode', string(link_native_home))" />
		<xsl:variable name="id_meta" select="metalib_id" />

		<h1><xsl:call-template name="page_name" /></h1>
				
		<!-- show a search box if: 
		1) config is set to show search boxes on db detail page
		2) The db is searchable by the current user/session, OR
		2a) the db is searchable in general, and user is not logged in or on campus. 
		( Ie, if they are logged in or on campus and we know they can't search, don't show search box. )
		-->
		
		<xsl:if test="$show_db_detail_search = 'true' and searchable = '1'">
			<xsl:choose>
				<xsl:when test="searchable_by_user = '1' or //request/authorization_info/affiliated = 'false'">
					<form name="form1" method="get" action="{$base_url}/" class="metasearchForm">
						<input type="hidden" name="base" value="metasearch" />
						<input type="hidden" name="action" value="search" />
						<input type="hidden" name="context">
							<xsl:attribute name="value"><xsl:value-of select="title_display"/></xsl:attribute>
						</input>
						<input type="hidden" name="context_url" value="{$request_uri}" />
						<div id="search">
							<xsl:call-template name="search_box" />
						</div>
						<input type="hidden" name="database">
							<xsl:attribute name="id"><xsl:value-of select="metalib_id"/></xsl:attribute>
							<xsl:attribute name="value"><xsl:value-of select="metalib_id"/></xsl:attribute>
						</input>
					</form>
				</xsl:when>
				<xsl:otherwise>
					<i><img src="{$base_url}/images/famfamfam/magnifier.png" />Search <xsl:call-template name="db_restriction_display" />.</i>
				</xsl:otherwise>
			</xsl:choose>
		</xsl:if>
		
		<div id="saveDatabase">
			<a href="{add_to_collection_url}" class="iconCommand add"><xsl:copy-of select="$text_database_save_database" /></a>
		</div>
		
		<div class="databasesDescription">
			<xsl:value-of disable-output-escaping="yes" select="description" />
		</div>
		
		<dl>
			<div>
			<dt><xsl:copy-of select="$text_database_link" /></dt>
			<dd>		
				<a>
				<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
				<xsl:copy-of select="$text_database_go_to_database" />
				</a>
			</dd>
			</div>
			
			<div>
			<dt><xsl:copy-of select="$text_database_availability" /></dt>
			<dd>
				<xsl:choose>
					<xsl:when test="group_restriction">
						<xsl:call-template name="db_restriction_display" />
					</xsl:when>
					<xsl:when test="subscription = '1'">
						<xsl:copy-of select="$text_database_available_registered" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:copy-of select="$text_database_available_everyone" />
					</xsl:otherwise>
				</xsl:choose>
			</dd>
			</div>
			
			<xsl:if test="coverage">
				<div>
				<dt><xsl:copy-of select="$text_database_coverage" /></dt>
				<dd><xsl:value-of select="coverage" /></dd>
				</div>
			</xsl:if>
			
			<xsl:if test="link_guide">
				<div>
				<dt><xsl:copy-of select="$text_database_guide" /></dt>
				<dd>
					<a>
					<xsl:attribute name="href"><xsl:value-of select="link_guide" /></xsl:attribute>
						<xsl:copy-of select="$text_database_guide_help" />
					</a>
				</dd>
				</div>
			</xsl:if>
			
			<xsl:if test="creator">
				<div>
					<dt><xsl:copy-of select="$text_database_creator" />:</dt>
					<dd><xsl:value-of select="creator" /></dd>
				</div>
			</xsl:if>
			
			<xsl:if test="publisher">
				<div>
					<dt><xsl:copy-of select="$text_database_publisher" /></dt>
					<dd><xsl:value-of select="publisher" /></dd>
				</div>
			</xsl:if>
		</dl>
		
	</xsl:for-each>
	
</xsl:template>
</xsl:stylesheet>
