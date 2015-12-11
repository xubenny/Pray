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
        <title>new group</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <link rel="stylesheet" type="text/css" href="pray.css"/>
    </head>
    <body>
<?php

include_once 'constant.inc';

    if(isset($_SESSION["user_id"])) {
        // 如果没有参数，显示创建组界面
        if(!filter_has_var(INPUT_POST, "name")) {
            showNewGroup();
        }
        // 如果有POST过来的name，说明用户提交了创建组申请
        else {
            $user_id = $_SESSION["user_id"];
            $group_name = filter_input(INPUT_POST, "name");
            if($group_name == "") {
                showNewGroupFail($group_name);
            }
            else if($group_id = newGroup($user_id, $group_name)) {
                $_SESSION["group_id"] = $group_id;
                $_SESSION["group_name"] = $group_name;
                showNewGroupSuccess($group_name);
            }
            else {
                showNewGroupFail($group_name);
            }
        }
    }
    // 如果没有user_id，说明session过期，需要重新登录
    else {
        echo "<script>window.location = 'index.php'</script>";
    }
    
    function showNewGroup() {
        $user_name = $_SESSION["user_name"];
        $output =<<<HERE
            <header class="fixedHeader">
                <div class="headerLeftButton">
                    <a href="groupList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
                </div>
                <div class="headerTitle">
                    new group
                </div>
            </header>
            <section class="sectionWithFooter">
                <form method="post">
                    <br/><br/><br/><br/>
                    <input class="largeInput" autofocus required placeholder="create a new group" name="name"/><br/><br/>
                    <button class="largeButton">create</button><br/>
                </form>
                    <a class="mediumLink" href="groupList.php">cancel</a>
            </section>
            <footer class="fixedFooter">
                <div class="footerTitle">user:&nbsp;$user_name</div>
            </footer>
HERE;
        print $output;
    }
    
    function newGroup($user_id, $group_name) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("SELECT * FROM groups WHERE name=?");
            $smt->bindParam(1, $group_name);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }

            //有记录，说明组已经存在，不能再创建
            if($smt->fetch()) {
                return null;
            }
            else {
                // group id, group name, leader id
                $smt = $pdo->prepare("INSERT INTO groups VALUES (NULL,?,?)");
                $smt->bindParam(1, $group_name);
                $smt->bindParam(2, $user_id);
                if(!$smt->execute())
                {
                    print_r($smt->errorInfo());
                }
                
                // 取回group id
                $smt = $pdo->prepare("SELECT * FROM groups WHERE name=?");
                $smt->bindParam(1, $group_name);
                if(!$smt->execute())
                {
                    print_r($smt->errorInfo());
                }
                $row = $smt->fetch();
                $group_id = $row["group_id"];

                // 同步group member记录
                $smt = $pdo->prepare("INSERT INTO groupMember VALUES (NULL,?,?,0)");
                $smt->bindParam(1, $user_id);
                $smt->bindParam(2, $group_id);
                if(!$smt->execute())
                {
                    print_r($smt->errorInfo());
                }
                return $group_id;
            }

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
        
    }
    
    function showNewGroupSuccess($group_name) {
        $user_name = $_SESSION["user_name"];
        $output =<<< HERE
        <header class="fixedHeader">
            <div class="headerLeftButton">
                <a href="groupList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
            </div>
            <div class="headerTitle">
                congratulation!
            </div>
        </header>
        <section class="sectionWithFooter">
            <br/>
            <h1>group <mark>$group_name</mark> have been created</h1>
            <h3>now you are the group leader, tell people join your group and pray together</h3>
            <a class="largeLink" href="prayList.php">continue</a>
        </section>
        <footer class="fixedFooter">
            <div class="footerTitle">user:&nbsp;$user_name</div>
        </footer>
HERE;
        print $output;
    }
    
    function showNewGroupFail($group_name) {
        $user_name = $_SESSION["user_name"];
        $output =<<<HERE
        <header class="fixedHeader">
            <div class="headerLeftButton">
                <a href="groupList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
            </div>
            <div class="headerTitle">
                sorry!
            </div>
        </header>
        <section class="sectionWithFooter">
            <br/>
            <h1>group <mark>$group_name</mark> already exist or illegal</h1>
            <br/><br/>
            <a class="largeLink" href="newGroup.php">create group with another name</a><br/><br/><br/>
            <a class="mediumLink" href="groupList.php">cancel</a>
        </section>
        <footer class="fixedFooter">
            <div class="footerTitle">user:&nbsp;$user_name</div>
        </footer>
HERE;
        print $output;
    }

?>
    </body>
</html>