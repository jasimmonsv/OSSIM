<?php
/*****************************************************************************
*
*    License:
*
*   Copyright (c) 2003-2006 ossim.net
*   Copyright (c) 2007-2009 AlienVault
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
* Class and Function List:
* Function list:
* - Agentless()
* - get_ip()
* - get_hostname()
* - get_user()
* - get_password()
* - get_password_type()
* - get_descr()
* - add_monitoring_entry()
* - delete_monitoring_entry()
* - modify_monitoring_entry()
* - add_host_data()
* - modify_host_data()
* - delete_host_data()
* - get_list_m_entries()
* Classes list:
* - Agentless
*/

require_once 'classes/Host_os.inc';
require_once 'classes/Session.inc';
require_once 'classes/Util.inc';
require_once 'ossim_db.inc';

class Agentless {

	private $ip;
    private $hostname;
	private $user;
    private $pass;
	private $ppass;
	private $status;
    private $descr;
	
	function __construct($ip, $hostname, $user, $pass, $descr, $foundrows, $status=1, $ppass=null){
		$this->ip          = $ip;
		$this->hostname    = Util::htmlentities($hostname);
		$this->user        = $user;
		$this->pass        = $pass;
		$this->ppass       = $ppass;
		$this->status      = $status;
		$this->descr       = Util::htmlentities($descr);
		$this->foundrows   = $foundrows;
	}
	
	public function get_ip()        { return $this->ip; }
    public function get_hostname()  { return $this->hostname;}
	public function get_user()      { return $this->user; }
    public function get_pass()      { return $this->pass;}
	public function get_ppass()     { return $this->ppass; }
	public function get_status()    { return $this->status;}
    public function get_descr()     { return $this->descr;}
	public function get_foundrows() { return $this->foundrows;}
	

	public function get_list_pag ($conn, $extra = "")
	{
		mysql_escape_string($extra);
        $iffoundrows = preg_match("/LIMIT/", $extra) ? true : false;
        
		$select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *" : "*";
        $query = OssimQuery("SELECT $select FROM host_agentless $extra");
		
		$list = array();
        
		if (!$rs = & $conn->Execute($query))
		    print $conn->ErrorMsg();
        else
		{
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) 
					print $conn->ErrorMsg();
                else 
					$foundrows = $rf->fields["total"];
            }
            while (!$rs->EOF)
			{
                $list[] = new Agentless( $rs->fields["ip"],     $rs->fields["hostname"],  $rs->fields["user"], 
								    $rs->fields["pass"],  $rs->fields["descr"],  $foundrows, _("enabled"),  $rs->fields["ppass"]);
                $rs->MoveNext();
            }
        }
		
        return $list;
	}
	
	public function add_monitoring_entry($conn, $ip, $type, $frecuency, $state, $arguments)
	{
		$sql = "INSERT INTO host_agentless_entries (id, ip, type, frecuency, state, arguments) VALUES (?, ?, ?, ?, ?, ?)";
        $params = array(
            null,
			$ip,
            $type,
			$frecuency,
            $state,
            $arguments
        );
		
		if ($conn->Execute($sql, $params) === false)
		{
            //print 'Error Inserting Monitorig Host Entry: ' . $conn->ErrorMsg() . '<br/>';
            return false;
        } 
		else
		{
            $sql = "SELECT last_insert_id( ) AS id FROM host_agentless_entries";
			$params = array();
			if (!$rs = & $conn->Execute($sql, $params)) 
				return false;
			else
				return $rs->fields['id'];	
        }
	}
	
	public function delete_monitoring_entry($conn, $ip, $type, $id)
	{
		$sql = "DELETE FROM host_agentless_entries WHERE id=? AND ip=? AND type=?;";
        $params = array(
            $id,
			$ip,
			$type
        );
		
		if ($conn->Execute($sql, $params) === false)
		{
            //print 'Error Deleting Monitorig Host Entry: ' . $conn->ErrorMsg() . '<br/>';
            return false;
        } 
		else
			return true;
	}
	
	public function modify_monitoring_entry($conn, $type, $frecuency, $state, $arguments, $id)
	{
		$sql = "UPDATE FROM host_agentless_entries SET type=?, frecuency=?, state=?, arguments=? WHERE id=?;";
        $params = array(
            $type,
			$frecuency,
            $type,
			$state,
            $arguments,
            $id
        );
		
		if ($conn->Execute($sql, $params) === false)
		{
            //print 'Error Updating Monitorig Host Entry: ' . $conn->ErrorMsg() . '<br/>';
            return false;
        } 
		else
			return true;
		
	}
	
	public function add_host_data($conn, $ip, $hostname, $user, $pass, $ppass, $descr)
	{
		$sql = "INSERT INTO host_agentless (ip, hostname, user, pass, ppass, descr) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $ip,
            $hostname,
			$user,
			$pass,
            $ppass,
            $descr
        );
		
		if ($conn->Execute($sql, $params) === false)
		{
            //print 'Error Inserting Monitorig Host Data: ' . $conn->ErrorMsg() . '<br/>';
            return false;
        } 
		else
		    return $rs->fields['id'];	
    }
	
	public function modify_host_data ($conn, $ip, $hostname, $user, $pass, $ppass, $descr, $status=0)
	{
		$sql = "UPDATE FROM host_agentless SET hostname=?, user=?, pass=?, ppass=?, descr=? WHERE ip=?;";
        $params = array(
            $hostname,
			$user,
			$pass,
            $ppass,
            $descr,
			$ip
        );
		
		if ($conn->Execute($sql, $params) === false)
		{
            //print 'Error Updating Monitorig Host Data: ' . $conn->ErrorMsg() . '<br/>';
            return false;
        } 
		else
			return true;
	}
	
	public function get_list_m_entries ($conn, $extra = "")
	{
		mysql_escape_string($extra);
       
        $query = OssimQuery("SELECT * FROM host_agentless_entries $extra");
		
		$list = array();
        
		if (!$rs = & $conn->Execute($query))
		    return $conn->ErrorMsg();
        else
		{
            while (!$rs->EOF)
			{
                $list[] = array( 
					"id"        => $rs->fields["id"],   
					"ip"        => $rs->fields["ip"],   				
					"type"      => $rs->fields["type"],
					"frecuency" => $rs->fields["frecuency"],
					"state"     => $rs->fields["state"],
					"arguments" => $rs->fields["arguments"]							 
				);
                $rs->MoveNext();
            }
        }
		
        return $list;
	}
	
	public function delete_host_data($conn, $ip)
	{
		$sql = "DELETE FROM host_agentless_entries WHERE ip=?;";
        $params = array(
            $ip
		);
		
		if ($conn->Execute($sql, $params) === false)
		{
            //print 'Error Deleting Host: ' . $conn->ErrorMsg() . '<br/>';
            return false;
        } 
		else
		{
			$sql = "DELETE FROM host_agentless WHERE ip=?;";
			$params = array(
				$ip
			);
			
			if ($conn->Execute($sql, $params) === false)
				return false;
			else
				return true;
		}
			
	}

		
	
}
?>