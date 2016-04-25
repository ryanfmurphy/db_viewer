<?php
    # row_with_field_vals.php
    # -----------------------
    # support endpoint for the join-splice features
    # specifically for many-to-many / back-linked relationships

    { # init
        require_once('init.php');
        $vars = array_merge($_GET, $_POST);
    }

    { # do it
        if (isset($vars['fieldname']) && isset($vars['vals'])) {
            { # vars
                $fieldname = $vars['fieldname'];
                $vals = $vars['vals'];
                $data_type = isset($vars['data_type'])
                                ? $vars['data_type']
                                : null;
            }

            if (is_array($vals)) {
                $results = DbViewer::rows_with_field_vals($fieldname, $vals, $data_type);
                die(json_encode($results));
            }
            else { die('Invalid vals'); }
        }
        else {
            die('Need fieldname and vals');
        }
    }
?>
