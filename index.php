<?php
require 'fxnfile.php';

$dbname = 'church_booking';
$dbuser = 'root';
$pass = '';
$host = 'localhost';

//Connect to the databse
$dsn = "mysql:dbhost = ".$host.";dbname=".$dbname;
$pdo = new PDO($dsn, $dbuser, $pass);
// $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

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
}else{
    $sessionData = readFiles($fileName);
}
// $sessionDataArray[3] = $INPUT;

// var_dump($sessionDataArray);
$sessionDataArray = changeToArray(readFiles($fileName));
// $sessionDataArray = changeToArray($sessionData);
$nextLevel = $sessionDataArray[4];


$inputArray = explode("*", $INPUT);
$lastInput = trim($inputArray[sizeof($inputArray) - 1]);
$sessionDataArray[3] = $lastInput;

// --------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '1'){
    //check if the member exists
    $query = "SELECT * FROM church_member WHERE phone= ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$MSISDN]);
    $row = $stmt->fetch(PDO::FETCH_OBJ);

    if ($stmt->rowCount() > 0){
        $churchID = $row->churchID;
        $userID =$row->userID;

        $sessionDataArray[5] = $churchID;
        $sessionDataArray[6] = $userID;
        // echo $churchID."<br>";

        // echo "The user exists";
        //Query the database to get the user's church info
        $stmt_church = $pdo->prepare("SELECT * FROM church_info WHERE churchID = $churchID");
        $stmt_church->execute();
        $row_church = $stmt_church->fetch(PDO::FETCH_OBJ);

        $churchName = $row_church->churchName;
        $userName = $row->firstName." ".$row->lastName;

        $sessionDataArray[4] = 4;
        echo "CON Welcome $userName to $churchName service system:\n1: Book Service\n2: Update Settings\n";
        writeFiles($fileName, changeToString($sessionDataArray));
    }else{
        // echo "You are not registered to any church <br>";
        // echo "Enter your name to Continue with the registration. E.g. Maxwel Oduor:<br>";
        // $sessionDataArray[4] = 5;

        // writeFiles("session_files", changeToString($sessionDataArray));
        echo "END You are not registered for this service.\n";
    }
    exit();
}

