<?php
session_start();
$dbname = 'church_booking';
$dbuser = 'root';
$pass = '';
$host = 'localhost';

//Connect to the databse
$dsn = "mysql:dbhost = ".$host.";dbname=".$dbname;
$pdo = new PDO($dsn, $dbuser, $pass);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

//Get the variables from the USSD gateway
$SESSIONID = $_GET["SESSIONID"];
$USSDCODE = rawurldecode($_GET["USSDCODE"]);
$MSISDN = $_GET["MSISDN"];
$INPUT = rawurldecode($_GET["INPUT"]);

$query = "SELECT * FROM church_member WHERE phone= ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$MSISDN]);
// $row = $stmt->fetch(PDO::FETCH_OBJ);
$row1 = $stmt->fetch();

// print_r($_GET);
// exit();

//using fopen to create a file when it does not exist
// $myfile = fopen("newfile.txt", "w") or die("Unable to open file");
// $name = "Marvin Omondi Otieno";
// $age = 23;
// $school = "Moi University";
// $course = "Journalism";

// fwrite($myfile, "My name is $name.\n");
// fwrite($myfile, "I am $age years of age.\n");
// fwrite($myfile, "I study $course at $school.\n");
// fclose($myfile);

// echo "File created and written to successfully";
// echo "<br>";
// print_r($_GET);

//Store values into an array and convert them into string and back
// $myArray = ['Maxwel', 'Oduor', 'Otieno', 24, 'JKUAT', 'Computer Science'];
// $myString = implode('|', $myArray);
// echo $myString."<br>";

// $myArrayNew = explode('|', $myString);
// var_dump($myArrayNew);
// echo $myArrayNew[4];

//GET the session and msisdn values adn store them in a string in a file.
$sessionFile = fopen("session_files", "w") or die("Unable to open the file");
$arrayUser = [];
// var_dump($arrayUser);

// foreach ($row as $rowUser){
//     array_push($arrayUser, $rowUser->fName);
//     // echo "<br>".$rowUser->fName;
// }

$churchID = $row1->churchID;

$query_church = "SELECT * FROM church_info WHERE churchID= ?";
$stmt_church = $pdo->prepare($query_church);
$stmt_church->execute([$churchID]);
// $row = $stmt->fetch(PDO::FETCH_OBJ);
$row_church = $stmt_church->fetch();

// array_push($arrayUser, $row_church->churchName);

$query_service = "SELECT * FROM church_service WHERE churchID= ?";
$stmt_service = $pdo->prepare($query_service);
$stmt_service->execute([$churchID]);
// $row = $stmt->fetch(PDO::FETCH_OBJ);
$row_service = $stmt_service->fetchAll();

// foreach ($row_service as $row){
//     array_push($arrayUser, $row->serviceName." ".$row->serviceTheme." ".$row->serviceDate);
// }
// array_push($arrayUser, $row_service->serviceName);
// array_push($arrayUser, $row_service->serviceTheme);
// array_push($arrayUser, $row_service->serviceTime);

$sessionDetailsArray = [$SESSIONID, $MSISDN, $USSDCODE, $INPUT];
// $sessionDetailsArray = array_merge($arrayUser, $sessionDetailsArray);
$sessionDetailsString = implode('|', $sessionDetailsArray);

fwrite($sessionFile, $sessionDetailsString);
fclose($sessionFile);

// echo "<br> Save successful<br>";

// var_dump($arrayUser);
$level = 1;
$menu = "<br>   1. Register <br>
                2. Book Service <br>
                3. Exit<br>";

echo "Welcome $row1->firstName. Reply accordingly <br>";
echo $menu;

if ($INPUT == 1){
    $registerMenu = "<br> Enter your full names";
    echo $registerMenu."<br>";
    $level++;

    $fileEdit = fopen("session_files", "w");
    // array_push($sessionDetailsArray, $level);
    $sessionDetailsArray[4] = $level;
    var_dump($sessionDetailsArray);
    echo "<br>";
    $sessionDetailsString = implode('|', $sessionDetailsArray);
    var_dump($sessionDetailsString);
    echo "<br>";

    fwrite($fileEdit, $sessionDetailsString);
    fclose($fileEdit);

    echo $level."<br>";
    // var_dump($sessionDetailsString);
    echo "<br>";
}else if($INPUT == 2){
    $myfile = fopen("session_files", "r");
    $myString= fread($myfile, filesize("session_files"));
    $myArrayFile = explode('|', $myString);
    fclose($myfile);
    // echo $myArrayFile;
    var_dump($myArrayFile);
    echo "<br>";
    var_dump($myString);
}
?>

<!---This is how I plan to write this code. It's important to organize my ideas so that the flow will be easy.

When the user first dials the USSD code, we will get their credentials and match them against the database to find out if they are registered with any church.
This level of intereaction will be set to 1 - Beginning.
UNREGISTERED MEMBER -- If not a member, the member shall be directed to the register page. i.e Register, Exit. Level 2.

REGISTERED MEMBER -- If the user is a member then they will be directed to a different section of the code. i.e Book, Update settings, Exit.
Next we will get the details of the user from the database so as to display their exact church and available services.
using the phone number we can find out which church they belong to and use that churchID to find out the number of services available to display
MSISDN userPhone > church_member churchID > church_info churchName > church_service serviceName, ServiceTheme, serviceDate, etc. (table column)
Option 1: Book - Display the church services for them to choose from
Option 2: Update Settings - Show them their details and ask what to change. E.g. firtName, lastName, church, etc.
Option 3: Exit - Exit from the USSD;
This is level 3 - Members Page.

level 4
--->


