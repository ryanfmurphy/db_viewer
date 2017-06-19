<?php
    #todo #fixme this is used to save files that are not always json
    #            rename to something like save_data_as_file
    include("../includes/basic_init.php");
    
    if (isset($_POST['data'])
        && isset($_POST['filename'])
    ) {
        $data = $_POST['data'];
        $date = date('Y-m-d');
        $ext = (isset($_POST['ext'])
                    ? $_POST['ext']
                    : null);
        $filename = $_POST['filename']
                    . '_' . $date
                    . $ext;
        #$json_file = "stored_rows_dump_$date.json";
        file_put_contents($filename, $data);
        die('{"success":1}');
    }
    else {
        die('no data');
    }
?>
