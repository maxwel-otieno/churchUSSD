<?php

function saveArray($SESSIONFILE, $selected_item, $index) {
    $ussdArrayString = getArrayFromFile($SESSIONFILE);
    $ussdArray = explode("|", $ussdArrayString);
    $ussdArray[$index] = $selected_item;
    $newUssdString = implode("|", $ussdArray);
    writeToFile($SESSIONFILE, $newUssdString);
}

function writeToFile($fileName, $messageArray) {
    //flog('debug', "WR".$messageArray);
    $handleFile = fopen($fileName, "w");
    fwrite($handleFile, $messageArray);
    fclose($handleFile);
    return true;
}

function getArrayFromFile($fileName) {
    $handleFile = fopen($fileName, "r");
    if ($handleFile) {
        $message = fgets($handleFile, 4096);
    } else {
        $message = "Could not read from file - $fileName<br/>";
    }
    fclose($handleFile);
    return $message;
}

function getIndexValue($SESSIONFILE, $index) {
    $value = "null";
    $ussdArrayString = getArrayFromFile($SESSIONFILE);
    $ussdArray = explode("|", $ussdArrayString);
    $value = $ussdArray [$index];
    return $value;
}

function flog($file, $string, $logTitle = 'CUSTOM', $lineNo = '', $function = '') {
    $date = date("Y-m-d H:i:s");
    if ($fo = fopen($file, 'ab')) {
        fwrite($fo, "$date - [ $logTitle ] :$lineNo $function | $string\n");
        fclose($fo);
    } else {
        trigger_error("flog Cannot log '$string' to file '$file' ", E_USER_WARNING);
    }
}

function selectSQLi($query, $db_connect) {
    global $fatalLogs, $sqlLogs;
    $start = microtime_f();
    $result = mysqli_query($db_connect,"$query") or loqError($fatalLogs, 'selectSQLi', mysqli_error($db_connect), $query);
    $stop = microtime_f();
    $time = $stop - $start;
    // flog($sqlLogs, "selectSQLi() |" . sprintf(" %01.4f ", $time) . "| $query");
    return $result;
}

function insertSQLi($query, $db_connect) {
    global $fatalLogs, $sqlLogs;
    $start = microtime_f();
    mysqli_query($db_connect,"$query") or loqError($fatalLogs, 'insertSQLi', mysqli_error($db_connect), $query);
    $stop = microtime_f();
    $time = $stop - $start;
    // flog($sqlLogs, "insertSQLi() |" . sprintf(" %01.4f ", $time) . "| $query");
    return mysqli_insert_id($db_connect);
}

function updateSQLi($query, $db_connect) {
    global $fatalLogs, $sqlLogs;
    $start = microtime_f();
    mysqli_query($db_connect,"$query") or loqError($fatalLogs, 'updateSQLi', mysqli_error($db_connect), $query);
    $stop = microtime_f();
    $time = $stop - $start;
    // flog($sqlLogs, "updateSQLi() |" . sprintf(" %01.4f ", $time) . "| $query");
    return mysqli_affected_rows($db_connect);
}

function loqError($log, $function, $error, $query = "") {
    global $fatalLogs;
    flog($log, "$function | $error on | $query");
    if (($function == 'updateSQLi' or $function == 'insertSQLi')) {
        flog($fatalLogs, $query);
    }
}

function microtime_f() {
    list ($msec, $sec) = explode(" ", microtime());
    return ((float) $msec + (float) $sec);
}


function get_session_data($key){

    if(!empty($key)){
        global $tempfileName;
        $file_contents = file_get_contents($tempfileName);
        $data_array = json_decode($file_contents, true);
        return $data_array[$key];
    }
    else{
        return false;
    }
}

function set_session_data($key,$data){

    if(!empty($key) && !empty($data)){

        $data_array[$key] = $data;
        global $tempfileName;
        if (!file_exists($tempfileName)){
            file_put_contents($tempfileName, json_encode($data_array), FILE_TEXT | LOCK_EX);
            chmod($tempfileName, 0776);
            return true;
        }
        else{
            $file_contents = file_get_contents($tempfileName);
            $current_array = json_decode($file_contents, true);
            $current_array[$key] = $data;
            array_push($current_array,array($key=>$data));
            file_put_contents($tempfileName, json_encode($current_array), FILE_TEXT | LOCK_EX);
            return true;
        }
       
    }
    else{
        return false;
    }
}

function send_sms($sms_data){
 global $infoLog;
    $api_key = (!empty($sms_data['api_key']))? $sms_data['api_key']:"t3eXf8nf4l9x48a5dN4282gtbz5NXe4dz2cr3qA286DQc6F19dmg7cbe1343yxP9";
    $shortcode = "VOSH_Buru";
    $serviceId = '0';

    $smsdata = array(
        'api_key' => $api_key,
        'shortcode' => (!empty($sms_data['shortcode']))?$sms_data['shortcode']:$shortcode,
        'mobile' => $sms_data['mobile'],
        'message' => $sms_data['message'],
        'service_id' => $serviceId,
        'response_type' => "json",
        );

    $smsdata_string = json_encode($smsdata);
    $smsURL = "https://api.tililtech.com/sms/v3/sendsms";
   
    $options = array(
      'http' => array(
        'header'  => "Content-type: application/json\r\n"."Content-Length: " . strlen($smsdata_string) . "\r\n",
        'method'  => 'POST',
        'content' => $smsdata_string,
      ),
    );
    $context  = stream_context_create($options);
    $apiresult = file_get_contents($smsURL, false, $context);

    if (empty($apiresult)) { 
        flog($errorLog, "[".$sms_data['mobile']."] sms: ERROR on URL[$smsURL] | error[" . curl_error($ch) . "] | error code[" . curl_errno($ch) . "]\n");    
    }
    else{
        $arr_response = json_decode($apiresult,true);
        $response_details =  $arr_response[0];

        if(!empty($response_details['status_code']) && $response_details['status_code']!=='1000'){
            flog($infoLog, "[".$sms_data['mobile']."] sms: $apiresult");    
        }else{
             flog($infoLog, "[".$sms_data['mobile']."] sms: $apiresult");    
        }
    }

}


function remove_dump_files($dirName){
    $d = dir($dirName);
    $lastModified = 0;
    while($entry = $d->read()) {
        if ($entry != "." && $entry != "..") {
            if (!is_dir($dirName."/".$entry)) {
                $currentModified = filemtime($dirName."/".$entry);
                $t = time();              
                $difference_time = intval($t)-intval($currentModified);
                if($difference_time>600){
                    unlink($dirName."/".$entry);
                }
                
            }
        }
    }
    $d->close();
}