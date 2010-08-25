<?php
/**
 * PHP client for requesting reports from JasperServer via SOAP.
 */

class JasperClient {
  private $url;
  private $username;
  private $password;

  public function __construct($url, $username, $password) {
    $this->url = $url;
    $this->username = $username;
    $this->password = $password;
  }

  public function requestReport($report, $format, $params, $operationName) {
    $params_xml = "";

    foreach ($params as $name => $value) {
        if($name=='isListItem'){
          $isListItem=$value;
          continue;
        }
        if(isset($isListItem)){
            if(strstr($name, $isListItem.'_')){
                $params_xml .= "<parameter name=\"$isListItem\" isListItem=\"true\">'$value'</parameter>\n";
            }else{
                unset($isListItem);
                $params_xml .= "<parameter name=\"$name\"><![CDATA[$value]]></parameter>\n";
            }
        }else{
            $params_xml .= "<parameter name=\"$name\"><![CDATA[$value]]></parameter>\n";
        }
    }
    switch($operationName){
          case 'list':
          case 'listSubreport':
              $vOperationName = "list";
              break;
          case 'runReport':
              $vOperationName = $operationName;
              break;
          case 'get':
              $vOperationName = "get";
              break;
          default:
              break;
    }

    $request = "<request operationName=\"$vOperationName\" locale=\"en\">";
    switch($operationName){
          case 'list':
              $request .= "<resourceDescriptor name=\"\" wsType=\"folder\" uriString=\"/\">";
              break;
          case 'listSubreport':
              $request .= "<resourceDescriptor name=\"\" wsType=\"reportUnit\" uriString=\"/$report\">";
              break;
          case 'runReport':
              $request .= "<argument name=\"RUN_OUTPUT_FORMAT\">$format</argument>
            <resourceDescriptor name=\"\" wsType=\"\"
            uriString=\"$report\"
            isNew=\"false\">";
              break;
          case 'get':
              $request .= "<resourceDescriptor name=\"\" wsType=\"\" uriString=\"$report\">";
              break;
          default:
              break;
    }
    $request .= "
        <label>null</label>
        $params_xml
        </resourceDescriptor>
      </request>
    ";

    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));

    $return = array();
    try {
        $result = $client->__soapCall($vOperationName, array(
            new SoapParam($request,"requestXmlString")
        ));
        switch($operationName){
          case 'list':
              $return = $this->parseReponseWithList(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'listSubreport':
              $return = $this->parseReponseWithListSubreport(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'runReport':
              $return = $this->parseReponseWithReportData(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'get':
              $return = $this->parseReponseWithListResource(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          default:
              break;
        }
    } catch(SoapFault $exception) {
      $responseHeaders = $client->__getLastResponseHeaders();
      if ($exception->faultstring == "looks like we got no XML document" &&
         strpos($responseHeaders, "Content-Type: multipart/related;") !== false) {
         $return = $this->parseReponseWithReportData($responseHeaders, $client->__getLastResponse());
      } else {
          echo '<p>'._("Unable to connect to the Jasper Server, please wait a few seconds an try again. If the problem persists, make sure you have the ossim-compliance package installed and the Jasper Server is up and running.").'</p>';
         //throw $exception;
      }
    }
    return $return;
    /*
    if ($pdf)
      return $pdf;
    else
      throw new Exception("Jasper did not return PDF data. Instead got: \n$result");*/
  }
  protected function jasperException($xml){

      $domDocument=DOMDocument::loadXML($xml);
      echo '<html>
        <head>
            <title>&nbsp;</title>
            <link rel="stylesheet" type="text/css" href="../style/style.css"/>
        </head>
        <body>
            <p>'._("Report no exist").'</p>
            <form method="POST" action="#">';
      foreach($domDocument->childNodes as $nodos){
          $xml2 = simplexml_load_string($nodos->nodeValue);

          $result=explode("<returnMessage><![CDATA[", $xml2->returnMessage->asXML());
          $result=explode("]]></returnMessage>", $result[1]);

          echo "<p><strong>"._("Error Jasper").":</strong> ".$result[0]."</p>\n";
      }
      echo '
            <p><input class="btn center" type="button" value="Close" onclick="javascript:window.close();" /></p>
            </form>
         </body>
    </html>';
      die();
  }
  protected function parseReponseWithReportData($responseHeaders, $responseBody) {
      /*
       * Parsea el xml y devuelve datos de un report en pdf, rtf o xls
       */
        preg_match('/boundary="(.*?)"/', $responseHeaders, $matches);
        $boundary = $matches[1];
        if($boundary==''){
            $this->jasperException($responseBody);
        }
        $parts = explode($boundary, $responseBody);
        $export = null;
        foreach($parts as $part) {
            //print_r($part);
          if (strpos($part, "Content-Type: application/pdf") !== false) {
            $export = substr($part, strpos($part, '%PDF-'));
            break;
          }elseif (strpos($part, "Content-Type: application/rtf") !== false) {
            $export = substr($part, strpos($part, '{\rtf1'));
            break;
          }elseif (strpos($part, "Content-Type: application/xls") !== false) {
              //$pdf = substr($part, (strpos($part, '<report>')+8));
              //print_r($pdf);
              //die();
              $export = substr($part, (strpos($part, '<report>')+8));
              //$pdf = substr($part, strpos($part, 'Java Excel API v2.6'));

              break;
          }if (strpos($part, "Content-Type: application/octet-stream") !== false) {
            $export = substr($part, strpos($part, 'GIF89a'));
            break;
          }
        }

        return $export;
  }

  protected function parseReponseWithList($responseHeaders, $responseBody) {
      /*
       * Parsea un listado de reportes
       */
      $domDocument=DOMDocument::loadXML($responseBody);
      foreach($domDocument->childNodes as $nodos){
          $xml = simplexml_load_string($nodos->nodeValue);
          $array_temp=new ArrayObject();
          foreach ($xml->xpath('//resourceDescriptor') as $xml2){
              if($xml2['wsType']=='reportUnit'){
                  $array_temp[]=array('uriString'=>(string)$xml2['uriString'],'name'=>(string)$xml2['name'], 'label'=>(string)$xml2->label, 'creationDate'=>(string)$xml2->creationDate);
              }
          }

      }

      return $array_temp;
  }

  protected function parseReponseWithListSubreport($responseHeaders, $responseBody) {
      $domDocument=DOMDocument::loadXML($responseBody);
//echo html_entity_decode($responseBody);
      foreach($domDocument->childNodes as $nodos){
          $xml = simplexml_load_string($nodos->nodeValue);

          $array_temp=new ArrayObject();

          foreach ($xml->xpath('//resourceDescriptor') as $xml2){

              foreach($xml2->xpath('//resourceProperty') as $xml3){
                  if($xml3['name']=='PROP_QUERY'){
                      //echo $xml3->value;
                  }
              }

              if($xml2['wsType']=='jrxml'){
                  $array_temp[]=$this->getJrxml($xml2);
              }
              if($xml2['wsType']=='inputControl'){
                  // obtenemos los inputControl
                  $array_temp[]=$this->getInputControl($xml2);
              }
          }

      }

      return $array_temp;
  }

    protected function parseReponseWithResource($responseHeaders, $responseBody, $wsType) {
        /*
         * Parseamos el xml para obtener un recurso en concreto
         */
       $domDocument=DOMDocument::loadXML($responseBody);

      foreach($domDocument->childNodes as $nodos){
          $xml = simplexml_load_string($nodos->nodeValue);
          $array_temp=new ArrayObject();
          foreach ($xml->xpath('//resourceDescriptor') as $xml2){
              if($xml2['wsType']==$wsType){
                  switch($wsType){
                      case 'jdbc':
                          $array_temp[]=array('name'=>(string)$xml2['name']);
                          break;
                      default:
                          $array_temp='';
                          break;
                  }
              }
         }
      }
      
      return $array_temp;
    }
  /* Métodos definitivos */

  protected function getResourceProperty($xml,$name) {
      /*
       * Obtenemos el contenido del resource property
       */
      $return='';

      foreach ($xml->xpath('resourceProperty') as $xml2){
          if($xml2['name']==$name){
            $return=$xml2->value;
          }
      }

      return $return;
  }

  protected function getJrxml($xml){
    /*
     *
     */

      $array_temp=new ArrayObject(array('wsType'=>(string)$xml['wsType'],'name'=>(string)$xml['name'],'uriString'=>(string)$xml['uriString'], 'label'=>(string)$xml->label, 'creationDate'=>(string)$xml->creationDate));

      return $array_temp;
  }
  
  protected function getInputControl($xml){
    /*
     * ----------------------
     * PROP_INPUTCONTROL_TYPE
     * Valores que puede tomar
     * 1 -> Boolean
     
     * 3 -> Single-select List of Values
     * 8 -> Single-select List of Values (radio)
     * 6 -> Multi-select List of Values
     * 10 -> Multi-select List of Values (check box)
     * 4 -> Single-select Query
     * 9 -> Single-select Query (radio)
     * 7 -> Multi-select Query
     * ------------------------------------
     */
      $array_temp=new ArrayObject(array('wsType'=>(string)$xml['wsType'],'uriString'=>(string)$xml['uriString'],'name'=>(string)$xml['name'],'label'=>(string)$xml->label));

      foreach ($xml->xpath('resourceProperty') as $xml2){
          if($xml2['name']=='PROP_INPUTCONTROL_IS_MANDATORY'){
              $array_temp->offsetset('PROP_INPUTCONTROL_IS_MANDATORY',(bool)$xml2->value);
          }
          if($xml2['name']=='PROP_INPUTCONTROL_TYPE'){
              $array_temp->offsetset('PROP_INPUTCONTROL_TYPE',(string)$xml2->value);
          }
          /*
           * 11 -> Multi-select Query (check box)
           */
          if($xml2['name']=='PROP_QUERY_VALUE_COLUMN'){
              $array_temp->offsetset('PROP_QUERY_VALUE_COLUMN',(string)$xml2->value);
          }
          if($xml2['name']=='PROP_QUERY_VISIBLE_COLUMNS'){
              foreach ($xml2->xpath('resourceProperty') as $xml3){
                $array_temp->offsetset('PROP_QUERY_VISIBLE_COLUMN_NAME',(string)$xml3->value);
              }
          }
          /**/
      }
      switch($array_temp['PROP_INPUTCONTROL_TYPE']){
          case 2:
              /*
               * 2 -> Single Value
               */
              break;
          case 11:
              /*
               * 11 -> Multi-select Query (check box)
               */
               $result=$this->getDatawarehouse($this->getBd($this->getDatasource($xml)),$this->getQuery($xml));
               foreach($result as $value){
                   $array_temp2[]=array(
                           'PROP_QUERY_VALUE_COLUMN'=>(int)$value[0][$array_temp['PROP_QUERY_VALUE_COLUMN']],
                           'PROP_QUERY_VISIBLE_COLUMN_NAME'=>(string)$value[0][$array_temp['PROP_QUERY_VISIBLE_COLUMN_NAME']]
                   );
                   $array_temp->offsetset('Multi-select_Query',$array_temp2);
               }
              break;
      }
      
      return $array_temp;
  }

  protected function getInputControlFilter($PROP_INPUTCONTROL_TYPE,$name){
    /*
     * 1 -> Boolean
     * 3 -> Single-select List of Values
     * 8 -> Single-select List of Values (radio)
     * 6 -> Multi-select List of Values
     * 10 -> Multi-select List of Values (check box)
     * 4 -> Single-select Query
     * 9 -> Single-select Query (radio)
     * 7 -> Multi-select Query
     * 11 -> Multi-select Query (check box)
     * ------------------------------------
     */
      switch($PROP_INPUTCONTROL_TYPE){
          case 2:
              /*
               * 2 -> Single Value
               */
              switch($name){
                  case 'Month':
                        return date("m", time());
                      break;
                  case 'Year':
                        return date("Y", time());
                      break;
                  default:
                    return;
                    break;
              }
              break;
          case 11:
              /*
               * 11 -> Multi-select Query (check box)
               */
              switch($name){
                  case 'TYPE_CONTROL':
                      return true;
                      break;
                  default:
                      return false;
                      break;
              }
              break;
          default:
              return;
              break;
      }

  }

  private function getQuery($xml){
      foreach ($xml->xpath('resourceDescriptor') as $xml2){
          if($xml2['wsType']=='query'){
              foreach ($xml2->xpath('resourceProperty') as $xml3){
                  if($xml3['name']=='PROP_QUERY'){
                      return (string)$xml3->value;
                  }
              }
          }
      }
  }

  private function getDatasource($xml){
      foreach ($xml->xpath('resourceDescriptor') as $xml2){
          if($xml2['wsType']=='query'){
              foreach ($xml2->xpath('resourceDescriptor') as $xml3){
                  if($xml3['wsType']=='datasource'){
                      foreach ($xml3->xpath('resourceProperty') as $xml4){
                          if($xml4['name']=='PROP_REFERENCE_URI'){
                              return (string)$xml4->value;
                          }
                      }
                  }
              }
          }
      }
  }

  private function getBd($dataSource){
    $return=$this->getResource($dataSource,'jdbc');
    
    return $return[0]['name'];
  }

private function getDatawarehouse($bd,$sql){

    if($bd=='datawarehouse'){
        require_once ('ossim_db.inc');
        $db = new ossim_db();
        $conn = $db->connect();
        $conn->Execute('use datawarehouse');
        $sql = $sql;
        if ($rs_files = & $conn->Execute($sql)) {
            while (!$rs_files->EOF) {
                $filelist[] = array($rs_files->fields);
                $rs_files->MoveNext();
            }
        }
        
        $db->close($conn);
        return $filelist;
    }
    
    return;
}

public function getResource($uriString,$wsType) {

    $request = '<request operationName="get" locale="en">';
    $request .= '<resourceDescriptor name="" wsType="'.$wsType.'" uriString="'.$uriString.'">';

    $request .= "
        <label>null</label>
        </resourceDescriptor>
      </request>
    ";

    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));

    $return = null;
    try {
        try{
            $result = $client->__soapCall('get', array(
                new SoapParam($request,"requestXmlString")
            ));
        } catch(SoapFault $e){
             //$exception;
        }
        switch($wsType){
          case 'jdbc':
              $return = $this->parseReponseWithResource(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse(),
                $wsType);
              break;
          case 'img':
              $return = $this->parseReponseWithReportData(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse());
              break;
          case 'jrtx':
              $return = $this->getAttachment(
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse(),
                $wsType);
              break;
          default:
              break;
        }
      //print_r($client->__getLastResponse());
    } catch(SoapFault $exception) {
      $responseHeaders = $client->__getLastResponseHeaders();
      if ($exception->faultstring == "looks like we got no XML document" &&
          strpos($responseHeaders, "Content-Type: multipart/related;") !== false) {
        $return = $this->parseReponseWithReportData($responseHeaders, $client->__getLastResponse());
      } else {
        throw $exception;
      }
    }
    
    return $return;
  }

  private function getAttachment($responseHeaders, $responseBody, $wsType){
      if (strpos($responseBody, "![CDATA[The resource was not found]]") == true) {
          return null;
      }
      preg_match('/boundary="(.*?)"/', $responseHeaders, $matches);
        $boundary = $matches[1];
        $parts = explode($boundary, $responseBody);
        $export = null;
        foreach($parts as $part) {
          if($wsType=='jrtx'){
              if (strpos($part, "Content-Type: application/octet-stream") !== false) {
                $export = substr($part, strpos($part, '<?xml version="1.0" encoding="UTF-8"?>'));
                $export = mb_substr($export,0,(strpos($export, '</jasperTemplate>')+strlen('</jasperTemplate>')));
                break;
              }
          }
        }
        if($wsType=='jrtx'){
            $xml = simplexml_load_string($export);
            $i=0;
              foreach($xml->style as $xml2){
                $xml3 = simplexml_load_string($xml2->asXML());
                foreach($xml3->attributes() as $key => $valor){
                    $array_temp2[$key]=(string)$valor;
                }
                $array_temp[$i]=$array_temp2;
                $i++;
              }
                return $array_temp;
            }
  }

  public function getFormatExport($format){
      switch($format){
            case 'pdf':
                $report_format = "PDF";
                break;
            case 'rtf':
                $report_format = "RTF";
                break;
            case 'xls':
                $report_format = "XLS";
                break;
            default:
                return false;
                break;
        }

        return $report_format;
  }

  private function deleteResource($uriString,$uriParent,$wsType) {

      switch($wsType){
          case 'img':
              $resourceDescriptorName='head.gif';
              break;
          case 'jrtx':
              $resourceDescriptorName='Style.jrtx';
              break;
          default:
              return null;
              break;
      }

    $request = '<request operationName="delete" locale="en">';
    $request .= '<argument name="MODIFY_REPORTUNIT_URI">/'.$uriParent.'</argument>';
    $request .= '<resourceDescriptor name="'.$resourceDescriptorName.'" wsType="'.$wsType.'" uriString="/'.$uriString.'">';
    $request .= "
        </resourceDescriptor>
      </request>
    ";
      
    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));

    $return = null;
    try {
        try{
            $result = $client->__soapCall('delete', array(
                new SoapParam($request,"requestXmlString")
            ));
        } catch(SoapFault $e){
             //$exception;
        }
    } catch(SoapFault $exception) {
      
    }

    return $return;
  }

  public function putResource($uriString,$uriParent,$wsType,$file) {
        /*
         * Posible Bug al hacer un put - actualiza las tablas JIFileResource y JIResource pero no actuliza la tabla JIReportUnitResource
         * Temporalmente actualizamos directamente en la bd
         */
         require_once ('ossim_conf.inc');
         $path_conf = $GLOBALS["CONF"];
         require_once ('ossim_db.inc');
         /* database connect */
        $db = new ossim_db();
        //$temp_conn = $db->connect();
        $temp_conn = $db->custom_connect('localhost',$path_conf->get_conf("ossim_user"),$path_conf->get_conf("ossim_pass"));
        $temp_conn->Execute('use jasperserver');
        // obtenemos el id del reporte
        $sql='SELECT * FROM JIResourceFolder WHERE uri LIKE ?';
        $nameReport='/'.$uriParent.'_files';
        if (!$result = & $temp_conn->Execute($sql,array($nameReport))) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                $idReport=$result->fields["id"]
            );
            $result->MoveNext();
        }
        // obtenemos el id del recurso
        $sql='SELECT * FROM JIResource WHERE name LIKE ? AND parent_folder=?';
        $nameResource=explode($nameReport.'/', '/'.$uriString);
        if (!$result = & $temp_conn->Execute($sql,array($nameResource[1],$idReport))) {
            print $temp_conn->ErrorMsg();
            return;
        }
        while (!$result->EOF) {
            $tmp_array = array(
                $idResource=$result->fields["id"]
            );
            $result->MoveNext();
        }
        // guardamos el recurso en la bd
        $sql='UPDATE JIFileResource SET data=? WHERE id=? LIMIT 1';
        if (!$result = & $temp_conn->Execute($sql,array($file,$idResource))) {
            print $temp_conn->ErrorMsg();
            return;
        }
        //print_r($result);
        $db->close($temp_conn);

        return $result;
         //die();
      /*
      require_once('SOAP/Client.php');
      switch($wsType){
          case 'img':
              $resourceDescriptorName='head.gif';
              $iii='true';
              break;
          case 'jrtx':
              $resourceDescriptorName='Style.jrtx';
              $iii='true';
              break;
          default:
              return null;
              break;
      }
    $request = '<request operationName="put" locale="en">'."\n";
    $request .= '<resourceDescriptor name="'.$resourceDescriptorName.'" wsType="'.$wsType.'" uriString="'.'/'.$uriString.'" isNew="true">'."\n";
    $request .= '
        <label>'.$resourceDescriptorName.'</label>
        <description>'.$resourceDescriptorName.'</description>
        <resourceProperty name="PROP_HAS_DATA">
            <value><![CDATA[true]]></value>
        </resourceProperty>';
      if($wsType=='jrtx'){
      $request .= '
      <resourceProperty name="PROP_VERSION">
			<value><![CDATA[1]]></value>
		</resourceProperty>';
      }
    $request .= '
        <resourceProperty name="PROP_PARENT_FOLDER">
            <value><![CDATA['.'/'.$uriParent.'_files]]></value>
        </resourceProperty>
        </resourceDescriptor>
      </request>
    ';
//echo $request;
      $client = new SOAP_client($this->url, false, false, array("user" => $this->username, "pass" => $this->password));

      $attachment = array("body"=>$file, "content_type"=>"application/octet-stream", "cid"=>"123456".$wsType);

      $client->_options['attachments'] = 'Dime';
      //var_dump($attachment);
      $client->_attachments = array( $attachment );
      
      $params = array("request" => $request);
      $this->deleteResource($uriString,$uriParent,$wsType);
      $response = $client->call("put",$params,array('namespace' => "http://www.jaspersoft.com/namespaces/php"));
     //die();
    // echo $response;
      return $response;
       * */
  }

  public function copyResource($uriString,$uriParent,$wsType,$file) {
    switch($wsType){
          case 'img':
              $resourceDescriptorName='head.gif';
              break;
          case 'jrtx':
              $resourceDescriptorName='Style.jrtx';
              break;
          default:
              return null;
              break;
      }
    $request = '<request operationName="copy" locale="en">'."\n";
    $request .= '<argument name="DESTINATION_URI">'.'/'.$uriString.'</argument>'."\n";
    $request .= '<resourceDescriptor name="'.$resourceDescriptorName.'" wsType="'.$wsType.'" uriString="'.'/'.$file.'">'."\n";
    $request .= '
        </resourceDescriptor>
      </request>
    ';


    $client = new SoapClient(null, array(
        'location'  => $this->url,
        'uri'       => 'urn:',
        'login'     => $this->username,
        'password'  => $this->password,
        'trace'    => 1,
        'exception'=> 1,
        'soap_version'  => SOAP_1_1,
        'style'    => SOAP_RPC,
        'use'      => SOAP_LITERAL

      ));
    $this->deleteResource($uriString,$uriParent,$wsType);
    try {
        try{
            $result = $client->__soapCall('copy', array(
                new SoapParam($request,"requestXmlString")
            ));
        } catch(SoapFault $e){
             //$exception;
        }
    } catch(SoapFault $exception) {

    }

    return $result;
  }
  public function setJrtx($uriStyle,$uriParent,$parameters){

      $xml='<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE jasperTemplate PUBLIC "-//JasperReports//DTD Template//EN" "http://jasperreports.sourceforge.net/dtds/jaspertemplate.dtd">

<jasperTemplate>
	<style name="background_title" isDefault="false" backcolor="'.$parameters['backgroundTitle'].'"/>
	<style name="txt_title" isDefault="false" forecolor="'.$parameters['colorTitle'].'"/>
	<style name="background_subtitle" isDefault="false" backcolor="'.$parameters['backgroundSubtitle'].'"/>
	<style name="txt_subtitle" isDefault="false" forecolor="'.$parameters['colorSubtitle'].'"/>
        <style name="txt_content" isDefault="false" forecolor="'.$parameters['colorContent'].'"/>
</jasperTemplate>';

      $this->putResource($uriStyle,$uriParent,'jrtx',$xml);
      return;
  }
