<?php
    require(dirname(__DIR__)."/includes/basic_init.php");



    { # config vars
        require_once("$trunk/classes/Config.php");

        $default_values = Config::default_values(
            "/table_view/index.php"
        );

        #todo disable the inner config files
        $config_file_path = (file_exists("$trunk/db_config.php")
                                ? "$trunk/db_config.php"
                                : "$table_view_path/db_config.php");
        if ($config_file_path) {
            do_log("including config file: '$config_file_path'\n");
            $config = Config::load_config(
                $config_file_path, $default_values);
            extract($config); # creates $variables

            #todo #fixme - this seems like it might occlude what's in Config for $table_view_path
            #              is that what we want?  should we check the Config and only use this as a fallback?
            $table_view_path = "$trunk/table_view";
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

