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
* - Repository()
* - get_id_document()
* - get_title()
* - get_text()
* - get_date()
* - get_user()
* - get_keywords()
* - get_relevance()
* - get_count()
* - get_list()
* - get_repository_linked()
* - have_linked_documents()
* - get_document()
* - insert()
* - update()
* - attach()
* - get_attachments()
* - get_relationships()
* - get_relationships_by_link()
* - insert_relationships()
* - delete_relationships()
* - delete()
* - delete_attachment()
* - get_hostnet()
* - get_list_bylink()
* Classes list:
* - Repository
*/
// Repository class made in Granada (26 january 2009)
require_once ('classes/Net.inc');
require_once ('classes/Session.inc');
require_once ('classes/Security.inc');
require_once ('classes/Log_action.inc');
require_once ('classes/User_config.inc');
require_once ("ossim_db.inc");
define("MAXREL", 4);
class Repository {
    var $id_document;
    var $title;
    var $text;
    var $date;
    var $user;
    var $keywords;
    var $relavance;
    // List of attached files
    var $atch = array();
    var $rel = array();
    function Repository($id_document, $title, $text, $date, $user, $keywords, $relavance, $atch, $rel) {
        $this->id_document = intval($id_document);
        $this->title = $title;
        $this->text = $text;
        $this->date = $date;
        $this->user = $user;
        $this->keywords = $keywords;
        $this->relevance = $relavance;
        if (is_array($atch)) $this->atch = $atch;
        else $this->atch = array(
            $atch
        );
        if (is_array($rel)) $this->rel = $rel;
        else $this->rel = array(
            $rel
        );
    }
    function get_id_document() {
        return $this->id_document;
    }
    function get_title() {
        return $this->title;
    }
    function get_text() {
        return $this->text;
    }
    function get_date() {
        return $this->date;
    }
    function get_user() {
        return $this->user;
    }
    function get_keywords() {
        return $this->keywords;
    }
    function get_relevance() {
        return number_format($this->relevance * 100 / MAXREL, 2);
    }
    function get_count($conn) {
        $count = 0;
        return $count;
    }
    // Listing and search
    function get_list($conn, $from = 0, $maxrows = - 1, $search_str = "", $order = "date", $torder = 0) {
        $conf = $GLOBALS["CONF"];
        $version = $conf->get_conf("ossim_server_version", FALSE);
        $current_user = Session::get_session_user();
		// User perms
        /*
		$user_config = new User_config($conn);
		$kdbperms = $user_config->get(Session::get_session_user(),"user_docs",'php',"knowledgedb");
		if (count($kdbperms)<1) {
			$kdbperms = array ("admin"=>1,Session::get_session_user()=>1);
		}
		$user_where = "";
		foreach ($kdbperms as $u=>$v) {
			$user_where .= (($user_where != "") ? "," : "")."'".$u."'";
		}
        */
        if(Session::am_i_admin())
            $user_where = "";
        else if(preg_match("/pro|demo/i",$version)){
            if(Acl::am_i_proadmin()){
                $tmp = array();
                $entities_list = Acl::get_user_entities($current_user);
                foreach($entities_list as $entity) {
                    $tmp[] = "'".$entity."'";
                }
                $users = Acl::get_my_users($conn,Session::get_session_user());
                foreach ($users as $user){
                    $tmp[] = "'".$user["login"]."'";
                }
                if (count($tmp) > 0) $user_where = "and (user in (".implode(", ", $tmp).") or user='$current_user')";
                else $user_where = "and (user='$current_user')";
                
            }
            else {
                $tmp = array();
                $entities = Acl::get_user_entities($current_user);
                //print_r($current_user);  
                //print_r($entities);
                foreach ($entities as $entity) {
                    $tmp[] = "'".$entity."'";
                }
                if (count($tmp) > 0) $user_where = "and (user in (".implode(", ", $tmp).") or user='$current_user')";
                else $user_where = "and (user='$current_user')";
            }
        }
        else {
            $user_where = "and user='$current_user'"; 
        }
        
		$list = array();
        $limit_str = ($maxrows > 0) ? " LIMIT $from,$maxrows" : "";
		$torder = ($torder) ? "DESC" : "ASC";
        if ($search_str != "") {
            $title = "title LIKE \"%$search_str%\"";
            $keywords = "keywords LIKE \"%$search_str%\"";
            $text = "MATCH(text) AGAINST(\"$search_str\")"; // FULLTEXT search
            if (preg_match("/AND/", $search_str)) {
                $search_str = preg_replace("/\s*AND\s*/", " AND ", $search_str); // normalize format
                $st = explode(" AND ", $search_str); // construct where clause
                $title = $keywords = $text = "";
                foreach($st as $clause) {
                    $title.= ($title == "" ? "" : " AND ") . "title LIKE \"%$clause%\"";
                    $keywords.= ($keywords == "" ? "" : " AND ") . "keywords LIKE \"%$clause%\"";
                }
                $title = "($title)";
                $keywords = "($keywords)";
                $search_str = preg_replace("/ AND /", " ", $search_str);
                $search_str = preg_replace("/(\S+)/", "+\\1", $search_str);
                $text = "MATCH(text) AGAINST(\"$search_str\" IN BOOLEAN MODE)";
            } elseif (preg_match("/OR/", $search_str)) {
                $search_str = preg_replace("/\s*OR\s*/", " OR ", $search_str); // normalize format
                $st = explode(" OR ", $search_str); // construct where clause
                $title = $keywords = $text = "";
                foreach($st as $clause) {
                    $title.= ($title == "" ? "" : " OR ") . "title LIKE \"%$clause%\"";
                    $keywords.= ($keywords == "" ? "" : " OR ") . "keywords LIKE \"%$clause%\"";
                }
                $title = "($title)";
                $keywords = "($keywords)";
                $search_str = preg_replace("/ OR /", " ", $search_str);
                $text = "MATCH(text) AGAINST(\"$search_str\")";
            }
            $sqlall = "(SELECT id,title,text,date,user," . MAXREL . " as relevance FROM repository WHERE $title $user_where) UNION (SELECT id,title,text,date,user," . MAXREL . " as relevance FROM repository WHERE $keywords $user_where) UNION (SELECT id,title,text,date,user,$text as relevance FROM repository WHERE $text $user_where) ORDER BY $order $torder";
            $sql = "(SELECT id,title,text,date,user," . MAXREL . " as relevance FROM repository WHERE $title $user_where) UNION (SELECT id,title,text,date,user," . MAXREL . " as relevance FROM repository WHERE $keywords $user_where) UNION (SELECT id,title,text,date,user,$text as relevance FROM repository WHERE $text $user_where) ORDER BY $order $torder $limit_str";
        } else {
            $sqlall = "SELECT * FROM repository WHERE 1=1 $user_where ORDER BY $order $torder";
            //error_log($sqlall,3,"/tmp/repository.log");
            $sql = "SELECT * FROM repository WHERE 1=1 $user_where ORDER BY $order $torder $limit_str";
            //error_log("\n".$sql,3,"/tmp/repository.log");
        }
        //echo "<br><br><br>$sqlall<br>$sql<br>";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
        $resall = & $conn->Execute($sqlall);
        $nrows = $resall->numrows();
        //echo "NUMROWS para $search_str: $nrows<br>";
        while (!$rs->EOF) {
            $filelist = array();
            if ($rs->fields["id"] != "" && $list[$rs->fields["id"]]) { // No mostrar repetidos
                $rs->MoveNext();
                $nrows--;
                continue;
            }
            // Get attachments
            $sql = "SELECT * FROM repository_attachments WHERE id_document=" . $rs->fields['id'];
            if ($rs_files = & $conn->Execute($sql)) {
                while (!$rs_files->EOF) {
                    $filelist[] = array(
                        "id" => $rs_files->fields["id"],
                        "name" => $rs_files->fields["name"],
                        "id_document" => $rs_files->fields["id_document"],
                        "type" => $rs_files->fields["type"]
                    );
                    $rs_files->MoveNext();
                }
            }
            $rellist = array();
            // Get relationships
            $sql = "SELECT * FROM repository_relationships WHERE id_document=" . $rs->fields['id'];
            if ($rs_rel = & $conn->Execute($sql)) {
                while (!$rs_rel->EOF) {
                    $rellist[] = array(
                        "keyname" => $rs_rel->fields["keyname"],
                        "name" => $rs_rel->fields["name"],
                        "id_document" => $rs_rel->fields["id_document"],
                        "type" => $rs_rel->fields["type"]
                    );
                    $rs_rel->MoveNext();
                }
            }
            $list[$rs->fields["id"]] = new Repository($rs->fields["id"], $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["user"], $rs->fields["keywords"], $rs->fields["relevance"], $filelist, $rellist);
            $rs->MoveNext();
        }
        return array(
            $list,
            $nrows
        );
    }
    // Get the repository list linked to a host
    function get_repository_linked($conn, $ip, $type = 'host') {
        $ret = array();
        $sql = "SELECT * FROM repository_relationships WHERE keyname='$ip' AND type='$type'";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
        while (!$rs->EOF) {
            $ret[] = Repository::get_document($conn, $rs->fields['id_document']);
            $rs->MoveNext();
        }
        return $ret;
    }
    // Get the repository list linked to a host
    function have_linked_documents($conn, $ip, $type = 'host') {
        $sql = "SELECT * FROM repository_relationships WHERE keyname='$ip' AND type='$type'";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return false;
        }
        if (!$rs->EOF) return $rs->numrows();
        return false;
    }
    // Get a document by id
    function get_document($conn, $id_document) {
        $ret = array();
        $title = "";
        $text = "";
        $keywords = "";
        $sql = "SELECT * FROM repository WHERE id=$id_document";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
        if (!$rs->EOF) {
            // Get attachments
            $filelist = array();
            $sql = "SELECT * FROM repository_attachments WHERE id_document=" . $id_document;
            if ($rs_files = & $conn->Execute($sql)) {
                while (!$rs_files->EOF) {
                    $filelist[] = array(
                        "id" => $rs_files->fields["id"],
                        "name" => $rs_files->fields["name"],
                        "id_document" => $rs_files->fields["id_document"],
                        "type" => $rs_files->fields["type"]
                    );
                    $rs_files->MoveNext();
                }
            }
            $ret['title'] = $rs->fields["title"];
            $ret['text'] = $rs->fields["text"];
            $ret['keywords'] = $rs->fields["keywords"];
            $ret['date'] = $rs->fields["date"];
            $ret['user'] = $rs->fields["user"];
            $ret['id'] = $id_document;
            $ret['num_atch'] = count($filelist);
        }
        return $ret;
    }
    // Insert new document and returns auto-generated Id
    function insert($conn, $title, $text, $keywords, $user) {
        $sql = "INSERT into repository ( title, text, date, keywords, user ) VALUES   (?,?,?,?,?)";
        $params = array(
            $title,
            $text,
            date("Y-m-d") ,
            $keywords,
            $user
        );
        if ($conn->Execute($sql, $params) == false) {
            print _('Error inserting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        // get lastid
        $res = $conn->query("SELECT LAST_INSERT_ID() as lastid");
        if ($rw = $res->fetchRow()) $id_document = $rw["lastid"];
        return $id_document;
    }
    // Edit a document
    function update($conn, $id_document, $title, $text, $keywords) {
        $sql = "UPDATE repository SET title=?, text=?, date=?, keywords=? WHERE id=$id_document";
        $params = array(
            $title,
            $text,
            date("Y-m-d") ,
            $keywords
        );
        if ($conn->Execute($sql, $params) == false) {
            print _('Error Updating: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $id_document
        );
        Log_action::log(59, $infolog);
    }
    // Link a file to a document and returns the file in-disk name (iddocument_idfile.type)
    function attach($conn, $id_document, $filename) {
        // Check if exists
        $sql = "SELECT * FROM repository_attachments WHERE name='$filename'";
        if ($rs = & $conn->Execute($sql)) {
            if ($rs->numrows() > 0) {
                print _('Error inserting: File exists in Database').'<BR>';
                exit;
            }
        }
        $type = preg_replace("/.*\.(..?.?)$/", "\\1", $filename);
        $sql = "INSERT into repository_attachments ( id_document, name, type ) VALUES   (?,?,?)";
        $params = array(
            $id_document,
            $filename,
            $type
        );
        if ($conn->Execute($sql, $params) == false) {
            print _('Error inserting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $filename,
            $id_document
        );
        Log_action::log(60, $infolog);
        
        // get lastid
        $res = $conn->query("SELECT LAST_INSERT_ID() as lastid");
        if ($rw = $res->fetchRow()) $id_file = $rw["lastid"];
        return $id_document . "_" . $id_file . "." . $type;
    }
    // Get a list with files linked to a document by id
    function get_attachments($conn, $id_document) {
        $filelist = array();
        // Get attachments
        $sql = "SELECT * FROM repository_attachments WHERE id_document=$id_document";
        if ($rs_files = & $conn->Execute($sql)) {
            while (!$rs_files->EOF) {
                $filelist[] = array(
                    "id" => $rs_files->fields["id"],
                    "name" => $rs_files->fields["name"],
                    "id_document" => $id_document,
                    "type" => $rs_files->fields["type"]
                );
                $rs_files->MoveNext();
            }
        }
        return $filelist;
    }
	// Get a unique file linked to a document by id
    function get_attachment($conn, $id_document, $id) {
        $file = "";
		$name = "";
        // Get attachments
        $sql = "SELECT * FROM repository_attachments WHERE id_document=$id_document AND id=$id";
        if ($rs_files = & $conn->Execute($sql)) {
            if (!$rs_files->EOF) {
                $file = $rs_files->fields["id_document"]."_".$rs_files->fields["id"].".".$rs_files->fields["type"];
				$name = $rs_files->fields["name"];
            }
        }
        return array($file,$name);
    }
    // Get a list with relationships
    function get_relationships($conn, $id_document) {
        $rellist = array();
        // Get relationships
        $sql = "SELECT * FROM repository_relationships WHERE id_document=$id_document";
        if ($rs_files = & $conn->Execute($sql)) {
            while (!$rs_files->EOF) {
                $rellist[] = array(
                    "id" => $rs_files->fields["id"],
                    "key" => $rs_files->fields["keyname"],
                    "name" => $rs_files->fields["name"],
                    "type" => $rs_files->fields["type"]
                );
                $rs_files->MoveNext();
            }
        }
        return $rellist;
    }
    // Get a list with relationships by link id
    function get_relationships_by_link($conn, $id_link) {
        $rellist = array();
        // Get relationships
        $sql = "SELECT d.id,d.title,r.id_document FROM repository_relationships r,repository d WHERE r.keyname='$id_link' AND r.id_document=d.id";
        if ($rs_files = & $conn->Execute($sql)) {
            while (!$rs_files->EOF) {
                $rellist[] = array(
                    "id" => $rs_files->fields["id"],
                    "title" => $rs_files->fields["title"],
                    "id_document" => $rs_files->fields["id_document"]
                );
                $rs_files->MoveNext();
            }
        }
        return $rellist;
    }
    function get_linked_by_directive($conn, $directive) {
        $rellist = array();
        // Get relationships
        $sql = "SELECT d.id,d.title,r.id_document,d.date,d.text FROM repository_relationships r,repository d WHERE r.name='$directive' AND r.keyname='$directive' AND r.type='directive' AND r.id_document=d.id";
        if ($rs_files = & $conn->Execute($sql)) {
            while (!$rs_files->EOF) {
                $rellist[] = array(
                    "id" => $rs_files->fields["id"],
                    "title" => $rs_files->fields["title"],
                    "text" => $rs_files->fields["text"],
                    "date" => $rs_files->fields["date"],
                    "id_document" => $rs_files->fields["id_document"]
                );
                $rs_files->MoveNext();
            }
        }
        return $rellist;
    }
    // Insert a snort reference
    function insert_snort_references($conn, $id_document, $pid, $sid) {
        // Check if exists or autoinsert values
        $conn->Execute("INSERT IGNORE INTO snort.reference_system VALUES (NULL,'kdb')");
        $sql = "SELECT ref_system_id FROM snort.reference_system WHERE ref_system_name='kdb'";
        if (!$rs = &$conn->Execute($sql)) {
            print _('DB Error: ').$conn->ErrorMsg();
            exit;
        }
        $refsystemid = $rs->fields['ref_system_id'];
        $conn->Execute("INSERT IGNORE INTO snort.reference VALUES (NULL,$refsystemid,'$id_document')");
        $sql = "SELECT ref_id FROM snort.reference WHERE ref_system_id=$refsystemid and ref_tag='$id_document'";
        if (!$rs1 = &$conn->Execute($sql)) {
            print _('DB Error: ').$conn->ErrorMsg();
            exit;
        }
        $refid = $rs1->fields['ref_id'];
        $conn->Execute("INSERT IGNORE INTO snort.sig_reference VALUES ($pid, $sid, $refid)");
    }
    // Delete a snort reference
    function delete_snort_references($conn, $id_document) {
        // Check if exists or autoinsert values
        $sql = "SELECT ref_system_id FROM snort.reference_system WHERE ref_system_name='kdb'";
        if (!$rs = &$conn->Execute($sql)) {
            print _('DB Error: ').$conn->ErrorMsg();
            exit;
        }
        $refsystemid = $rs->fields['ref_system_id'];
        if ($refsystemid!="") {
            $sql = "SELECT ref_id FROM snort.reference WHERE ref_system_id=$refsystemid and ref_tag='$id_document'";
            if (!$rs1 = &$conn->Execute($sql)) {
                print _('DB Error: ').$conn->ErrorMsg();
                exit;
            }
            $refid = $rs1->fields['ref_id'];
            $conn->Execute("DELETE FROM snort.reference WHERE ref_id=$refid");
            $conn->Execute("DELETE FROM snort.sig_reference WHERE ref_id=$refid");
        }
    }
    // Insert a new link
    function insert_relationships($conn, $id_document, $name, $table, $key) {
        // Check if exists
        $sql = "SELECT * FROM repository_relationships WHERE keyname='$key' AND type='$table' AND id_document=$id_document";
        if ($rs = & $conn->Execute($sql)) {
            if ($rs->numrows() > 0) {
                print _('Error inserting: File exists in Database').'<BR>';
                exit;
            }
        }
        $sql = "INSERT into repository_relationships ( id_document, name, type, keyname ) VALUES (?,?,?,?)";
        $params = array(
            $id_document,
            $name,
            $table,
            $key
        );
        if ($conn->Execute($sql, $params) == false) {
            print _('Error inserting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $name,
            $id_document
        );
        Log_action::log(62, $infolog);
    }
    // Delete a link
    function delete_relationships($conn, $id_document, $key) {
        // Delete link
        $sql = "DELETE FROM repository_relationships WHERE id_document = ? AND keyname=?";
        $params = array(
            $id_document,
            $key
        );
        if ($conn->Execute($sql, $params) === false) {
            print _('Error deleting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $id_document,
            $key
        );
        Log_action::log(63, $infolog);
    }
    // Delete a document, and linked files
    function delete($conn, $id_document) {
        // Delete relationships
        $sql = "DELETE FROM repository_relationships WHERE id_document = ?";
        $params = array(
            $id_document
        );
        if ($conn->Execute($sql, $params) === false) {
            print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        // Delete attachments
        $sql = "DELETE FROM repository_attachments WHERE id_document = ?";
        $params = array(
            $id_document
        );
        if ($conn->Execute($sql, $params) === false) {
            print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        if (is_dir("/tmp/ossim/$id_document")) {
            // List the contents of the directory table
            $dir_content = scandir("/tmp/ossim/$id_document");
            // Is it a directory?
            if ($dir_content != FALSE) {
                // For each directory entry
                foreach($dir_content as $entry) {
                    if ($entry == "." || $entry == "..") continue;
                    unlink("/tmp/ossim/$id_document/$entry");
                }
                rmdir("/tmp/ossim/$id_document");
            }
        }
        // Delete document
        $sql = "DELETE FROM repository WHERE id = ?";
        $params = array(
            $id_document
        );
        if ($conn->Execute($sql, $params) === false) {
            print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $id_document
        );
        Log_action::log(58, $infolog);
    }
    // Delete file and row attached to a document by file id
    function delete_attachment($conn, $id, $uploads_dir) {
        $sql = "SELECT * FROM repository_attachments WHERE id=$id";
        if ($rs = & $conn->Execute($sql)) {
            if (!$rs->EOF) {
                // Del filesystem file
                $disk_file = $rs->fields['id_document'] . "_" . $id . "." . $rs->fields['type'];
                //system ("sudo rm -f $uploads_dir/$disk_file");
                unlink("$uploads_dir/" . $rs->fields['id_document'] . "/$disk_file");
                // Del database row
                $sql = "DELETE FROM repository_attachments WHERE id = ?";
                $params = array(
                    $id
                );
                if ($conn->Execute($sql, $params) === false) {
                    print _('error deleting: ') . $conn->ErrorMsg() . '<BR>';
                    exit;
                }
            }
        }
        $infolog = array(
            $disk_file
        );
        Log_action::log(61, $infolog);
    }
    // Get host or host_group or net or net_group List
    function get_hostnet($conn, $table) {
        $ret = array();
        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM $table";
		if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
        $resall = & $conn->Execute("SELECT FOUND_ROWS() as contador");
        if ($rw = $resall->fetchRow()) $num_rows = $rw["contador"];
		if ($table == "host" || $table == "host_group" || $table == "net" || $table == "net_group") $num_rows = 0;
		while (!$rs->EOF) {
			// Special case: host table
			if ($table == "host") {
				// User sensor filtering
				if (Session::hostAllowed($conn,$rs->fields["ip"])) {
					$ret[] = array(
					"key" => $rs->fields["ip"],
					"name" => $rs->fields["hostname"] . " (" . $rs->fields["ip"] . ")"
					);
					$num_rows++;
				}
			}
			elseif ($table == "host_group") {
				if (Session::groupHostAllowed($conn,$rs->fields["name"])) {
					$ret[] = array(
						"key" => $rs->fields["name"],
						"name" => $rs->fields["name"]
					);
					$num_rows++;
				}
			}
			elseif ($table == "net") {
				if (Session::netAllowed($conn,$rs->fields["name"])) {
					$ret[] = array(
						"key" => $rs->fields["name"],
						"name" => $rs->fields["name"]
					);
					$num_rows++;
				}
			}
			elseif ($table == "net_group") {
				if (Session::groupAllowed($conn,$rs->fields["name"])) {
					$ret[] = array(
						"key" => $rs->fields["name"],
						"name" => $rs->fields["name"]
					);
					$num_rows++;
				}
			}
            // Special case: incidents table
            elseif ($table == "incident") {
				require_once ('classes/Session.inc');
				require_once ('classes/Incident.inc');
				$user = Session::get_session_user();
				$allowed = Incident::get_users_list($conn, $rs->fields["id"]);
				if (in_array($user, $allowed)) {
					$ret[] = array(
						"key" => $rs->fields["id"],
						"name" => $rs->fields["title"]
					);
				}
            // Others
            } else $ret[] = array(
                "key" => $rs->fields["name"],
                "name" => $rs->fields["name"]
            );
            $rs->MoveNext();
        }
        return array(
            $ret,
            $num_rows
        );
    }
    // Get list by relationship
    function get_list_bylink($conn, $from = 0, $maxrows = - 1, $keyname = "") {
        $list = array();
        $limit_str = ($maxrows > 0) ? " LIMIT $from,$maxrows" : "";
        $sqlall = "SELECT d.id,d.title,d.text,d.date,d.user,d.keywords FROM repository d, repository_relationships r WHERE d.id=r.id_document AND r.keyname='$keyname'";
        $sql = "SELECT d.id,d.title,d.text,d.date,d.user,d.keywords FROM repository d, repository_relationships r WHERE d.id=r.id_document AND r.keyname='$keyname'$limit_str";
        //echo "$sqlall<br>$sql<br>";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
            return array();
        }
        $resall = & $conn->Execute($sqlall);
        $nrows = $resall->numrows();
        //echo "NUMROWS para $search_str: $nrows<br>";
        while (!$rs->EOF) {
            $filelist = array();
            if ($rs->fields["id"] != "" && $list[$rs->fields["id"]]) { // No mostrar repetidos
                $rs->MoveNext();
                $nrows--;
                continue;
            }
            // Get attachments
            $sql = "SELECT * FROM repository_attachments WHERE id_document=" . $rs->fields['id'];
            if ($rs_files = & $conn->Execute($sql)) {
                while (!$rs_files->EOF) {
                    $filelist[] = array(
                        "id" => $rs_files->fields["id"],
                        "name" => $rs_files->fields["name"],
                        "id_document" => $rs_files->fields["id_document"],
                        "type" => $rs_files->fields["type"]
                    );
                    $rs_files->MoveNext();
                }
            }
            $rellist = array();
            // Get relationships
            $sql = "SELECT * FROM repository_relationships WHERE id_document=" . $rs->fields['id'];
            if ($rs_rel = & $conn->Execute($sql)) {
                while (!$rs_rel->EOF) {
                    $rellist[] = array(
                        "keyname" => $rs_rel->fields["keyname"],
                        "name" => $rs_rel->fields["name"],
                        "id_document" => $rs_rel->fields["id_document"],
                        "type" => $rs_rel->fields["type"]
                    );
                    $rs_rel->MoveNext();
                }
            }
            $list[$rs->fields["id"]] = new Repository($rs->fields["id"], $rs->fields["title"], $rs->fields["text"], $rs->fields["date"], $rs->fields["user"], $rs->fields["keywords"], "", $filelist, $rellist);
            $rs->MoveNext();
        }
        return array(
            $list,
            $nrows
        );
    }
}
?>