/*
 *
 * List Helpers
 *
 */
  public function getInputControlHTML($xml, $elements = null){
      /*
       * 
       */
      $html='';

      $iterator = $xml->getIterator();

      while($iterator->valid()){
          $iterator2=$iterator->current();

          if($iterator2['wsType']=='inputControl'){
              switch($iterator2['PROP_INPUTCONTROL_TYPE']){
                  case 2:
                      /*
                       * 2 -> Single Value
                       */
                      if($elements!=null || $iterator2['name']=='reportUser' || $iterator2['name']=='reportWWW'){
                          break;
                      }
                      $html.='<li>';
                      $id_temp=str_replace('/','_',$iterator2['uriString']);
                      if($iterator2['name']=='Year'||$iterator2['name']=='Month'){
                        $html.='<div class="widget">';
                      }
                      $html.='<label for="'.$id_temp.'">'._($iterator2['label']).'</label>';
                      $html.='<input type="text" id="'.$id_temp.'"name="'.$iterator2['name'].'" value="'.$this->getInputControlFilter($iterator2['PROP_INPUTCONTROL_TYPE'],$iterator2['name']).'" />';
                      if($iterator2['name']=='Year'||$iterator2['name']=='Month'){
                        $html.='<div class="widgetCalendar" id="'.$id_temp.'_widgetCalendar"></div></div>';
                      }
                      $html.='</li>';
                      break;
                  case 11:
                      /*
                       *  11 -> Multi-select Query (check box)
                       */
                      if($iterator2['name']=='TYPE_CONTROL'&&$elements==null){
                          break;
                      }
                      $html.='<input type="hidden" name="isListItem" value="'.$iterator2['name'].'" />';
                      foreach($iterator2['Multi-select_Query'] as $key => $value){
                          $html.='<li>';
                          $html.='<input type="checkbox" class="inputCheckbox" name="'.$iterator2['name'].'_'.$key.'" value="'.$value['PROP_QUERY_VALUE_COLUMN'].'"';
                          if($this->getInputControlFilter($iterator2['PROP_INPUTCONTROL_TYPE'],$iterator2['name'])){
                              $html.='checked';
                          }
                          $html.='> '._($value['PROP_QUERY_VISIBLE_COLUMN_NAME']).'<br>';
                          $html.='</li>';
                      }
                      break;
                  default:
                     break;
              }
          }
        $iterator->next();
      }
      return $html;

  }

  public function getSubReportHtml($xml){
      /*
       *
       */
      $html='';

      $iterator = $xml->getIterator();
      while($iterator->valid()){
          $iterator2=$iterator->current();
          if($iterator2['wsType']=='jrxml'){
              $html.='<li style="text-align: left">';
              $html.='<a href="jasper-report-pdf.php?reportUnit='.$iterator2['uriString'].'" target="_blank">';
              $html.=_($iterator2['label']);
              $html.='</a> <i> ('.date("Y-m-d H:i:s",$iterator2['creationDate']/1000).')</i>';
              $html.='</li>';
          }
        $iterator->next();
      }

      return $html;

  }

  public function getHeaderExportHtml($report_unit,$format,$params = null){
      switch($format){
            case 'PDF':
                $contentType = 'application/pdf';
                $extension = '.pdf';
                break;
            case 'RTF':
                $contentType = 'application/msword';
                $extension = '.rtf';
                break;
            case 'XLS':
                $contentType = 'application/vnd.ms-excel';
                $extension = '.xls';
                break;
            default:
                return false;
                break;
      }
      header('Content-type: '.$contentType);
      header("Cache-Control: public, must-revalidate");
      header("Pragma: hack");
      //header('Content-Disposition: attachment; filename="'.$report_unit.$params.$extension.'"');
      header('Content-Disposition: inline; filename="'.$report_unit.$params.$extension.'"');
      //header("Content-Transfer-Encoding: binary ");

      return;
  }

  public function getParameterHtml($report_unit){
        $url='jasper_include/parameter/';
        $file=$url.$report_unit.'.inc';

        if (file_exists($file)) {
            return $file;
        } else {
            return false;
        }
    }
  public function getPermission($report_unit){
      switch($report_unit){
          case 'Alarms_Report':
              $permission1='MenuIncidents';
              $permission2='ControlPanelAlarms';
              break;
          case 'Business_and_Compliance_ISO_PCI_Report':
              $permission1='MenuReports';
              $permission2='ReportsReportServer';
              break;
          case 'Geographic_Report':
              $permission1='MenuEvents';
              $permission2='EventsForensics';
              break;
          case 'Incidents_Report':
              $permission1='MenuReports';
              $permission2='ReportsReportServer';
              break;
          case 'Metrics_Report':
              $permission1='MenuControlPanel';
              $permission2='ControlPanelMetrics';
              break;
          case 'Security_DB_Destination_Port':
          case 'Security_DB_Events':
          case 'Security_DB_Sensors':
          case 'Security_DB_Source_Port':
          case 'Security_DB_Unique_Address':
          case 'Security_DB_Unique_Country_Events':
          case 'Security_DB_Unique_Events':
          case 'Security_DB_Unique_Plugin':
          case 'Security_Report':
          case 'SIEM_Events_Unique_IP_Links':
              $permission1='MenuEvents';
              $permission2='EventsForensics';
              break;
          case 'SEM_Report':
              $permission1='MenuEvents';
              $permission2='ControlPanelSEM';
              break;
          default:
              return true;
              break;

      }
      Session::logcheck($permission1, $permission2);
  }
}

