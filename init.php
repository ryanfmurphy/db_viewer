<?php
    $trunk = __DIR__;
    $trunkParent = dirname($trunk);
    include("$trunkParent/db_viewer/init.php"); #todo don't depend on db_viewer
    include("$trunk/dash_config.php");
