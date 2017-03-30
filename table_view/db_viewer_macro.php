<?php
    # save and load endpoint for db_viewer macros
    $requestVars = array_merge($_GET, $_POST);
    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $macroName = $requestVars["name"];
    #todo #fixme make macro path a config
    $path = TRUNK . "server/db/db_viewer/table_view/macros/$macroName.json";
    Util::errlog("path = $path\n");

    if ($requestMethod == "POST") {
        Util::errlog("doing POST\n");
        Util::errlog("  requestVars=" . print_r($requestVars, 1) . "\n");
        $events = $requestVars["events"];
        Util::errlog("events = ".print_r($events,1)."\n");

        file_put_contents($path, json_encode($events));
        # get JSON from front-end and save to file
        $response = array(
            'success' => true,
        );
        die(json_encode($response));
    }
    elseif ($requestMethod == "GET") {
        Util::errlog("doing GET\n");
        $macro = file_get_contents($path);
        die($macro);
    }
    else {
        Util::errlog("warning: neither POST nor GET\n");
    }
?>
