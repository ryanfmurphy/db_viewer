<?php
    class DbViewer {

        # table name manipulation functions
        #----------------------------------

        # prepend table schema etc
        public static function full_tablename($tablename) {
            global $db_type;

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
		public static function just_tablename($full_tablename) {
			$dotPos = strpos($full_tablename, '.');
			return ($dotPos !== false
						? substr($full_tablename, $dotPos+1)
						: $full_tablename);
		}


        # Rendering and Type-Recognition Functions
        # ----------------------------------------

        public static function outputDbError($db) { #keep
?>
<div>
    <p>
        <b>Oops! Could not get a valid result.</b>
    </p>
    <p>
        mysqli_error = <?= mysqli_error(Util::getDbConn()) ?>
    </p>
</div>
<?php
        }

        #todo maybe move to different class?
        public static function is_url($val) {
            if (is_string($val)) {
                $url_parts = parse_url($val);
                $schema = (isset($url_parts['scheme'])
                                ? $url_parts['scheme']
                                : null);

                if ($schema) {
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
            }
            else {
                return false;
            }
        }

        # format $val as HTML to put in <td>
        #   ($table should have no quotes)
        public static function val_html($val, $fieldname, $table=null) {
            global $field_render_filters_by_table, $dash_path;

            #do_log("top of val_html(val='$val', fieldname='$fieldname')\n");

            # pg array is <ul>
            if (DbUtil::seems_like_pg_array($val)) {
                $vals = DbUtil::pgArray2array($val);
                return self::array_as_html_list($vals);
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
            elseif (self::isTableNameVal($val, $fieldname)) {
                { # vars
                    $tablename = $val;
                    $cmp = class_exists('Campaign');
                    $hasPhpExt = !$cmp;
                    $local_uri = ($hasPhpExt
                                    ? 'db_viewer.php'
                                    : 'db_viewer');
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
                return $fn($val, $dash_path, $fieldname);
            }
            # default quoting / handling of val
            else {
                $val = htmlentities($val);
                $val = nl2br($val); # show newlines as <br>'s

                { # get bigger column width for longform text fields
                    #todo factor this logic in the 2 places we have it
                    # (here and in dash)
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

        public static function array_as_html_list($array) { #keep
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

        public static function isTableNameVal($val, $fieldName) { #keep
            return ((preg_match('/Tables_in_/', $fieldName)
                     || $fieldName == "Name")
                            ? true
                            : false);
        }

        public static function get_submit_url($requestVars) {
            $uri = $_SERVER['REQUEST_URI'];
            $uri_no_query = strtok($uri, '?');
            return "$uri_no_query";
        }

        public static function dash_edit_url($dash_path, $tablename_no_quotes, $primary_key) {
            return "$dash_path?edit=1&table=$tablename_no_quotes&primary_key=$primary_key";
        }

        public static function echo_edit_link($dash_path, $tablename_no_quotes, $primary_key) {
?>
        <td class="action_cell">
            <a  href="<?= DbViewer::dash_edit_url($dash_path, $tablename_no_quotes, $primary_key) ?>"
                class="row_edit_link"
                target="_blank"
            >
                edit
            </a>
        </td>
<?php
        }

        public static function echo_special_ops(
            $special_ops_cols, $tablename_no_quotes,
            $primary_key_field, $primary_key, $crud_api_path,
            $row
        ) {
            foreach ($special_ops_cols as $special_ops_col) {
?>
        <td class="action_cell">
            <ul>
<?php
                foreach ($special_ops_col as $special_op) {

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

                        $special_op_url = "$crud_api_path?$query_str";
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
?>
            <li>
                <nobr>
                    <a  href="<?= $special_op_url ?>"
                        class="row_edit_link"
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

    }

