<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:import href="../templates/workflow.xsl" />


	<xsl:template match="/">
		<div>
			<div id="formContainer"></div>
			<div id="jobInfos"></div>
			<xsl:apply-templates select="/page/workflow" mode="edit-tree" />
		</div>
    </xsl:template>

</xsl:stylesheet>
