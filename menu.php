<?php

/**
 * USSD API
 * Author: Daniel Nyanumba
 * Email: danznya@gmail.com
 * Date: 2015-01-01
 */
// session_start();
// include '_utils.php';


// $fatalLogs = str_ireplace("\\", "/", __DIR__) . '/logs/fatal.log';
// $sqlLogs = str_ireplace("\\", "/", __DIR__) . '/logs/sql.log';
// $infoLog = str_ireplace("\\", "/", __DIR__) . '/logs/info.log';
// $errorLog = str_ireplace("\\", "/", __DIR__) . '/logs/error.log';

//db connection 
// $db_connecti = mysqli_connect("localhost", "root", "") or die("0|Error 1; Service cannot be processsed at the moment. Please try again later.|null|null|end|null");
// $db_connecti = mysqli_connect("localhost", "root", "nqW2k7eEa2mgwf9") or die("0|Error 1; Service cannot be processsed at the moment. Please try again later.|null|null|end|null");
// mysqli_select_db($db_connecti, 'church_service') or die("0|Error 2; Service cannot be processsed at the moment. Please try again later.|null|null|end|null");

//Get the variables from the USSD gateway
$SESSIONID = $_GET["SESSIONID"];
$USSDCODE = rawurldecode($_GET["USSDCODE"]);
$MSISDN = $_GET["MSISDN"];
$INPUT = rawurldecode($_GET["INPUT"]);

// print_r($_GET);
// exit();

$church = 'VOSH Church Int\'l- Buruburu';

//general messages | error
$message = "0|An unknown error occured. Please try again after some time.|null|null|end|null";
//menu
$mainMenu ="\n1. Update Details\n2. Attend Service\n0. Exit:";

$filePathDir = str_ireplace("\\", "/", __DIR__) . '/sessionFiles/';

remove_dump_files($filePathDir);

//session file
$fileName = $filePathDir . "" . $SESSIONID . "_" . $MSISDN;
$tempfileName = $filePathDir . "" . $SESSIONID . "_" . $MSISDN.'_tmp';

if (!file_exists($fileName)) {
    $TEMPLEVEL = '1';

    $ussdArray[0] = $SESSIONID;
    $ussdArray[1] = $MSISDN;
    $ussdArray[2] = $USSDCODE;
    $ussdArray[3] = $TEMPLEVEL;
    $ussdArrayString = implode("|", $ussdArray);
    $ussdArrayString = $ussdArrayString . "|null|null|null|null|null|null|null|null|null|null|null|null|null|null|null|null";

    writeToFile($fileName, $ussdArrayString);
    //chown($filePath, "apache.appDev");
    chmod($fileName, 0776);
} else {
    $ussdArrayString = getArrayFromFile($fileName);
}
$theArray = explode('|', $ussdArrayString);
$TEMPLEVEL = $theArray[3];
// $EXTRA = $theArray[19]; //either null or value_

flog($infoLog, "Session: [$MSISDN] $SESSIONID | $TEMPLEVEL | $INPUT");

//INPUT can be 33 or 33*2*Dan etc depending on level where the user is in the session
//Note that this can be used together with the session id to know which level the user is so that you can display a different menu
$inputArray = explode("*", $INPUT);
//the last value after * is what the user entered last
$lastInput = trim($inputArray[sizeof($inputArray) - 1]);

//if on a shared ussd, the initial input will be the identifier of the shared code. e.g *658*33# .. this input will be 33
//-------------------------------------------------------TEMPLEVEL == 1---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 1) {
    //check if  user is registered or not, if user is registered then diplay a menu for update
    // $message = "2|Welcome to XYZ [$MSISDN]. Select an option:$mainMenu|null|null|null|null";

    $result = selectSQLi("select member_id,full_name from members where mobile='$MSISDN' order by member_id asc limit 1;", $db_connecti);
    if (mysqli_num_rows($result) > '0') {
        $row = mysqli_fetch_array($result);
        $id = $row['member_id'];
        $names = $row['full_name'];
        $message = "1001|Welcome $names to $church.$mainMenu|null|null|null|$names";
        set_session_data('names', $names);
    } else {
        $id = insertSQLi("INSERT INTO members (`mobile`, `step`, `createdon`) VALUES ('$MSISDN', '1', now())", $db_connecti);
        $message = "2|Welcome to $church member registration.\nPlease enter your full name\n[e.g Edwin Morgan]:|null|null|null|null";
    }
    saveArray($fileName, $id, 7);
}

