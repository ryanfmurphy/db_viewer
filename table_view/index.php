<?php
    {
    /*

    DB Viewer - database table view with inline dynamic joins
    =========================================================
    Copyright (c) 2016 Ryan Murphy

    This program provides a PHP-HTML-Javascript web interface
    for a SQL Database, allowing you to type in queries and view
    the results in a table format.  You can hide/show rows and
    columns, and click on key fields to join in data from new
    tables.


    Summary of This File
    --------------------
    * run a sql query
    * build an html table to display the results
    * javascript to allow splicing in join data from other tables
    
    Caveats
    -------
    * may have to set max_input_vars in php.ini for large views
        because column vals[] sent in POST as array - sometimes hits max

    * names / text fields can be used to join, but they must match exactly,
        even if the varchar is collated as case insensitive

    */
    }

    { # init

        { # vars
            $cmp = class_exists('Util');
        }

        { # init: defines $db, TableView,
            # and Util (if not already present)
            $trunk = dirname(__DIR__);
            $cur_view = 'table_view';
            require("$trunk/includes/init.php");
        }

        { # vars
            { # url & resource setup - jquery etc
                {
                    if (!isset($js_path)) { # allow js_path to be specified in config
                        $js_path = ($cmp ? '/js/shared' : '/js');
                    }
                    $jquery_url = "$js_path/jquery-1.12.3.js";
                }

                if (!isset($php_ext)) {
                    $php_ext = ($cmp ? false : true); #todo move out
                }
            }

            { # get sql query (if any) from incoming request
                { # get sql and sanitize
                    $sql = (isset($requestVars['sql'])
                                ? $requestVars['sql']
                                : null);

                    # we just want normal newlines
                    # www forms often post with \r\n
                    $sql = str_replace("\r\n", "\n", $sql);
                }

                { # just tablename? turn to select statement

                    # we'll also decide whether to order by time
                    $order_by_time = (isset($requestVars['order_by_time'])
                                        ? $requestVars['order_by_time']
                                        : false);

                    $sqlHasNoSpaces = (strpos(trim($sql), ' ') === false);
                    if (strlen($sql) > 0
                            && $sqlHasNoSpaces
                    ) {
                        $tablename = $sql;

                        $sql = "select * from "
                                        .DbUtil::quote_tablename($sql)
                                        ." limit 100";

                        # and order by time field if there is one
                        #$requestVars['order_by_time'] = true;
                        $order_by_time = true;
                    }
                }

                { # allow destructive queries?
                    $allow_destructive_queries = Config::$config['allow_destructive_queries'];
                    $query_is_destructive = $destructive_kw = DbUtil::query_is_destructive($sql);
                    if (!$allow_destructive_queries
                        && $query_is_destructive
                    ) {
                        die("Cannot perform a destructive query: keywork '$destructive_kw' found");
                    }
                }
            }

            { # vars
                $inferred_table = DbUtil::infer_table_from_query($sql);
                $tablename_no_quotes = DbUtil::strip_quotes(
                        DbUtil::just_tablename($inferred_table)
                );

                { # allow it to select minimal fields
                        #todo cleanup: $minimal var? ternary op?
                        #todo allow this for other queries, not just tablename "queries"
                        #todo we need to check that we only get fields that exist
                        $would_be_minimal_fields
                                = Config::$config['would_be_minimal_fields']
                                        = TableView::would_be_minimal_fields(
                                                $tablename_no_quotes
                                        );
                        $minimal_fields = ($minimal
                                                ? $would_be_minimal_fields
                                                : null);
                }


                { # limit/offset/order_by_time stuff: #todo factor into fn

                    # limit, offset, query_wo_limit
                    # #todo #fixme this doesn't take into account "order_by time_added"
                    $limit_info = DbUtil::infer_limit_info_from_query($sql);

                    { # prep for order_by_time
                        #(already set)
                        #$order_by_time = (isset($requestVars['order_by_time'])
                        #                    && $requestVars['order_by_time']);

                        $order_by_to_add_to_sql = null;
                        if ($order_by_time) {
                            $time_field = DbUtil::get_time_field(
                                        $tablename_no_quotes, $schemas_in_path);
                            if ($time_field) {
                                $order_by_to_add_to_sql = "\norder by $time_field desc";

                                #todo - would this always be true? can I remove this "if"?
                                if (isset($limit_info['query_wo_limit'])) {
                                    $limit_info['query_wo_limit'] .= $order_by_to_add_to_sql;
                                }
                            }
                        }
                    }

                    { # rebuild sql based on limit / offset / order_by
                        # passed in limit takes precedence
                        # over one already baked into the sql query
                        if (isset($requestVars['limit'])
                            || isset($requestVars['offset'])
                            || $order_by_to_add_to_sql
                        ) {

                            { # populate limit/offset from sql query
                                # if not in GET vars
                                if ($limit_info['limit'] !== null
                                    && !isset($requestVars['limit'])
                                ) {
                                    $requestVars['limit'] = $limit_info['limit'];
                                }

                                if ($limit_info['offset'] !== null
                                    && !isset($requestVars['offset'])
                                ) {
                                    $requestVars['offset'] = $limit_info['offset'];
                                }
                            }

                            { # strip off limit/offset off sql query if any
                                if (isset($limit_info['query_wo_limit'])) {
                                    $sql = $limit_info['query_wo_limit'];
                                }
                            }

                            { # get vals from GET vars if any
                                # GET vars supercede what's in the sql query
                                if (isset($requestVars['limit'])) {
                                    $limit_info['limit'] = $requestVars['limit'];
                                }
                                if (isset($requestVars['offset'])) {
                                    $limit_info['offset'] = $requestVars['offset'];
                                }
                            }

                            { # add to query
                                if ($limit_info['limit'] !== null) {
                                    $sql .= "\nlimit $limit_info[limit]";
                                }
                                if ($limit_info['offset'] !== null) {
                                    $sql .= "\noffset $limit_info[offset]";
                                }
                            }
                        }
                    } # passed in limit takes precedence
                } # limit/offset/order_by_time stuff: #todo factor into fn

            }
        }
    }

    { # html
?>
<!DOCTYPE html>
<html>
<?php
        { # <head> (including js)
                $page_title = $tablename_no_quotes
                                                ? "-$tablename_no_quotes-"
                                                : "DB Viewer"
?>
<head>
        <title><?= $page_title ?></title>
<?php
            include("$trunk/table_view/html/links_and_scripts.php");
            include("$trunk/table_view/js/table_view_util.js.php");
            include("$trunk/table_view/style.css.php");
?>
</head>
<?php
        }

        { # <body>
?>
<body>
<?php
            include("$trunk/table_view/html/help.php");
            include("$trunk/table_view/html/query_form.php"); # form

            { # report inferred table, create link
                if ($inferred_table) {
                    $maybe_minimal = $links_minimal_by_default
                                        ? '&minimal'
                                        : '';
?>
    <p> Query seems to be with respect to the
        <code><?= $inferred_table ?></code> table.

<?php
                    { # "create" link
?>
        <a href="<?= $obj_editor_uri ?>?table=<?= $tablename_no_quotes . $maybe_minimal ?>"
             target="_blank"
        >
            Create a new <code><?= $tablename_no_quotes ?></code>
        </a>
<?php
                    }
?>
    </p>
<?php
                }
            }

            { # get & display query data ...
                # & provide js interface

                #todo infinite scroll using OFFSET and LIMIT
                if ($sql) {
                    $rows = Db::sql($sql);

                    include("$trunk/table_view/html/results_table.php"); # html
                    include("$trunk/table_view/js/table_view.js.php"); # js
                }

                { # js to show even if there's no query in play
?>
<script>

    function queryBoxElem() {
        return document.getElementById('query-box');
    }

</script>
<?php
                }
            }
?>

    <!-- init popup menu -->
    <div class="popr-box" data-box-id="1">
        <div class="popr-item">example</div>
        <div class="popr-item">popup</div>
        <div class="popr-item">data</div>
        <div class="popr-item">(will be dynamically overridden)</div>
    </div>

    <script>
        $(document).ready(function() {
            $('.popr').popr();

            // on click popup item
            $(document).on('click', '.popr-item', function(e){
                var elem = lastClickedElem;
                var popupItemElem = e.target;
                backlinkJoinTable = popupItemElem.innerHTML.trim();
                openBacklinkedJoin(elem);
            });

            // show_hide_mode toggle
            $(document).on('keypress', 'body', function(e){
                var focusedElem = document.activeElement;
                if (queryBoxElem() === focusedElem) { // Ctrl-Enter
                    var Enter_code = 13;
                    var UNIX_Enter_code = 10;
                    if (e.ctrlKey
                        && (e.which == Enter_code
                            || e.which == UNIX_Enter_code)
                    ) {
                        $('#query_form').submit();
                    }
                }
                else { // show-hide mode
                    var H_code = 104;
                    if (e.which == H_code) {
                        show_hide_mode = 1 - show_hide_mode;
                        if (show_hide_mode) {
                            alert('\
Show/Hide Mode Enabled:\n\
\n\
Click a column to hide it, shift-click to reveal it again.\n\
Alt-Click a row to hide it, alt-shift-click to reveal it again.\n\
Press H again to disable.\
');
                        }
                        else {
                            alert('Show/Hide Mode Disabled');
                        }
                    }
                }
            });
        });
    </script>
</body>
<?php
        } # <body>
?>
</html>
<?php
    }
?>
