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
    <div id="sidebar_float" class="sidebar_float">
      <xsl:call-template name="account_sidebar"/>
    </div>
  
		<div class="loginBox">
			<table border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td><span class="error"><img src="{$base_url}/images/warning.gif" width="30" height="28" alt="" /></span></td>
				<td><h3 class="error"><xsl:value-of select="//error/heading" /></h3></td>
			</tr>
			</table>
			<xsl:choose>
            
        <!-- make sure that database errors are not shown to the user -->
          
        <xsl:when test="message[@type = 'PDOException']">
            <p>There was a problem with the database.</p>
        </xsl:when>
        <xsl:when test="request/base = 'folder'">
          <p><xsl:value-of select="message"/></p>
            <p>You can <a href="./?base=folder">access your saved records here</a>.</p>
        </xsl:when>                
        <xsl:otherwise>
          <p><xsl:value-of select="message"/></p>              
        </xsl:otherwise>
        </xsl:choose>
        
      <xsl:if test="//excluded_dbs/database">
        <p>You do not have access to search these included databases:</p>
        	<ul>
			<xsl:for-each select="//excluded_dbs/database">
				<li><strong><xsl:value-of select="title_display"/></strong>:
				<xsl:choose>
					<xsl:when test="group_restriction">
						<xsl:call-template name="db_restriction_display" />
					</xsl:when>
					<xsl:when test="subscription = '1'">
						Only available to registered users.
					</xsl:when>
				</xsl:choose>
				</li>
			</xsl:for-each>
			</ul>
      </xsl:if>
		</div>    
    
	</div>
	
</xsl:template>
</xsl:stylesheet>