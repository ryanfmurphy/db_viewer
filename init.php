<?php

	function do_log($msg) {
		$logPath = __DIR__.'/error_log';
		#$msg .= " (to $logPath)";
		error_log("$msg", 3, $logPath);
	}
	function log_and_say($msg) {
		echo $msg;
		do_log($msg);
	}

    { # basic init
        #todo generalize timezone
        # btw - why are we not allowed to use the system's timezone setting??
        date_default_timezone_set("America/Chicago");

		do_log(date('c') . " - db_viewer received a request\n");
        ini_set('memory_limit', '4000M');
        $requestVars = array_merge($_GET, $_POST);
    }

    { # vars - initial values
        $pluralize_table_names = false;
        $slow_tables = array();

        require_once('db_config.php');
    }

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

	{ # vars adjustments
        { # search_path
            if (!isset($search_path)) {
                if ($db_type == 'pgsql') {
                    $search_path = 'public';
                }
                else {
                    $search_path = $db_name;
                }
            }

            { # get as array
                $search_path_no_spaces = str_replace($search_path, ' ', '');
                $schemas_in_path = explode(',', $search_path_no_spaces);
            }
        }

	}

    require_once('classes/DbViewer.php');

    { # postgres-specific setup
        if ($db_type == 'pgsql') {
            if (isset($search_path)) {
                Util::sql("set search_path to $search_path");
            }
        }
    }
