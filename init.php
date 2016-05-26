<?php

    { # basic init
        ini_set('memory_limit', '4000M');
        $requestVars = array_merge($_GET, $_POST);
    }

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

	{ # vars
		if (!isset($search_path)) {
			if ($db_type == 'pgsql') {
				$search_path = 'public'; #todo this is not really going to work for mysql
			}
			else {
				$search_path = $db_name;
			}
		}

		$schemas_in_path = explode(',', $search_path);
	}

    require_once('classes/DbViewer.php');

    { # postgres-specific setup
        if ($db_type == 'pgsql') {
            if (isset($search_path)) {
                Util::sql("set search_path to $search_path");
            }
        }
    }