// --------------------------------------------------------------------------------------------------------------------------------
if ($nextLevel == '4'){
    // $inputArray = explode("*", $INPUT);
    // $lastInput = trim($inputArray[sizeof($inputArray) - 1]);
    // $sessionDataArray[3] = $lastInput;

    $churchID = $sessionDataArray[5]; 
    // $sessionDataArray[3] = 1;

    $stmt_services = $pdo->prepare("SELECT * FROM church_service WHERE churchID = ?");
    $stmt_services->execute([$churchID]);
    $row_service = $stmt_services->fetchAll(PDO::FETCH_OBJ);

    $service = [];
    $serviceID = [];
    // $count = 1;

    if ($lastInput == '1'){
    // if ($sessionDataArray[3] == 1){
        echo "CON Select a service you would wish to attend\n\n";

        //Query the database to get the user details and church services
        foreach($row_service as $serve){
            $n_service = [$serve->serviceID, $serve->serviceName, $serve->serviceTheme, date("D, M", strtotime($serve->serviceDate))];
            array_push($service, $n_service);
            array_push($serviceID, $serve->serviceID);
            // $count++;
        }
        // print_r($service);
        for ($i=0; $i<sizeof($service); $i++){
            echo $service[$i][0]." : ".$service[$i][1]." - ".$service[$i][2]." - ".$service[$i][3]."\n";
        }

        // var_dump($service);
        // var_dump($service[2]);
        // echo $serviceID."<br>";

        // $sessionDataArray = [$SESSIONID, $MSISDN, $USSDCODE, $INPUT, 8, $service];
        $serviceDataString = changeToString($serviceID);
        $sessionDataArray[7] = $serviceDataString;
        // var_dump($serviceDataString);
        echo "\n";
        // print_r($sessionDataArray);
        $sessionDataArray[4] = 8;
        // var_dump($sessionDataArray);
        writeFiles($fileName, changeToString($sessionDataArray));

    }else if($lastInput == 2){
        echo "CON Select which data you would like to edit.\n";
        echo "1: First Name \n2: Last Name\n3: email Address\n4: Church Name\n";
        $sessionDataArray[4] = 7;
        writeFiles($fileName, changeToString($sessionDataArray));
    }
    // else if($INPUT == 978){
    //     echo "END Kusumbua tu!!";
    //     exit();
    // }
    else if($lastInput == 0){
        echo "END Thank You for booking with us";
    }else{
        echo "END Wrong Input\n";
    }
    // echo "<br>I am level $nextLevel. Welcome.<br>";    
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

if ($nextLevel === '7'){
    // echo "You selected $INPUT";
    if ($lastInput === "1"){
        echo "CON Enter your new First Name";
        // $query = "UPDATE church_member SET firstName=?";
        $sessionDataArray[4] = 10;
        writeFiles($fileName, changeToString($sessionDataArray));
    }
    exit();
}

// --------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '8'){
    // $inputArray = explode("*", $INPUT);
    // $lastInput = trim($inputArray[sizeof($inputArray) - 1]);

    $services = array_slice($sessionDataArray, 7);
    if (!in_array($lastInput, $services)){
        // echo "END Wrong Input\n";
        echo "CON You have entered the wrong Input\n 1. Back\n 0. Exit ";
        $sessionDataArray[4] = 4;
        writeFiles($fileName, changeToString($sessionDataArray));
    }else{
    // echo "<br>";
    //churchID
    // echo $sessionData[5]."<br>";
    // var_dump($services);

    $stmt_service = $pdo->prepare("SELECT * FROM church_service WHERE serviceID = ?");
    $stmt_service->execute([$lastInput]);
    $row_service = $stmt_service->fetch(PDO::FETCH_OBJ);

    echo "CON confirm service booking: \n";
    echo $row_service->serviceName." - ".$row_service->serviceTheme."\n";
    echo "1: Confirm\n0: Cancel";

    $sessionDataArray[4] = 9;

    //store the service ID in the session file
    $sessionDataArray[7] =$lastInput;
    array_splice($sessionDataArray, 7, 100, $lastInput);
    // echo $services[$INPUT-1];
    writeFiles($fileName, changeToString($sessionDataArray));
    }
    exit();
}

// --------------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '9'){
    // $inputArray = explode("*", $INPUT);
    // $lastInput = trim($inputArray[sizeof($inputArray) - 1]);

    if ($lastInput === "0"){
        echo "END You have cancelled the booking\n";
        exit();
    }else if($lastInput === "1"){
        $rev_ID = mt_rand();
        echo "END You have successfully booked for the service.\nYour reservation ID is $rev_ID\n";

        //get the user data
        $stmt_user = $pdo->prepare("SELECT * FROM church_member WHERE userID = ?");
        $stmt_user->execute([$sessionDataArray[6]]);
        $row_usr_data = $stmt_user->fetch(PDO::FETCH_OBJ);

        $memberEmail = $row_usr_data->email;
        $memberPhone = $row_usr_data->phone;
        $memberName = $row_usr_data->firstName;

        //Update the reservation table
        $stmt_res = $pdo->prepare("INSERT INTO service_reservation (`serviceID`, `cust_rev_ID`, `churchID`, `reservationTime`, `memberName`, `memberPhone`, `memberEmail`) VALUES(?, ?, ?, NOW(), ?, ?, ?)");
        $stmt_res->execute([$sessionDataArray[7], $rev_ID, $sessionDataArray[5], $memberName, $memberPhone, $memberEmail]);
    }else{
        echo "CON You have entered the wrong Input\n 1. Continue booking\n 0. Exit ";
        $sessionDataArray[4] = 4;
        writeFiles($fileName, changeToString($sessionDataArray));
        // exit();
    }
}

// --------------------------------------------------------------------------------------------------------------------------

if ($nextLevel === '10'){
    // echo "END Thank you $lastInput";
    $stmt_upd_fName = $pdo->prepare("UPDATE church_member SET firstName=$lastInput WHERE userID=?");
    $stmt_upd_fName->execute([$sessionDataArray[10]]);
    
    echo "END first Name updated successfully";

    // $sessionDataArray[4] = 11;
    // writeFiles($fileName, changeToString($sessionDataArray));
    exit();
}

// ------------------------------------------------------------------------------------------------------------------------



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