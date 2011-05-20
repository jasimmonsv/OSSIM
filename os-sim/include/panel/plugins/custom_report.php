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
* - getCategoryName()
* - showSubCategoryHTML()
* - showSettingsHTML()
* - showWindowContents()
* Classes list:
* - Plugin_Custom_HTML extends Panel
*/
require_once 'ossim_db.inc';
class Plugin_Custom_Report extends Panel {
    var $defaults = array(
        'width' => '600',
		'height' => '500',
		'refresh' => false,
		'secondRefresh' => '300',
		'reportName' => '',
		'run' => ''
    );
    function getCategoryName() {
        return _("Custom Report contents");
    }
    function showSubCategoryHTML() {
		// get list reports
		$db = new ossim_db();
		$dbconn = $db->connect();

		$creports = array();
		$subreports_ac = array();
			
		$sql_search = "";
			if( $search!="" )     $sql_search = "AND name like '%$search%'";
		
		$result = $dbconn->Execute("SELECT login, name, value FROM user_config where category='custom_report' $sql_search ORDER BY name ASC");
		
		$hi=0;
		
		while ( !$result->EOF ) {
			$available = false;
				
			$unserializedata      = unserialize($result->fields["value"]);
			$available_for_user   = $unserializedata["user"];
			$available_for_entity = $unserializedata["entity"];
			
			// check if this report is available for session user
			if (Session::am_i_admin()) {
				$available = true;
			}
			else if($available_for_user=="0") {
				$available = true;
			}
			else if(($available_for_user!="" && $available_for_user==$session_user) || ($result->fields["login"]==$session_user)) {
				$available = true;
			}
			else if(preg_match("/pro|demo/i",$version)){
				if(Acl::am_i_proadmin())
				{
					$entities_list = Acl::get_entities_admin($dbconn,Session::get_session_user());
					$entities = array_keys($entities_list[0]);
					$users = Acl::get_my_users($dbconn,Session::get_session_user());
					
					$users_login = array();
					foreach ($users as $user){ $users_login[] = $user["login"]; }
					if(in_array($available_for_entity, $entities) || in_array($available_for_user, $users_login) || in_array($result->fields["login"], $users_login)){
						$available = true;
					}
				}
				else 
				{
					$entities = Acl::get_user_entities(Session::get_session_user());
					if(in_array($available_for_entity, $entities)){
						$available = true;
					}
				}
			}
			
			// save report if is available
			$maxpag = 20;
			$to = $pag*$maxpag;
			$from = $to - $maxpag;

			if($available) {
				if ($from <= $hi && $hi < $to)  {$creports[] = $result->fields;}
				// autocomplete
				$key = base64_encode($result->fields["name"]."###".$result->fields["login"]);
				$subreports_ac[$key] = trim($result->fields["name"]);
				$hi++;
			}
			$result->MoveNext();
		}
		
		$dbconn->disconnect();
		//
        $html = '<table style="margin:0;padding:0;width:100%;font-size:11px">
					<tr>
						<td colspan="2">'._('Properties report').':</td>
					</tr>
					<tr>
						<td>'._('Report Name').':</td>
						<td>
							<select name="run">';
								foreach($subreports_ac as $key => $value)
								{
									$html .= '<option value="'.$key.'"';
									$html .= ( $this->get('run')==$key ) ?  ' selected="selected"' : "";
									$html .= '>'.$value.'</option>';
								}
			$html .= ' 		</select>
						</td>
					</tr>
					<tr>
						<td>'._('Refresh report').':</td>
						<td><input name="refresh" value="false" ';
					if($this->get('refresh')=='false'){ $html .= 'checked="checked" '; }
			
			$html .= 'type="radio">'._('No').'
					<input name="refresh" value="true" ';
					if($this->get('refresh')=='true'){ $html .= 'checked="checked" '; }
			
			$html .= 'type="radio">'._('Yes').'
						<input style="width:80px" type="text" name="secondRefresh" value="'.$this->get('secondRefresh').'" /> '._('seconds').'</td>
					</tr>
				</table>';
		
        return $html;
    }
	
    function showSettingsHTML() {
        $html = '<table style="margin:0;padding:0;width:100%;font-size:11px">
				  <tr>
					<td colspan="2">'._('Properties window report').':</td>
				  </tr>
				  <tr>
					<td>'._('Width').':</td>
					<td><input type="text" name="width" value="'.$this->get('width').'" /> px</td>
				  </tr>
				  <tr>
					<td>'._('Height').':</td>
					<td><input type="text" name="height" value="'.$this->get('height').'" /> px</td>
				  </tr>
				</table>';
		
        return $html;
    }
	
    function showWindowContents() {
		if (!$this->get('run')) {
            return _("Please configure options at the Sub-category tab");
        }
		
		$url = '../report/wizard_run.php?mode=dashboard';
		if($this->get('refresh')=='true'){
			$url .= '&refresh='.$this->get('secondRefresh');
		}
		$url .= '&widthDashboards='.$this->get('width').'&run='.$this->get('run');
		
		$html .= '<iframe id="contIframe" frameborder="0" src ="'.$url.'" width="'.$this->get('width').'" height="'.$this->get('height').'"></iframe>';
        
		return $html;
    }
}
?>
