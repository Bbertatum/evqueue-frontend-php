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


require_once 'conf/sites_base.php';
require_once 'inc/logger.php';
require_once 'lib/XSLEngine.php';
require_once 'inc/evqueue.php';


if(count($QUEUEING) == 0)
{
	// Not yet configured
	header('Location: install.php');
	die();
}

if (isset($_GET['action']))
	switch ($_GET['action']) {
		case 'logout':
			@session_start();
			$sessionName = session_name();
			$sessionCookie = session_get_cookie_params();
			session_destroy();
			setcookie($sessionName, false, $sessionCookie['lifetime'], $sessionCookie['path'], $sessionCookie['domain'], $sessionCookie['secure']);
			break;
	}


$xsl = new XSLEngine();
if (isset($_POST['login']) && isset($_POST['password'])) {

	$pwd = sha1($_POST['password'], true);
	$evqueue->SetUserLogin($_POST['login']);
	$evqueue->SetUserPwd($pwd);
	$nodes = [];
	
	try
	{
		$xml = $evqueue->Api('ping');
		foreach($QUEUEING as $scheme){
			$evqueue_node = new evQueue($scheme,$_POST['login'],$pwd);
			$evqueue_node->Api('ping');
			$node_name = $evqueue_node->GetParserRootAttributes()['NODE'];
			if(isset($nodes[$node_name]) || $node_name == '')
				throw new Exception('Node name can\'t be null and should be unique');
			$nodes[$node_name] = $scheme;
		}
		
	}
	catch(Exception $e)
	{
		if($e->getCode() != evQueue::ERROR_AUTH_FAILED){
			$xsl->AddFragment('<error>evqueue-ko</error>');
		}
		else{
			$xsl->AddFragment('<error>wrong-creds</error>');
		}
		$xsl->DisplayXHTML('xsl/auth.xsl');
		die();
	}
	

	@session_start();
	$_SESSION['user_login'] = $_POST['login'];
	$_SESSION['user_pwd'] = $pwd;
	$_SESSION['user_profile'] = $evqueue->GetProfile();
	$_SESSION['nodes'] = $nodes;
	session_write_close();
	header('Location: index.php');
	die();
}


$xsl->DisplayXHTML('xsl/auth.xsl');


?>