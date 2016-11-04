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


$xsl = new XSLEngine();

if(isset($_POST['commit-name'])){
	$xsl->Api('git','save_task',['name' => $_POST['commit-name'],'commit_log' => $_POST['commit-log'],'force' => $_POST['commit-force']]);
}

$xsl->AddFragment(["tasks" => $xsl->Api("tasks", "list")]);

if($_SESSION['git_enabled'])
{
	$xsl->Api("git", "pull");
	$xsl->AddFragment(["git-tasks" => $xsl->Api("git", "list_tasks")]);
}

$xsl->DisplayXHTML('xsl/list_tasks.xsl');

?>
