<?php
 /*
  * This file is part of evQueue
  * 
  * evQueue is free software: you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation, either version 3 of the License, or
  * (at your option) any later version.
  * 
  * evQueue is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with evQueue. If not, see <http://www.gnu.org/licenses/>.
  * 
  * Authors: Nicolas Jean, Christophe Marti 
  */

require_once 'inc/auth_check.php';
require_once 'inc/logger.php';
require_once 'lib/XSLEngine.php';
require_once 'lib/workflow_instance.php';
require_once 'lib/WebserviceWrapper.php';


$xsl = new XSLEngine();

require 'conf/queueing.php';
foreach ($QUEUEING as $node_name => $conf) {
	$wfi = new WorkflowInstance($node_name);
	
	if (isset($_GET['action']) && $_GET['action'] == 'reset') {
		$ws = new WebserviceWrapper('reset-stats', 'resetStats', ['node_name'=>$_GET['node_name']], true);
		$ws->FetchResult();
		header('Location: system_statistics.php');
		die();
	}
	
	$xsl->AddFragment('<global node_name="'.htmlspecialchars($node_name).'">'.$wfi->GetStatistics("global").'</global>');
}

$xsl->DisplayXHTML('xsl/system_statistics.xsl');

?>