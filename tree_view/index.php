<?php
    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
        require("$trunk/tree_view/hash_color.php");
        require("$trunk/tree_view/vars.php");
    }

    if (!$root_table || $edit_vars) {
        require("$trunk/tree_view/vars_form.php");
        die();
    }

    $omit_root_node = ($backend == 'db');

    #todo #fixme do I need this header? #security
    header("Access-Control-Allow-Origin: <origin> | *");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <?php require("$trunk/tree_view/style.css.php") ?>
        </head>

        <body>
<?php
    { # Configure Tree link
        $vars_for_edit_link = $requestVars;
        $vars_for_edit_link['edit_vars'] = 1;
?>
            <span id="alert"></span>
            <a id="edit_vars_link"
               href="<?= "?".http_build_query($vars_for_edit_link) ?>"
               target="_blank">
                Configure Tree
            </a>
<?php
    }

    function table_name_w_color($table) {
        { ob_start();
            $color = name_to_rgb($table);
?>
            <span class="<?= $table ?>_tbl_color">
                <?= $table ?>
            </span>
<?php
            $html = ob_get_clean();
        }
        return $html;
    }

    # come up with a short textual headline describing the view
    function tree_view_summary_txt($root_table, $parent_relationships) {
        $txt = table_name_w_color($root_table) . " → ";
        $tables = array();
        # use keys for uniqueness
        foreach ($parent_relationships as $relationship) {
            $table = $relationship['child_table'];
            $tables[
                table_name_w_color($table)
            ] = 1;
        }
        $txt .= implode(', ', array_keys($tables));
        return $txt;
    }

?>
            <h1>
                🌳 Tree View:
                <span id="summary">
<?php
    if ($backend == 'db') {
?>
                    <?= tree_view_summary_txt($root_table, $parent_relationships) ?>
<?php
    }
    else {
?>
                    filesystem
<?php
    }
?>
                </span>
            </h1>
<?php
    if ($load_d3_via_cdn) {
?>
        <script src="//d3js.org/d3.v3.min.js"></script>
<?php
    }
    else {
?>
        <script src="<?= $d3_js_uri ?>"></script>
<?php
    }
?>
        <?php include('tree_view.js.php') ?>
    </body>
</html>

