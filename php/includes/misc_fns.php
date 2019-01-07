<?php

    # some misc functions
    function do_log($msg) {
        $logPath = __DIR__.'/error_log';
        error_log("$msg", 3, $logPath);
    }

    function log_and_say($msg) {
        echo $msg;
        do_log($msg);
    }

?>