class UpdateSql {
  private $name;
  private $url='jasper_include/updateSql/';
  private $file;

  public function __construct($name) {
    $this->name=$name;
    $this->file=$this->url.$this->name.'.inc';
  }

  public function update(){
    if (file_exists($this->file)) {
        require($this->file);
    } else {
        return false;
    }
  }
}

class FormatText{
  private $name;

   public function __construct($name){
       $text = self::limpiar_acentos(trim($name));
       $text = strtolower($text);
       // strip all non word chars
       $text = preg_replace('/\W/', ' ', $text);
       // replace all white space sections with a dash
       $text = preg_replace('/\ +/', '-', $text);
       // trim dashes
       $text = preg_replace('/\-$/', '', $text);
       $text = preg_replace('/^\-/', '', $text);

       $this->name=$text;
  }
        /** ELIMINA ACENTOS **/
    private static function limpiar_acentos($s){
        $s = ereg_replace("á|à|â|ã|ª","a",$s);
        $s = ereg_replace("Á|À|Â|Ã","A",$s);
        $s = ereg_replace("Í|Ì|Î","I",$s);
        $s = ereg_replace("í|ì|î","i",$s);
        $s = ereg_replace("é|è|ê","e",$s);
        $s = ereg_replace("É|È|Ê","E",$s);
        $s = ereg_replace("ó|ò|ô|õ|º","o",$s);
        $s = ereg_replace("Ó|Ò|Ô|Õ","O",$s);
        $s = ereg_replace("ú|ù|û","u",$s);
        $s = ereg_replace("Ú|Ù|Û","U",$s);
        $s = str_replace("ç","c",$s);
        $s = str_replace("Ç","C",$s);
        $s = str_replace("ñ","n",$s);
        $s = str_replace("Ñ","N",$s);
        
        return $s;
    }
    public function getName(){
        return $this->name;
    }
}
?>
