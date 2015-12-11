<?php
session_start();
?>
<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <title>pray</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes">
        <link rel="stylesheet" type="text/css" href="pray.css"/>
		<link rel="apple-touch-icon" href="images\icon.jpg" />
    </head>
    <body>
<?php

include_once 'constant.inc';

    // 如果有session，则不用重新登录
    if(isset($_SESSION["user_id"])) {
        echo "<script>window.location = 'groupList.php'</script>";
    }
    // 如果没有POST信息，则显示login界面
    else if(!filter_has_var(INPUT_POST, "name")) {
        showLogin();
    }
    // 如果有POST过来的name，说明用户输入了用户名和密码，应进行login处理
    else {
        $name = filter_input(INPUT_POST, "name");
        $pass = filter_input(INPUT_POST, "pass");

        //如果用户名和密码合法，则跳转到groupList page
        if($user_id = getUserId($name, $pass)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["user_name"] = $name;
            echo "<script>window.location = 'groupList.php'</script>";
        }
        //否则显示登录失败
        else {
            showLoginFail($name);
        }
    }

    function showLogin() {
        $output =<<<HERE

            <header class="mediumHeader"><img src="images\cross.jpg" width="162" height="108"></header>
            <form method="post">
                <input class="largeInput" autofocus required placeholder="your name" name="name"/><br/>
                <input class="largeInput" type="password" placeholder="your password" name="pass"/><br/>
                <button class="largeButton">sign in</button>
            </form>
            <a class="largeLink" href="signup.php">sign me up</a>
HERE;
        print $output;
    }

    function getUserId($name, $pass) {

        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM users WHERE name=? and pass=?");
            $smt->bindParam(1, $name);
            $smt->bindParam(2, $pass);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }

            //有匹配记录
            if($row = $smt->fetch())
                return $row["user_id"];
            else
                return null;
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    } // end userId()

    function showLoginFail($name) {
        $output =<<< HERE

            <header class="largeHeader">sorry!</header>
            <section class="sectionOnly">
                <h3><mark>$name</mark> is not exist or password not match, please try again</h3>
                <br/><br/>
                <a class="largeLink" href="index.php">sign in again</a>
                <br/><br/><br/>
                <a class="mediumLink" href="signup.php">sign me up</a>
            </section>
HERE;
        print $output;
    } // end showLoginFail()
?>
    </body>
</html>
