<?php
    { # some misc functions
        function do_log($msg) {
            $logPath = __DIR__.'/error_log';
            error_log("$msg", 3, $logPath);
        }
        function log_and_say($msg) {
            echo $msg;
            do_log($msg);
        }
    }

    { # basic init
        #todo generalize timezone
        # btw - why are we not allowed to use the system's timezone setting??
        date_default_timezone_set("America/Chicago");

		do_log(date('c') . " - db_viewer received a request\n");
        ini_set('memory_limit', '4000M');

        $requestVars = array_merge($_GET, $_POST);
        $trunk = dirname(__DIR__);
    }

    { # config vars
        require_once("$trunk/classes/Config.php");

        $default_values = Config::default_values($trunk);

        $config_file_path = (file_exists("$trunk/db_config.php")
                                ? "$trunk/db_config.php"
                                : "$db_viewer_path/db_config.php");
        if ($config_file_path) {
            do_log("including config file: '$config_file_path'\n");
            $config = Config::load_config($config_file_path, $trunk, $default_values);
            extract($config); # creates $variables
            $db_viewer_path = "$trunk/db_viewer"; #todo #fixme rename to $db_viewer_trunk
        }
    }

    { # Util includes
        require_once("$trunk/db_stuff/Db.php");
        require_once("$trunk/db_stuff/DbUtil.php");
        require_once("$trunk/classes/DbViewer.php");
        require_once("$trunk/classes/Curl.php");
    }

	{ # vars adjustments after includes
        { # search_path
            if (!isset($search_path)) {
                $search_path =
                    ($db_type == 'pgsql'
                        ? $search_path = 'public'
                        : $search_path = $db_name
                    );
            }

            $schemas_in_path
                = DbUtil::schemas_in_path(
                      $search_path
                  );

            DbUtil::set_db_search_path($search_path);
        }

        if (isset($requestVars['header_every'])) {
            $header_every = $requestVars['header_every'];
        }
	}

