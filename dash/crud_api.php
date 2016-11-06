<?php

# crud_api.php - process simple CRUD actions

{ # vars / includes / setup
    $dash_trunk = __DIR__;
    $trunk = dirname($dash_trunk);
    /*
    include("$dash_trunk/db_config.php");
    include("$trunk/db_stuff/Db.php");
    include("$trunk/db_stuff/DbUtil.php");
    */
    include("$dash_trunk/init.php");

    #todo #fixme is this redundant?
    $vars = array_merge($_GET,$_POST);
}

{ # get action
    if (!isset($vars['action'])) {
        die('no action');
    }
    $action = $vars['action'];
    unset($vars['action']);
}

{
    $matchesActionPattern = preg_match("@^
        (?<action>
            # get|get1
            view|create|update|delete
        )
        _(?<table>\w+)
    $@x", $action, $matches);

    if ($matchesActionPattern) {
        $table = $matches['table'];

        #$ClassName = Model::ClassName($table);

        switch ($action) {

            case "view_$table":

                if (isset($vars['select_fields'])) {
                    $select_fields = $vars['select_fields'];
                    unset($vars['select_fields']);
                }
                elseif (isset($vars['db_viewer_minimal_mode'])) {
                    $minimal = $vars['db_viewer_minimal_mode'];
                    unset($vars['db_viewer_minimal_mode']);
                }
                else {
                    $select_fields = null;
                }

                die(json_encode(
                    Db::viewTable($table, $vars, $select_fields, $minimal)
                ));
                break;

            case "create_$table":
                die(json_encode(
                    Db::insertRow($table, $vars)
                ));
                break;

            case "update_$table":
                die(json_encode(
                    Db::updateRows($table, $vars)
                ));

            case "delete_$table":
                die(json_encode(
                    Db::deleteRows($table, $vars)
                ));

            /*
            case "action_get1_$table":
                die(json_encode(
                    Model::get1($vars, $ClassName)
                ));
                break;

            case "action_get_$table":
                die(json_encode(
                    Model::get($vars, $ClassName)
                ));
                break;
            */

            default:
                die("action not in swtich choices");
        }

    }
    else {
        die("action doesn't match pattern");
    }
}

