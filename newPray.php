<?php
    session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>new pray</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <link rel="stylesheet" type="text/css" href="pray.css"/>
    </head>
    <body>
<?php
include_once 'constant.inc';

    // 如果没有POST消息，说明是首次跳转过来，显示新建pray页面
    if(!filter_has_var(INPUT_POST, "content")) {
        showNewPray();
    }
    // 如果有POST消息，则把内容加到pray数据库中
    else {
        $content = filter_input(INPUT_POST, "content");
        newPray($content);
        echo "<script>window.location = 'prayList.php'</script>";
    }
    
    function showNewPray() {
        $user_name = $_SESSION["user_name"];
        $group_name = $_SESSION["group_name"];
        
        $output =<<<HERE
        <header class="fixedHeader">
            <div class="headerLeftButton">
                <a href="prayList.php"><img border="0" src="images\backButton.jpg" width="30" height="30"></a>
            </div>
            <div class="headerTitle">
                new item
            </div>
        </header>
        <section class="sectionWithFooter">
            <br/><br/>
            <form method="post">
                <textarea class="prayInput" rows="6" autofocus required placeholder="please type what you want to pray" name="content"></textarea><br/>
                <button class="largeButton">submit</button>
            </form>
        </section>
        <footer class="fixedFooter">
            <div class="footerTitle">user:&nbsp;$user_name \t\t $group_name</div>
        </footer>
HERE;
        print $output;
    }
    
    function newPray($content) {
        $user_id = $_SESSION["user_id"];
        $group_id = $_SESSION["group_id"];

        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("INSERT INTO prayList VALUES (NULL, ?, ?, ?)");
            $smt->bindParam(1, $user_id);
            $smt->bindParam(2, $group_id);
            $smt->bindParam(3, $content);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
        
    }
?>
    </body>
</html>

