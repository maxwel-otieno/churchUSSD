<?php
require 'fxnfile.php';

$fatalLogs = str_ireplace("\\", "/", __DIR__) . '/logs/fatal.log';
$sqlLogs = str_ireplace("\\", "/", __DIR__) . '/logs/sql.log';
$infoLog = str_ireplace("\\", "/", __DIR__) . '/logs/info.log';
$errorLog = str_ireplace("\\", "/", __DIR__) . '/logs/error.log';

//Get the variables from the USSD gateway
$SESSIONID = $_GET["SESSIONID"];
$USSDCODE = rawurldecode($_GET["USSDCODE"]);
$MSISDN = $_GET["MSISDN"];
$INPUT= rawurldecode($_GET["INPUT"]);

// if (file_exists("session_files")){
//     $sessionDataString = readFiles("session_files");
// }else{
//     $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 1, $INPUT, 1];
//     // $sessionData[3] = 1;
//     writeFiles("session_files", changeToString($sessionData));
// }

// if(file_exists("user_input_file")){
//     $INPUTArray = changeToArray(readFiles("user_input_file"));
//     array_push($INPUTArray, $INPUT);
//     // $INPUTArray[sizeof($INPUTArray)] = $INPUT;
//     writeFiles("user_input_file", changeToString($INPUTArray));
// }else{
//     writeFiles("user_input_file", $INPUT);
//     $INPUTArray = changeToArray(readFiles("user_input_file"));
//     // array_push($INPUTArray, $INPUT);
// }

// $row1 = $stmt->fetch();

// $nextLevel = nextLevel();
// $prevLevel = prevLevel();

//NEW CODE
$filePathDir = str_ireplace("\\", "/", __DIR__) . '/sessionFiles/';
$fileName = $filePathDir."_". $SESSIONID . "_" . $MSISDN;

if (!file_exists($fileName)){
    $sessionData = [$SESSIONID, $MSISDN, $USSDCODE, $INPUT, 1];
    writeFiles($fileName, changeToString($sessionData));
    chmod($fileName, 0776);
}else{
    $sessionData = readFiles($fileName);
}
// $sessionDataArray[3] = $INPUT;

// var_dump($sessionDataArray);
$sessionDataArray = changeToArray(readFiles($fileName));
// $sessionDataArray = changeToArray($sessionData);
$nextLevel = $sessionDataArray[4];

//Get the last input by the user
$inputArray = explode("*", $INPUT);
$lastInput = trim($inputArray[sizeof($inputArray) - 1]);
$sessionDataArray[3] = $lastInput;

flog($infoLog, "Session: [$MSISDN] $SESSIONID | $nextLevel | $INPUT");
// --------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '1'){
    //check if the member exists
    $row = fetchDB('church_member', 'phone', $MSISDN)[0];

    // $row = fetchDB('church_member', 'phone', $MSISDN)[0];
    $count = fetchDB('church_member', 'phone', $MSISDN)[1];

    if ($count > 0){
        $churchID = $row->churchID;
        $row_church = fetchDB('church_info', 'churchID', $churchID)[0];
        $userID =$row->userID;

        $sessionDataArray[5] = $churchID;
        $sessionDataArray[6] = $userID;

        $churchName = $row_church->churchName;
        $userName = $row->firstName." ".$row->lastName;

        $sessionDataArray[4] = 2;
        echo "CON Hello $userName\nWelcome to $churchName service system:\n1: Book Service\n2: Update Settings\n3: My Services\n0: Exit\n";
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else{
        // echo "You are not registered to any church <br>";
        // echo "Enter your name to Continue with the registration. E.g. Maxwel Oduor:<br>";
        // $sessionDataArray[4] = 5;

        // writeFiles("session_files", changeToString($sessionDataArray));
        echo "END You are not registered for this service.\n";
    }
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}

