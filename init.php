<?php
    { # init
        $trunk = __DIR__;
        $trunkParent = dirname($trunk);
    }

    { # default values
        $fields2omit_global = array();
        $multipleTablesFoundInDifferentSchemas = false;
        $search_path = null;
    }

    { # custom config
        include("$trunk/db_config.php");
        include("$trunk/dash_config.php");
    }

    { # classes
        include("$trunk/classes/Db.php");
        include("$trunk/classes/DbUtil.php");
    }

