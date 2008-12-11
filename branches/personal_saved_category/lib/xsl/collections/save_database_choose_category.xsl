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
<xsl:include href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>



<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">


<div id="container">
  <div id="searchArea">
    <xsl:for-each select="//database">
    
    <xsl:variable name="id" select="metalib_id" />
    <xsl:variable name="username" select="//request/username" />
    <xsl:variable name="return" select="//request/return" />
    
    <h2><xsl:value-of select="title_display" />: Save to personal collection</h2>
    
    <form method="GET" action="{$base_url}">
      <input type="hidden" name="base" value="collections"/>
      <input type="hidden" name="action" value="save_choose_subheading"/>
      <input type="hidden" name="id" value="{$id}" />
      <input type="hidden" name="username" value="{$username}" />
      <input type="hidden" name="return" value="{$return}" />
    
      <p>
        Use one of your existing collections: 
        <select name="subject">
          <!-- if no existing ones, use our default name -->
          <xsl:if test="count(/*/userCategories/category) = 0">
            <option value="NEW">My Collection</option>
          </xsl:if>
          <xsl:for-each select="/*/userCategories/category">
            <option value="{normalized}"><xsl:value-of select="name"/></option>
          </xsl:for-each>
        </select>
      </p>
      <p>
        Or create new one: <input type="text" name="new_subject_name"></input>
      </p>
      
  
      <p>
        <input type="submit" name="cancel" value="cancel"/>
        <xsl:text> </xsl:text>      
        <input type="submit" name="save" value="save"/>
      </p>
    </form>
    </xsl:for-each>
  </div>
</div>
</xsl:template>
</xsl:stylesheet>
