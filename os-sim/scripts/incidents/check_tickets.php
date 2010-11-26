<?
set_include_path('/usr/share/ossim/include');
require_once ('classes/Session.inc');
require_once ('ossim_conf.inc'); 
require_once ('ossim_sql.inc');  


$conf = $GLOBALS["CONF"];
$mdays = $conf->get_conf("tickets_max_days", FALSE); 

$db = new ossim_db();
$dbconn = $db->connect();


$result=$dbconn->execute("SELECT id, title, date, ref, type_id, priority, last_update, in_charge, submitter FROM incident WHERE DATEDIFF( now( ) , date ) > $mdays AND STATUS = 'open'");

while ( !$result->EOF ) {
    //echo $result->fields["in_charge"];
    unset($email_data);
    if (preg_match("/^\d+$/",$result->fields["in_charge"])) {
        $entity_name_type = array();
        $entity_name_type = Acl::get_entity_name_type($dbconn,$result->fields["in_charge"]);
        $in_charge = $entity_name_type[0]." [".$entity_name_type[1]."]";
    }
    else {
        $in_charge = $result->fields["in_charge"];
    }
    $email_data = array("id" => $result->fields["id"],
                        "title" => $result->fields["title"],
                        "date" => $result->fields["date"],
                        "ref" => $result->fields["ref"],
                        "type_id" => $result->fields["type_id"],
                        "priority" => $result->fields["priority"],
                        "last_update" => $result->fields["last_update"],
                        "in_charge" => $in_charge,
                        "submitter" => $result->fields["submitter"]
                        );
    if (!preg_match("/^\d+$/",$result->fields["in_charge"])) { //in_charge is a user
        $user_data = Session::get_list($dbconn, "WHERE login='".$result->fields["in_charge"]."'");
        //echo $user_data[0]->get_email(); 
        sendEmail($conf, $email_data, $user_data[0]->get_email());
    }
    else { // in_charge is a entity
        $entity_data = Acl::get_entity($dbconn,$result->fields["in_charge"]);
        if($entity_data["admin_user"]!="") { // exists pro admin
            $pro_admin_data = Session::get_list($dbconn, "WHERE login='".$entity_data["admin_user"]."'");
            //echo $pro_admin_data[0]->get_email();
            sendEmail($conf, $email_data, $pro_admin_data[0]->get_email());
        }
        else { // doesn't exit pro admin
            $users = Acl::get_users($dbconn);
            $user_list = $users[0];
            foreach ($user_list as $user) if(in_array($result->fields["in_charge"],$user['entities'])) { // send an e-mail to each user
                //echo $user['email'];
                sendEmail($conf, $email_data, $user['email']);
            }
        }
    }
    $result->MoveNext()."<br>";
}

$dbconn->disconnect();

function sendEmail ($conf, $email_data, $email) {
    $from=$conf->get_conf("from");
    $fromName=$conf->get_conf("from");
    $to=$email;
    $toName=$email;
    $subject=_('Ticket Open: ').$email_data["title"];
    $body='<html>
                <head>
                <title>'.$subject.'</title>
                </head>
                <body>'.
                '<table width="100%" cellspacing="0" cellpadding="0" style="border:0px;">'.
                    '<tr><td width="75">'._('Id:').'</td><td>'.$email_data["id"].'</td></tr>'.
                    '<tr><td width="75">'._('Title:').'</td><td>'.$email_data["title"].'</td></tr>'.
                    '<tr><td width="75">'._('Date:').'</td><td>'.$email_data["date"].'</td></tr>'.
                    '<tr><td width="75">'._('Ref:').'</td><td>'.$email_data["ref"].'</td></tr>'.
                    '<tr><td width="75">'._('Type id:').'</td><td>'.$email_data["type_id"].'</td></tr>'.
                    '<tr><td width="75">'._('Priority:').'</td><td>'.$email_data["priority"].'</td></tr>'.
                    '<tr><td width="75">'._('Last update:').'</td><td>'.$email_data["last_update"].'</td></tr>'.
                    '<tr><td width="75">'._('In charge:').'</td><td>'.$email_data["in_charge"].'</td></tr>'.
                    '<tr><td width="75">'._('Submitter:').'</td><td>'.$email_data["submitter"].'</td></tr>'. 
                '</table>'.
                '</body>
                </html>
                ';
    require_once('classes/PHPMailer.inc');
    require_once('classes/PHPMailerSMTP.inc');  // optional, gets called from within class.phpmailer.php if not already loaded

    $mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch

    $mail->IsSMTP(); // telling the class to use SMTP
    try {
      if($conf->get_conf("use_ssl")=='yes'){ $mailHost='ssl://'; }else{ $mailHost=''; }
      $mailHost.=$conf->get_conf("smtp_server_address");
      $mail->Host       = $mailHost;// SMTP server
      $mail->SMTPDebug  = 1;                     // enables SMTP debug information (for testing)
      $mailSMTPUser=$conf->get_conf("smtp_user");
      if(!empty($mailSMTPUser)){
          $mailSMTPAuth=true;
          $mailUsername=$conf->get_conf("smtp_user");
          $mailPassword=$conf->get_conf("smtp_pass");
      }else{
          $mailSMTPAuth=false;
          $mailUsername='';
          $mailPassword='';
      }
      $mail->SMTPAuth   = $mailSMTPAuth;                  // enable SMTP authentication
      $mail->Port       = $conf->get_conf("smtp_port");                    // set the SMTP port for the GMAIL server (26)
      $mail->Username   = $mailUsername; // SMTP account username
      $mail->Password   = $mailPassword;        // SMTP account password
      $mail->AddAddress($to,$toName);
      $mail->SetFrom($from,$fromName);
      $mail->Subject = $subject;
      $mail->AltBody = _("To view the message, please use an HTML compatible email viewer!"); // optional - MsgHTML will create an alternate automatically
      $mail->MsgHTML($body);
      $mail->Send();
      return true;
    } catch (phpmailerException $e) {
      echo $e->errorMessage(); //Pretty error messages from PHPMailer
    } catch (Exception $e) {
      echo $e->getMessage(); //Boring error messages from anything else!
    }
}


?>
