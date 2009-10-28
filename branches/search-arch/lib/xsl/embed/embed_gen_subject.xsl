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
	
<xsl:import href="../includes.xsl" />

<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" doctype-system="http://www.w3.org/TR/html4/loose.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround">
		<xsl:with-param name="surround_template">none</xsl:with-param>
		<xsl:with-param name="sidebar">none</xsl:with-param>
	</xsl:call-template>
</xsl:template>

<xsl:template name="page_name">
	<xsl:value-of select="$text_header_snippet_generate" />
</xsl:template>

<xsl:template name="breadcrumb">
	<xsl:call-template name="breadcrumb_databases">
		<xsl:with-param name="condition">2</xsl:with-param>
	</xsl:call-template>
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="noscript_content">
		<a href="{//embed_info/direct_url}">
			<xsl:value-of select="//category/@name" />
		</a>
	</xsl:variable>
	
	<script type="text/javascript">
		snip_base_url = '<xsl:value-of select="embed_info/raw_embedded_action_url" />';
		snip_noscript_content = '<xsl:copy-of select="$noscript_content"/>';
	</script>
	
	<script type="text/javascript" src="{$base_url}/javascript/embed-gen-update.js"></script>
	
	<h1><xsl:call-template name="page_name" />: <xsl:value-of select="category/@name" /></h1>
	
	<div class="yui-gd">
		
		<div class="yui-u first">
		
			<div id="snippetControl">
		
				<form method="GET" id="generator" action="{$base_url}">
					<input type="hidden" name="base" value="{request/base}" />
					<input type="hidden" name="action" value="{request/action}" />
					<input type="hidden" name="subject" value="{request/subject}" />
					<input type="hidden" name="username" value="{category/@owned_by_user}"/>
						 
					<fieldset id="snippetDisplay">
						<legend><h2><xsl:copy-of select="$text_snippet_display_options" /></h2></legend>
						
						<table id="snippetDisplayTable" summary="{$text_ada_table_for_display}">
							<tr>
							<td><label for="disp_show_title"><xsl:copy-of select="$text_snippet_show_title" /></label></td>
							<td>
								<select id="disp_show_title" name="disp_show_title">
									<option value="true">
										<xsl:if test="request/disp_show_title = 'true'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:value-of select="$text_snippet_display_yes" />
									</option>
									<option value="false">
										<xsl:if test="request/disp_show_title = 'false'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:value-of select="$text_snippet_display_no" />
									</option>
								</select>
							</td>
							</tr>
							<tr>
							<td><label for="disp_show_search"><xsl:copy-of select="$text_snippet_show_searchbox" /></label></td>
							<td>
								<select name="disp_show_search" id="disp_show_search">
									<option value="true">
										<xsl:if test="request/disp_show_search = 'true'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:value-of select="$text_snippet_display_yes" />
									</option>
									<option value="false">
										<xsl:if test="request/disp_show_search = 'false'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:value-of select="$text_snippet_display_no" />
									</option>
								</select>
							
							</td>
						</tr>
						<tr>
						<td><label for="disp_show_subcategories"><xsl:copy-of select="$text_snippet_show_databases" /></label></td>
						<td>
							<select name="disp_show_subcategories" id="disp_show_subcategories">
								<option value="true">
									<xsl:if test="request/disp_show_subcategories = 'true'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>
									<xsl:value-of select="$text_snippet_display_yes" />
								</option>
								<option value="false">
									<xsl:if test="request/disp_show_subcategories = 'false'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>      
									<xsl:value-of select="$text_snippet_display_no" />
								</option>
							</select>
						</td>
						</tr>
						<tr>
						<td colspan="2">
							<label for="disp_only_subcategory"><xsl:copy-of select="$text_snippet_show_section" /></label>
							<div id="snippetSubcategories">
								<select name="disp_only_subcategory" id="disp_only_subcategory">
									<option value=""><xsl:value-of select="$text_snippet_display_all" /></option>
									<xsl:for-each select="//subcategory">
										<option>
											<xsl:if test="request/disp_only_subcategory = @id">
												<xsl:attribute name="selected">selected</xsl:attribute>
											</xsl:if>
											<xsl:attribute name="value">
												<xsl:value-of select="@id" />
											</xsl:attribute>
											<xsl:value-of select="@name" />
										</option>
									</xsl:for-each>
								</select>
							</div>
						</td>
						</tr>
						<tr>
						<td><label for="disp_embed_css"><xsl:copy-of select="$text_snippet_show_css" /></label></td>
						<td>
							<select id="disp_embed_css" name="disp_embed_css">
								<option value="true">
									<xsl:if test="request/disp_embed_css = 'true'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>
									<xsl:value-of select="$text_snippet_display_yes" />
								</option>
								<option value="false">
									<xsl:if test="request/disp_embed_css = 'false'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>      
									<xsl:value-of select="$text_snippet_display_no" />
								</option>
							</select>
						</td>
						</tr>
					</table>

					<p class="optionInfo"><xsl:copy-of select="$text_snippet_show_css_explain" /></p>
					
					<p><input type="submit" value="{$text_snippet_refresh}" /></p>
					
					</fieldset>
					
					<div id="snippetInclude">
					
						<h2><xsl:copy-of select="$text_snippet_include_options" /></h2>
						
						<h3>1. <label for="direct_url_content"><xsl:copy-of select="$text_snippet_include_server" /></label></h3>
						<p><xsl:copy-of select="$text_snippet_include_server_explain" /></p>	
				
						<textarea id="direct_url_content" readonly="yes" class="displayTextbox">
							<xsl:value-of select="embed_info/embed_direct_url" />
						</textarea> 
				
						<h3>2. <label for="js_widget_content"><xsl:copy-of select="$text_snippet_include_javascript" /></label></h3>
						<p><xsl:copy-of select="$text_snippet_include_javascript_explain" /></p>
						
						<textarea id="js_widget_content" readonly="yes" class="displayTextbox">
							<script type="text/javascript" charset="utf-8" >
								<xsl:attribute name="src"><xsl:value-of select="embed_info/embed_js_call_url"/></xsl:attribute>
							</script>
							<noscript>
								<xsl:copy-of select="$noscript_content" />
							</noscript>
						</textarea>
				
						<h3>3. <xsl:copy-of select="$text_snippet_include_html" /></h3>
						<p><xsl:copy-of select="$text_snippet_include_html_explain" /></p>
						
						<a target="_blank" id="view_source_link">
							<xsl:attribute name="href" >
							<xsl:value-of select="embed_info/embed_direct_url" />
							<xsl:text>&amp;format=text</xsl:text>
							</xsl:attribute>
							<xsl:copy-of select="$text_snippet_include_html_source" />
						</a>
					</div>
	
				</form>
			</div>
		</div>
		<div class="yui-u">
		
			<fieldset id="example">
				<legend id="example_legend"><xsl:copy-of select="$text_snippet_example" /></legend>
				<div id="example_container">
				<div id="example_content">
					<xsl:value-of disable-output-escaping="yes" select="php:functionString('Xerxes_Command_Embed::getEmbedContent', embed_info/embed_direct_url)"	/>
				</div>
				</div>
			</fieldset>
			
		</div>
		
	</div>

</xsl:template>
</xsl:stylesheet>
