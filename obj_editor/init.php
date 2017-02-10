<?php
    require(dirname(__DIR__)."/includes/basic_init.php");

    $obj_editor_trunk = __DIR__; #todo get rid of this when we can

    { # config vars
        include("$trunk/classes/Config.php");

        $default_values = Config::default_values(
            "/obj_editor/index.php"
        );

        #todo only allow the root db_config
        if (file_exists("$trunk/db_config.php")) {
            $config = Config::load_config(
                "$trunk/db_config.php", $default_values);
            extract($config);
        }
        elseif (file_exists("$trunk/obj_editor/db_config.php")) {
            $config = Config::load_config(
                "$trunk/obj_editor/db_config.php", $default_values);
            extract($config);
        }
        else {
            header("HTTP/1.1 302 Redirect");
            header("Location: setup.php");
        }

        #todo #fixme is this always right?  does this occlude the Config value?
        $table_view_uri = "/table_view/index.php";
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

        { # minimal
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

