<?php

# crud_api.php - process simple CRUD actions

{ # vars / includes / setup
    $obj_editor_trunk = __DIR__;
    $trunk = dirname($obj_editor_trunk);
    include("$obj_editor_trunk/init.php");

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
        |
        special_op
    $@x", $action, $matches);

    if ($matchesActionPattern) {
        if (isset($matches['table'])) {
            $table = $matches['table'];
        }
        elseif (isset($vars['table'])) {
            $table = $vars['table'];
        }

        switch ($action) {

            case "view_$table":

                if (isset($vars['select_fields'])) {
                    $select_fields = $vars['select_fields'];
                    unset($vars['select_fields']);
                }
                elseif (isset($vars['table_view_minimal_mode'])) {
                    $minimal = $vars['table_view_minimal_mode'];
                    unset($vars['table_view_minimal_mode']);
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
                    Db::insert_row($table, $vars)
                ));
                break;

            case "update_$table":
                die(json_encode(
                    Db::update_rows($table, $vars)
                ));

            case "delete_$table":
                die(json_encode(
                    Db::delete_rows($table, $vars)
                ));

            case "special_op":
                $col_idx = isset($vars['col_idx'])
                                ? $vars['col_idx']
                                : null;
                $op_idx = isset($vars['op_idx'])
                                ? $vars['op_idx']
                                : null;
                $primary_key = isset($vars['primary_key'])
                                ? $vars['primary_key']
                                : null;

                # avoid globals, use config array
                $id_mode == Config::$config['id_mode'];

                $primary_key_field = DbUtil::get_primary_key_field(
                                        $id_mode, $table);
                $fn = DbViewer::special_op_fn(
                        $table, $col_idx, $op_idx, $primary_key);
                $row = DbViewer::select_by_pk(
                        $table, $primary_key_field, $primary_key);
                die(
                    $fn($table, $row, $primary_key_field, $primary_key)
                );

            default:
                die("action not in swtich choices");
        }

    }
    else {
        die("action doesn't match pattern");
    }
}

