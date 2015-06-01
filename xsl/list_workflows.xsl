<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:import href="templates/main-template.xsl" />
	<xsl:import href="templates/list_workflows.xsl" />
	
	<xsl:variable name="topmenu" select="'settings'" />
	
	<xsl:variable name="javascript">
		<src>js/list-workflows.js</src>
	</xsl:variable>
	
	<xsl:template name="content">
		<div id="workflow" class="contentList">
			<xsl:call-template name="list_workflows"/>
		</div>
	</xsl:template>
	
</xsl:stylesheet>
