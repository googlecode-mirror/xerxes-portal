<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- 
	TEXT LABELS 
	These are global variables that provide the text for the system.  We'll be slowly
	replacing the text in the templates with these starting with version 1.3.
	
	Variable names should follow the pattern of: text_{location}_{unique-name}
	Keep them in alphabetical order!!
-->
	
	<xsl:variable name="text_ada_version">For best results, click this link for accessible version</xsl:variable>
	
	<xsl:variable name="text_breadcrumb_seperator"> / </xsl:variable>
	
	<xsl:variable name="text_databases_access_available">Only available to </xsl:variable>
	<xsl:variable name="text_databases_access_group_and">and</xsl:variable>
	<xsl:variable name="text_databases_access_users">users</xsl:variable>
	
	<xsl:variable name="text_databases_az_search">List databases matching: </xsl:variable>
	<xsl:variable name="text_databases_az_breadcrumb_all">All databases</xsl:variable>
	<xsl:variable name="text_databases_az_breadcrumb_matching">Databases matching</xsl:variable>
	
	<xsl:variable name="text_databases_category_quick_desc">
		Search <xsl:value-of select="count(//category[1]/subcategory[1]/database)"/> of our most popular databases
	</xsl:variable>
	<xsl:variable name="text_databases_category_subject">Search by Subject</xsl:variable>
	<xsl:variable name="text_databases_category_subject_desc">Search databases specific to your area of study.</xsl:variable>

	<xsl:variable name="text_folder_export_records_all">All of my saved records </xsl:variable>
	<xsl:variable name="text_folder_export_records_labeled">All of my saved records labeled </xsl:variable>
	<xsl:variable name="text_folder_export_records_selected">Only the records I have selected below.</xsl:variable>
	<xsl:variable name="text_folder_export_records_type">All of my saved records of the type </xsl:variable>

	<xsl:variable name="text_folder_header_my">My Saved Records</xsl:variable>
	<xsl:variable name="text_folder_header_temporary">Temporary Saved Records</xsl:variable>	
	<xsl:variable name="text_folder_login_beyond">to save records beyond this session</xsl:variable>
	<xsl:variable name="text_folder_login">Log-in</xsl:variable>
	<xsl:variable name="text_folder_options_tags">Labels</xsl:variable>
	<xsl:variable name="text_folder_return">Return to search results</xsl:variable>
	
	<xsl:variable name="text_header_login">Log-in</xsl:variable>
	<xsl:variable name="text_header_logout">
		<xsl:text>Log-out </xsl:text>
		<xsl:choose>
			<xsl:when test="//request/authorization_info/affiliated[@user_account = 'true']">
				<xsl:value-of select="//request/session/username" />
			</xsl:when>
			<xsl:when test="//session/role = 'guest'">
				<xsl:text>Guest</xsl:text>
			</xsl:when>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="text_header_savedrecords">My Saved Records</xsl:variable>
	<xsl:variable name="text_header_collections">My Saved Databases</xsl:variable>
	<xsl:variable name="text_link_resolver_available">Full text available</xsl:variable>
	<xsl:variable name="text_link_resolver_check">Check for availability</xsl:variable>
	<xsl:variable name="text_link_holdings">Availability</xsl:variable>
	<xsl:variable name="text_link_original_record">Original record</xsl:variable>
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
	<xsl:variable name="text_searchbox_search">Search</xsl:variable>
	<xsl:variable name="text_searchbox_spelling_error">Did you mean: </xsl:variable>	
	<xsl:variable name="text_searchbox_options_fewer">Fewer Options</xsl:variable>
	<xsl:variable name="text_searchbox_options_more">More Options</xsl:variable>
	
	<xsl:variable name="text_records_fulltext_pdf">Full-Text in PDF</xsl:variable>
	<xsl:variable name="text_records_fulltext_html">Full-Text in HTML</xsl:variable>
	<xsl:variable name="text_records_fulltext_available">Full-Text Available</xsl:variable>
	
	<xsl:variable name="text_records_tags">Labels: </xsl:variable>

	<xsl:variable name="text_record_citation_note">These citations are software generated and may contain errors. 
	To verify accuracy, check the appropriate style guide.</xsl:variable>
	
	<xsl:variable name="text_collection_default_new_name" select="//config/default_collection_name" />
	<xsl:variable name="text_collection_default_new_section_name" select="//config/default_collection_section_name" />


</xsl:stylesheet>