//-------------------------------------------------------TEMPLEVEL == 2---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 2) {

    if(is_numeric($lastInput) && intval($lastInput)==99){
        $message = "3|Enter your year of Birth ( 99 to skip )\n [e.g. 1990]:|null|null|null|null";
    }
    else{
            $full_name = ucwords(str_ireplace("'", "\'", $lastInput));
            $names_x = explode(' ', $full_name);
            $count = count($names_x);
        if ($count>= 2) {
            saveArray($fileName, $full_name, 9);
            set_session_data('names', $full_name);
            $member_id = getIndexValue($fileName, 7);
            updateSQLi("update members set full_name='$full_name', step=2,updatedby=$member_id,updatedon=current_timestamp()  where member_id=".$member_id, $db_connecti);
            $message = "3|Enter your year of Birth ( 99 to skip )\n [e.g. 1990]:|null|null|null|null";
        }
        else{
            $message = "2|Please enter your full name ( 99 to skip )\n[e.g. Edwin Morgan]:|null|null|null|null";
        }
    }
   
}


//-------------------------------------------------------TEMPLEVEL == 3---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 3) {
    
    if(is_numeric($lastInput) && intval($lastInput)==99){
        $message = "4|Enter your Place of Residence ( 99 to skip ):|null|null|null|null";
    }
    else{
        $dob = intval(trim($lastInput));    
        if(!empty($dob) && (intval(date('Y'))-$dob)>13){
            saveArray($fileName, $dob, 10);
            $member_id = getIndexValue($fileName, 7);
            updateSQLi("update members set dob='$dob', step=3,updatedby=$member_id,updatedon=current_timestamp() where member_id=".$member_id, $db_connecti);
            $message = "4|Enter your Place of Residence ( 99 to skip ):|null|null|null|null";
        }
        else{
            $message = "3|Enter your valid year of Birth ( 99 to skip ):|null|null|null|null";
        }
    }
}


//-------------------------------------------------------TEMPLEVEL == 4---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 4) {
    if(is_numeric($lastInput) && intval($lastInput)==99){

        $cell_groups = selectSQLi("SELECT cell_id,cell_name FROM church_service.cell_group order by cell_name asc;", $db_connecti);
        $cells_menu ='';
        if (mysqli_num_rows($cell_groups) > 0) {            
            $cells_ids ='';
            $cells_menu ='';
            $menu_index = 1;
           while($row = mysqli_fetch_array($cell_groups)){
                $cells_menu = $cells_menu."\n".$menu_index.". ".$row['cell_name'];
                $cells_ids[$menu_index] = $row['cell_id'];
                $menu_index = $menu_index+1;
            }
            set_session_data('cells_ids',$cells_ids);
            set_session_data('cells_menu',$cells_menu);
         }
        $message = "5|Select your Cell Group ( 99 to skip ):$cells_menu|null|null|null|null";
    }
    else{
    $residence = trim(str_ireplace("'", "\'", $lastInput));
    if(!empty($residence)){
        saveArray($fileName, $residence, 11);
        $member_id = getIndexValue($fileName, 7);
        updateSQLi("update members set residence='$residence', step=4,updatedby=$member_id,updatedon=current_timestamp() where member_id=".$member_id, $db_connecti);
        //get all the cells from the database---
        $cell_groups = selectSQLi("SELECT cell_id,cell_name FROM church_service.cell_group order by cell_name asc;", $db_connecti);
        $cells_menu ='';
        if (mysqli_num_rows($cell_groups) > 0) {            
            $cells_ids ='';
            $cells_menu ='';
            $menu_index = 1;
           while($row = mysqli_fetch_array($cell_groups)){
                $cells_menu = $cells_menu."\n".$menu_index.". ".$row['cell_name'];
                $cells_ids[$menu_index] = $row['cell_id'];
                $menu_index = $menu_index+1;
            }
            set_session_data('cells_ids',$cells_ids);
            set_session_data('cells_menu',$cells_menu);
         }
        $message = "5|Select your Cell Group ( 99 to skip ):$cells_menu|null|null|null|null";
    }
    else{
        $message = "4|Enter your Place of Residence  ( 99 to skip ):|null|null|null|null";
    }
}

}


