<?php
    session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>group list</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <link rel="stylesheet" type="text/css" href="pray.css"/>
    </head>
    <body>
<?php

include_once 'constant.inc';
include_once 'commonFunc.inc';

    // 如果有logout消息，说明用户点击了logout
    if(filter_has_var(INPUT_POST, "logout")) {
        // 清除所有session
        session_unset();
        echo "<script>window.location = 'index.php'</script>";
    }
    // 如果有user_id，说明用户已经成功login，显示group列表
    else if(isset($_SESSION["user_id"])) {

        if(filter_has_var(INPUT_POST, "isAccept")) {
            $isAccept = filter_input(INPUT_POST, "isAccept");
            $user_id = filter_input(INPUT_POST, "user_id");
            $group_id = filter_input(INPUT_POST, "group_id");
            joinApprove($isAccept, $user_id, $group_id);
        }

        if(needApproveJoinGroup()) {
            showJoinApprove();
        }
        else {
            showGroupList();
        }
    }
    // 如果没有user_id，说明session过期，需要重新登录
    else {
        echo "<script>window.location = 'index.php'</script>";
    }
    
    function joinApprove($isAccept, $user_id, $group_id) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            
            // 把用户加入到组成员中
            if($isAccept) {
                $smt = $pdo->prepare("INSERT INTO groupMember VALUES (NULL, ?, ?, 0)");
                $smt->bindParam(1, $user_id);
                $smt->bindParam(2, $group_id);
                if(!$smt->execute())
                    print_r($smt->errorInfo());
            }

            // 不管接受或拒绝，都从等待确认列表中删除
            $smt = $pdo->prepare("DELETE FROM waitForJoinGroup WHERE user_id=? AND group_id=?");
            $smt->bindParam(1, $user_id);
            $smt->bindParam(2, $group_id);
            if(!$smt->execute())
                print_r($smt->errorInfo());
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
    function needApproveJoinGroup() {
        $leader_id = $_SESSION["user_id"];
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM waitForJoinGroup, groups WHERE groups.leader_id=$leader_id AND groups.group_id=waitForJoinGroup.group_id");
            if(!$smt->execute())
                print_r($smt->errorInfo());
            
            if($smt->fetch())
                return true;
            else
                return false;

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
    function showJoinApprove() {
        $leader_id = $_SESSION["user_id"];
        $leader_name = getUserNameById($leader_id);
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM waitForJoinGroup, groups WHERE groups.leader_id=$leader_id AND groups.group_id=waitForJoinGroup.group_id");
            if(!$smt->execute())
                print_r($smt->errorInfo());
            
            // 取第一个记录进行处理
            if($row = $smt->fetch()) {
                $user_id = $row["user_id"];
                $group_id = $row["group_id"];
                $user_name = getUserNameById($user_id);
                $group_name = $row["name"];
                
                $output =<<<HERE
                <header class="fixedHeader">
                    <div class="headerLeftButton">
                        <a href="groupList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
                    </div>
                    <div class="headerTitle">
                        join request
                    </div>
                </header>
                <section class="sectionWithFooter">
                    <br/>
                    <h1><mark>$user_name</mark> want to join group <mark>$group_name</mark>, do you accept?</h1>
                    <form method="post">
                        <input type="hidden" name="user_id" value=$user_id/>
                        <input type="hidden" name="group_id" value=$group_id/>
                        <button class="largeButton" name="isAccept" value=1>accept</button><br/>
                        <button class="mediumButton" name="isAccept" value=0>reject</button>
                    </form>
                </section>
                <footer class="fixedFooter">
                    <div class="footerTitle">user:&nbsp;$leader_name</div>
                </footer>
HERE;
                print $output;
            }
            
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
   

    
    function showGroupList() {
        //输出页面上面部分
        $output =<<<HERE
            <header class="fixedHeader">
                <div class="headerLeftButton">
                <form method="post">
                    <button id="logout" name="logout"><img border="0" src="images\logoutButton.jpg" width="30" height="30"></button>
                </form>
                </div>
                <div class="headerTitle">
                    select a group
                </div>
                <div class="headerRightButton">
                    <a href="newGroup.php"><img border="0" src="images\plusButton.jpg" width="30" height="30"></a>
                </div>
            </header>
            <section class="sectionWithFooter">
                <form method="post" action="prayList.php">
HERE;
        print $output;
            
        //输出页面中间列表部分
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("SELECT * FROM groups");

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }

            while ($row = $smt->fetch()) {
                $group_id = $row["group_id"];
                $group_name = $row["name"];
                $output =<<<HERE
                    <p class="groupButtonWrap"><button class="groupItem" name="group_id" value=$group_id>$group_name
HERE;
                if($newPrays = prayUnsaw($group_id)) {
                    $output .= "<mark class='newRemind'>&nbsp;$newPrays&nbsp;new</mark>";
                }
                $output .="</button></p>";
                print $output;
            }
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }

        //输出页面下面部分
        $user_name = $_SESSION["user_name"];
        $output =<<<HERE
                </form>
            </section>
            <footer class="fixedFooter">
                <div class="footerTitle">user:&nbsp;$user_name</div>
            </footer>
HERE;
        print $output;
    }
    
    function prayUnsaw($group_id) {
        $user_id = $_SESSION["user_id"];
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM groupMember WHERE user_id=? AND group_id=?");
            $smt->bindValue(1, $user_id);
            $smt->bindValue(2, $group_id);
            if(!$smt->execute())
                print_r($smt->errorInfo());

            //用户不是这个组的成员
            if($smt->rowCount() == 0)
                return 0;

            $row = $smt->fetch();
            $last_saw_pray = $row["last_saw_pray"];
            
            $smt = $pdo->prepare("SELECT * FROM prayList WHERE group_id = ? AND pray_id > ?");
            $smt->bindValue(1, $group_id);
            $smt->bindValue(2, $last_saw_pray);
            if(!$smt->execute())
                print_r($smt->errorInfo());
            
            //有多少个reply是在最后看到的reply之后产生的
            return $smt->rowCount();

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }

?>
    </body>
</html>
