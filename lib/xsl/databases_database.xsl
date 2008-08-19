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

	<xsl:variable name="request_uri"	select="//request/server/request_uri" />
	
	<div id="container">
		
		<div id="searchArea">
			<xsl:for-each select="//database">
			
				<xsl:variable name="native_link" select="php:function('urlencode', string(link_native_home))" />
				<xsl:variable name="id_meta" select="metalib_id" />
				
				<h2><xsl:value-of select="title_display" /></h2>
				
				<div class="databasesDescription">
					<xsl:value-of select="translate(description,'#', '')" />			
				</div>
				
				<table class="databasesTable">
					<tr>
						<td class="databasesAttribute">Link:</td>
						<td class="databasesValue">		
							<a>
							<xsl:attribute name="href"><xsl:value-of select="xerxes_native_link_url" /></xsl:attribute>
								Go to this database!
							</a>
						</td>
					</tr>

					
					<tr>
						<td class="databasesAttribute">Availability:</td>
						<td class="databasesValue">
							<xsl:choose>
                <xsl:when test="group_restriction">
                  <xsl:call-template name="db_restriction_display" />
                </xsl:when>
								<xsl:when test="subscription = '1'">
									Only available to registered users.
								</xsl:when>
								<xsl:otherwise>
									Available to everyone.
								</xsl:otherwise>
							</xsl:choose>
						</td>
					</tr>
					
					<xsl:if test="coverage">
					<tr>
						<td class="databasesAttribute">Coverage:</td>
						<td class="databasesValue"><xsl:value-of select="coverage" /></td>
					</tr>
					</xsl:if>
					
					<xsl:if test="link_guide">
					<tr>
						<td class="databasesAttribute">Guide:</td>
						<td class="databasesValue">
							<a>
								<xsl:attribute name="href"><xsl:value-of select="link_guide" /></xsl:attribute>
								Help in using this database
							</a>
						</td>
					</tr>
					</xsl:if>

					<xsl:if test="creator">
					<tr>
						<td class="databasesAttribute">Creator:</td>
						<td class="databasesValue"><xsl:value-of select="creator" /></td>
					</tr>
					</xsl:if>

					<xsl:if test="publisher">
					<tr>
						<td class="databasesAttribute">Publisher:</td>
						<td class="databasesValue"><xsl:value-of select="publisher" /></td>
					</tr>
					</xsl:if>
					
				</table>
				
			</xsl:for-each>
		</div>

		<div id="sidebar">
			
		</div>
	</div>
	
</xsl:template>
</xsl:stylesheet>
