<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- 
	TEXT LABELS 
	These are global variables that provide the text for the system.
	
	Variable names should follow the pattern of: text_{location}_{unique-name}
	Keep them in alphabetical order!!
-->
	
	<xsl:variable name="text_ada_version">For best results, click this link for accessible version</xsl:variable>
	
	<xsl:variable name="text_authentication_login_explain"></xsl:variable>
	<xsl:variable name="text_authentication_login_failed"><span style="background-color: yellow">Sorry, your username or password was incorrect.</span></xsl:variable>
	<xsl:variable name="text_authentication_login_pagename">Login</xsl:variable>
	<xsl:variable name="text_authentication_login_password"><span style="background-color: yellow">password:</span></xsl:variable>
	<xsl:variable name="text_authentication_login_username"><span style="background-color: yellow">username:</span></xsl:variable>
		
	<xsl:variable name="text_authentication_logout_confirm"><span style="background-color: yellow">Are you sure you want to end your session?</span></xsl:variable>
	<xsl:variable name="text_authentication_logout_pagename">Logout</xsl:variable>
	
	<xsl:variable name="text_breadcrumb_seperator"> &gt; </xsl:variable>
	
	<xsl:variable name="text_databases_access_available"><span style="background-color: yellow">Only available to </span></xsl:variable>
	<xsl:variable name="text_databases_access_group_and"><span style="background-color: yellow">and</span></xsl:variable>
	<xsl:variable name="text_databases_access_users"><span style="background-color: yellow">users</span></xsl:variable>
	
	<xsl:variable name="text_databases_az_backtop"><span style="background-color: yellow">Back to top</span></xsl:variable>
	<xsl:variable name="text_databases_az_breadcrumb_all"><span style="background-color: yellow">All databases</span></xsl:variable>
	<xsl:variable name="text_databases_az_breadcrumb_matching"><span style="background-color: yellow">Databases matching</span></xsl:variable>
	<xsl:variable name="text_databases_az_databases"><span style="background-color: yellow">databases</span></xsl:variable>
	<xsl:variable name="text_databases_az_hint_info">more information</xsl:variable>
	<xsl:variable name="text_databases_az_hint_searchable">searchable by <xsl:value-of select="$app_name" /></xsl:variable>
	<xsl:variable name="text_databases_az_pagename">Databases A-Z</xsl:variable>
	<xsl:variable name="text_databases_az_search"><span style="background-color: yellow">List databases matching: </span></xsl:variable>
		
	<xsl:variable name="text_databases_category_quick_desc"><span style="background-color: yellow">
		<xsl:text>Search </xsl:text>
		<xsl:call-template name="text_number_to_words">
			<xsl:with-param name="number" select="count(//category[1]/subcategory[1]/database[searchable = 1])" /> 
		</xsl:call-template>
		<xsl:text> of our most popular databases</xsl:text>
	</span></xsl:variable>
	<xsl:variable name="text_databases_category_subject"><span style="background-color: yellow">Search by Subject</span></xsl:variable>
	<xsl:variable name="text_databases_category_subject_desc"><span style="background-color: yellow">Search databases specific to your area of study.</span></xsl:variable>

	<xsl:variable name="text_error_databases_permission"><span style="background-color: yellow">You do not have access to search these databases</span></xsl:variable>
	<xsl:variable name="text_error_databases_registered"><span style="background-color: yellow">Only available to registered users.</span></xsl:variable>
	<xsl:variable name="text_error_pdo_exception"><span style="background-color: yellow">There was a problem with the database.</span></xsl:variable>

	<xsl:variable name="text_folder_export_records_all"><span style="background-color: yellow">All of my saved records </span></xsl:variable>
	<xsl:variable name="text_folder_export_records_labeled"><span style="background-color: yellow">All of my saved records labeled </span></xsl:variable>
	<xsl:variable name="text_folder_export_records_selected"><span style="background-color: yellow">Only the records I have selected below.</span></xsl:variable>
	<xsl:variable name="text_folder_export_records_type"><span style="background-color: yellow">All of my saved records of the type </span></xsl:variable>

	<xsl:variable name="text_folder_header_my"><span style="background-color: yellow">My Saved Records</span></xsl:variable>
	<xsl:variable name="text_folder_header_temporary"><span style="background-color: yellow">Temporary Saved Records</span></xsl:variable>	
	<xsl:variable name="text_folder_login_beyond"><span style="background-color: yellow">to save records beyond this session</span></xsl:variable>
	<xsl:variable name="text_folder_login"><span style="background-color: yellow">Log-in</span></xsl:variable>
	<xsl:variable name="text_folder_options_tags"><span style="background-color: yellow">Labels</span></xsl:variable>
	<xsl:variable name="text_folder_return"><span style="background-color: yellow">Return to search results</span></xsl:variable>
	
	<xsl:variable name="text_header_login"><span style="background-color: yellow">Log-in</span></xsl:variable>
	<xsl:variable name="text_header_logout"><span style="background-color: yellow">
		<xsl:text>Log-out </xsl:text>
		<xsl:choose>
			<xsl:when test="//request/authorization_info/affiliated[@user_account = 'true']">
				<xsl:value-of select="//request/session/username" />
			</xsl:when>
			<xsl:when test="//session/role = 'guest'">
				<xsl:text>Guest</xsl:text>
			</xsl:when>
		</xsl:choose>
	</span></xsl:variable>
	<xsl:variable name="text_header_savedrecords"><span style="background-color: yellow">My Saved Records</span></xsl:variable>
	<xsl:variable name="text_header_collections"><span style="background-color: yellow">My Saved Databases</span></xsl:variable>
	<xsl:variable name="text_link_resolver_available"><span style="background-color: yellow">Full text available</span></xsl:variable>
	<xsl:variable name="text_link_resolver_check"><span style="background-color: yellow">Check for availability</span></xsl:variable>
	<xsl:variable name="text_link_holdings"><span style="background-color: yellow">Availability</span></xsl:variable>
	<xsl:variable name="text_link_original_record"><span style="background-color: yellow">Original record</span></xsl:variable>
	<xsl:variable name="text_searchbox_ada_boolean">Boolean operator: </xsl:variable>
	<xsl:variable name="text_searchbox_boolean_and">And</xsl:variable>
	<xsl:variable name="text_searchbox_boolean_or">Or</xsl:variable>
	<xsl:variable name="text_searchbox_boolean_without">Without</xsl:variable>
	<xsl:variable name="text_searchbox_field_keyword">all fields</xsl:variable>
	<xsl:variable name="text_searchbox_field_title">title</xsl:variable>
	<xsl:variable name="text_searchbox_field_author">author</xsl:variable>
	<xsl:variable name="text_searchbox_field_subject">subject</xsl:variable>
	<xsl:variable name="text_searchbox_field_year">year</xsl:variable>
	<xsl:variable name="text_searchbox_field_issn">ISSN</xsl:variable>
	<xsl:variable name="text_searchbox_field_isbn">ISBN</xsl:variable>
	<xsl:variable name="text_searchbox_go">GO</xsl:variable>
	<xsl:variable name="text_searchbox_search"><span style="background-color: yellow">Search</span></xsl:variable>
	<xsl:variable name="text_searchbox_spelling_error"><span style="background-color: yellow">Did you mean: </span></xsl:variable>	
	<xsl:variable name="text_searchbox_options_fewer"><span style="background-color: yellow">Fewer Options</span></xsl:variable>
	<xsl:variable name="text_searchbox_options_more"><span style="background-color: yellow">More Options</span></xsl:variable>
	
	<xsl:variable name="text_records_fulltext_pdf"><span style="background-color: yellow">Full-Text in PDF</span></xsl:variable>
	<xsl:variable name="text_records_fulltext_html"><span style="background-color: yellow">Full-Text in HTML</span></xsl:variable>
	<xsl:variable name="text_records_fulltext_available"><span style="background-color: yellow">Full-Text Available</span></xsl:variable>
	
	<xsl:variable name="text_records_tags"><span style="background-color: yellow">Labels: </span></xsl:variable>

	<xsl:variable name="text_record_citation_note"><span style="background-color: yellow">These citations are software generated and may contain errors. 
	To verify accuracy, check the appropriate style guide.</span></xsl:variable>

	<xsl:template name="text_number_to_words">
		<xsl:param name="number" />
		<xsl:choose>
			<xsl:when test="$number = 1">one</xsl:when>
			<xsl:when test="$number = 2">two</xsl:when>
			<xsl:when test="$number = 3">three</xsl:when>
			<xsl:when test="$number = 4">four</xsl:when>
			<xsl:when test="$number = 5">five</xsl:when>
			<xsl:when test="$number = 6">six</xsl:when>
			<xsl:when test="$number = 7">seven</xsl:when>
			<xsl:when test="$number = 8">eight</xsl:when>
			<xsl:when test="$number = 9">nine</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$number" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
	
	
	
	
	
	
	
</xsl:stylesheet>