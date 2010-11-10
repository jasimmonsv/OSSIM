<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-20010 AlienVault
*   All rights reserved.
*
*   This package is free software; you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation; version 2 dated June, 1991.
*   You may not use, modify or distribute this program under any other version
*   of the GNU General Public License.
*
*   This package is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details.
*
*   You should have received a copy of the GNU General Public License
*   along with this package; if not, write to the Free Software
*   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
*   MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
****************************************************************************/
/**
* Class: OMP
*/
class OMP{
    private $binary_location;
    private $ip;
    private $port;
    private $user;
    private $password;
    private $tmpfile;
    private $db;
    private $conn;
    
    public function __construct($path="", $host="", $port="", $user="", $pass="", $tmpfile="") {

        require_once "ossim_db.inc";
        $this->db = new ossim_db();
        $this->conn = $this->db->connect();
   
        if ($path=="" || $host=="" || $port=="" || $user=="" || $pass=="") {
            require_once("classes/Session.inc");
            $conf = $GLOBALS["CONF"];
        }
        
        if($path=="") { $this->binary_location = $conf->get_conf("nessus_path", FALSE); }
            else { $this->binary_location = $path; }
        
        if($host=="") { $this->ip = $conf->get_conf("nessus_host", FALSE); }
            else { $this->ip = $host; }
        
        if($port=="") { $this->port = $conf->get_conf("nessus_port", FALSE); }
        else { $this->port = $port; }
        
        if($user=="") { $this->user = $conf->get_conf("nessus_user", FALSE); }
        else { $this->user = $user; }
        
        if($pass=="") { $this->password = $conf->get_conf("nessus_pass", FALSE); }
        else { $this->password = $pass; }

        if($tmpfile=="") { $this->tmpfile = "/usr/share/ossim/www/tmp/omp.xml"; }
        else { $this->tmpfile = $tmpfile; }
    }
    
    function __destruct(){
        $this->db->close($this->conn);
    }
    
    // ------  private functions ------
    private function execute_command ($command) {
        
        $binary_location = $this->binary_location;
        $ip = $this->ip;
        $port = $this->port;
        $user = $this->user;
        $password = $this->password;
        $tmpfile = $this->tmpfile;
        
        $ecommand = "$binary_location -h $ip -p $port -u $user -w $password -iX \"$command\" > $tmpfile 2>&1";
        
        system($ecommand);

        $data = file_get_contents($tmpfile);
        $xml = @simplexml_load_string($data);

        if (!is_object($xml)) {
            if (preg_match("/^Failed/i", $data)) {
                echo "OMP: ".$data."<br>";
            }
            else {
                echo _("Cant' read XML:")." ".$tmpfile."<br>";
            }
            return FALSE;
        }
        if (!preg_match("/^20.*/",$xml->attributes()->status)) {
            if (!preg_match("/^Failed to find task.*/",$xml->attributes()->status_text)) {
                echo _("Error: status = ".$xml->attributes()->status.", status_text = ".$xml->attributes()->status_text." ($tmpfile)")."<br>";
            }
            return FALSE;
        }
        
        return $xml;
    }
    
    private function get_config_id_by_sid($sid) {
 
        $config_id = "";
        
        $result = $this->conn->Execute("SELECT name, owner FROM vuln_nessus_settings WHERE id=$sid");
        $cname = $result->fields["name"];
        $cuser = $result->fields["owner"];
        
        $xml = $this->execute_command ("<get_configs />");
        
        if ($xml==FALSE) { return $config_id; }

        foreach ($xml->config as $config) {
            if( ($cname ==(string)$config->name) && ($cuser==(string)$config->comment) )
            $config_id = (string)$config->attributes()->id;
        }
        return $config_id;
    }
    
