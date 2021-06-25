<?php

$dbname = 'church_booking';
$dbuser = 'root';
$pass = '';
$host = 'localhost';

//Connect to the databse
$dsn = "mysql:dbhost = ".$host.";dbname=".$dbname;
$pdo = new PDO($dsn, $dbuser, $pass);
// $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

//RECCURING FUNCTIONS
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function changeToArray($fileString){
    $fileArray = explode("|", $fileString);
    return $fileArray;
}

function changeToString($fileArray){
    $fileString = implode("|", $fileArray);
    return $fileString;
}

function writeFiles($fileName, $whatToWrite){
    $openedFile = fopen($fileName, "w");
    // changeToArray($whatToWrite);
    fwrite($openedFile, $whatToWrite);
    fclose($openedFile);
}
function readFiles($fileName){
    $openedFile = fopen($fileName, "r"); 
    $theFile = fread($openedFile, filesize($fileName));
    fclose($openedFile);
    return $theFile; 
}

function nextLevel(){
    $sessionDataArray = changeToArray(readFiles("session_files"));
    $nextLevel = $sessionDataArray[3];
    return $nextLevel;
}
function prevLevel(){
    $sessionDataArray = changeToArray(readFiles("session_files"));
    $prevLevel = $sessionDataArray[5];
}

// function set_session_data($fileName, $num){
//     $sessionDataArray[4] = $num;
//     writeFiles($fileName, changeToString($sessionDataArray));
//  }

function fetchDB($table, $columnName, $ID){
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM $table WHERE $columnName = ?");
    $stmt->execute([$ID]);
    $row = $stmt->fetch(PDO::FETCH_OBJ);

    $rowCount = $stmt->rowCount();
    $dbData = [$row, $rowCount];
    return $dbData;

    // return $row;
}

//Fetch all from the DB
function fetchAllDB($table, $columnName, $ID){
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM $table WHERE $columnName = ?");
    $stmt->execute([$ID]);
    $row = $stmt->fetchAll(PDO::FETCH_OBJ);

    $rowCount = $stmt->rowCOunt();
    $dbData = [$row, $rowCount];
    return $dbData;
}

function deleteDB($table, $column, $ID){
    $stmt = $GLOBALS['pdo']->prepare("DELETE FROM $table WHERE $column = ?");
    $stmt->execute([$ID]);
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

function send_sms($sms_data){
    global $infoLog;
    $api_key = (!empty($sms_data['api_key']))? $sms_data['api_key']:"FI69rM2L43OY7wKgki0bChuomEGUSecVJBQXDx8fjTdqaNPRvnzA1lyWs5pHtZ";
    $shortcode = 'Tilil';
    $serviceId = '0';

    $smsdata = array(
        'api_key' => $api_key,
        'shortcode' => (!empty($sms_data['shortcode']))?$sms_data['shortcode']:$shortcode,
    // 'shortcode' => $sms_data['shortcode'],
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
        // print_r($errorLog, "[".$sms_data['mobile']."] sms: ERROR on URL[$smsURL] | error[" . curl_error($ch) . "] | error code[" . curl_errno($ch) . "]\n");
    }
    else{
        $arr_response = json_decode($apiresult,true);
        $response_details =  $arr_response[0];

        if(!empty($response_details['status_code']) && $response_details['status_code']!=='1000'){
            //Failed
            flog($infoLog, "[".$sms_data['mobile']."] sms: $apiresult");    
            // print_r($infoLog, "[".$sms_data['mobile']."] sms: $apiresult");
        }else{
            //Success
            flog($infoLog, "[".$sms_data['mobile']."] sms: $apiresult");  
            // print_r($infoLog, "[".$sms_data['mobile']."] sms: $apiresult");
        }
    }
   
}