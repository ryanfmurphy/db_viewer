<?php

# crud_api.php - process simple CRUD actions

{ # vars / includes / setup
    $trunk = dirname(__DIR__);
    $cur_view = 'obj_editor';
    $cur_subview = 'crud_api';
    include("$trunk/includes/init.php");

    #todo #fixme is this redundant?
    $vars = array_merge($_GET,$_POST);
}

#todo #cleanup - action should be just view|create|update|delete
#                and table should be filled out
{ # get action
    if (!isset($vars['action'])) {
        die('no action');
    }
    $action = $vars['action'];
    unset($vars['action']);
}

{ # handle ___settings
    { # no_js: don't send empty fields
        if (isset($vars['___settings'])
            && isset($vars['___settings']['no_js'])
            && $vars['___settings']['no_js']
        ) {
            foreach ($vars as $key => $val) {
                if ($val === ''
                    || $val === Config::$config['custom_select_magic_value']
                ) {
                    unset($vars[$key]);
                }
            }
        }
    }
    unset($vars['___settings']);
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

        $fields_w_array_type                    = Config::$config['fields_w_array_type'];
        $automatic_curly_braces_for_arrays      = Config::$config['automatic_curly_braces_for_arrays'];
        $MTM_array_fields_to_not_require_commas = Config::$config['MTM_array_fields_to_not_require_commas'];

        #todo #factor into a fn
        # MTM stands for Mobile Travel Mode
        # fields in this array don't require commas between array items,
        # unless you provide a leading space
        if (is_array($MTM_array_fields_to_not_require_commas)) {
            foreach ($vars as $field_name => $field_val) {
                $do_add_commas_this_field = (
                    in_array($field_name,
                             $MTM_array_fields_to_not_require_commas)
                    && strlen($field_val) > 0
                    && strpos($field_val, ',') === false
                    && $field_val[0] != '{' #todo check end } too?
                    && $field_val[0] != ' '
                );

                if ($do_add_commas_this_field) {
                    $vars[$field_name] = preg_replace('/\s+/',', ',$field_val);
                }
            }
        }

        #todo #factor into a fn
        # automatically add curly braces for arrays
        # (if enabled)
        if ($automatic_curly_braces_for_arrays) {
            foreach ($vars as $field_name => $field_val) {
                if (is_array($fields_w_array_type)
                    && in_array($field_name, $fields_w_array_type)
                ) {
                    # check if {} is already present and if so don't add them
                    $field_val = trim($field_val);
                    if ($field_val[0] != '{'
                        || $field_val[strlen($field_val)-1] != '}'
                    ) {
                        #todo #fixme use a more robust process that handles quotes
                        $vars[$field_name] = '{'.$field_val.'}';
                    }
                }
            }
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

                $primary_key_field = DbUtil::get_primary_key_field($table);
                $fn = TableView::special_op_fn( $table, $col_idx,
                                                $op_idx, $primary_key );
                $row = TableView::select_by_pk( $table, $primary_key_field,
                                                $primary_key );
                die(
                    $fn( $table, $row, $primary_key_field, $primary_key )
                );

            default:
                die("action not in swtich choices");
        }

    }
    else {
        die("action doesn't match pattern");
    }
}

