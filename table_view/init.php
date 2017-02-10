<?php
    $cur_view = 'table_view';

    require(dirname(__DIR__)."/includes/basic_init.php");

    { # config vars
        require_once("$trunk/classes/Config.php");

        $default_values = Config::default_values(
            "/$cur_view/index.php"
        );

        if (file_exists("$trunk/db_config.php")) {
            $config = Config::load_config("$trunk/db_config.php",
                                          $default_values);
            extract($config);
        }
        else {
            header("HTTP/1.1 302 Redirect");
            header("Location: setup.php");
        }
    }

    include("$trunk/includes/include_classes.php");

	{ # vars adjustments after includes
        { # search_path
            if (!isset($search_path)) {
                $search_path =
                    ($db_type == 'pgsql'
                        ? $search_path = 'public'
                        : $search_path = $db_name
                    );
            }

            $schemas_in_path = DbUtil::schemas_in_path($search_path);
            DbUtil::set_db_search_path($search_path);
        }

        if (isset($requestVars['header_every'])) {
            $header_every = $requestVars['header_every'];
        }
	}

