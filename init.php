<?php
    { # init
        $trunk = __DIR__;
        $trunkParent = dirname($trunk);
        #include("$trunkParent/db_viewer/init.php"); #todo don't depend on db_viewer
    }

    { # default values
        $fields2omit_global = array();
        $multipleTablesFoundInDifferentSchemas = false;
    }

    { # custom config
        include("$trunk/db_config.php");
        include("$trunk/dash_config.php");
    }

    { # classes
        include("$trunk/classes/Db.php");
        include("$trunk/classes/DbUtil.php");
    }

