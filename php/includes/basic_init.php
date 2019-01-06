<?php
    # basic init

    #todo generalize timezone
    date_default_timezone_set("America/Chicago");
    ini_set('memory_limit', '4000M');

    $requestVars = array_merge($_GET, $_POST);
    $trunk = dirname(__DIR__);

    include("$trunk/includes/misc_fns.php");

    do_log(date('c') . " - table_view received a request\n");
?>
