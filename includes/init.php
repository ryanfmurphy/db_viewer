<?php
    # init.php
    # needs $cur_view to be defined prior to including

    require(dirname(__DIR__)."/includes/basic_init.php");

    { # config vars
        require_once("$trunk/classes/Config.php");

        if (!isset($cur_subview)) $cur_subview = 'index';
        $default_values = Config::default_values(
            "/$cur_view/$cur_subview.php"
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

        # number of rows between repeating the header row
        if (isset($requestVars['header_every'])) {
            $header_every = $requestVars['header_every'];
        }

        # mobile_travel_mode
        # some special settings for if you're on a mobile device
        # that is accessing e.g. a locally hosted DB Viewer yet
        # is not always connected to it - you can leave a form open
        # and type your notes and save them whenever you are connected
        # again and ready to save
        if (isset($requestVars['mobile_travel_mode'])) {
            $mobile_travel_mode = $requestVars['mobile_travel_mode'];
            # so you can change tables without reload
            if ($mobile_travel_mode) {
                $need_alt_for_no_reload = false;
                $minimal = true;
                Config::$config['minimal_fields'] =
                    array(
                        "name",
                        "txt",
                    );
                $minimal_fields = Config::$config['minimal_fields'];
            }
        }

        if (isset($requestVars['need_alt_for_no_reload'])) {
            $need_alt_for_no_reload = $requestVars['need_alt_for_no_reload'];
        }

        {   # minimal
            #todo #fixme - collapse this with
            # the Config::default_values code for minimal
            if (isset($_GET['minimal'])) {
                $minimal = $_GET['minimal'];

                # allow key without var in query str:
                # /index.php?minimal
                $minimal = ($minimal || $minimal === '');

                if ($minimal) {
                    if (!isset($minimal_fields)) {
                        $minimal_fields = array(
                            "name",
                            "txt",
                        );
                    }
                    $only_include_these_fields = $minimal_fields;
                }
            }
            elseif (!isset($minimal)) {
                $minimal = null;
            }
        }
    }

