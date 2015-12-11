<?php

    function getUserNameById($user_id) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM users WHERE user_id=$user_id");
            if(!$smt->execute())
                print_r($smt->errorInfo());

            if($row = $smt->fetch())
                return $row["name"];
            else
                return null;
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
    function getGroupNameById($group_id) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM groups WHERE group_id=$group_id");
            if(!$smt->execute())
                print_r($smt->errorInfo());

            if($row = $smt->fetch())
                return $row["name"];
            else
                return null;
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }

?>