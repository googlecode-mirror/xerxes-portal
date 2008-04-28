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
	
	<a name="top" />
	
	<div id="databasesAlpha">
	
		<div class="subject">
			<h1>Databases A-Z</h1>
		</div>
		
		<xsl:variable name="lower">abcdefghijklmnopqrstuvwxyz</xsl:variable>
		<xsl:variable name="upper">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
		
		<div class="databasesAlphaMenu">
		
		<xsl:for-each select="databases/database">
			
			<xsl:variable name="letter" select="substring(translate(title_display,$lower,$upper), 1, 1)" />
			
			<xsl:if test="substring(translate(preceding-sibling::database[1]/title_display,$lower,$upper), 1, 1) !=  $letter">
				<a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#<xsl:value-of select="$letter" /></xsl:attribute> 
				<xsl:value-of select="$letter" /></a>
				<span class="letterSeperator"> | </span> 
			</xsl:if>

		</xsl:for-each>
		
		</div>
		
		<xsl:for-each select="databases/database">
		
			<xsl:variable name="letter" select="substring(translate(title_display,$lower,$upper), 1, 1)" />
			
			<xsl:if test="substring(translate(preceding-sibling::database[1]/title_display,$lower,$upper), 1, 1) !=  $letter">
				<a name="{$letter}"><h2><xsl:value-of select="$letter" /></h2></a>
				<div class="alphaBack">
					[ <a><xsl:attribute name="href"><xsl:value-of select="/knowledge_base/request/server/request_uri" />#top</xsl:attribute>  Back to top</a> ]
				</div>
			</xsl:if>
		
			<div class="alphaDatabase">
				<xsl:variable name="link_native_home" select="php:function('urlencode', string(link_native_home))" />
				<xsl:variable name="id_meta" select="metalib_id" />		
						
				<div class="alphaTitle">
					<a>
					<xsl:attribute name="href"><xsl:call-template name="proxy_link" /></xsl:attribute>
						<xsl:value-of select="title_display" />
					</a>
					<xsl:if test="title_display">
						&amp;nbsp;<a>
						<xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute>
						<img alt="info" src="images/info.gif" >
							<xsl:attribute name="src"><xsl:value-of select="/knowledge_base/config/base_url" />/images/info.gif</xsl:attribute>
						</img>
					</a>
					</xsl:if>
				</div>
				
				<div class="alphaDescription">
					<xsl:value-of select="translate(description,'#','')" />
				</div>
			</div>

		</xsl:for-each>
	</div>
	
</xsl:template>
</xsl:stylesheet>