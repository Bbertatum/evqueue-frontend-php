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

require_once __DIR__ . '/includes/inc/logger.php';
require_once __DIR__ . '/includes/lib/XSLEngine.php';
require_once __DIR__ . '/includes/inc/evqueue.php';

if(count($QUEUEING) == 0)
{
	// Not yet configured
	header('Location: install.php');
	die();
}

if (isset($_GET['action']) && $_GET['action']=='logout')
{
		@session_start();
		$sessionName = session_name();
		$sessionCookie = session_get_cookie_params();
		session_destroy();
		setcookie($sessionName, false, $sessionCookie['lifetime'], $sessionCookie['path'], $sessionCookie['domain'], $sessionCookie['secure']);
		header('Location: auth.php');
		die();
}

// Redirect to index if already identified
if(isset($_SESSION['user_login']))
{
	header('Location: index.php');
	die();
}


$xsl = new XSLEngine();

// Try anonymous login
try{
	$cluster->Api('ping');

	$_SESSION['user_login'] = "anonymous";
	$_SESSION['user_pwd'] = "";
	$_SESSION['user_profile'] = "ADMIN";
	
	$node_names = $cluster->GetNodeNames();
	$_SESSION['nodes'] = $node_names;

  $query = parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);
  header('Location: index.php'.(empty($query)?'':'?'.$query));
  die();
}
catch(Exception $e){
  if($e->getCode() != evQueue::ERROR_AUTH_REQUIRED){
    $xsl->AddError($e->getMessage());
    $xsl->DisplayXHTML('xsl/auth.xsl');
    die();
	}
}


if (isset($_POST['login']) && isset($_POST['password'])) {

	$pwd = sha1($_POST['password'], true);
	$cluster->SetUserLoginPwd($_POST['login'], $pwd, true);

  try
  {
		$xsl->Api('ping');
	}
	catch(Exception $e)
	{
		$xsl->DisplayXHTML('xsl/auth.xsl');
		die();
	}

  try {
    $node_names = $cluster->GetNodeNames();
  }
  catch (Exception $e) {
    $xsl->DisplayXHTML('xsl/auth.xsl');
		die();
  }

	@session_start();
	$_SESSION = [];
	$_SESSION['user_login'] = $_POST['login'];
	$_SESSION['user_pwd'] = $pwd;
	$_SESSION['user_profile'] = $cluster->GetProfile();
	$_SESSION['nodes'] = $node_names;
	$_SESSION['git_enabled'] = $cluster->GetConfigurationEntry('git.repository')!=""?true:false;
	session_write_close();
  $query = parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);
  header('Location: index.php'.(empty($query)?'':'?'.$query));
	die();
}


$xsl->DisplayXHTML('xsl/auth.xsl');

?>
