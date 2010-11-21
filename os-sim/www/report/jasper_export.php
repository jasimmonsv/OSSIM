<?php
    require_once ('classes/Session.inc');
    require_once ('classes/JasperReport.inc');
    Session::useractive();
    $POSTReportUnit=POST('reportUnit');
    $GETFormat=GET('format');
    $attach_mode=(GET('attachment')=="true") ? true : false;
    $port = explode ("\n",`grep 'Listen' /etc/apache2/ports.conf | awk '{print $2}'`);
    $_SERVER["APACHE_PORT"]= (is_array($port) && intval($port[0])>0) ? intval($port[0]) : 80;
    $_POST['reportWWW']='http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER["APACHE_PORT"].'/';
    if(!is_null($POSTReportUnit)){
        require_once ('ossim_conf.inc');
        $client = new JasperClient($conf);
        $client->getPermission($POSTReportUnit);

        if($GETFormat=='email'){
            $format='pdf';
        }else{
            $format=$GETFormat;
        }
        $report_format=$client->getFormatExport($format);

        $report_unit = '/'.$POSTReportUnit;

        if(count($_POST)>1){
            foreach($_POST as $key => $value){
               if($key!='reportUnit'){
                   $report_params[$key]=$value;
                   $params.='_'.$value;
               }
            }
        }else{
            $report_params=array();
        }
        if($GETFormat=='email'){
            ?>
            <html>
                <head>
                    <title>&nbsp;</title>
                    <link rel="stylesheet" type="text/css" href="../style/style.css"/>
                    <script type="text/javascript" src="../js/jquery-1.3.1.js"></script>
                </head>
                <body>
                    <p id="load" style="visibility:visible;"><img src="../pixmaps/loading.gif" width="16" height="16" /> <?php echo _("Loading..."); flush(); ?></p>
            <?php
            $result = $client->requestReport($report_unit, $report_format,$report_params,'runReport');
            $POSTReportLabel=POST('reportLabel');
            $fileName = new FormatText($_SESSION['_user'].' '.$POSTReportLabel);
            $fileUrl="jasper_include/temp/".$fileName->getName().'.pdf';
            if(is_file($fileUrl)){
                unlink($fileUrl);
            }

            $archivo=fopen($fileUrl, "w");
            fwrite($archivo, $result);
            fclose($archivo);

            function sendMail($from,$fromName,$to,$toName,$subject,$body,$attachment,$fileName) {
                $conf = $GLOBALS["CONF"];
                require_once('classes/PHPMailer.inc');
                include("classes/PHPMailerSMTP.inc");  // optional, gets called from within class.phpmailer.php if not already loaded

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
                  $mail->AddAttachment($attachment, $fileName.'_'.date('Ymj-Hms').'.pdf');
                  $mail->AltBody = _("To view the message, please use an HTML compatible email viewer!"); // optional - MsgHTML will create an alternate automatically
                  $mail->MsgHTML($body);
                  $mail->Send();
                  echo "<p>"._("PDF Sent OK\n")."</p>";
                } catch (phpmailerException $e) {
                  echo $e->errorMessage(); //Pretty error messages from PHPMailer
                } catch (Exception $e) {
                  echo $e->getMessage(); //Boring error messages from anything else!
                }
            }
            $subject=_('Report (')._($POSTReportLabel)._(') - OSSIM');
            $parameterForEmail=array();
            foreach($_POST as $key => $value){
                if(!(preg_match("/TYPE_CONTROL_\d+/",$key)||preg_match('/isListItem/',$key)||preg_match('/email/',$key)||preg_match('/reportUser/',$key)||preg_match('/reportWWW/',$key)||preg_match('/reportUnit/',$key)||preg_match('/reportLabel/',$key))){
                    $parameterForEmail[_($key)]=$value;
                }
            }
            $body='<html>
                    <head>
                    <title>'.$subject.'</title>
                    </head>
                    <body>
                        <p><b>'._('Report').':</b> '._($POSTReportLabel).'</p>
                        <p><b>'._('User').':</b> '.$_SESSION['_user'].'</p>';
            foreach($parameterForEmail as $key => $value){
                $body.='<p><b>'.$key.':</b> '.$value.'</p>';
            }
                $body.='
                    </body>
                    </html>
                    ';
            $POSTEmail=POST('email');
            sendMail($conf->get_conf("from"),$conf->get_conf("from"),$POSTEmail,$POSTEmail,$subject,$body,$fileUrl,$fileName->getName());
      ?>
                      <script type='text/javascript'>
                        //<![CDATA[
                            $(document).ready(function(){
                                $("#load").attr({
                                  style: "visibility:hidden;"
                                });
                            });
                       //]]>
                      </script>
                    <form method="POST" action="#">
                        <p><input class="btn center" type="button" value="<?=_('Close')?>" onclick="javascript:parent.GB_hide();" /></p>
                    </form>
                </body>
            </html>
      <?php
        }else{
            $result = $client->requestReport($report_unit, $report_format,$report_params,'runReport');
            $client->getHeaderExportHtml($report_unit,$report_format,$params,$attach_mode);
            echo $result;
        }
    }else{
      ?>
    <html>
        <head>
            <title>&nbsp;</title>
            <link rel="stylesheet" type="text/css" href="../style/style.css"/>            
        </head>
        <body>
            <p><?php echo _("Report no exist"); ?></p>
            <form method="POST" action="#">
                <p><input class="btn center" type="button" value="<?=_('Close')?>" onclick="javascript:window.close();" /></p>
            </form>
         </body>
    </html>
      <?php
    }

?>