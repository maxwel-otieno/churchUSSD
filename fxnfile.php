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