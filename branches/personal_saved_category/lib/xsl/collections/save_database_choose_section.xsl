<?xml version="1.0" encoding="UTF-8"?>



<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:import href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>



<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">Save to personal collection</xsl:template>


<xsl:template name="main">
<xsl:variable name="id" select="/*/databases/database/metalib_id" />
<xsl:variable name="username" select="//request/username" />
<xsl:variable name="return" select="//request/return" />


<div id="container">
  <div id="searchArea">
    <xsl:for-each select="//category">
    <h2><xsl:value-of select="/*/databases/database/title_display" />: Save to personal collection: <xsl:value-of select="@name" /></h2>
    <h3>Choose a section</h3>
    <form method="GET" action="{$base_url}">
      <input type="hidden" name="base" value="collections"/>
      <input type="hidden" name="action" value="save_complete"/>
      <input type="hidden" name="id" value="{$id}" />
      <input type="hidden" name="username" value="{$username}" />
      <input type="hidden" name="subject" value="{@normalized}" />
      <input type="hidden" name="return" value="{@return}" />
    
      <p>
        Use an existing section: 
        <select name="subcategory">
          <!-- if no existing ones, use our default name -->
          <xsl:if test="count(/*/category/subcategory) = 0">
            <option value="NEW">Databases</option>
          </xsl:if>
          <xsl:for-each select="/*/category/subcategory">
            <option value="{@id}"><xsl:value-of select="@name"/></option>
          </xsl:for-each>
        </select>
      </p>
      <p>
        Or create new one: <input type="text" name="new_subcategory_name"></input>
      </p>
      
  
      <p>
        <input type="submit" value="cancel"/>
        <xsl:text> </xsl:text>      
        <input type="submit" value="save"/>
      </p>
    </form>
    </xsl:for-each>
  </div>
</div>
</xsl:template>
</xsl:stylesheet>
