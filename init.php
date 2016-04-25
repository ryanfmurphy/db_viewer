<?php
	require_once('db_config.php');

    {   # some programs may provide
        # a DB connection and Util class themselves
        if (!class_exists('Util')) {
            $db = new PDO(
                "$db_type:host=$db_host;dbname=$db_name",
                $db_user, $db_password
            );

            require_once('classes/Util.php');
        }
    }

    require_once('classes/DbViewer.php');

    { # postgres-specific setup
        if (isset($pg) && $pg) {
            if (isset($search_path)) {
                Util::sql("set search_path to $search_path");
            }
        }
    }
