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
	xmlns:php="http://php.net/xsl">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/knowledge_base">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<form name="form1" method="get" action="{$base_url}/" onSubmit="return databaseLimit(this)">
	<input type="hidden" name="base" value="metasearch" />
    <input type="hidden" name="action" value="search" />
	<input type="hidden" name="context" value="{$category_name}" />
	<input type="hidden" name="context_url" value="{$request_uri}" />
	
	<div id="container">
		<div id="searchArea">
	
			<div class="subject">
				<h1><xsl:value-of select="//category/@name" /></h1>
			</div>
				
			<div id="search">
				<div class="searchBox">
					<label for="field">Search</label> <xsl:text> </xsl:text>
					<select id="field" name="field">
						<option value="WRD" selected="selected">all fields</option>
						<option value="WTI">title</option>
						<option value="WAU">author</option>
						<option value="WSU">subject</option>
					</select><xsl:text> </xsl:text>
					<label for="query">for</label> <xsl:text> </xsl:text>
					<input id="query" name="query" type="text" size="30" /><xsl:text> </xsl:text>
					<input name="submit" type="submit" value="GO" />
				</div>
			</div>
			
			<div class="subjectDatabases">
				<xsl:for-each select="category/subcategory">
					<fieldset class="subjectSubCategory">
					<legend><xsl:value-of select="@name" /></legend>
					
					<xsl:variable name="subcategory" select="position()" />
				
					<table summary="this table lists databases you can search" class="subjectCheckList">
					<xsl:for-each select="database">
						<xsl:variable name="id_meta" select="metalib_id" />

						<tr valign="top">		
							<td>
								<xsl:choose>
									<xsl:when test="searchable = 1">
										<xsl:choose>
											<xsl:when test="subscription = '1' and /knowledge_base/request/session/role = 'guest'">
												<img src="{$base_url}/images/lock.gif" alt="restricted to campus users only" />
											</xsl:when>
											<xsl:otherwise>
												<xsl:element name="input">
													<xsl:attribute name="name">database</xsl:attribute>
													<xsl:attribute name="id"><xsl:value-of select="metalib_id" /></xsl:attribute>
													<xsl:attribute name="value"><xsl:value-of select="metalib_id" /></xsl:attribute>
													<xsl:attribute name="type">checkbox</xsl:attribute>
													<xsl:if test="$subcategory = 1 and searchable/@count &lt;= 10">
														<xsl:attribute name="checked">checked</xsl:attribute>
													</xsl:if>
												</xsl:element>
											</xsl:otherwise>
										</xsl:choose>
									</xsl:when>
									<xsl:otherwise>
										<img src="{$base_url}/images/link-out.gif" alt="non-searchable database" />
									</xsl:otherwise>
								</xsl:choose>
							</td>
							<td>
								<xsl:element name="label">
									<xsl:attribute name="for"><xsl:value-of select="metalib_id" /></xsl:attribute>
									
									<xsl:variable name="link_native_home" select="php:function('urlencode', string(link_native_home))" />
									
									<a>
									<xsl:attribute name="href"><xsl:call-template name="proxy_link" /></xsl:attribute>
										<xsl:value-of select="title_display" />
									</a>					
								</xsl:element>
							</td>							
						</tr>
					</xsl:for-each>	
					</table>
					
					</fieldset>
					
				</xsl:for-each>
			</div>
		</div>
		<div id="sidebar">
			
		</div>
	</div>

	</form>
	
</xsl:template>
</xsl:stylesheet>