    public function get_config_id_by_name_comment($name, $comment="") {

        $config_id = "";
    
        $xml = $this->execute_command ("<get_configs />");
        
        if ($xml==FALSE) { return $config_id; }

        foreach ($xml->config as $config) {
            if($comment!="") {
                if( ($name ==(string)$config->name) && ($comment==(string)$config->comment) ) {    $config_id = (string)$config->attributes()->id;    }
            }
            else {
                if( ($name ==(string)$config->name) ) {    $config_id = (string)$config->attributes()->id;    }
            }
        }
        return $config_id;
    }
    
    private function delete_tasks_by_config_id($config) {
        
        $delete_tasks = array();
        
        $xml = $this->execute_command("<get_tasks />");
        
        if ($xml!=FALSE) {
            foreach ($xml->task as $task) { // save tasks with config_id = $config
                if($config == (string)$task->config->attributes()->id) {    $delete_tasks[]= (string)$task->attributes()->id;    }
            }
            foreach ($delete_tasks as $dtask) {
                $this->execute_command("<delete_task task_id='$dtask' />");
            }
        }
    }
    
    // ------  public functions ------
    
    public function set_plugins_by_family ($sid) {
    
        $familyHash = array();
        $command = "";
        
        $id_config = $this->get_config_id_by_sid($sid);
        
        if ($id_config =="") { return; }
        
        $this->delete_tasks_by_config_id($id_config);
        
        
        // Disable all families
        
        $result=$this->conn->Execute("SELECT name FROM vuln_nessus_family");
        $command = "<commands>";
        while (!$result->EOF) {
            $fname = $result->fields['name'];
            $command .= "<modify_config config_id='$id_config'><nvt_selection><family>$fname</family></nvt_selection></modify_config>";
            $result->MoveNext();
        }
        $command .= "</commands>";
        
        $this->execute_command($command);
        
        // Select enabled plugins
        
        $result=$this->conn->Execute("SELECT f.name as fname, p.oid as plugin_id FROM vuln_nessus_settings_plugins AS sp
                                            LEFT JOIN vuln_nessus_plugins AS p ON sp.id = p.id
                                            LEFT JOIN vuln_nessus_family AS f ON sp.family = f.id
                                            WHERE sp.enabled =  'Y'
                                            AND sp.sid = '$sid'");
        while (!$result->EOF) {
            $fname = $result->fields['fname'];
            $plugin_id = $result->fields['plugin_id'];
            $familyHash["$fname"]["$plugin_id"]++;
            $result->MoveNext();
        }
        
        // Update openvas-manager plugins
        
        foreach ($familyHash as $family => $v2) {
            $command = "<modify_config config_id='$id_config'><nvt_selection><family>$family</family>";
            foreach ($v2 as $oid => $v3) {
                $command .= "<nvt oid='$oid'/>";
            }
            $command .= "</nvt_selection></modify_config>";
            $this->execute_command($command);
        }
    }
    
    public function set_preferences ($sid) {
        
        require_once("classes/Util.inc");
        
        $id_config = $this->get_config_id_by_sid($sid);
        
        if ($id_config =="") { return; }
        
        $this->delete_tasks_by_config_id($id_config);
        
        $command = "";
        
        // Select preferences
        
        $result=$this->conn->Execute("SELECT sp.nessus_id as nessus_id, sp.value as value, p.oid as oid FROM vuln_nessus_settings_preferences AS sp
                                        LEFT JOIN vuln_nessus_plugins AS p ON p.id = sp.id
                                        WHERE sp.sid=$sid AND sp.value!=''");

        
        while (!$result->EOF) {
            if(!Util::is_utf8($result->fields['nessus_id'])) {
                $command .= "<modify_config config_id='$id_config'>";
                $command .= "<preference>";
                if($result->fields['oid']!="") {
                    $command .= "<nvt oid='".$result->fields['oid']."'/>";
                }
                $command .= "<name>".$result->fields['nessus_id']."</name>";
                //$command .= "<name>".Util::utf8_encode2($result->fields['nessus_id'])."</name>";
                //$command .= "<name><![CDATA[".$result->fields['nessus_id']."]]></name>";
                $command .= "<value>".base64_encode($result->fields['value'])."</value>";
                $command .= "</preference>";
                $command .= "</modify_config>\n";
            }
            $result->MoveNext();
        }
        if ($command!="") {
            $command = "<commands>".$command."</commands>";
            $this->execute_command($command);
        }
    }
    public function get_tasks_using_config($sid) {
        $running_tasks = array();
        
        $id_config = $this->get_config_id_by_sid($sid);
        
        if ($id_config =="") { return; }
        
        $xml = $this->execute_command("<get_tasks />");
        
        foreach ($xml->task as $task) { 
            if((string)$task->status != "Done" && $id_config == (string)$task->config->attributes()->id) {    $running_tasks[]= (string)$task->attributes()->id;    }
        }
        
        return $running_tasks;
    }
    public function get_used_sids() {
        $used_configs = array();
        $pname = "";
        $puser = "";
        $sids = array();
        
        $xml = $this->execute_command("<get_tasks />");
        
        if ($xml == FALSE) { return $sids; }
        
        foreach ($xml->task as $task) { 
            if((string)$task->status != "Done") {    $used_configs[]= (string)$task->config->attributes()->id;   }
        }
        
        // get profile sid
        foreach($used_configs as $config) {
            $xml = $this->execute_command("<get_configs config_id='$config'/>");
            foreach($xml->config as $config) {
                $pname = (string)$config->name;
                $puser = (string)$config->comment;
                $result=$this->conn->Execute("SELECT id FROM vuln_nessus_settings WHERE name='$pname' AND owner='$puser' LIMIT 1");
                $sids[] = $result->fields['id'];
            }
        }
        return $sids;
    }
    public function create_new_config($sid) {
        
        $pname = "";
        $puser = "";
        
        $id_config = $this->get_config_id_by_sid($sid);
        
        if($id_config!="") {
            $this->set_plugins_by_family($sid);
            $this->set_preferences($sid);
        }
        else {
            $result=$this->conn->Execute("SELECT name, owner FROM vuln_nessus_settings WHERE id='$sid' LIMIT 1");
            
            $pname = $result->fields['name'];
            $puser = $result->fields['owner'];

            $id_ff = $this->get_config_id_by_name_comment("Full and fast");
            $this->execute_command("<create_config><copy>".$id_ff."</copy><name>$pname</name><comment>$puser</comment></create_config>");
            $this->set_plugins_by_family($sid);
            $this->set_preferences($sid);
        }
    }
    public function play_task($job_id){
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $this->execute_command ("<start_task task_id='$config_id' />");

    }
    public function pause_task($job_id){
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];

        $this->execute_command ("<pause_task task_id='$config_id' />");

    }
    
    public function stop_task($job_id){
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $this->execute_command ("<stop_task task_id='$config_id' />");

    }
    
    public function delete_task($job_id){
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $tstatus = $this->get_task_status($job_id);

        if($tstatus["status"]!="") {
            if($tstatus["status"]!="Stopped") {
                $this->stop_task($job_id);
            }
            $this->execute_command ("<delete_task task_id='$config_id' />");
        }
    }
    
    public function resume_task($job_id) {
                
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $task_status = $this->get_task_status($job_id);
        
        if( preg_match("/^stopped.*/i", $task_status["status"]) ) {
            $this->execute_command ("<resume_stopped_task task_id='$config_id' />");
        }
        else {
            $this->execute_command ("<resume_paused_task task_id='$config_id' />");
        }
    }
    public function get_task_status($job_id){
        
        $status = array("status"=>"", "progress"=>"");
        
        $result = $this->conn->Execute("SELECT meth_CPLUGINS FROM vuln_jobs WHERE id=$job_id");
        $config_id = $result->fields["meth_CPLUGINS"];
        
        $xml = $this->execute_command("<get_tasks task_id='$config_id'/>");
        
        if ($xml==FALSE) { return ($status); }
        
        $status["status"] = trim((string) $xml->task[0]->status);
        $status["progress"] = trim((string) $xml->task[0]->progress);
        
        return ($status);

    }
}