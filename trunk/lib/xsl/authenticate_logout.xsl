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

<xsl:template match="/authenticate">
	<xsl:call-template name="surround" />
</xsl:template>

<xsl:template name="main">

	<xsl:variable name="return"		select="request/return" />
	
	<form name="form1" method="post" action="./?base=authenticate">
	<input name="action" type="hidden" value="logout" />
	<input name="return" type="hidden" value="{$return}" />
	<input name="postback" type="hidden" value="true" />
	
	<div id="container">
	  <div class="loginBox">
		  <h2>Log out </h2>
		  <p>Are you sure you want to end your session? </p>
		  <p>
		    <input type="submit" name="Submit" value="Log out" />
		  </p>
	    </div>
	</div>
	
	</form>
	
</xsl:template>
</xsl:stylesheet>