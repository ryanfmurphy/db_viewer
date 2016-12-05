<?php
    { # init
        $dash_trunk = __DIR__;
        $trunk = dirname($dash_trunk);
    }

    { # default values
        $default_values = array(
            'multipleTablesFoundInDifferentSchemas' => false,
            'search_path' => null,
            'only_include_these_fields' => null,
            'edit' => isset($edit) ? $edit : null,
            'links_minimal_by_default' => false,
            'default_values_by_table' => array(),
            'minimal_field_inheritance' => true,
            'use_field_ordering_from_minimal_fields' => false,
            'minimal' => isset($requestVars['minimal']) ? true : false,
            'db_viewer_path' => "/db_viewer/db_viewer.php",
            'crud_api_path' => "/dash/crud_api.php",
        );
    }

    { # custom config
        include("$trunk/classes/Config.php");
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

        {   # other fields that need to exist
            # even if not present in config
            if (!isset($fields_to_make_selects)) {
                $fields_to_make_selects = array();
            }
        }
    }

