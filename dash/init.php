<?php
    { # init
        $dash_trunk = __DIR__;
        $trunk = dirname($dash_trunk);
    }

    { # default values
        $multipleTablesFoundInDifferentSchemas = false;
        $search_path = null;
        $only_include_these_fields = null;

        if (!isset($edit)) {
            $edit = null;
        }
    }

    { # custom config
        #todo #fixme log which config you run
        if (file_exists("$trunk/db_config.php")) {
            include("$trunk/db_config.php");
        }
        elseif (file_exists("$dash_trunk/db_config.php")) {
            include("$dash_trunk/db_config.php");
        }
        include("$dash_trunk/dash_config.php");
    }

    { # classes
        include("$trunk/db_stuff/Db.php");
        include("$trunk/db_stuff/DbUtil.php");
        include("$trunk/classes/DbViewer.php");
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

        { # paths
            if (!isset($db_viewer_path)) {
                $db_viewer_path = "/db_viewer/db_viewer.php";
            }

            if (!isset($crud_api_path)) {
                $crud_api_path = "/dash/crud_api.php";
            }
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
                    $only_include_these_fields = &$minimal_fields;
                }
            }
            else {
                $minimal = null;
            }
        }

        {   # other fields that need to exist
            # even if not present in config
            if (!isset($fields_to_make_selects)) {
                $fields_to_make_selects = array();
            }
        }

    }

