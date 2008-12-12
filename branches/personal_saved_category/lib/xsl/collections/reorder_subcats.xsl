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
  Rename
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="category_name"	select="//category/@name" />
	<xsl:variable name="request_uri"	select="//request/server/request_uri" />

	<form name="form1" method="get" action="{$base_url}/" onSubmit="return databaseLimit(this)">
	<input type="hidden" name="base" value="collections" />
	<input type="hidden" name="action" value="reorder_subcats" />
  <input type="hidden" name="subject" value="{//category/@normalized}" />
  <input type="hidden" name="username" value="{//category/@owned_by_user}" />
  <input type="hidden" name="return" value="{//request/return}" />
	
	<div id="container">
    <div class="folderReturn">
      <img alt="" src="http://testbox.mse.jhu.edu/xerxes/images/back.gif"/><span class="folderReturnText"><a href="./?base=collections&amp;action=edit_form&amp;subject={//category/@normalized}&amp;username={//category/@owned_by_user}">Return to edit page</a></span>
  </div>
  
		<div id="searchArea">
	
				<h1>Reorder
          <xsl:choose>
            <xsl:when test="string(//request/subcategory)">
              Databases
            </xsl:when>
            <xsl:otherwise>
              Sections
            </xsl:otherwise>
          </xsl:choose>
        </h1>

        <p>
          <table>
            <xsl:for-each select="//category/subcategory">
              <tr>
                <td><xsl:value-of select="@position"/></td>
                <td><input type="text" size="2" name="subcat_seq_{@id}" /></td>
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
