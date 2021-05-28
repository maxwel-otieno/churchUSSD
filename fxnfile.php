<?php

//RECCURING FUNCTIONS
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