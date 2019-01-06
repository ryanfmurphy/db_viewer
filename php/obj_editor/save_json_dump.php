<?php
    #todo #fixme this is used to save files that are not always json
    #            rename to something like save_data_as_file
    include("../includes/basic_init.php");

    # valid post?
    if (isset($_POST['data'])
        && isset($_POST['filename'])
    ) {
        # put data in vars
        $data = $_POST['data'];
        $date = date('Y-m-d');
        $ext = (isset($_POST['ext'])
                    ? $_POST['ext']
                    : null);

        # decide filename
        $filename_after_date = isset($_POST['filename_after_date'])
                                    ? '.' . $_POST['filename_after_date']
                                    : null;
        $filename_base = $_POST['filename']
                    . '.' . $date;
        $ext_no = 1;
        $filename = $filename_base . $filename_after_date . $ext;

        # duplicate file? save with number added
        $ext_no = 0;
        do {
            $ext_no++; # first no will be 1
            $filename = $filename_base . "." . $ext_no . $filename_after_date . $ext;
        }
        while (file_exists($filename));

        # save file
        file_put_contents($filename, $data);

        # respond
        die('{"success":1}');
    }
    else {
        die('no data');
    }
?>
