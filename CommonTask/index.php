<?php
$username = "root" ;
$link = new mysqli("localhost", $username, "","lab5_wt");
$link -> set_charset("UTF-8");
$error_message = "Connection to SQL failed " ;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Request</title>
</head>
<body>
    <section>
        <form action="index.php" method="post">
            <input type="submit" value="Add to BD" name="insert">
            <input type="submit" value="Select" name="select">
            <input type="submit" value="View Table" name="view">
            <input type="submit" value="vote" name="ex_vote"><br><br>

<?php
    if (!$link) echo "Connection to SQL failed ";
    else {
        //echo "Connect to mySQL successfully".'<br><br>';

        $table_columns_name = $link->query("SELECT * FROM `contacts`")->fetch_assoc() ;
        if ($table_columns_name != false xor $table_columns_name != null) $count = count($table_columns_name);
        else {
            $count = 0 ;
            $table_columns_name = array("id"=>"id","name"=>"name","mail"=>"mail","password"=>"password");
        }

        if ($_POST["insert"] || $_POST["select"]) {

            foreach (array_keys($table_columns_name) as $name) {
                if (!($_POST["insert"] && $name == 'id'))
                echo $name . " : " . "<input type=\"text\" name=$name value='' >" . " ";
            }

            if ($_POST["insert"]  ) {

                foreach (array_keys($table_columns_name) as $name) {
                    if (!($_POST["insert"] && $name == 'id'))
                        if (strlen(trim($_POST[$name])) == 0)
                            die("<br><br> Incorrect put");
                }

                $start = microtime(true);
                $memory_start = memory_get_usage() ;

                $sql = "INSERT INTO `contacts`(`id`, `name`, `mail`,`password`) VALUES (";

                $id = $link->query("SELECT MAX(id) as max FROM  `contacts`")->fetch_assoc()['max'];
                $sql .= "".($id + 1).",";

                foreach (array_keys($table_columns_name) as $name) {
                    if ($name != 'id') $sql .= "'" . $_POST[$name] . "'" . ",";
                }
                $sql = preg_replace("/,$/", "", $sql) . ")";
     
                $start = microtime(true);
                $result = $link->query($sql);
                $time = microtime(true) - $start;
                $memory = memory_get_usage() - $memory_start ;
                if ($result === false) echo "nea";
                else {
                    logging($link,"insert to bd");
                    echo "<br>"."Time : " . $time."<br>";
                    echo "used RAM : ".$memory."<br>";
                }
            }
            elseif ($_POST["select"]) {

                $f = false ;
                $entered_data = array();
                $i = 0 ;
                foreach (array_keys($table_columns_name) as $name) {

                    if (strlen(trim($_POST[$name])) != 0) {
                        $f=true;
                        $entered_data += array($name => $_POST[$name]) ;
                    }
                } if (!$f) die("<br><br> Incorrect put");

                $memory_start = memory_get_usage() ;
                $start = microtime(true);
                $request = $link->query("SELECT * FROM `contacts`");
                $time = microtime(true) - $start;
                $memory = memory_get_usage() - $memory_start ;

                echo "<br>";
                if ($request !== false) {

                    while (($row = $request->fetch_assoc()) != false ){
                        $i = 0 ;
                        $fl = true;

                        foreach ($entered_data as $item) {
                            $q = $row[array_keys($entered_data)[$i]];
                            if ($item != $row[array_keys($entered_data)[$i++]]) {
                                $fl = false ;
                                break;
                            }
                        }
                        if ($fl){
                            foreach ($row as $value) echo $value." ";
                            echo "<br>";
                        }
                    }

                    $request->free();

                    logging($link,"select from bd");
                    echo "Time : " . $time."<br>";
                    echo "used RAM : ".$memory."<br>";

                } else echo "Request error";
            }

        } else {
            if ($_POST["view"]) {
                if ($table_columns_name == null) return;
            $id = array_keys($table_columns_name)[0];
            $request = $link->query("SELECT * FROM `contacts` ORDER BY ".$id);
            if ($request !== false) {
                $i = 0;
                while (($row = $request->fetch_assoc()) != false) {
                    foreach ($row as $value) echo $value." ";
                    echo "<br>";
                }
                $request->free();
            }
            logging($link,"view all bd");
        } else {
                echo "<input type='Hidden' name='id' value='1'>";
            echo "<p><b>Оцените сайт:</b></p>";

            $table = $link->query("SELECT * FROM `vote`");
            while (($row = $table->fetch_assoc()) != false) {
                $str = "<input type='Radio' name='vote' value='" . $row['id'] . "'> " . $row['name'] . "<br>";
                echo $str;
            }
            echo "<input type='Submit' value=' Голосовать' name='submit' style='margin-top:10px;'><br><br>";

            if (isset($_POST['submit'])) {

                $id = $_POST['vote'];
                $result = $link->query("UPDATE `vote` SET amount = amount + 1 WHERE id = '$id' ");
                $sum = $link->query("SELECT SUM(`amount`) AS sum FROM `vote`")->fetch_assoc()['sum'];
                $table = $link -> query("SELECT * FROM `vote`");
                while (($row = $table->fetch_assoc()) != false ) {
                    $str = $row['name'].' '.$row['amount'];
                    $graf = $row['amount']*100/$sum;
                    echo "<tr><td>".$row['name']." "."</td><td style='text-align: center'><b>".$row['amount']."</b></td><td>".
                        " <span style='font-size: small'>".round($graf, 3). "%</span><div style='background: #dd5044; height:5px; width:" .round($graf, 0)."px'></div></td></tr>";
                }
                echo "<br>"."<br>";
            }
            if ($result != false) logging($link,"user voted successfully");

            }

        }
    }

function logging ($link, $description)
{
    $date = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'];
    $username = $GLOBALS['username'] ;
    $sql = "INSERT INTO  `logging`(`ip`, `user`, `description`, `data-time`) VALUES (";
    $sql .= "'$ip','$username','$description','$date')";

    $link->query($sql);
}

?>
        </form>
    </section>
</body>
</html>