// --------------------------------------------------------------------------------------------------------------------------------
if ($nextLevel == '2'){
    // $inputArray = explode("*", $INPUT);
    // $lastInput = trim($inputArray[sizeof($inputArray) - 1]);
    // $sessionDataArray[3] = $lastInput;

    $churchID = $sessionDataArray[5]; 
    $row_service = fetchAllDB('church_service', 'churchID', $churchID)[0];

    $service = [];
    $serviceID = [];
    // $count = 1;

    if ($lastInput == '1'){
    // if ($sessionDataArray[3] == 1){
        echo "CON Select a Service to Book\n";

        //Query the database to get the user details and church services
        foreach($row_service as $serve){
            $n_service = [$serve->serviceID, $serve->serviceName, date("d M", strtotime($serve->serviceDate)), date("h:i a", strtotime($serve->time_from))];
            array_push($service, $n_service);
            array_push($serviceID, $serve->serviceID);
            // $count++;
        }
        // print_r($service);
        for ($i=0; $i<sizeof($service); $i++){
            echo ($i+1).": ".$service[$i][1]."- ".$service[$i][2].", ".$service[$i][3]."\n";
        }
        echo "00: Home";
        // var_dump($service);
        // var_dump($service[2]);
        // echo $serviceID."<br>";

        // $sessionDataArray = [$SESSIONID, $MSISDN, $USSDCODE, $INPUT, 8, $service];
        $serviceDataString = changeToString($serviceID);
        $sessionDataArray[7] = $serviceDataString;
        // var_dump($serviceDataString);
        echo "\n";
        // print_r($sessionDataArray);
        $sessionDataArray[4] = 3;
        // var_dump($sessionDataArray);
        // writeFiles($fileName, changeToString($sessionDataArray));

    }else if($lastInput == 2){
        echo "CON Select which data you would like to edit.\n";
        echo "1: First Name\n2: Last Name\n3: Email Address\n0: Back";
        $sessionDataArray[4] = 4;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else if($lastInput == 3){
        echo "CON 1: View My Services\n2: Delete a service\n0: Back\n";
        $sessionDataArray[4] = 5;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else if($lastInput === '0'){
        echo "END Thank You for booking with us";
    }else{
        echo "CON Wrong Input\n";
        echo "1: Book Service\n2: Update Settings\n3: My Services\n0: Exit\n";

        $sessionDataArray[4] = 2;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }
    // echo "<br>I am level $nextLevel. Welcome.<br>";   
    writeFiles($fileName, changeToString($sessionDataArray)); 
    exit();
}

// --------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '3'){
    // $inputArray = explode("*", $INPUT);
    // $lastInput = trim($inputArray[sizeof($inputArray) - 1]);
    $row = fetchDB('church_member', 'phone', $MSISDN)[0];
    $churchID = $row->churchID;
    $row_church = fetchDB('church_info', 'churchID', $churchID)[0];

    $servicesArray = array_slice($sessionDataArray, 7);

    // print_r($servicesArray);
    $services=array();
    for ($a=0; $a<sizeof($servicesArray); $a++){
        array_push($services, ($a+1));
    }

   if ($lastInput == 00){        
        $churchName = $row_church->churchName;
        $userName = $row->firstName." ".$row->lastName;

        echo "CON Hello $userName\nWelcome to $churchName service system:\n1: Book Service\n2: Update Settings\n3: My Services\n0: Exit\n";
        $sessionDataArray[4] = 2;
   }else if (!in_array($lastInput, $services)){
        // echo "END Wrong Input\n";
        echo "CON Wrong input\nThere's no such service\n1: Back\n0: Exit ";
        $sessionDataArray[4] = 2;
        // $sessionDataArray[100] =1;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else{
    // $booked_serviceID = 
    $row_service = fetchDB('church_service', 'serviceID', $servicesArray[$lastInput-1])[0];
    // var_dump($row_service);

    echo "CON confirm service booking: \n";
    echo $row_service->serviceName."- ".date("d M", strtotime($row_service->serviceDate)).",".date("H a", strtotime($row_service->time_from))."\n";
    echo "1: Confirm\n0: Cancel";

    $sessionDataArray[4] = 9;
    //store the service ID in the session file
    $sessionDataArray[7] =$servicesArray[$lastInput-1];

    //use array_splice to delete elements in a certain range and replace with the serviceID
    array_splice($sessionDataArray, 7, 100, $servicesArray[$lastInput-1]);
    // echo $services[$INPUT-1];
    // writeFiles($fileName, changeToString($sessionDataArray));
    }
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}

// -------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '4'){
    //UserID = $sessionDataArray[6]
    $row = fetchDB('church_member', 'phone', $MSISDN)[0];
    $churchID = $row->churchID;
    $row_church = fetchDB('church_info', 'churchID', $churchID)[0];
    $churchName = $row_church->churchName;
    $userName = $row->firstName." ".$row->lastName;

    if ($lastInput === "0"){
        echo "CON Hello $userName\nWelcome to $churchName service system:\n1: Book Service\n2: Update Settings\n3: My Services\n0: Exit\n";
        $sessionDataArray[4] = 2;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else if ($lastInput === "1"){
        echo "CON Your current First Name is $row->firstName\n";
        echo "Enter your new First Name";
        $sessionDataArray[4] = 10;
        // writeFiles($fileName, changeToString($sessionDataArray));

    }else if ($lastInput === "2"){
        echo "CON Your current Last Name is $row->lastName\n";
        echo "Enter your new Last Name";
        $sessionDataArray[4] = 11;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else if ($lastInput === "3"){
        echo "CON Your current email is $row->email\n";
        echo "Enter your new email Address";
        $sessionDataArray[4] = 12;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else if($lastInput === "00"){
        echo "END Thank You for booking with us";
    }
    // else if($lastInput == 00){        
    //     echo "END Thank You for booking with us";
    // }
    else{
        echo "CON Wrong Input\nSelect which data you would like to edit.\n";
        echo "1: First Name\n2: Last Name\n3: email Address\n";
        
        $sessionDataArray[4] = 4;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}
// ------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '5'){
    $reservation4Usr = fetchAllDB('service_reservation', 'userID', $sessionDataArray[6])[0];
    $reservation4UsrCount = fetchAllDB('service_reservation', 'userID', $sessionDataArray[6])[1];

    if ($lastInput == 1){
        if ($reservation4UsrCount <= 0){
            echo "CON You have not booked for any service yet\n1: Book Service\n2: Update Settings\n0:Exit\n";
            $sessionDataArray[4] = 2;
            // writeFiles($fileName, changeToString($sessionDataArray));
        }else{
            echo "CON My Services\n";
            $myServicesID = [];
            foreach($reservation4Usr as $reserve){
                // $n_services = [$reserve->serviceName, date("D, M", strtotime($serve->serviceDate))];
                array_push($myServicesID, $reserve->serviceID);
            }
            
            $myServices = array();
            for ($i=0; $i<count($myServicesID); $i++){
                $service4Usr = fetchDB('church_service', 'serviceID', $myServicesID[$i])[0];
                $n_service = [$service4Usr->serviceName, date("d M", strtotime($service4Usr->serviceDate)), date("h:i a", strtotime($service4Usr->time_from))];
                array_push($myServices, $n_service);
                // array_push($serviceID, $serve->serviceID);
                // $count++;
            }
            // print_r($service);
            for ($i=0; $i<sizeof($myServices); $i++){
                echo $myServices[$i][0]." - ".$myServices[$i][1].", ".$myServices[$i][2]."\n";
            }
            echo "0: Back";
            $sessionDataArray[4] = 8;
        }
        writeFiles($fileName, changeToString($sessionDataArray));
    }else if($lastInput == 2){
        if ($reservation4UsrCount <= 0){
            echo "CON You have not booked for any service yet\n1: Book Service\n2: Update Settings\n";
            $sessionDataArray[4] = 2;
            // writeFiles($fileName, changeToString($sessionDataArray));
        }else{
            echo "CON Select the Service to Delete\n";
            $myServicesID = [];
            foreach($reservation4Usr as $reserve){
                // $n_services = [$reserve->serviceName, date("D, M", strtotime($serve->serviceDate))];
                array_push($myServicesID, $reserve->serviceID);
            }
            
            $myServices = array();
            for ($i=0; $i<count($myServicesID); $i++){
                $service4Usr = fetchDB('church_service', 'serviceID', $myServicesID[$i])[0];
                $n_service = [$service4Usr->serviceName, date("d M", strtotime($service4Usr->serviceDate)), date("h:i a", strtotime($service4Usr->time_from))];
                array_push($myServices, $n_service);
                // array_push($serviceID, $serve->serviceID);
                // $count++;
            }
            // print_r($service);
            for ($i=0; $i<sizeof($myServices); $i++){
                echo ($i+1).": ".$myServices[$i][0]." - ".$myServices[$i][1].", ".$myServices[$i][2]."\n";
            }
            echo "0: Back";

            $myServiceIDString = changeToString($myServicesID);
            array_push($sessionDataArray, $myServiceIDString);
            $sessionDataArray[4] = 6;
        // print_r($sessionDataArray);
        }        
        writeFiles($fileName, changeToString($sessionDataArray));        
    }else if ($lastInput == 0){
        $row = fetchDB('church_member', 'phone', $MSISDN)[0];
        $churchID = $row->churchID;
        $row_church = fetchDB('church_info', 'churchID', $churchID)[0];

        $churchName = $row_church->churchName;
        $userName = $row->firstName." ".$row->lastName;
        echo "CON Hello $userName\nWelcome to $churchName service system:\n1: Book Service\n2: Update Settings\n3: My Services\n0:Exit";
        $sessionDataArray[4] = 2;
        // echo "CON You will exit from the system";
    }else{
        echo "CON wrong Input";
    }    
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();     
}

// -------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '6'){
    $services = array();
    for ($a=7; $a<sizeof($sessionDataArray); $a++){
        array_push($services, $sessionDataArray[$a]);
    }
    // $servicesNo = count($services);
    // print_r($servicesNo);

    if ($lastInput == 0){
        echo "CON 1: View My Services\n2: Delete a service\n0: Back\n";
        $sessionDataArray[4] = 5;
    }else if($lastInput<=sizeof($services) && $lastInput>0){
        $serviceInfo = fetchDB('church_service', 'serviceID', $services[$lastInput-1])[0];
        echo "CON Delete ".$serviceInfo->serviceName." - ".$serviceInfo->serviceTheme."\n1: Delete\n0: Back\n";

        //use array_splice to delete elements in a certain range and replace with the serviceID
        array_splice($sessionDataArray, 7, 100, $services[$lastInput-1]);
        // print_r($sessionDataArray);
        $sessionDataArray[4] = 7;
        // echo "CON The correct service has been selected";
    }else{
        echo "END The service does not exist";
    }
        writeFiles($fileName, changeToString($sessionDataArray));
        exit();
    // switch($lastInput){
    //     case '1':
    //         echo "END You selected ".$services[1];
    //         break;
    //     case '2':
    //         echo "END You selected ".$services[2];
    //         break;
    //     case '3':
    //         echo "END You selected ".$services[3];
    //         break;
    //     default:
    //         echo "Wrong Input";
    //         break;
    // }
    // echo "CON You selected $lastInput\n";
}
// -------------------------------------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel == 7){
    if ($lastInput == 0){
        echo "CON 1: View My Services\n2: Delete a service\n0: Back\n";
        $sessionDataArray[4] = 5;
    }else if ($lastInput == 1){
        //Delete the selected service from the DB
        $stmt_delete = $pdo->prepare("DELETE FROM service_reservation WHERE userID=? AND serviceID=?");
        $stmt_delete->execute([$sessionDataArray[6], $sessionDataArray[7]]);
        // echo "END You have elected to delete the service with an ID of ".$sessionDataArray[7];
        echo "END Service Deleted successfully";
    }else {
        echo "CON Wrong input\n1: View My Services\n2: Delete a service\n0: Back\n";
        $sessionDataArray[4] = 5;
    }
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}
// -------------------------------------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel == 8){
    if ($lastInput != 0){
        echo "END Wrong input\nThank you for booking with us";
    }else{
        echo "CON 1: View My Services\n2: Delete a service\n0: Back\n";
        $sessionDataArray[4] = 5;
    }
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}
// -------------------------------------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '9'){
    if ($lastInput === "0"){
        echo "END Thank You for booking with us\n\nYou have cancelled the booking\n";
    }else if($lastInput === "1"){
        $rev_ID = mt_rand();

        //check if the serice exists
        $query_rev = "SELECT * FROM service_reservation WHERE serviceID = ? AND userID = ?";
        $stmt_rev = $pdo->prepare($query_rev);
        $stmt_rev->execute([$sessionDataArray[7] , $sessionDataArray[6]]);
        $row = $stmt_rev->fetchAll();

        if ($stmt_rev->rowCount() <= 0){
            $stmt_res = $pdo->prepare("INSERT INTO service_reservation (`serviceID`, `cust_rev_ID`, `reservationTime`, `userID`) VALUES(?, ?, NOW(), ?)");
            $stmt_res->execute([$sessionDataArray[7], $rev_ID, $sessionDataArray[6]]);
            
            echo "END You have successfully booked for the service.\nYour reservation ID is $rev_ID\n";

            $row_member = fetchDB('church_member', 'phone', $MSISDN)[0];
            // $row_church = fetchDB('church_info', 'churchID', $sessionDataArray[5])[0];
            $row_service = fetchDB('church_service', 'serviceID', $sessionDataArray[7])[0];

            $sms_msg = "Hello, $row_member->firstName $row_member->lastName. You have successfully booked the '$row_service->serviceTheme' to be held on ".date('d M', strtotime($row_service->serviceDate))." from ".date('h:i a', strtotime($row_service->time_from))." to ".date('h:i a', strtotime($row_service->time_to))."\nYour reservation ID is $rev_ID";
            // $churchName = $row_church->churchName;
            send_sms(array('mobile'=>$MSISDN,'message'=>$sms_msg)); 
        }else{            
            echo "END You have already booked for this service";
        }

        //get the user data
        // $row_usr_data = fetchDB('church_member', 'userID', $sessionDataArray[6])[0];

        // //Check if the reservation alresy exists
        // $row_serv_res = fetchAllDB('service_reservation', 1, 1)[0];

        // $memberEmail = $row_usr_data->email;
        // $memberPhone = $row_usr_data->phone;
        // $memberName = $row_usr_data->firstName;


        // $countErr = array();
        // //check if a member exists.
        // foreach($row_serv_res as $a){
        //     if ($a->serviceID == $sessionDataArray[7] && ($row_usr_data->phone) == $MSISDN){
        //         array_push($countErr, 0);
        //     }else{
        //         array_push($countErr, 1);
        //     }
        // }
        // if (in_array(0, $countErr) == TRUE){
        //     echo "END You have already booked for this service";
        // }else{        
        //     //Update the reservation table
            
        // }
    }else{
        echo "CON You have entered the wrong Input\n1: Continue booking\n0: Exit ";
        $sessionDataArray[4] = 2;
        writeFiles($fileName, changeToString($sessionDataArray));
        // exit();
    }
    exit();
}

