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
	xmlns:php="http://php.net/xsl">
<xsl:import href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">Save to personal collection</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_databases" />
	<a href="{/*/databases/database/url}">
		<xsl:value-of select="/*/databases/database/title_display" />
	</a> <xsl:copy-of select="$text_breadcrumb_separator" />
	<xsl:call-template name="page_name"/>
</xsl:template>

<xsl:template name="sidebar">
	<xsl:call-template name="account_sidebar"/>
	<xsl:call-template name="collections_sidebar"/>
</xsl:template>

<xsl:template name="main">

<!-- load js globals with some string variables the js will need -->
<script type="text/javascript">
collection_default_new_name = '<xsl:value-of select="$text_collection_default_new_name" />';
collection_default_new_section_name = '<xsl:value-of select="$text_collection_default_new_section_name" />';
</script>

<div id="container">
	<div id="searchArea">

	<xsl:for-each select="/*/databases/database[1]">
		<xsl:variable name="id" select="metalib_id" />
		<!-- username in request, unless they JUST logged in, then take it from
		session -->

		<xsl:variable name="username"><xsl:choose><xsl:when test="string(//request/username)"><xsl:value-of select="//request/username" /></xsl:when><xsl:otherwise><xsl:value-of select="//session/username"/></xsl:otherwise></xsl:choose></xsl:variable>
		<xsl:variable name="return" select="//request/return" />
		      
		<h2><xsl:call-template name="page_name"/>: <xsl:value-of select="title_display" /></h2>
		
		<form method="GET" id="save_database" action="{$base_url}">
			<input type="hidden" name="lang" value="{//request/lang}" />
			<input type="hidden" name="base" value="collections"/>
			<input type="hidden" id="action_input" name="action" value="save_choose_subheading"/>
			<input type="hidden" name="id" value="{$id}" />
			<input type="hidden" name="username" value="{$username}" />
			<input type="hidden" name="return" value="{$return}" />

			<div id="subjectChoice" class="miniForm">
				<h3>1. Choose a collection</h3><!-- @todo: i18n -->
				<p>
					<select id="subject" name="subject">
						<!-- if no existing ones, use our default name -->
						<xsl:if test="count(/*/userCategories/category) = 0">
							<option id="new_collection" value="NEW"><xsl:copy-of select="$text_collection_default_new_name"/></option>
						</xsl:if>
						<xsl:for-each select="/*/userCategories/category">
							<option value="{normalized}"><xsl:value-of select="name"/></option>
						</xsl:for-each>
					</select>
				</p>
			</div>
			 
			<!-- hidden div that will be shown and loaded by javascript -->
			<div id="subcategory_choice" class="miniForm" style="display: none">
				<h3>2. Choose a section</h3><!-- @todo: i18n -->
				<p>
					<select id="subcategory" name="subcategory">
					</select>
				</p>
			</div>
			
			<p>
				<input type="submit" name="save" value="save" class="submit_save-db-choose-cat{$language_suffix}" />
			</p>
		</form>
	</xsl:for-each>
	</div>
</div>
</xsl:template>
</xsl:stylesheet>
