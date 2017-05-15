<?php
    include("../includes/basic_init.php");
    
    if (isset($_POST['json_dump'])) {
        $json_dump = $_POST['json_dump'];
        $date = date('Y-m-d');
        $json_file = "stored_rows_dump_$date.json";
        file_put_contents($json_file, $json_dump);
        die('{"success":1}');
    }
    else {
        die('no json_dump');
    }
?>