// --------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '10'){
    if (!is_string($lastInput)){
        echo "CON Wrong input\nName should be letters only\n\n2: Back \n0: Exit";
        $sessionDataArray[4] = 2;
        writeFiles($fileName, changeToString($sessionDataArray));
    }else{
        $stmt_upd_fName = $pdo->prepare("UPDATE church_member SET firstName=? WHERE userID=?");
        $stmt_upd_fName->execute([$lastInput, $sessionDataArray[6]]);
    
        if ($stmt_upd_fName){
            echo "CON First Name Updated Succesfully\n1: Book a service\n2: Continue Updating\n0: Exit";
            $sessionDataArray[4] = 2;
            // writeFiles($fileName, changeToString($sessionDataArray));
            // set_session_data($fileName,2);
        }else{
            echo "END Failed to update first name";
        }
        // $sessionDataArray[4] = 11;
        writeFiles($fileName, changeToString($sessionDataArray));
    }
    exit();
}

// ------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '11'){
    if (!is_string($lastInput)){
        echo "CON Wrong input\nName should be letters only\n\n2: Back \n0: Exit";
        $sessionDataArray[4] = 2;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }else{
        $stmt_upd_lName = $pdo->prepare("UPDATE church_member SET lastName=? WHERE userID=?");
        $stmt_upd_lName->execute([$lastInput, $sessionDataArray[6]]);
    
        if ($stmt_upd_lName){
            echo "CON Last Name Updated Succesfully\n1: Book a service\n2: Continue Updating\n0: Exit";
            $sessionDataArray[4] = 2;
            // writeFiles($fileName, changeToString($sessionDataArray));
        }else{
            echo "END Failed to update your Last Name";
        }
        // $sessionDataArray[4] = 11;
    }
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}
// ----------------------------------------------------------------------------------------------------------------------------
if ($nextLevel === '12'){
    if (!empty($lastInput)) {
        // check if e-mail address is well-formed           
        $userEmail = test_input($lastInput);   
        if (filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {                
            $stmt_upd_email = $pdo->prepare("UPDATE church_member SET email=? WHERE userID=?");
            $stmt_upd_email->execute([$lastInput, $sessionDataArray[6]]);

            echo "CON Your email has been changed\n1: Book a service\n2: Continue Updating\n0: Exit";
            $sessionDataArray[4] = 2;
            // writeFiles($fileName, changeToString($sessionDataArray));
        }else{
            echo "CON Wrong email format\n Use a valid email address";
            $sessionDataArray[4] = 12;
            // writeFiles($fileName, changeToString($sessionDataArray));
        }
    }else{
        echo "CON Email cannot be empty\n3: Re-enter email address \n0: Exit";
        $sessionDataArray[4] = 4;
        // writeFiles($fileName, changeToString($sessionDataArray));
    }
    // $sessionDataArray[4] = 11;
    writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}

// --------------------------------------------------------------------------------------------------------------------------------
// if ($nextLevel === '5'){
//     if (!is_int($INPUT)){
//         echo "Welcome $INPUT to our system. Enter your email address to continue";
//         $sessionDataArray[4] = 6;
//         writeFiles("session_files", changeToString($sessionDataArray));
//     }else{
//         echo "Wrong Input<br>";
//         echo "Enter the correct name to continue<br>";
//         $sessionDataArray[4] = 5;
//         writeFiles("session_files", changeToString($sessionDataArray));
//     }
    
// }

// --------------------------------------------------------------------------------------------------------------------------------

// if ($nextLevel === '6'){
//     // $email = $sessionDataArray[3];
//     $email =$INPUT;
//     echo "email address captured as $email.";
// }

// --------------------------------------------------------------------------------------------------------------------------------


// if ($stmt->rowCount() >= 1){
//     // echo "User found";

//     if($row->email != "" && $row->churchID != ""){
//         //Identify the church the user is registered under
//         $churchID = $row->churchID;
//         $query_church = "SELECT * FROM church_info WHERE churchID= ?";
//         $stmt_church = $pdo->prepare($query_church);
//         $stmt_church->execute([$churchID]);
//         $row_church = $stmt_church->fetch(PDO::FETCH_OBJ);

//         // echo $row_church->churchName;
        
//         $message = "Welcome ".$row->firstName." ".$row->lastName.", to $row_church->churchName <br>";

//         //Find all the services related to this church
//         $query_service = "SELECT * FROM church_service WHERE churchID= ?";
//         $stmt_service = $pdo->prepare($query_service);
//         $stmt_service->execute([$churchID]);
//         $row_service = $stmt_service->fetchAll(PDO::FETCH_OBJ);

//         $services = [];
//         $count = 1;
//     }

//     if ($INPUT === ''){        
//         // $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 1, $INPUT, '0'];
//         // writeFiles("session_files", changeToString($sessionData));
//         echo "INPUT is empty";
//     }else if ($INPUT === '0'){        
//         $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 1, $INPUT, '0'];
//         writeFiles("session_files", changeToString($sessionData));
//     }else if ($INPUT === '00'){          
//         echo "You have exited the Application<br>";      
//         // $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 3, $INPUT];
//         // writeFiles("session_files", changeToString($sessionData));
//         exit();
//     }else{    
//         // $sessionDataString = implode('|', $sessionData);
//         if ($nextLevel == 1){ 
//             $curLevel = 1;    
//             echo $message;
//             echo "Here is a list of services that you can book from <br><br>";
        
//             foreach ($row_service as $service){
//                 array_push($services, $count.": ".$service->serviceTheme." - ".date("D, M", strtotime($service->serviceDate)));
//                 $count++;
//             }
            
//             for($i=0; $i<sizeof($services);$i++){
//                 echo $services[$i];
//                 echo "<br>";
//             }

//             // use \n for linebreak instea dof <br>
//             echo "\n0: Back <br>";
//             echo "\n00: Exit <br>";

//             // var_dump($services);

//             //INSERT USER DATA INTO THE SESSION FILE
//             // array_push($INPUTArray, 4);

//             writeFiles("user_input_file", changeToString($INPUTArray));
//             // echo readFiles("user_input_file")."<br>";
//             echo "<br>";

//             // $sessionDataString = implode('|', $sessionData);
//             $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 3, $INPUT, $curLevel];
//             $sessionData = array_merge($sessionData, $services);
//             writeFiles("session_files", changeToString($sessionData));

//             // echo readFiles("user_input_file");
//         }

//         if($nextLevel == 3){
//             $curLevel = 3;
//             // $selection = $INPUTArray[sizeof($INPUTArray)-1];

//             $sessionData = changeToArray($sessionDataString);
//             // $no_of_elements = sizeof($sessionData) - 5;

//             $services = array_slice($sessionData, 6);
//             // foreach ($row_service as $service_1){
//             //     echo $service_1->serviceName."<br>";
//             // }
//             // $INPUT = $sessionData[4];

//             $usrINPUTArr = changeToArray(readFiles("user_input_file"));
//             $usrINPUT = $usrINPUTArr[sizeof($usrINPUTArr)-1];

//             // if ($usrINPUT === '0'){
//             //     echo "I will take you back to the step that you were in"; 
//             //     $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 1, $INPUT];
//             //     writeFiles("session_files", changeToString($sessionData));
//             // }else if ($usrINPUT === '00'){
//             //     echo "I will exit from the application";
//             //     $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 00, $INPUT];
//             //     writeFiles("session_files", changeToString($sessionData));
//             // }
//             if ($usrINPUT > $stmt_service->rowCount()){
//                 echo "This option does not exist";
//                 $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 3, $INPUT, $curLevel];
//                 writeFiles("session_files", changeToString($sessionData));
//             }else {
//                 echo $services[$usrINPUT-1]."<br>";
//                 echo $curLevel;
//                 $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 5, $INPUT, $curLevel];
//                 writeFiles("session_files", changeToString($sessionData));
//             }
            
//             // echo "You have selected a church <br>";

//             // array_push($INPUTArray, $INPUT);
//             // print_r($INPUTArray);
//             // echo sizeof($INPUTArray);
//             // var_dump($INPUTArray);
//             // echo "<br>";

//             // writeFiles("user_input_file", changeToString($INPUTArray));
//             // // echo readFiles("user_input_file")."<br>";
//             // echo "<br>";

//             // $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 1, $INPUT];
//             // writeFiles("session_files", changeToString($sessionData));

//         }
        
//         if($nextLevel == 2){
//             if ($INPUT == 1){
//                 // $currLevel = 2;
//                 $stmt_add_user = $pdo->prepare("INSERT INTO church_member (phone,) VALUES($MSISDN)");
//                 $stmt_add_user->execute();

//                 echo "welcome to the registration page <br>";
//                 echo "Enter your name. E.g. Jane Doe<br>";
//                 // var_dump($INPUTArray);

//                 $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 4, $INPUT];
//                 writeFiles("session_files", changeToString($sessionData));  
//             }else if ($INPUT == 00){
//                 echo "You will exit from the Application";
//                 $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 4, $INPUT];
//                 writeFiles("session_files", changeToString($sessionData));  
//             }
//             // array_push($INPUTArray, readFiles("user_input_file"));
//             writeFiles("user_input_file", changeToString($INPUTArray));
//             echo readFiles("user_input_file");
//         }

//         if($nextLevel == 21){
//             if ($INPUT == 1){
//                 // $currLevel = 2;
//                 $stmt_add_user = $pdo->prepare("INSERT INTO church_member (phone) VALUES($MSISDN)");
//                 $stmt_add_user->execute();

//                 echo "welcome to the registration page <br>";
//                 echo "Enter your name. E.g. Jane Doe<br>";
//                 // var_dump($INPUTArray);

//                 $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 4, $INPUT];
//                 writeFiles("session_files", changeToString($sessionData));  
//             }else{
//                 echo "User name incorrect";
//                 $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 2, $INPUT];
//                 writeFiles("session_files", changeToString($sessionData)); 
//             }
//             // array_push($INPUTArray, readFiles("user_input_file"));
//             writeFiles("user_input_file", changeToString($INPUTArray));
//             echo readFiles("user_input_file");
//         }
        
//         if ($nextLevel == 4){
//             echo "Welcome $INPUT to this church.";
//         }

//         // if ($nextLevel == 00){
//         //     echo "You have exited the Application<br>";
//         //     exit();
//         // }

//         if ($nextLevel == 5){
//             $curLevel = 5;
//             echo "Confirm that you are booking the service<br>";
//             echo "\n 1: YES<br> 2: NO <br>";
//             $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 1, $INPUT, $curLevel];
//             writeFiles("session_files", changeToString($sessionData)); 
//         }
//     }
// }
// if($stmt->rowCount() < 1){
//     // echo "User not found";
//     $menu = "1: REGISTER<br>
//              00: EXIT<br>";

//     echo "You are not yet registered. Would you like to register?<br><br>";
//     echo $menu;

//     $sessionData = [$SESSIONID, $USSDCODE, $MSISDN, 2, $INPUT];  
//     writeFiles("session_files", changeToString($sessionData));  
// }

?>