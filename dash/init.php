<?php
    { # init
        $dash_trunk = __DIR__;
        $trunk = dirname($dash_trunk);
    }

    { # config vars
        include("$trunk/classes/Config.php");

        $default_values = Config::default_values($trunk);

        #todo #fixme log which config you run
        if (file_exists("$trunk/db_config.php")) {
            #include("$trunk/db_config.php");
            $config = Config::load_config("$trunk/db_config.php", $trunk, $default_values);
            extract($config);
        }
        elseif (file_exists("$dash_trunk/db_config.php")) {
            #include("$dash_trunk/db_config.php");
            $config = Config::load_config("$dash_trunk/db_config.php", $trunk, $default_values);
            extract($config);
        }
        #include("$dash_trunk/dash_config.php");
        $config = Config::load_config("$dash_trunk/dash_config.php", $trunk, $config);
        extract($config);

        #todo #fixme this should be a different variable, maybe db_viewer_uri
        $default_values['db_viewer_path'] = "/db_viewer/db_viewer.php";
    }

    { # classes
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

            $schemas_in_path = DbUtil::schemas_in_path($search_path);
            DbUtil::setDbSearchPath($search_path);
        }

        { # minimal
            if (isset($_GET['minimal'])) {
                $minimal = $_GET['minimal'];

                # allow key without var in query str:
                # /index.php?minimal
                $minimal = ($minimal || $minimal==='');

                if ($minimal) {
                    if (!isset($minimal_fields)) {
                        $minimal_fields = array(
                            "name",
                            "txt",
                            "what",
                        );
                    }
                    $only_include_these_fields = $minimal_fields;
                }
            }
            else {
                $minimal = null;
            }
        }
    }

