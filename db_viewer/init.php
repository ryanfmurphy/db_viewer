<?php
    { # some misc functions
        function do_log($msg) {
            $logPath = __DIR__.'/error_log';
            #$msg .= " (to $logPath)";
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
    }

    { # vars - initial values
        $pluralize_table_names = false;
        $slow_tables = array();
        $js_path = '/db_viewer/js';
        $dash_path = '/dash/index.php';
        $field_render_filters_by_table = array();
    }

    { # db_config include
        $trunk = __DIR__;
        include("$trunk/db_config.php");
    }

    { # Util includes
        # some programs may provide
        # a DB connection and Util class themselves
        if (class_exists('Db')) {
            $db = Db::conn();
        }
        else {
            if (!class_exists('Util')) {
                $db = new PDO(
                    "$db_type:host=$db_host;dbname=$db_name",
                    $db_user, $db_password
                );

                require_once("$trunk/classes/Util.php");
            }

            require_once("$trunk/classes/db_stuff/Db.php");
        }
        require_once("$trunk/classes/db_stuff/DbUtil.php");
        require_once("$trunk/classes/DbViewer.php");
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

            DbUtil::setDbSearchPath($search_path);
        }
	}

