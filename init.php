<?php
    { # init
        $trunk = __DIR__;
        $trunkParent = dirname($trunk);
    }

    { # default values
        $fields2omit_global = array();
        $multipleTablesFoundInDifferentSchemas = false;
        $search_path = null;
        $only_include_these_fields = null;

        if (!isset($edit)) {
            $edit = null;
        }
    }

    { # custom config
        include("$trunk/db_config.php");
        include("$trunk/dash_config.php");
    }

    { # classes
        include("$trunk/classes/db_stuff/Db.php");
        include("$trunk/classes/db_stuff/DbUtil.php");
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

            if (!isset($orm_router_path)) {
                $orm_router_path = "/orm_router";
            }
        }

        { # minimal
            if (isset($_GET['minimal'])) {
                $minimal = $_GET['minimal'];
                if ($minimal || $minimal==='') {
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
        }

    }

