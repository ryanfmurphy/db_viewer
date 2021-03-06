<?php

# crud_api.php - process simple CRUD actions

#todo move this out of obj_editor

{ # vars / includes / setup
    $trunk = dirname(__DIR__);
    $cur_view = 'obj_editor';
    $cur_subview = 'crud_api';
    include("$trunk/includes/init.php");

    #todo #fixme is this redundant?
    $vars = array_merge($_GET,$_POST);

    #todo #fixme this can clash if we have a field named 'action'
    #            create a mode where all the fields are stored
    #            in a nested array called e.g. 'fields' or 'vars'
    { # get action
        if (!isset($vars['action'])) {
            die('no action');
        }
        $action = $vars['action'];
        unset($vars['action']);
    }
}

{ # handle ___settings
    { # no_js: don't send empty fields - #todo #cleanup - put in fn
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

{   # check uri for a recognizable action
    $matchesActionPattern = preg_match("@^
        (?<action>
            view|get|get1|create|update|delete
        )
        (?:_(?<table>\w+))?
        | add_to_array
        | remove_from_array
        | special_op
        | get_parent
    $@x", $action, $matches);

    if ($matchesActionPattern) {
        if (isset($matches['table'])) {
            $table = $matches['table'];
        }
        elseif (isset($vars['table'])) {
            $table = $vars['table'];
            unset($vars['table']);
        }
        else {
            $table = null;
        }

        $fields_w_array_type                    = Config::$config['fields_w_array_type'];
        $automatic_curly_braces_for_arrays      = Config::$config['automatic_curly_braces_for_arrays'];
        $MTM_array_fields_to_not_require_commas = Config::$config['MTM_array_fields_to_not_require_commas'];

        EditorBackend::process_array_fields($vars);

        #todo #factor into a fn
        # automatically add curly braces for arrays
        # (if enabled)
        if ($automatic_curly_braces_for_arrays) {
            foreach ($vars as $field_name => $field_val) {
                if (DbUtil::field_is_array($field_name)) {
                    # check if {} is already present and if so don't add them
                    $field_val = trim($field_val);
                    if (strlen($field_val) > 0
                        && ($field_val[0] != '{'
                            || $field_val[strlen($field_val)-1] != '}')
                    ) {
                        #todo #fixme use a more robust process that handles quotes
                        $vars[$field_name] = '{'.$field_val.'}';
                    }
                }
            }
        }

        switch ($action) {

            case "view":
            case "view_$table": #todo #deprecate

                if (isset($vars['select_fields'])) {
                    $select_fields = $vars['select_fields'];
                    unset($vars['select_fields']);
                }
                else {
                    $select_fields = null;
                }

                if (isset($vars['table_view_minimal_mode'])) {
                    $minimal = $vars['table_view_minimal_mode'];
                    unset($vars['table_view_minimal_mode']);
                }

                if (isset($vars['strict_wheres'])) {
                    $strict_wheres = $vars['strict_wheres'];
                    unset($vars['strict_wheres']);
                }
                else {
                    $strict_wheres = false;
                }

                #todo remove var, assume true for !strict_wheres
                if (isset($vars['match_aliases_on_name'])) {
                    $match_aliases_on_name = $vars['match_aliases_on_name'];
                    unset($vars['match_aliases_on_name']);
                }
                else {
                    $match_aliases_on_name = true;
                }

                die(json_encode(
                    Db::view_table(
                        $table, $vars, $select_fields,
                        $minimal, $strict_wheres, $match_aliases_on_name
                    )
                ));
                break;

            #note - gives only the 1st parent if there's > 1
            case 'get_parent':
                $primary_key = (isset($vars['primary_key'])
                                    ? $vars['primary_key']
                                    : die('no primary_key'));
                #todo #fixme generalize
                #todo do not assume parent_ids / array field
                # maybe do not assume entity table?

                #todo don't assume 'id' field
                $sql = "
                           select parent_ids[1] parent_id from entity
                           where id = ".Db::quote($primary_key)."
                       ";
                $rows = Db::sql($sql);

                if (count($rows)) {
                    $row = $rows[0];
                    die(json_encode($row['parent_id']));
                }
                else {
                    die('no parent');
                }
                break;

            case "get1":
            case "get1_$table": #todo #deprecate this format of action
                $get1 = true;
            case "get":
            case "get_$table": #todo #deprecate this format of action
                if (!isset($get1)) $get1 = false;
                $where_clauses = $vars['where_clauses']
                                    ? $vars['where_clauses']
                                    : die('need where_clauses');
                die(json_encode(
                    Db::get(
                        $table, $where_clauses, $get1,
                        false # loosen matching to possibly allow @>, LIKE, etc
                    )
                ));
                break;

            case "create":
            case "create_$table": #todo #deprecate this format of action
                die(json_encode(
                    Db::insert_row($table, $vars)
                ));
                break;

            case "update":
            case "update_$table": #todo #deprecate this format of action
                die(json_encode(
                    Db::update_rows($table, $vars)
                ));

            case "delete":
            case "delete_$table": #todo #deprecate this format of action
                die(json_encode(
                    Db::delete_rows($table, $vars)
                ));

            case "add_to_array":
                if (!$table) die('ERROR: no table supplied');
                $primary_key = (isset($vars['primary_key'])
                                ? $vars['primary_key']
                                : die('ERROR: no primary_key supplied'));
                $field_name = (isset($vars['field_name'])
                                ? $vars['field_name']
                                : die('ERROR: no field_name supplied'));
                $val_to_add = (isset($vars['val_to_add'])
                                ? $vars['val_to_add']
                                : die('ERROR: no val_to_add supplied'));
                $val_to_replace = (isset($vars['val_to_replace'])
                                ? $vars['val_to_replace']
                                : null);
                die(json_encode(
                    Db::add_to_array(
                        $table, $primary_key, $field_name, $val_to_add,
                        $val_to_replace # optional
                    )
                ));

            case "remove_from_array":
                if (!$table) die('ERROR: no table supplied');
                $primary_key = (isset($vars['primary_key'])
                                ? $vars['primary_key']
                                : die('ERROR: no primary_key supplied'));
                $field_name = (isset($vars['field_name'])
                                ? $vars['field_name']
                                : die('ERROR: no field_name supplied'));
                $val_to_remove = (isset($vars['val_to_remove'])
                                ? $vars['val_to_remove']
                                : die('ERROR: no val_to_remove supplied'));
                die(json_encode(
                    Db::remove_from_array(
                        $table, $primary_key, $field_name, $val_to_remove
                    )
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

