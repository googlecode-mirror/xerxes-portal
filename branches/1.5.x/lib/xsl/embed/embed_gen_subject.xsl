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
	Generate Snippet
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="noscript_content">
		<xsl:element name="a">
			<xsl:attribute name="href"><xsl:value-of select="//embed_info/direct_url" /></xsl:attribute>
			<xsl:value-of select="//category/@name" />
		</xsl:element>
	</xsl:variable>
	
	<script type="text/javascript">
		snip_base_url = '<xsl:value-of select="embed_info/raw_embedded_action_url" />';
		snip_noscript_content = '<xsl:copy-of select="$noscript_content"/>';
	</script>
	
	<script type="text/javascript" src="{$base_url}/javascript/embed-gen-update.js"></script>
	
	<h1><xsl:call-template name="page_name" /></h1>
	
	<div class="yui-gd">
		
		<div class="yui-u first">
		
			<div id="snippetControl">
		
				<form method="GET" id="generator" action="{$base_url}">
					<input type="hidden" name="base" value="{request/base}" />
					<input type="hidden" name="action" value="{request/action}" />
					<input type="hidden" name="subject" value="{request/subject}" />
					<input type="hidden" name="username" value="{category/@owned_by_user}"/>
						 
					<fieldset id="snippetDisplay">
						<legend><h2>Display options</h2></legend>
						
						<table id="snippetDisplayTable" summary="for display only">
							<tr>
							<td><label for="disp_show_title">Show title?</label></td>
							<td>
								<select id="disp_show_title" name="disp_show_title">
									<option value="true">
										<xsl:if test="request/disp_show_title = 'true'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										yes
									</option>
									<option value="false">
										<xsl:if test="request/disp_show_title = 'false'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										no
									</option>
								</select>
							</td>
							</tr>
							<tr>
							<td><label for="disp_show_search">Show search box?</label></td>
							<td>
								<select name="disp_show_search" id="disp_show_search">
									<option value="true">
										<xsl:if test="request/disp_show_search = 'true'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										yes
									</option>
									<option value="false">
										<xsl:if test="request/disp_show_search = 'false'">
										<xsl:attribute name="selected">selected</xsl:attribute>
										</xsl:if>
										no
									</option>
								</select>
							
							</td>
						</tr>
						<tr>
						<td><label for="disp_show_subcategories">Show databases?</label></td>
						<td>
							<select name="disp_show_subcategories" id="disp_show_subcategories">
								<option value="true">
									<xsl:if test="request/disp_show_subcategories = 'true'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>
									yes
								</option>
								<option value="false">
									<xsl:if test="request/disp_show_subcategories = 'false'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>      
									no
								</option>
							</select>
						</td>
						</tr>
						<tr>
						<td colspan="2">
							<label for="disp_only_subcategory">Show specific section?</label>
							<div id="snippetSubcategories">
								<select name="disp_only_subcategory" id="disp_only_subcategory">
									<option value="">ALL</option>
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
						<td><label for="disp_only_subcategory">Include CSS?</label></td>
						<td>
							<select id="disp_embed_css" name="disp_embed_css">
								<option value="true">
									<xsl:if test="request/disp_embed_css = 'true'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>
									yes
								</option>
								<option value="false">
									<xsl:if test="request/disp_embed_css = 'false'">
									<xsl:attribute name="selected">selected</xsl:attribute>
									</xsl:if>      
									no
								</option>
							</select>
						</td>
						</tr>
					</table>

					<p class="optionInfo">Including the CSS works imperfectly.  If you need to, it's better to define 
					CSS styles for the snippet in the external website itself.</p>
					
					
					<p><input type="submit" value="refresh" /></p>
					
					</fieldset>
					
					<div id="snippetInclude">
					
						<h2>Include Options</h2>
						
						<h3>1. Server-side include url</h3>
						<p>Preferred method of inclusion, if your external website can support a server-side include.</p>	
				
						<textarea id="direct_url_content" readonly="yes" class="displayTextbox">
							<xsl:value-of select="embed_info/embed_direct_url" />
						</textarea> 
				
						<h3>2. Javascript widget</h3>
						<p>Should work in any external website that allows javascript, but viewers' browsers 
						must support javascript.</p>
						
						<textarea id="js_widget_content" readonly="yes" class="displayTextbox">
							<script type="text/javascript" charset="utf-8" >
								<xsl:attribute name="src"><xsl:value-of select="embed_info/embed_js_call_url"/></xsl:attribute>
							</script>
							<noscript>
								<xsl:copy-of select="$noscript_content" />
							</noscript>
						</textarea>
				
						<h3>3. HTML Source </h3>
						<p>Last resort. If this is your only option, you can embed this HTML source directly into your external website. 
						However, if data or features change here, your snippet will not reflect those changes, and may even stop working. 
						Use with care.</p>
						<a target="_blank" id="view_source_link">
						<xsl:attribute name="href" >
						<xsl:value-of select="embed_info/embed_direct_url" />
						<xsl:text>&amp;format=text</xsl:text>
						</xsl:attribute>
						View snippet source
						</a>
					</div>
	
				</form>
			</div>
		</div>
		<div class="yui-u">
		
			<fieldset id="example">
				<legend id="example_legend">Example</legend>
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
