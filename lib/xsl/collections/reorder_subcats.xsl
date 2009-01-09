<?xml version="1.0" encoding="UTF-8"?>

<!--

Edit subject page for user-created subjects. Only used for non-AJAX version.
 
 -->
 
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl">
<xsl:import href="../includes.xsl" />
<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>

<xsl:template match="/*">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="page_name">
  Reorder
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<form name="form1" method="get" action="{$base_url}/">
	<input type="hidden" name="base" value="collections" />
	<input type="hidden" name="action" value="reorder_subcats" />
  <input type="hidden" name="subject" value="{//category/@normalized}" />
  <input type="hidden" name="username" value="{//category/@owned_by_user}" />
  <input type="hidden" name="return" value="{//request/return}" />
	
	<div id="container">
    <div id="sidebar_float" class="sidebar_float">
      <xsl:call-template name="account_sidebar"/>
    </div>
		<div id="searchArea">
	
				<h1>Reorder Sections</h1>         

        <p>
          <table>
            <xsl:for-each select="//category/subcategory">
              <tr>
                <td><xsl:value-of select="@position"/></td>
                <td><input class="reorder" type="text" size="2" name="subcat_seq_{@id}" /></td>
                <td><xsl:value-of select="@name" /></td>
              </tr>              
            </xsl:for-each>
          </table>
        </p>
          
        <p><input type="submit" name="save" value="save"/></p>
      </div>
    </div>
	</form>
	
</xsl:template>

</xsl:stylesheet>