//-------------------------------------------------------TEMPLEVEL == 5---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 5) {
    if(is_numeric($lastInput) && intval($lastInput)==99){
        $names = get_session_data('names');
        $flash ='You accepted the details';  
        $message = "0|Thank you $names. {$flash}.|null|null|end|null";
    }
    else{
        $cell_id = intval(trim($lastInput));
        if(!empty($cell_id)){
            $cells_ids = get_session_data('cells_ids');
            updateSQLi("update members set cell_group=". $cells_ids[$cell_id].", step=5 where member_id=".getIndexValue($fileName, 7), $db_connecti);

            $result = selectSQLi("select member_id,full_name,mobile,dob,residence,cell_group.cell_name as cell_group from members inner join cell_group on (members.cell_group=cell_group.cell_id) where mobile='$MSISDN' order by member_id asc limit 1;", $db_connecti);
            if (mysqli_num_rows($result) > '0') {
                $row = mysqli_fetch_array($result);            
                $message = "6|Confirm Member Details:\nFull Name: ".$row['full_name']."\nYear of Birth: ".$row['dob']."\nResidence: ".$row['residence']."\nCell Group: ".$row['cell_group']."\n\n 1.Main Menu |null|null|null|null";
            }
            else{
                $message = "6|Confirm Details:\nFull Name:".getIndexValue($fileName, 9)."\nYear of Birth: ".getIndexValue($fileName, 10)."\nResidence: ".getIndexValue($fileName, 11)."\n\n1. Correct\n2. Cancel|null|null|null|null";
            }
        }
        else{
            $message = "5|Select your Cell Group ( 99 to skip ):".get_session_data('cells_menu')."|null|null|null|null";
        }
}
}


//-------------------------------------------------------TEMPLEVEL == 6---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 6) {
    $names = get_session_data('names');
    if($lastInput == '1'){
        $flash ='You accepted the details';  
        $sms_register = "Dear " .$names. ", thank you for registering with $church. We hope you enjoy our Services.";
        send_sms(array('mobile'=>$MSISDN,'message'=>$sms_register));
    }else{
        $flash ='You cancelled the details';
    } 
    $message = "0|Thank you $names. {$flash}.|null|null|end|null";
}


//-------------------------------------------------------TEMPLEVEL == 1001---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 1001) {

    if ($lastInput == '1') {
        $message = "1002|Update Details Menu.\n1. Check Details\n2. Change Details\n0. Exit|null|null|null|null";
    } 
    elseif($lastInput == '2') {
          //check if the user finished the steps if not return the user to the last step
          $id = getIndexValue($fileName, 7);
          $result = selectSQLi("select (case when sd.service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) AND unix_timestamp(concat(current_date(),' ',show_from))<=unix_timestamp() then DATE_ADD(current_date(), INTERVAL 1 DAY) ELSE current_date() END) as service_date, service_id,concat(sd.day_name,' [ ',TIME_FORMAT(sd.time_from, '%h:%i %p') ,' - ',TIME_FORMAT(sd.time_to, '%h:%i %p'),' ]') as day_name,st.type_name from service_attendance sa inner join members m on (m.member_id=sa.member_id) left join service_day sd on (sa.service_day=sd.day_id) left join service_type st on (sa.service_type=st.type_id) where (sd.service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) or (sd.service_day=dayofweek(current_date()) AND unix_timestamp(concat(current_date(),' ',time_to))>=unix_timestamp())) and sa.service_date=(case when sd.service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) AND unix_timestamp(concat(current_date(),' ',show_from))<=unix_timestamp() then DATE_ADD(current_date(), INTERVAL 1 DAY) ELSE current_date() END) AND m.member_id=$id  and sa.service_status=0 order by service_id asc limit 1;", $db_connecti);
          if (mysqli_num_rows($result) > '0') {
              $row = mysqli_fetch_array($result); 
              $service_id = $row['service_id'];  
              $message = "1|Your Service attendance Details:\nService Date: ".$row['service_date']."\nService Type: ".$row['type_name']."\nDay: ".$row['day_name']."\n\n 1.Main Menu |null|null|null|null"; 
          }
          else{              
              $result = selectSQLi("SELECT type_id,type_name FROM church_service.service_type where type_status=1;", $db_connecti);
              if (mysqli_num_rows($result) > '0') {
                  $service_options = '';
                  while($row = mysqli_fetch_array($result)){
                    $service_options = $service_options."\n".$row['type_id'].". ".$row['type_name'];
                  }           
                  $message = "2001|Select Service type:$service_options|null|null|null|null";
                  set_session_data('service_options',$service_options);
              }
              else{
                $message = "0|Sorry No Worship Service available.|null|null|end|null";
              }
          }
    } 
    elseif($lastInput=='0'){
        $message = "0|Thank you for using our services.|null|null|end|null";
    }
    else {
        $message = "1|Invalid selection. $mainMenu|null|null|null|null";
    }
}


