<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: David Walker
 copyright: 2007 California State University
 version 1.1
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->

<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:include href="includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/error">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">
	
	<div id="content">
		<div class="loginBox">
			<table border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td><span class="error"><img src="{$base_url}/images/warning.gif" width="30" height="28" /></span></td>
				<td><h3 class="error">Sorry, there was an error</h3></td>
			</tr>
			</table>
			<xsl:choose>
            
            	<!-- make sure that database errors are not shown to the user -->
                
            	<xsl:when test="message[@type = 'PDOException']">
                	<p>There was a problem with the database.</p>
                </xsl:when>
            	<xsl:when test="request/base = 'folder'">
                	<p>You can <a href="./?base=folder">access your saved records here</a>.</p>
                </xsl:when>                
                <xsl:otherwise>
			<p><xsl:value-of select="message" /></p>
                </xsl:otherwise>
            </xsl:choose>
		</div>
	</div>
	
</xsl:template>
</xsl:stylesheet>