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

require_once "conf/queueing.php";
require_once "lib/Logger.php";


class WorkflowInstance {
	private $evqueue_ip;
	private $evqueue_port;
	
	
	public function __construct($evqueue_ip=QUEUEING_HOST, $evqueue_port=QUEUEING_PORT) {
		$this->evqueue_ip = $evqueue_ip;
		$this->evqueue_port = $evqueue_port;
	}
	
	/**
	 * 
	 * @param type $workflow_name
	 * @param array $parameters (<string> => </string>)
	 * @param type $mode 'synchronous'/'asynchronous'
	 * @return boolean
	 */
	public function LaunchWorkflowInstance($workflow_name, $parameters = array(), $mode = 'asynchronous', $user_host = false) {
		$params_xml = '';
		foreach ($parameters as $param => $value) {
			$param = htmlspecialchars($param);
			$value = htmlspecialchars($value);
			$params_xml .= "<parameter name=\"$param\" value=\"$value\" />";
		}
		
		$workflow_name = htmlspecialchars($workflow_name);
		if ($user_host) {
			list($user,$host) = split('@', $user_host);
			$user = htmlspecialchars($user);
			$host = htmlspecialchars($host);
			$user_host = $user ? "user='$user' host='$host'" : "host='$host'";
		} else {
			$user_host = '';
		}
		
		$wf = "<workflow name='$workflow_name' action='info' mode='$mode' $user_host>$params_xml</workflow>\n";
		
		$written = null;
		for ($i=0; $i<10; $i++) {
			$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
			if ($s === false) {
				Logger::GetInstance()->Log(LOG_WARNING,'WorkflowInstance',"[FAIL-$i] $workflow_name fsockopen failed");
				sleep(2);
				continue;
			}
			
			$written = @fwrite($s,$wf);
			if ($written === strlen($wf))
				break;
			
			Logger::GetInstance()->Log(LOG_WARNING,'WorkflowInstance',"[FAIL-$i] $workflow_name fwrite failed");
			sleep(2);
		}
		
		if ($written !== strlen($wf))
			Logger::GetInstance()->log(LOG_ERR,'WorkflowInstance',"Could not write workflow to evqueue ($this->evqueue_ip:$this->evqueue_port): ".htmlspecialchars($wf));
		
		$xml = fread($s,4096);
		$xml = simplexml_load_string($xml);
		
		if ($xml === false) {
			Logger::GetInstance()->Log(LOG_WARNING,'WorkflowInstance',"[FAIL] $workflow_name fread failed (invalid XML), workflow was:".htmlspecialchars($wf));
			return false;
		}
		
		$workflow_instance_id = (int)$xml->attributes()->{'workflow-instance-id'};
		fclose($s);
		
		if (strlen($workflow_instance_id) > 0) {
			Logger::GetInstance()->Log(LOG_NOTICE,'WorkflowInstance',"[SUCCESS-$i] $workflow_name launched with ID $workflow_instance_id");
			return $workflow_instance_id;
		}
		
		Logger::GetInstance()->Log(LOG_WARNING,'WorkflowInstance',"[FAIL] $workflow_name fread failed (no workflow_id), workflow was:".htmlspecialchars($wf));
		return false;
	}
	
	
	/*
	 * Gives the status of a workflow instance determined by $workflow_instance_id.
	 * @return the status as a string, or false if the workflow instance was not
	 * found.
	 */
	public function GetWorkflowStatus( $workflow_instance_id ) {
		$output = $this->GetWorkflowOutput($workflow_instance_id);
		if(!$output)
			return false;
		
		$xml = simplexml_load_string($output);
		$workflow_instance_status = (string)$xml['status'];
		
		if (in_array($workflow_instance_status, array('EXECUTING','TERMINATED','ABORTED')))
			return $workflow_instance_status;
		
		return false;
	}
	
	
	public function StopWorkflow ($workflow_instance_id) {
		
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		fwrite($s,"<workflow id='$workflow_instance_id' action='cancel' />\n");
		$output = stream_get_contents($s);
		fclose($s);
		
		return true;
	}
	
	
	public function GetWorkflowOutput($workflow_instance_id)
	{
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		fwrite($s,"<workflow id='$workflow_instance_id'/>\n");
		$output = stream_get_contents($s);
		fclose($s);
		
		if(!$s)
			return false;
		return $output;
	}
	
	
	public function GetStatistics($type)
	{
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		fwrite($s,"<statistics type='$type' />\n");
		$output = stream_get_contents($s);
		fclose($s);
		
		if(!$s)
			return false;
		return $output;
	}
	
	public function GetConfiguration()
	{
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		fwrite($s,"<status type='configuration' />\n");
		$output = stream_get_contents($s);
		fclose($s);
		
		if(!$s)
			return false;
		return $output;
	}
	
	
	public function ResetStatistics()
	{
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		fwrite($s,"<statistics type='global' action='reset' />\n");
		$output = stream_get_contents($s);
		fclose($s);
		
		if(!$s)
			return false;
		return $output;
	}
	
	
	/*
	 * Reloads the lists of tasks, workflows and workflow schedules.
	 */
	public static function ReloadEvqueue () {
		system('killall -HUP evqueue');
	}
	
	/*
	 * Asks all tasks having a retry schedule, and that failed, to try again immediately.
	 */
	public static function RetryAll () {
		system('killall -USR1 evqueue');
	}
	
	
	/*
	 * Immediately stops the execution of a task.
	 */
	public function KillTask ($workflow_instance_id, $task_pid) {
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		@fwrite($s,"<workflow id='$workflow_instance_id' action='killtask' pid='$task_pid' />");
		$xml = stream_get_contents($s);
		fclose($s);
		
		return true;
	}
	
	
	public function GetNextExecutionTime() {
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		@fwrite($s,'<status type="scheduler" />');
		$xml = stream_get_contents($s);
		fclose($s);
		
		return $xml;  // for now we return the XML directly, since it will be used in the XSL only
	}
	
	
	public function GetRunningWorkflows() {
		$s = @fsockopen($this->evqueue_ip,$this->evqueue_port);
		if ($s === false)
			return false;
		
		@fwrite($s,'<status type="workflows" />');
		$xml = stream_get_contents($s);
		fclose($s);
		
		return $xml;  // for now we return the XML directly, since it will be used in the XSL only
	}
	
}

?>