//-------------------------------------------------------TEMPLEVEL == 1002---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 1002) {

    if ($lastInput == '1') {
        $result = selectSQLi("select member_id,full_name,mobile,dob,residence,cell_group.cell_name as cell_group from members inner join cell_group on (members.cell_group=cell_group.cell_id) where mobile='$MSISDN' order by member_id asc limit 1;", $db_connecti);
        if (mysqli_num_rows($result) > '0') {
            $row = mysqli_fetch_array($result);            
            $message = "1|Church Member Details:\nFull Name: ".$row['full_name']."\nYear of Birth: ".$row['dob']."\nResidence: ".$row['residence']."\nCell Group: ".$row['cell_group']."\n\n 1.Main Menu |null|null|null|null";
        }
        else {
            $id = insertSQLi("INSERT INTO members (`mobile`, `step`, `createdon`) VALUES ('$MSISDN', '1', now())", $db_connecti);
            $message = "2|Welcome to $church member registration.\nPlease enter your full name ( 99 to skip )\n[e.g Edwin Morgan]:|null|null|null|null";
        }
       
    }
    elseif ($lastInput == '2') {
         //check if the user finished the steps if not return the user to the last step
         $result = selectSQLi("select member_id, full_name from members where mobile='$MSISDN' order by member_id asc limit 1;", $db_connecti);
        if (mysqli_num_rows($result) > '0') {
            $row = mysqli_fetch_array($result); 
            $id = $row['member_id'];
            $full_name = $row['full_name'];
            set_session_data('names', $full_name);
            $message = "2|Welcome $full_name to $church member registration.\nPlease enter your full name ( 99 to skip )\n[e.g Edwin Morgan]:|null|null|null|null";
        }
        saveArray($fileName, $id, 7);
    } 
    elseif($lastInput=='0'){
        $message = "0|Thank you for using our services.|null|null|end|null";
    }
    else {
        $message = "1|Invalid selection. $mainMenu|null|null|null|null";
    }
}


//-------------------------------------------------------TEMPLEVEL == 2001---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 2001) {

    $service_type = intval(trim($lastInput));

    $service_options = get_session_data('service_options'); 
    if(!empty($service_type)){

        $member_id = getIndexValue($fileName, 7);      
        //get fellowship day
        $result = selectSQLi("SELECT day_id,concat(day_name,' [ ',TIME_FORMAT(time_from, '%h:%i %p') ,' - ',TIME_FORMAT(time_to, '%h:%i %p'),' ]') as day_name,service_type FROM church_service.service_day where service_day.service_type=$service_type AND day_status=1  AND ( (service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) AND unix_timestamp(concat(current_date(),' ',show_from))<=unix_timestamp()) or (service_day=dayofweek(current_date()) AND unix_timestamp(concat(current_date(),' ',show_to))>=unix_timestamp()))  order by service_day asc;", $db_connecti);

        if (mysqli_num_rows($result) > '0') {

            $service_details = selectSQLi("SELECT (case when service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) AND unix_timestamp(concat(current_date(),' ',show_from))<=unix_timestamp() then DATE_ADD(current_date(), INTERVAL 1 DAY) ELSE current_date() END)as service_date FROM church_service.service_day where  service_day.service_type=$service_type AND day_status=1 AND ( (service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) AND unix_timestamp(concat(current_date(),' ',show_from))<=unix_timestamp()) or (service_day=dayofweek(current_date()) AND unix_timestamp(concat(current_date(),' ',show_to))>=unix_timestamp())) order by service_day asc limit 1;", $db_connecti);
                if($s_row = mysqli_fetch_array($service_details)){
                        $service_date = $s_row['service_date'];
                }

            $service_id = insertSQLi("INSERT INTO service_attendance(`member_id`,service_type, `step`, `createdon`,`service_date`) VALUES ('$member_id', $service_type,'2', now(),'$service_date')", $db_connecti);
            saveArray($fileName, $service_id, 13);
            $service_menu = '';
            $service_ids = [];
            $menu_id = 1;
            while($row = mysqli_fetch_array($result)){
              $service_menu = $service_menu."\n".$menu_id.". ".$row['day_name'];
              $service_ids[$menu_id] = $row['day_id'];
              $menu_id = $menu_id+1;
            }              
            $message = "2002|Select Service Day:".$service_menu."|null|null|null|null";
            set_session_data('service_ids',$service_ids);
            set_session_data('service_menu',$service_menu);
         
        }
        else{
            $message = "0|Sorry No Worship Service available.|null|null|end|null";
        }       
        if($service_type>2){
            $message = "2001|Invalid selection. $service_options|null|null|null|null";
        }
    }
    else{
        $message = "2001|Invalid selection. $service_options|null|null|null|null";
    }
}


