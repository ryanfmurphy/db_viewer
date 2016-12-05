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
    }

    { # vars - initial values
        $default_values = array(
            'id_fields_are_uuids' => null, # neither true nor false if unspecified
            'header_every' => 15,
            'pluralize_table_names' => false,
            'slow_tables' => array(),
            'field_render_filters_by_table' => array(),
            'special_ops' => array(),
            'backgroundImages' => array(),
            'inferred_table' => null,
            'links_minimal_by_default' => false,
            'use_field_ordering_from_minimal_fields' => false,
            'minimal' => isset($requestVars['minimal'])
                            ? true
                            : false,
            # URI paths
            'js_path' => '/db_viewer/js',
            'dash_path' => '/dash/index.php',
            'crud_api_path' => "/dash/crud_api.php",
            # include paths
            'db_viewer_path' => __DIR__,
            'trunk' => dirname(__DIR__),
        );
    }

    { # db_config include
        $trunk = $default_values['trunk'];
        require_once("$trunk/classes/Config.php");

        $config_file_path = (file_exists("$trunk/db_config.php")
                                ? "$trunk/db_config.php"
                                : "$db_viewer_path/db_config.php");
        if ($config_file_path) {
            do_log("including config file: '$config_file_path'\n");
            $config = Config::load_config($config_file_path, $trunk, $default_values);
            extract($config); # creates $variables
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

            DbUtil::setDbSearchPath($search_path);
        }

        if (isset($requestVars['header_every'])) {
            $header_every = $requestVars['header_every'];
        }
	}

