<?php
    session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>pray list</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <link rel="stylesheet" type="text/css" href="pray.css"/>
    </head>
    <body>
<?php

include_once 'constant.inc';
include_once 'commonFunc.inc';

    // 如果有POST过来的group_id，说明用户是从groupList页面跳转过来，判断是否能进入群组
    if(filter_has_var(INPUT_POST, "group_id"))
    {
        $group_id = filter_input(INPUT_POST, "group_id");
        $user_id = $_SESSION["user_id"];

        //检查用户是否该组成员
        if(isMember($user_id, $group_id)) {
            $_SESSION["group_id"] = $group_id;
            $_SESSION["group_name"] = getGroupNameById($group_id);
            showPrayList($group_id);
        }
        //没有记录，询问用户是否加入该组
        else {
            showJoinGroup($group_id);
        }

    }
    // 如果有POST过来的joinGroup，说明用户是从joinGroup页面跳转过来
    else if(filter_has_var(INPUT_POST, "joinGroup")) {
        $group_id = filter_input(INPUT_POST, "joinGroup");
        $user_id = $_SESSION["user_id"];
        joinGroup($user_id, $group_id);
    }
    // 如果没有POST消息，而session中有group id，说明是从其他页面跳转过来（pray detail或new group），直接显示pray list
    else if(isset ($_SESSION["group_id"])) {
        $group_id = $_SESSION["group_id"];
        showPrayList($group_id);
    }
    // 如果POST和session都没有，可能页面过期，重新登录
    else {
        echo "<script>window.location = 'index.php'</script>";
    }
    
    function isMember($user_id, $group_id) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("SELECT * FROM groupMember WHERE user_id=? AND group_id=?");
            $smt->bindParam(1, $user_id);
            $smt->bindParam(2, $group_id);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }

            //有记录，说明是该组成员
            if($smt->fetch()) {
                return true;
            }
            else {
                return false;
            }
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
    function showPrayList($group_id) {
        //输出页面上面部分
        $group_name = $_SESSION["group_name"];
        $output =<<<HERE
        <header class="fixedHeader">
            <div class="headerLeftButton">
                <a href="groupList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
            </div>
            <div class="headerTitle">$group_name</div>
            <div class="headerRightButton">
                <a href="newPray.php"><img border="0" src="images\plusButton.jpg" width="30" height="30"></a>
            </div>
        </header> 

        <section class="sectionWithFooter">
            <form method="post" action="prayDetail.php">
HERE;
        print $output;
            
        //输出页面中间列表部分
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT pray_id, user_id, content FROM prayList WHERE group_id=$group_id ORDER BY pray_id DESC");
            if(!$smt->execute())
                print_r($smt->errorInfo());

            $last_saw_pray = 0;
            while ($row = $smt->fetch()) {
                $pray_id = $row["pray_id"];
                $user_name = getUserNameById($row["user_id"]);
                $content = $row["content"];
                $output =<<<HERE
                    <p class="prayItemWrap"><button class="prayItem" name="pray_id" value=$pray_id><mark>$user_name:&nbsp;</mark>$content
HERE;
                if($newReplys = replyUnsaw($pray_id)) {
                    $output .= "<mark class='newRemind'>&nbsp;$newReplys&nbsp;new</mark>";
                }
                $output .= "</button></p>";
                print $output;

                if($pray_id > $last_saw_pray)
                    $last_saw_pray = $pray_id;
            }
            refreshLastSawPray($last_saw_pray);
            
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
    
    function replyUnsaw($pray_id) {
        $user_id = $_SESSION["user_id"];
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM lastSawReply WHERE user_id=? AND pray_id=?");
            $smt->bindValue(1, $user_id);
            $smt->bindValue(2, $pray_id);
            if(!$smt->execute())
                print_r($smt->errorInfo());
            
            if($smt->rowCount() > 0) {
                $row = $smt->fetch();
                $last_saw_reply = $row["last_saw_reply"];
            }
            else
                $last_saw_reply = 0;
            
            $smt = $pdo->prepare("SELECT * FROM replyList WHERE pray_id = ? AND reply_id > ?");
            $smt->bindValue(1, $pray_id);
            $smt->bindValue(2, $last_saw_reply);
            if(!$smt->execute())
                print_r($smt->errorInfo());
            
            //有多少个reply是在最后看到的reply之后产生的
            return $smt->rowCount();

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
    function refreshLastSawPray($last_saw_pray) {
        $user_id = $_SESSION["user_id"];
        $group_id = $_SESSION["group_id"];
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("UPDATE groupMember SET last_saw_pray=? WHERE user_id=? AND group_id=?");
            $smt->bindValue(1, $last_saw_pray);
            $smt->bindValue(2, $user_id);
            $smt->bindValue(3, $group_id);
            if(!$smt->execute())
                print_r($smt->errorInfo());

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
    
    function showJoinGroup($group_id) {
        $user_name = $_SESSION["user_name"];
        $output =<<<HERE
        <header class="fixedHeader">
            <div class="headerLeftButton">
                <a href="groupList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
            </div>
            <div class="headerTitle">
                join a group
            </div>
        </header>
        <section class="sectionWithFooter">
            <br/>
            <h1>send join request to group leader?</h1>
            <form method="post">
                <button class="largeButton" name="joinGroup" value=$group_id>send</button>
            </form>
            <a class="mediumLink" href="groupList.php">cancel</a>
        </section>
        <footer class="fixedFooter">
            <div class="footerTitle">user:&nbsp;$user_name</div>
        </footer>
HERE;
        print $output;
    }
    
    function joinGroup($user_id, $group_id) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM waitForJoinGroup WHERE user_id=$user_id AND group_id=$group_id");
            if(!$smt->execute())
                print_r($smt->errorInfo());

            //如果已经有申请过就不用重复申请了
            if(!$smt->fetch()) {
                $smt = $pdo->prepare("INSERT INTO waitForJoinGroup VALUES (NULL, $user_id, $group_id)");
                if(!$smt->execute())
                    print_r($smt->errorInfo());
            }
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
        
        // show feedback
        $leader_name = getLeaderName($group_id);
        $user_name = $_SESSION["user_name"];
        $output =<<<HERE
        <header class="fixedHeader">
            <div class="headerLeftButton">
                <a href="groupList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
            </div>
            <div class="headerTitle">
                join request sent
            </div>
        </header>
        <section class="sectionWithFooter">
            <br/>
            <h1>join group request already sent to leader <mark>$leader_name</mark>, please remind him(her) to approve</h1>
            <a class="largeLink" href="groupList.php">continue</a>
        </section>
        <footer class="fixedFooter">
            <div class="footerTitle">user:&nbsp;$user_name</div>
        </footer>
HERE;
        print $output;
    }
    
    function getLeaderName($group_id) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");

            $smt = $pdo->prepare("SELECT * FROM groups WHERE group_id=$group_id");
            if(!$smt->execute())
                print_r($smt->errorInfo());

            $row = $smt->fetch();
            if(!$row)
                return null;
            $leader_id = $row["leader_id"];
            
            $smt = $pdo->prepare("SELECT * FROM users WHERE user_id=$leader_id");
            if(!$smt->execute())
                print_r($smt->errorInfo());

            $row = $smt->fetch();
            if(!$row)
                return null;
            return $row["name"];            
            
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }

?>        
    </body>
</html>