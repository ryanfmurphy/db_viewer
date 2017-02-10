<?php
    { # init
        $obj_editor_trunk = __DIR__;
        $trunk = dirname($obj_editor_trunk);
    }

    { # config vars
        include("$trunk/classes/Config.php");

        $default_values = Config::default_values(
            $trunk, "/dash/index.php"
        );

        #todo only allow the root db_config
        if (file_exists("$trunk/db_config.php")) {
            $config = Config::load_config(
                "$trunk/db_config.php", $trunk, $default_values);
            extract($config);
        }
        elseif (file_exists("$obj_editor_trunk/db_config.php")) {
            $config = Config::load_config(
                "$obj_editor_trunk/db_config.php", $trunk, $default_values);
            extract($config);
        }
        else {
            header("HTTP/1.1 302 Redirect");
            header("Location: setup.php");
        }
        #$config = Config::load_config("$obj_editor_trunk/dash_config.php", $trunk, $config);
        #extract($config);

        #todo #fixme is this always right?  does this occlude the Config value?
        $table_view_uri = "/table_view/index.php";
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
            DbUtil::set_db_search_path($search_path);
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

