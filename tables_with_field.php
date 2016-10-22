<?php
    # tables_with_field.php
    # ---------------------
    # for backlinked join-splice features
    # get list of tables that have a certain field

    { # init
        require_once('init.php');
		do_log(date('c') . " - db_viewer.tables_with_field received a request\n");
        $vars = array_merge($_GET, $_POST);
		do_log(print_r($vars,1));
    }

    { # do it
        if (isset($vars['fieldname'])) {
            { # vars
                $fieldname = $vars['fieldname'];
                $data_type = (isset($vars['data_type'])
                                ? $vars['data_type']
                                : null);
                $vals = (isset($vars['vals'])
                                ? $vars['vals']
                                : null);
                $base_table = (isset($vars['base_table'])
                                ? $vars['base_table']
                                : null);
            }

            { # take base_table into account
                if ($base_table) {
                    $fieldname = DbViewer::fieldname_given_base_table($fieldname, $base_table);
                }
            }

            { # do the query
                $results = DbViewer::tables_with_field($fieldname, $data_type, $vals);
                die(json_encode($results));
            }
        }
        else {
            die('Need fieldname');
        }
    }
?>
