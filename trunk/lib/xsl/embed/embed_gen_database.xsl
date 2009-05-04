<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version: 1.1
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
		<xsl:with-param name="condition">3</xsl:with-param>
	</xsl:call-template>
	<xsl:call-template name="page_name" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="noscript_content">
		<a href="{//embed_info/direct_url}">
			<xsl:value-of select="//database/title_display" />
		</a>
	</xsl:variable>
	
	<!-- js to update example immediately -->
	
	<script type="text/javascript">
		snip_base_url = '<xsl:value-of select="embed_info/raw_embedded_action_url" />';
		snip_noscript_content = '<xsl:copy-of select="$noscript_content"/>';
	</script>
	
	<script type="text/javascript" src="{$base_url}/javascript/embed-gen-update.js"></script>

	<h1><xsl:call-template name="page_name" />: <xsl:value-of select="databases/database/title_display" /></h1>
		
	<div class="yui-gd">
		
		<div class="yui-u first">
			
			<div id="snippetControl">
				
				<form method="GET" id="generator">
					<input type="hidden" name="base" id="base" value="embed" />
					<input type="hidden" name="action" id="action" value="gen_database" />
					<input type="hidden" name="id">
						<xsl:attribute name="value"><xsl:value-of select="request/id" /></xsl:attribute>
					</input>
					
					<fieldset id="snippetDisplay">
						<legend><h2><xsl:copy-of select="$text_snippet_display_options" /></h2></legend>
						
						<table id="snippetDisplayTable" summary="{$text_ada_table_for_display}">
							<tr>
								<td><label for="disp_show_search"><xsl:copy-of select="$text_snippet_show_desc" /></label></td> 
								<td>
									<select name="disp_show_desc" id="disp_show_search">
										<option value="true">
											<xsl:if test="request/disp_show_desc = 'true'">
											<xsl:attribute name="selected">selected</xsl:attribute>
											</xsl:if>
											<xsl:value-of select="$text_snippet_display_yes" />
										</option>
										<option value="false">
											<xsl:if test="request/disp_show_desc = 'false'">
											<xsl:attribute name="selected">selected</xsl:attribute>
											</xsl:if>      
											<xsl:value-of select="$text_snippet_display_no" />
										</option>
									</select>    
								</td>
							</tr>
							<tr>
								<td><label for="disp_show_info_link"><xsl:copy-of select="$text_snippet_show_info_button" /></label></td> 
								<td>
									<select name="disp_show_info_link" id="disp_show_info_link">
									<option value="true">
										<xsl:if test="request/disp_show_info_link = 'true'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										<xsl:value-of select="$text_snippet_display_yes" />
									</option>
									<option value="false">
										<xsl:if test="request/disp_show_info_link = 'false'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>      
										<xsl:value-of select="$text_snippet_display_no" />
									</option>
									</select>
								</td>
							</tr>
						</table>
						
						<p><input type="submit" value="{$text_snippet_refresh}" /></p>
						  
					</fieldset>
					
					<div id="snippetInclude">
						
						<h2><xsl:copy-of select="$text_snippet_include_options" /></h2>
						
						<h3>1. <xsl:copy-of select="$text_snippet_include_server" /></h3>
						<p><xsl:copy-of select="$text_snippet_include_server_explain" /></p>
						
						<textarea id="direct_url_content" class="displayTextbox" readonly="yes">
							<xsl:value-of select="embed_info/embed_direct_url" />
						</textarea> 
						
						<h3>2. <xsl:copy-of select="$text_snippet_include_javascript" /></h3>
						<p><xsl:copy-of select="$text_snippet_include_javascript_explain" /></p>
						
						<textarea id="js_widget_content" class="displayTextbox" readonly="yes">
							<script type="text/javascript" charset="utf-8" >
								<xsl:attribute name="src"><xsl:value-of select="embed_info/embed_js_call_url"/></xsl:attribute>
							</script>
							<noscript>
								<xsl:copy-of select="$noscript_content"/>
							</noscript>
						</textarea>
						
						<h3>3. <xsl:copy-of select="$text_snippet_include_html" /></h3>
						<p><xsl:copy-of select="$text_snippet_include_html_explain" /></p>
						
						<p>
							<a target="_blank"  id="view_source_link" class="optionInfo">
								<xsl:attribute name="href" >
								<xsl:value-of select="embed_info/embed_direct_url" />
								<xsl:text>&amp;format=text</xsl:text>
								</xsl:attribute>
								<xsl:copy-of select="$text_snippet_include_html_source" />
							</a>
						</p>
						
						<xsl:variable name="passThroughURL" select="/*/databases/database[1]/xerxes_native_link_url"/>
						
						<h3>4. <xsl:copy-of select="$text_snippet_include_url" /></h3>
						<p><xsl:copy-of select="$text_snippet_include_url_explain" /></p>
						
						<p><a href="{$passThroughURL}"><xsl:copy-of select="$text_snippet_include_url" /></a></p> 
						
						<textarea id="passthrough_url_display" class="displayTextbox" readonly="yes">
							<xsl:value-of select="$passThroughURL" />
						</textarea>
					
					</div>
				</form>
			</div>
		</div>

		<div class="yui-u">
		
			<fieldset id="example">
			<legend><xsl:copy-of select="$text_snippet_example" /></legend>
				<div id="example_container">
					<div id="example_content">
						<xsl:value-of disable-output-escaping="yes" select="php:functionString('Xerxes_Command_Embed::getEmbedContent', embed_info/embed_direct_url)"  />
					</div>
				</div>
			</fieldset>
		</div>
	</div>

</xsl:template>
</xsl:stylesheet>
