<?php
    # tables_with_field.php
    # ---------------------
    # for backlinked join-splice features
    # get list of tables that have a certain field

    { # init
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
            }

            $results = DbViewer::tables_with_field($fieldname, $data_type=NULL);
            die(json_encode($results));
        }
        else {
            die('Need fieldname');
        }
    }
?>
