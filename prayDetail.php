<?php
    session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>pray detail</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <link rel="stylesheet" type="text/css" href="pray.css"/>
    </head>
    <body>
<?php

include_once 'constant.inc';
include_once 'commonFunc.inc';

    // 如果有reply消息，说明是提交了回复内容
    if(filter_has_var (INPUT_POST, "reply")){
        $content = filter_input(INPUT_POST, "reply");
        newReply($content);
    }
    
    // 有pray id消息，说明是从prayList跳转过来
    if(filter_has_var(INPUT_POST, "pray_id")) {
        $pray_id = filter_input(INPUT_POST, "pray_id");
        $_SESSION["pray_id"] = $pray_id;
        showPrayDetail($pray_id);
    }
    // 有pray id，可能是自我刷新
    else if(isset ($_SESSION["pray_id"])) { 
        $pray_id = $_SESSION["pray_id"];
        showPrayDetail($pray_id);
    }
    // 如果没有途径取得pray id，可能出错了，只能重新登录
    else {
        echo "<script>window.location = 'index.php'</script>";
    }
        
    
    function newReply($content) {
        $pray_id = $_SESSION["pray_id"];
        $user_id = $_SESSION["user_id"];
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("INSERT INTO replyList VALUES (NULL, ?, ?, ?)");
            $smt->bindParam(1, $pray_id);
            $smt->bindParam(2, $user_id);
            $smt->bindParam(3, $content);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }
        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
    function showPrayDetail($pray_id) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("SELECT * FROM prayList WHERE pray_id=?");
            $smt->bindParam(1, $pray_id);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }
            $row = $smt->fetch();
            if(!$row)
                echo "can not get pray record";
            $owner_id = $row["user_id"];
            $owner_name = getUserNameById($owner_id);
            $pray_content = $row["content"];

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
        
        //输出页面上面部分
        $output =<<<HERE
        <header class="fixedHeader">
            <div class="headerLeftButton">
                <a href="prayList.php" class="backButton"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
            </div>
            <div class="headerTitle">
                pray detail
            </div>
        </header> 
        <section class="sectionWithFooter"> 
            <div class="detailBody">
                <p class="prayItemWrap"><mark>$owner_name:</mark></p>
                <p class="prayItemWrap">$pray_content</p>
            </div>
            <div class="replyList">
HERE;
        print $output;
        
        //输出页面中间列表部分
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("SELECT * FROM replyList WHERE pray_id=?");
            $smt->bindParam(1, $pray_id);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }
            $last_saw_reply = 0;
            while($row = $smt->fetch()) {
                $fans_id = $row["user_id"];
                $fans_name = getUserNameById($fans_id);
                $reply_content = $row["content"];
                $output =<<<HERE
                <p class="replyItem"><mark>$fans_name:&nbsp;</mark>$reply_content</p>
HERE;
                print $output;
                
                if($row["reply_id"] > $last_saw_reply)
                    $last_saw_reply = $row["reply_id"];
            }
            refreshLastSawReply($pray_id, $last_saw_reply);

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
        
        //输出页面下面部分
        $output =<<<HERE
            </div>
        </section>
        <footer class="fixedFooter">
            <form method="post">
                <input class="replyInput" type="text" autofocus required placeholder="your comment" name="reply">
                <button class="replyButton">go</button>
            </form>
        </footer>
HERE;
         print $output;
    }
    
    function refreshLastSawReply($pray_id, $last_saw_reply) {
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
                $smt = $pdo->prepare("UPDATE lastSawReply SET last_saw_reply=? WHERE user_id=? AND pray_id=?");
                $smt->bindValue(1, $last_saw_reply);
                $smt->bindValue(2, $user_id);
                $smt->bindValue(3, $pray_id);
                if(!$smt->execute())
                    print_r($smt->errorInfo());
            }
            else {
                $smt = $pdo->prepare("INSERT INTO lastSawReply VALUES (NULL, ?, ?, ?)");
                $smt->bindValue(1, $user_id);
                $smt->bindValue(2, $pray_id);
                $smt->bindValue(3, $last_saw_reply);
                if(!$smt->execute())
                    print_r($smt->errorInfo());
            }

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }
    
?>
    </body>
</html>

