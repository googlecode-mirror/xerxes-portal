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

	<xsl:variable name="return" 	select="request/return" />
	<xsl:variable name="local" 		select="request/local" />

	<xsl:variable name="username">
		<xsl:if test="not(contains(request/session/username,'local@')) and not(contains(request/session/username,'guest@'))">
			<xsl:value-of select="request/session/username" />
		</xsl:if>
	</xsl:variable>
	
		
	<div id="container">
		<div class="loginBox">
			<h2>Login</h2>
			<p class="loginNote">This resource is restricted.  
			Please login with the <strong>demo</strong> user account.</p>
			
			<xsl:choose>
				<xsl:when test="error = 'authentication'">
					<p class="error">Sorry, your username of password was incorrect.</p>
				</xsl:when>
				<xsl:when test="error = 'authorization'">
					<p class="error">Sorry, you are not allowed to use this feature.</p>				
				</xsl:when>
			</xsl:choose>
			
			
			<form name="login" method="post" action="./?base=authenticate">		
				<input name="action" type="hidden" value="login" />
				<input name="return" type="hidden" value="{$return}" />
				<input name="local" type="hidden" value="{$local}" />
				<input name="postback" type="hidden" value="true" />  
				<table border="0" cellspacing="0" cellpadding="8" summary="">
					<tr>
						<td><label for="username">username:</label></td>
						<td><input name="username" type="text" id="username" value="{$username}" /></td>
					</tr>
					<tr>
						<td><label for="password">password:</label></td>
						<td><input name="password" type="password" id="password" /></td>
					</tr>
					<tr>
						<td> </td>
						<td align="right"><input type="submit" name="Submit" value="Log In" /></td>
					</tr>
				</table>
			
			</form>
			
			<!--
			<form name="guest" method="post" action="./?base=authenticate">
				<input name="action" type="hidden" value="guest" />
				<input name="return" type="hidden" value="{$return}" />
				<input name="postback" type="hidden" value="true" />
				<h2>. . . or Gest Login</h2>
				<p class="loginNote">Allows you to access and search the Library's publically available databases.</p>
				<p><input type="submit" name="Submit2" value="Guest Login" /></p>
			
			</form>
			-->
			
		</div>
	</div>
	
</xsl:template>
</xsl:stylesheet>