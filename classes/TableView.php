<?php
    class TableView {

        # table name manipulation functions
        #----------------------------------

        # prepend table schema etc
        public static function full_tablename($tablename) {
            $db_type = Config::$config['db_type'];

            if ($db_type == 'pgsql') {

                $table_schemas = array( #todo get from database
                    'company' => 'market',
                    'quote' => 'market',
                );

                if (isset($table_schemas[$tablename])) {
                    $schema = $table_schemas[$tablename];
                    return "$schema.$tablename";
                }
                else {
                    return $tablename;
                }
            }
            else {
                return $tablename;
            }
        }

		# peel off schema/database
        # Q. does this handle quotes in tablename?
		public static function just_tablename($full_tablename) {
			$dotPos = strpos($full_tablename, '.');
			return ($dotPos !== false
						? substr($full_tablename, $dotPos+1)
						: $full_tablename);
		}


        # Rendering and Type-Recognition Functions
        # ----------------------------------------

        public static function output_db_error($db) {
?>
<div>
    <p>
        <b>Oops! Could not get a valid result.</b>
    </p>
    <p>
        PDO::errorCode(): <?= $db->errorCode() ?>
    </p>
    <div>
        PDO::errorInfo():
        <pre><?php print_r($db->errorInfo()) ?></pre>
    </div>
</div>
<?php
        }

        #todo maybe move to different class?
        public static function is_url($val) {
            if (is_string($val)) {
                return preg_match('@^\w+://@', $val);
                /*
                $url_parts = parse_url($val);
                $protocol = (isset($url_parts['scheme'])
                                ? $url_parts['scheme']
                                : null);

                if ($protocol) {
                    # prevent false positives where after the colon we have stuff other than a link
                    # e.g. just some notes to self, but with a colon after the header

                    $colonPos = strpos($val, ':');
                    $hasStuffAfterColon = strlen($val) > $colonPos + 1;
                    if ($hasStuffAfterColon) {
                        $charAfterColon = $val[$colonPos + 1];
                        $isWhitespace = ctype_space($charAfterColon);
                        return ( !$isWhitespace
                                    ? true # probably a url
                                    : false # might be notes etc
                               );
                    }
                    else {
                        return false;
                    }
                }
                else {
                    return false;
                }
                */
            }
            else {
                return false;
            }
        }

        # format $val as HTML to put in <td>
        #   ($table should have no quotes)
        public static function val_html($val, $fieldname, $table=null) {
            $field_render_filters_by_table = Config::$config['field_render_filters_by_table'];
            $obj_editor_uri = Config::$config['obj_editor_uri'];
            $show_images = Config::$config['show_images'];
            $image_max_width = Config::$config['image_max_width'];

            #do_log("top of val_html(val='$val', fieldname='$fieldname')\n");

            # pg array is <ul>
            if (DbUtil::seems_like_pg_array($val)) {
                $vals = DbUtil::pg_array2array($val);
                return self::array_as_html_list($vals);
            }
            # show images
            elseif ($show_images
                    && $fieldname == 'image_url'
            ) {
                $image_url = $val;

                { ob_start();
?>
        <img    src="<?= $image_url ?>"
                <?= ($image_max_width
                            ? 'style="max-width: '.$image_max_width.'"'
                            : '') ?>
        />
<?php
                    return ob_get_clean();
                }
            }
            # urls are links
            elseif (self::is_url($val)) {
                $val = htmlentities($val);
                { ob_start();
?>
        <a href="<?= $val ?>" target="_blank">
            <?= $val ?>
        </a>
<?php
                    return ob_get_clean();
                }
            }
            # links from table_list
            elseif (self::is_table_name_val($val, $fieldname)) {
                { # vars
                    $tablename = $val;
                    $cmp = class_exists('Campaign');
                    $hasPhpExt = !$cmp;
                    $local_uri = ($hasPhpExt
                                    ? 'index.php'
                                    : 'db_viewer'); #todo #fixme simplify
                }

                { ob_start(); # provide a link
                    $val = htmlentities($val);
?>
        <a href="<?= $local_uri ?>?sql=select * from <?= $tablename ?> limit 100"
           target="_blank"
        >
            <?= $val ?>
        </a>
<?php
                    return ob_get_clean();
                }
            }
            # filters from array in db_config
            elseif ($table !== null
                    && isset($field_render_filters_by_table[$table][$fieldname])
            ) {
                $fn = $field_render_filters_by_table[$table][$fieldname];
                return $fn($val, $obj_editor_uri, $fieldname);
            }
            # allow html (don't escape val)
            elseif ((isset(Config::$config['fields_that_render_html'])
                     && in_array($fieldname, Config::$config['fields_that_render_html']))
                    || (isset(Config::$config['fields_that_render_html_by_table'])
                        && isset(Config::$config['fields_that_render_html_by_table'][$table])
                        && in_array($fieldname, Config::$config['fields_that_render_html_by_table'][$table]))
            ) {
                return $val;
            }
            # default quoting / handling of val
            else {
                $val = htmlentities($val);
                $val = nl2br($val); # show newlines as <br>'s

                { # get bigger column width for longform text fields
                    #todo factor this logic in the 2 places we have it
                    # (here and in obj_editor)
                    if ($fieldname == 'txt' || $fieldname == 'src') {
                        ob_start();
?>
                        <div class="wide_col">
                            <?= $val ?>
                        </div>
<?php
                        return ob_get_clean();
                    }
                }

                return $val;
            }
        }

        public static function array_as_html_list($array) {
            { ob_start();
?>
        <ul>
<?php
                foreach ($array as $val) {
?>
            <li><?= htmlentities($val) ?></li>
<?php
                }
?>
        </ul>
<?php
                return ob_get_clean();
            }
        }

        public static function is_table_name_val($val, $fieldName) {
            return ((preg_match('/^Tables_in_|^tablename$|^relname$/', $fieldName)
                     || $fieldName == "Name")
                            ? true
                            : false);
        }

        public static function get_submit_url($requestVars) {
            $uri = $_SERVER['REQUEST_URI'];
            $uri_no_query = strtok($uri, '?');
            return "$uri_no_query";
        }

        public static function obj_editor_url($obj_editor_uri, $tablename_no_quotes, $primary_key) {
            return $obj_editor_uri
                        ."?edit=1"
                        ."&table=$tablename_no_quotes"
                        ."&primary_key=$primary_key";
        }

        # returns js code that defines a fn
        public static function obj_editor_url__js($obj_editor_uri) {
            ob_start();
?>
            <script>
                function obj_editor_url(tablename_no_quotes, primary_key) {
                    return '<?= str_replace("'", "\\'", $obj_editor_uri) ?>'
                                +"?edit=1"
                                +"&table=" + tablename_no_quotes
                                +"&primary_key=" + primary_key;
                }
            </script>
<?php
            return ob_get_clean();
        }

        // allow alt-click to change the ?minimal value
        public static function echo_js_handle_edit_link_onclick_fn() {
            ob_start();
?>
        <script>
        // #todo move
        function handle_edit_link_onclick(elem, key_event, url2) {
            url = elem.href;
            if (key_event.altKey) {
                console.log('yes');
                elem.href = url2;
                setTimeout(function(){
                    elem.href = url;
                }, 150);
                window.open(elem.href, '_blank');
            }
        }
        </script>
<?php
            return ob_get_clean();
        }

        # needs handle_edit_link_onclick js fn (above)
        public static function echo_edit_link(
            $obj_editor_uri, $tablename_no_quotes, $primary_key, $minimal = false
        ) {
            $base_url = TableView::obj_editor_url(
                $obj_editor_uri, $tablename_no_quotes, $primary_key
            );
            if ($minimal) {
                $url = "$base_url&minimal";
                $url2 = $base_url;
            }
            else {
                $url = $base_url;
                $url2 = "$base_url&minimal";
            }
?>
        <td class="action_cell">
            <a  href="<?= $url ?>"
                class="row_edit_link"
                target="_blank"
                onclick="handle_edit_link_onclick(this, event, '<?= $url2 ?>')"
            >
                edit
            </a>
        </td>
<?php
        }

        // allow alt-click to change the ?minimal value
        public static function echo_js__hit_url_and_rm_row_from_ui__fn() {
            ob_start();
?>
        <script>
        function hit_url_and_rm_row_from_ui(elem, event, url) {
            $.ajax({
                url: url,
                dataType: 'json',
                success: function(r) {
                    elem.closest('tr').remove();
                },
                error: function() {
                    console.log('Something went wrong');
                }
            });
        }
        </script>
<?php
            return ob_get_clean();
        }

        public static function get_delete_url(
            $obj_editor_uri, $tablename_no_quotes, $primary_key
        ) {
            $crud_api_uri = Config::$config['crud_api_uri'];
            $archive_instead_of_delete = Config::$config['archive_instead_of_delete'];

            #todo #fixme #assumption: primary_key field is "id"
            $where_str = "where_clauses[id]=$primary_key";
            if ($archive_instead_of_delete) {
                $is_archived_field = Config::$config['is_archived_field'];
                if (!$is_archived_field) {
                    #todo #fixme show this error earlier to avoid
                    #            weird partial table rendering
                    die("Error: 'archive_instead_of_delete' is enabled"
                        ." without an 'is_archived_field' defined");
                }
                return "$crud_api_uri?action=update_entity"
                                    ."&$where_str"
                                    ."&$is_archived_field=t";
            }
            else {
                return "$crud_api_uri?action=delete_entity"
                                    ."&$where_str";
            }
        }

        # needs hit_url_and_rm_row_from_ui js fn (above)
        public static function echo_delete_button(
            $obj_editor_uri, $tablename_no_quotes, $primary_key
        ) {
            $url = self::get_delete_url(
                $obj_editor_uri, $tablename_no_quotes, $primary_key
            );
?>
        <td class="action_cell">
            <a  class="row_delete_button link"
                target="_blank"
                onclick="hit_url_and_rm_row_from_ui(this, event, '<?= $url ?>')"
            >
                delete
            </a>
        </td>
<?php
        }

        public static function special_op_url(
            $special_op, $tablename_no_quotes,
            $primary_key_field, $primary_key, $crud_api_uri,
            $row, $op_col_idx, $op_idx
        ) {
            # the kind of special_op that changes fields
            if (isset($special_op['changes'])) {
                # query string
                $query_vars = array_merge(
                    array(
                        'action' => "update_$tablename_no_quotes",
                        'where_clauses' => array(
                            $primary_key_field => $primary_key,
                        ),
                    ),
                    $special_op['changes']
                );
                $query_str = http_build_query($query_vars);

                $special_op_url = "$crud_api_uri?$query_str";
            }
            # the kind of special_op that goes to a url
            # with {{mustache_vars}} subbed in
            elseif (isset($special_op['url'])) {
                $special_op_url = preg_replace_callback(
                    '/{{([a-z_]+)}}/',
                    function($match) use ($row) {
                        $fieldname = $match[1];
                        return $row[$fieldname];
                    },
                    $special_op['url']
                );
            }
            # custom fn
            elseif (isset($special_op['fn'])) {
                $special_op_url = TableView::special_op_fn_url($tablename_no_quotes, $op_col_idx, $op_idx, $primary_key);
            }

            return $special_op_url;
        }

        public static function special_op_fn_url($tablename_no_quotes, $col_idx, $op_idx, $primary_key) {
            $crud_api_uri = Config::$config['crud_api_uri'];
            $special_ops = Config::$config['special_ops'];
            
            if (TableView::special_op_fn(
                    $tablename_no_quotes, $col_idx, $op_idx, $primary_key
                )
            ) {
                #todo #fixme use http_build_query()
                return $crud_api_uri
                            ."?action=special_op"
                            ."&col_idx=$col_idx"
                            ."&op_idx=$op_idx"
                            ."&table=$tablename_no_quotes"
                            ."&primary_key=$primary_key";
            }
            else {
                return null;
            }
        }

        public static function special_op_fn($tablename_no_quotes, $col_idx, $op_idx) {
            $special_ops = Config::$config['special_ops'];
            if (isset($special_ops[$tablename_no_quotes][$col_idx][$op_idx]['fn'])) {
                return $special_ops[$tablename_no_quotes][$col_idx][$op_idx]['fn'];
            }
            else {
                return null;
            }
        }

        public static function echo_special_ops(
            $special_ops_cols, $tablename_no_quotes,
            $primary_key_field, $primary_key, $crud_api_uri,
            $row
        ) {
            foreach ($special_ops_cols as $op_col_idx => $special_ops_col) {
?>
        <td class="action_cell">
            <ul>
<?php
                foreach ($special_ops_col as $op_idx => $special_op) {

                    $special_op_url = self::special_op_url(
                        $special_op, $tablename_no_quotes,
                        $primary_key_field, $primary_key, $crud_api_uri,
                        $row, $op_col_idx, $op_idx
                    );
                    $href_or_onclick = (isset($special_op['remove'])
                                        && $special_op['remove']
                                            ? "href=\"$special_op_url\""
                                            : "onclick=\"hit_url_and_rm_row_from_ui(
                                                            this, event, '$special_op_url')\"");
?>
            <li>
                <nobr>
                    <a  <?= $href_or_onclick ?>
                        class="row_edit_link link"
                        target="_blank"
                    >
                        <?= $special_op['name'] ?>
                    </a>
                </nobr>
            </li>
<?php
                }
?>
        </ul>
<?php
            }
?>
    </td>
<?php
        }

        public static function choose_background_image(
            $tablename, &$background_images
        ) {
            return ($tablename
                   && isset($background_images[$tablename])
                        ? $background_images[$tablename]
                        : (isset($background_images['fallback image'])
                            ? $background_images['fallback image']
                            : null)
                  );
        }

        public static function select_by_pk_sql($table, $primary_key_field, $primary_key) {
            $primary_key__esc = Db::sql_literal($primary_key); # escape as sql literal
            $sql = "
                select * from ".DbUtil::quote_tablename($table)."
                where $primary_key_field = $primary_key__esc
            ";
            return $sql;
        }

        public static function select_by_pk($table, $primary_key_field, $primary_key) {
            $sql = TableView::select_by_pk_sql($table, $primary_key_field, $primary_key);
            $all1rows = Db::sql($sql);

            if (is_array($all1rows) && count($all1rows)) {
                return $all1rows[0];
            }
            else {
                return null;
            }
        }


        # used in table_view to put row fields into the right order
        public static function ordered_row(
            $row, $ordered_fields
        ) {
            $new_row = array();
            foreach ($ordered_fields as $field_name) {
                if (array_key_exists($field_name, $row)) {
                    $new_row[$field_name] = $row[$field_name];
                    unset($row[$field_name]);
                }
            }
            foreach ($row as $field_name => $val) {
                $new_row[$field_name] = $val;
            }
            return $new_row;
        }

        # used in table_view to put row fields into the right order
        public static function prep_row($row) {
            $use_field_ordering_from_minimal_fields = Config::$config['use_field_ordering_from_minimal_fields'];
            $would_be_minimal_fields = Config::$config['would_be_minimal_fields'];
            $minimal = Config::$config['minimal'];
            if ($use_field_ordering_from_minimal_fields) {
                return self::ordered_row(
                    $row, $would_be_minimal_fields
                );
            }
            else {
                return $row;
            }
        }

        # used in obj_editor view to put
        # row fields into the right order
          # same as ordered_row but just fieldname array
          # instead of key => val pairs of a row
        public static function ordered_fields($fields, $ordered_fields) {
            $fields_in_order = array();
            foreach ($ordered_fields as $name) {
                if (in_array($name, $fields)) {
                    $fields_in_order[] = $name;
                }
            }
            foreach ($fields as $name) {
                if (!in_array($name, $ordered_fields)) {
                    $fields_in_order[] = $name;
                }
            }

            return $fields_in_order;
        }

        # analogous to prep_row but for obj_editor's form fields
        public static function prep_fields($fields) {
            $use_field_ordering_from_minimal_fields = Config::$config['use_field_ordering_from_minimal_fields'];
            $would_be_minimal_fields = Config::$config['would_be_minimal_fields'];

            if ($use_field_ordering_from_minimal_fields
                && is_array($would_be_minimal_fields)
            ) {
                return self::ordered_fields(
                        $fields, $would_be_minimal_fields);
            }
            else {
                return $fields;
            }
        }

        public static function would_be_minimal_fields($tablename_no_quotes) {
            $minimal_fields_by_table = Config::$config['minimal_fields_by_table'];
            $minimal_fields = Config::$config['minimal_fields'];
            $minimal_field_inheritance = Config::$config['minimal_field_inheritance'];

            $ret = (isset($minimal_fields_by_table[$tablename_no_quotes])
                        ? $minimal_fields_by_table[$tablename_no_quotes]
                        : (isset($minimal_fields)
                            ? $minimal_fields
                            : array('name','txt','tags')));

            if ($minimal_field_inheritance
                && is_array($minimal_fields)
            ) {
                foreach ($minimal_fields as $field) {
                    if (!in_array($field, $ret)) {
                        $ret[] = $field;
                    }
                }
            }

            return $ret;
        }

        public static function quot_str_for_js($string) {
            $string = str_replace("'", "\\'", $string);
            $string = str_replace("\n", '\n', $string);
            return "'$string'";
        }

        public static function query_is_order_by_field($query, $field) {
            return (preg_match("/\\border by $field\\b/", $query)
                        ? true
                        : false);
        }

        public static function load_macro($macro_name) {
            #todo #fixme make macro path a config
            $trunk = dirname(__DIR__);
            $path = "$trunk/table_view/macros/$macro_name.json";
            return json_decode(
                file_get_contents($path)
            );
        }

        public static function get_sql_from_macro_name($macro_name) {
            $macro = TableView::load_macro($macro_name);
            $sql_event = $macro[0];
            $sql = $sql_event->load_query;
            return $sql;
        }

        public static function get_macro_names() {
            #todo #fixme make macro path a config
            $trunk = dirname(__DIR__);
            $path = "$trunk/table_view/macros";
            $filenames = glob("$path/*.json");
            $macro_names = array();
            foreach ($filenames as $filename) {
                $macro_names[] = basename($filename, '.json');
            }
            return $macro_names;
        }

    }