//-------------------------------------------------------TEMPLEVEL == 2002---------------------------------------------------------------------------------------------------------------------------
if ($TEMPLEVEL == 2002) {

    $service_day = intval(trim($lastInput));    
    $service_menu = get_session_data('service_menu');
    
    if(!empty($service_day)){

        $service_id = getIndexValue($fileName, 13);
        $member_id = getIndexValue($fileName, 7);
        $service_ids = get_session_data('service_ids');

        if($service_day<4 && !empty($service_ids[$service_day])){            
            updateSQLi("update service_attendance set service_day='".$service_ids[$service_day]."', step=3,updatedby=$member_id,updatedon=current_timestamp() where service_id=".$service_id, $db_connecti);

            $result = selectSQLi("select (case when sd.service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) AND unix_timestamp(concat(current_date(),' ',show_from))<=unix_timestamp() then DATE_ADD(current_date(), INTERVAL 1 DAY) ELSE current_date() END) as service_date, service_id,concat(sd.day_name,' [ ',TIME_FORMAT(sd.time_from, '%h:%i %p') ,' - ',TIME_FORMAT(sd.time_to, '%h:%i %p'),' ]') as day_name,st.type_name from service_attendance sa inner join members m on (m.member_id=sa.member_id) left join service_day sd on (sa.service_day=sd.day_id) left join service_type st on (sa.service_type=st.type_id) where (sd.service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) or (sd.service_day=dayofweek(current_date()) AND unix_timestamp(concat(current_date(),' ',time_to))>=unix_timestamp())) and sa.service_date=(case when sd.service_day=DAYOFWEEK(DATE_ADD(current_date(), INTERVAL 1 DAY)) AND unix_timestamp(concat(current_date(),' ',show_from))<=unix_timestamp() then DATE_ADD(current_date(), INTERVAL 1 DAY) ELSE current_date() END) AND m.member_id=$member_id  and sa.service_status=0 order by service_id asc limit 1;", $db_connecti);
            if (mysqli_num_rows($result) > '0') {
                $row = mysqli_fetch_array($result); 

                $sms_msg = "Dear " .get_session_data('names'). ",Your Service attendance Details:\nService Date: ".$row['service_date']."\nService Type: ".$row['type_name']."\nDay: ".$row['day_name'].".\nThank you.";
                 send_sms(array('mobile'=>$MSISDN,'message'=>$sms_msg));  
                
                $message = "0|Your Service attendance Details:\nService Type: ".$row['type_name']."\nDay: ".$row['day_name']."\n\nThank you for Booking the Service.|null|null|end|null"; 
  
            }
            else{                
                 $message = "0|Thank you for Booking the Service. |null|null|end|null";
            }
        }
        else{
        $message = "2002|Invalid Selection Select Service Day:$service_menu|null|null|null|null";
        }
    }
    else{
        $message = "2002|Invalid Selection Select Service Day:$service_menu|null|null|null|null";
    }
}

//save session
$mx = explode('|', $message);
$nextTempLevel = $mx[0];
$response = $mx[1];

if($mx[4] == 'end'){
    $con = 'END'; 
    if (file_exists($tempfileName)){ unlink($tempfileName);}
}
else{
    $con = 'CON';
}

$newExtra = $mx[5];

saveArray($fileName, $nextTempLevel, 3);
saveArray($fileName, $newExtra, 19);

flog($infoLog, "Message: [$MSISDN] $response");

header('Content-type: text/plain');
echo $con . " " . $response;

//other functions here