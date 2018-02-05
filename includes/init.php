<?php
    # init.php
    # needs $cur_view to be defined prior to including

    require(dirname(__DIR__)."/includes/basic_init.php");

    { # config vars
        require_once("$trunk/classes/Config.php");

        if (file_exists("$trunk/db_config.php")) {
            # config exists: load it

            { # figure out view_uri for default_values
                #todo #fixme - don't require setting cur_view
                #              (right now we need it so we can pass in
                #               uri to Config::default_values)
                $view_uri = '';
                if ($cur_view) { # add main view part
                    $view_uri .= "/$cur_view";
                }
                { # add subview part
                    if (!isset($cur_subview)) {
                        $cur_subview = 'index';
                    }
                    $view_uri .= "/$cur_subview.php";
                }
                #echo "view_uri = '$view_uri'\n";
            }
            $default_values = Config::default_values($view_uri);

            $config = Config::load_config("$trunk/db_config.php",
                                          $default_values);
            extract($config);
        }
        else {
            # config doesn't exist: redirect to setup
            header("HTTP/1.1 302 Redirect");
            header("Location: setup.php");
            die();
        }

        # if not logged in, go to auth page (if config'd that way)
        $have_db_auth = true;
        if ((!isset($db_user)
            || !isset($db_password))
            && $db_prompt_for_auth
        ) {
            $have_db_auth = false;
            if ($cur_subview != 'auth') {
                header("HTTP/1.1 302 Redirect");
                header("Location: $prompt_for_auth_uri");
                die();
            }
        }
    }

    include("$trunk/includes/include_classes.php");

    if ($have_db_auth) {
        # try connecting to the db (just to make sure auth is ok)
        Db::conn();
    }

    # vars adjustments after includes
    if ($cur_subview != 'auth') {
        { # search_path
            if (!isset($search_path)) {
                $search_path =
                    ($db_type == 'mysql'
                        ? $search_path = $db_name
                          # sqlite will have a pretend 'public' schema for our purposes
                          # #todo #fixme are there any sqlite cases where this search_path breaks?
                        : $search_path = 'public'
                    );
            }

            $schemas_in_path = DbUtil::schemas_in_path(/*$search_path*/);
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
                    EditorBackend::$default_minimal_fields;
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
                        $minimal_fields =
                            EditorBackground::$default_minimal_fields;
                    }
                    $only_include_these_fields = $minimal_fields;
                }
            }
            elseif (!isset($minimal)) {
                $minimal = null;
            }
        }
    }

