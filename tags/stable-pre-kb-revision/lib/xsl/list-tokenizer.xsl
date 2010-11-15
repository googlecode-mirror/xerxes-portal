<?xml version="1.0" encoding="UTF-8"?>

<!--

 author: Ivan Masar
 copyright: 2010 Ivan Masar
 version: $Id$
 package: Xerxes
 link: http://xerxes.calstate.edu
 license: http://www.gnu.org/licenses/
 
 -->


<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">
<xsl:output indent="yes" method="xml"/>

<!--
Purpose: From a list of tokens ($list) separated by delimiter ($delimiter), print the index of requested item ($find-item)
Params:
 * $list (string):	a string of items delimited by $delimiter
 * $delimiter (string):	delimiter of items in $list
 * $find-item (int):	which item to find in $list
Return value: (int) position of requested item in list, indexed from 1; empty if not found
-->
<xsl:template name="find-item-in-list">
	<xsl:param name="list" />
	<xsl:param name="delimiter" />
	<xsl:param name="find-item">1</xsl:param>
	<xsl:param name="recursion-depth">1</xsl:param>
	
	<xsl:variable name="newlist">
		<xsl:choose>
			<xsl:when test="contains($list, $delimiter)"><xsl:value-of select="normalize-space($list)" /></xsl:when>
			<xsl:otherwise><xsl:value-of select="concat(normalize-space($list), $delimiter)"/></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="first" select="substring-before($newlist, $delimiter)" />
	<xsl:variable name="remaining" select="substring-after($newlist, $delimiter)" />
	
	<!-- DEBUG: (recursion-depth:<xsl:value-of select="$recursion-depth" />,find-item:<xsl:value-of select="$find-item" />,first:<xsl:value-of select="$first" />)<br/> -->
	<xsl:if test="$find-item = $first">
		<xsl:value-of select="$recursion-depth" />
	</xsl:if>
	
	<xsl:if test="$remaining">
		<xsl:call-template name="find-item-in-list">
			<xsl:with-param name="list" select="$remaining" />
			<xsl:with-param name="delimiter"><xsl:value-of select="$delimiter" /></xsl:with-param>
			<xsl:with-param name="find-item"><xsl:value-of select="$find-item" /></xsl:with-param>
			<xsl:with-param name="recursion-depth"><xsl:value-of select="$recursion-depth + 1" /></xsl:with-param>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

<!--
Purpose: From a list of tokens ($list) separated by delimiter ($delimiter), print the n-th in order ($index)
Params:
 * $list (string):	a string of items delimited by $delimiter
 * $delimiter (string):	delimiter of items in $list
 * $index (int):	which item in order to return from $list, indexed from 1
Return value: value of requested item; empty if index not found
-->
<xsl:template name="n-th-item-in-list">
	<xsl:param name="list" />
	<xsl:param name="delimiter" />
	<xsl:param name="index">1</xsl:param>
	<xsl:param name="recursion-depth">1</xsl:param>
	
	<xsl:variable name="newlist">
		<xsl:choose>
			<xsl:when test="contains($list, $delimiter)"><xsl:value-of select="normalize-space($list)" /></xsl:when>
			<xsl:otherwise><xsl:value-of select="concat(normalize-space($list), $delimiter)" /></xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	
	<xsl:variable name="first" select="substring-before($newlist, $delimiter)" />
	<xsl:variable name="remaining" select="substring-after($newlist, $delimiter)" />
	
	<!-- DEBUG: (recursion-depth:<xsl:value-of select="$recursion-depth" />,index:<xsl:value-of select="$index" />)<br/> -->
	<xsl:if test="$index = $recursion-depth">
		<xsl:value-of select="$first" />
	</xsl:if>
	
	<xsl:if test="$remaining">
		<xsl:call-template name="n-th-item-in-list">
			<xsl:with-param name="list" select="$remaining" />
			<xsl:with-param name="delimiter"><xsl:value-of select="$delimiter" /></xsl:with-param>
			<xsl:with-param name="index"><xsl:value-of select="$index" /></xsl:with-param>
			<xsl:with-param name="recursion-depth"><xsl:value-of select="$recursion-depth + 1" /></xsl:with-param>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>

