<?php
    { # init
        $trunk = __DIR__;
        $trunkParent = dirname($trunk);
    }

    { # default values
        $fields2omit_global = array();
        $multipleTablesFoundInDifferentSchemas = false;
        $search_path = null;
    }

    { # custom config
        include("$trunk/db_config.php");
        include("$trunk/dash_config.php");
    }

    { # classes
        include("$trunk/classes/Db.php");
        include("$trunk/classes/DbUtil.php");
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

            $schemas_in_path
                = DbUtil::schemas_in_path(
                      $search_path
                  );

            DbUtil::setDbSearchPath($search_path);
        }
	}

