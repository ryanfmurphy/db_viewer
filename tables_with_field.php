<?php
    # tables_with_field.php
    # ---------------------
    # for backlinked join-splice features
    # get list of tables that have a certain field

    { # init
		ini_set('memory_limit', '4000M');
        require_once('init.php');
        $vars = array_merge($_GET, $_POST);
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
            }

            $results = DbViewer::tables_with_field($fieldname, $data_type, $vals);
            die(json_encode($results));
        }
        else {
            die('Need fieldname');
        }
    }
?>
