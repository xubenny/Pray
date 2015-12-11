<?php
    session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <title>sign up</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
        <link rel="stylesheet" type="text/css" href="pray.css"/>
    </head>
    <body>
<?php
include_once 'constant.inc';

    // 如果没有POST消息，则显示注册界面
    if(!filter_has_var(INPUT_POST, "name")) {
        showSignup();
    }
    // 如果有POST过来name，则处理signup请求
    else {
        $name = filter_input(INPUT_POST, "name");
        $pass = filter_input(INPUT_POST, "pass");

        if($name == "") {
            showSignupFail($name);
        }
        else if($user_id = newUser($name, $pass)) {
            $_SESSION["user_id"] = $user_id;
            $_SESSION["user_name"] = $name;
            showSignupSuccess($name);
        }
        else {
            showSignupFail($name);
        }
    }

    function showSignup() {
        $output =<<<HERE
            <header class="largeHeader">sign up</header>
            <form method="post">
                <input class="largeInput" autofocus required placeholder="create a name" name="name"/><br/>
                <input class="largeInput" type=password placeholder="create password" name ="pass"/><br/>
                <button class="largeButton">sign up</button>
            </form>
                <a class="mediumLink" href="index.php">cancel</a>
HERE;
        print $output;
    }

    function newUser($name, $pass) {
        try {
            $pdo = new PDO("mysql:host=".DBHOST."; port=".DBPORT."; dbname=".DBNAME, DBUSER, DBPASS);
            //$pdo = new PDO("mysql:host=localhost; dbname=pray","pray", "pray");
            $smt = $pdo->prepare("SELECT * FROM users WHERE name=?");
            $smt->bindParam(1, $name);

            if(!$smt->execute())
            {
                print_r($smt->errorInfo());
            }

            //有记录，说明用户已经存在，不能再创建
            if($smt->fetch()) {
                return null;
            }
            else {
                $smt = $pdo->prepare("INSERT INTO users VALUES (NULL,?,?)");
                $smt->bindParam(1, $name);
                $smt->bindParam(2, $pass);
                if(!$smt->execute())
                {
                    print_r($smt->errorInfo());
                }

                $smt = $pdo->prepare("SELECT * FROM users WHERE name=?");
                $smt->bindParam(1, $name);
                if(!$smt->execute())
                {
                    print_r($smt->errorInfo());
                }
                $row = $smt->fetch();
                return $row["user_id"];
            }

        } catch (Exception $ex) {
            echo 'ERROR: '. $ex->getMessage();
        }
    }

    function showSignupSuccess($name) {
        $output =<<<HERE
            <header class="largeHeader">welcome <mark>$name</mark></header>
            <section class="sectionOnly">
                <h3>now you can login with your new account</h3>
                <a class="largeLink" href="groupList.php">continue</a>
            </section>
HERE;
        print $output;
    }

    function showSignupFail($name) {
        $output =<<< HERE
            <header class="largeHeader">sorry!</header>
            <section class="sectionOnly">
                <h3><mark>$name</mark> already exist or illegal</h3>
                <br/><br/>
                <a class="largeLink" href="signup.php">sign up with another name</a>
                <br/><br/><br/>
                <a class="mediumLink" href="index.php">sign in</a>
            </section>
HERE;
        print $output;
    }

?>
    </body>
</html>

