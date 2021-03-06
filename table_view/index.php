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
                        $js_path = (isset($cmp) && $cmp #todo #fixme do we still need this?
                                        ? '/js/shared'
                                        : '/js');
                    }
                    $jquery_url = "$js_path/jquery-1.12.3.js";
                }

                if (!isset($php_ext)) {
                    $php_ext = (isset($cmp) && $cmp ? false : true); #todo #fixme do we still need this
                }
            }

            { # get sql query (if any) from incoming request
                { # get sql and sanitize #todo #fixme turn into a fn (in DbUtil?)

                    # sql can be taken implicitly from a launched macro
                    $macroName = (isset($requestVars['play_macro'])
                                    ? $requestVars['play_macro']
                                    : null);

                    $sql = (isset($requestVars['sql'])
                                ? $requestVars['sql']
                                : ($macroName
                                        ? TableView::get_sql_from_macro_name($macroName)
                                        : null));

                    # we just want normal newlines
                    # www forms often post with \r\n
                    $sql = str_replace("\r\n", "\n", $sql);
                }

                # we'll also decide whether to order by time - #todo replace with order_by=fieldname
                $order_by_time = (isset($requestVars['order_by_time'])
                                    ? $requestVars['order_by_time']
                                    : false);

                { # just tablename? turn to select statement
                    $expanded_sql = Query::expand_tablename_into_query(
                        $sql, array(), null, ' limit 100' # to infer/rebuild it later on
                    );
                    if ($expanded_sql) {
                        # and order by time field if there is one
                        $order_by_time = true;
                        $sql = $expanded_sql;
                    }
                }

                DbUtil::disallow_destructive_queries($sql);
            }

            { # vars
                $inferred_table = Query::infer_table_from_query($sql);
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
                    $limit_info = Query::infer_limit_info_from_query($sql);

                    { # prep for order_by_time
                        $order_by_to_add_to_sql = null;
                        if ($order_by_time) {
                            $order_by_to_add_to_sql = DbUtil::order_by_time_sql(
                                $tablename_no_quotes, $schemas_in_path
                            );
                        }

                        if ($order_by_to_add_to_sql) {
                            #todo - would this always be true? can I remove this "if"?
                            if (isset($limit_info['query_wo_limit'])) {
                                $limit_info['query_wo_limit'] .= $order_by_to_add_to_sql;
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

                    # #todo #fixme put back in place one below issues are addressed
                    # 2 problems with this (it's close!)
                    #   1. plasters over other manually set order by's
                    #   2. doesn't add an order by if there wasn't one there already?

                    #{ # order_by via SimplerParser
                    #    # #todo #fixme eliminate a lot of the logic above

                    #    { # figure out $order_by
                    #        $order_by = null;
                    #        if (isset($requestVars['order_by'])) {
                    #            $order_by = isset($requestVars['order_by']);
                    #        }
                    #        else {
                    #            $default_order_by_by_table = Config::$config['default_order_by_by_table'];
                    #            $order_by = (isset($default_order_by_by_table[$tablename_no_quotes])
                    #                            ? $default_order_by_by_table[$tablename_no_quotes]
                    #                            : Config::$config['default_order_by']);
                    #        }
                    #    }

                    #    # inject order by clause into SQL query
                    #    if ($order_by) {
                    #        $s = new SimpleParser();

                    #        $identifier = '["\\w]+';
                    #        $identifier_list = "$identifier(?:\s*,\s*$identifier)*";
                    #        $sql = $s->preg_replace_callback_parse(
                    #            "@order\s*by\s*.*?(?:(?=\s*\blimit\b)|$)@",
                    #            function($match) use (&$requestVars, $order_by) {
                    #                return "order by $order_by";
                    #            },
                    #            $sql,
                    #            /*blank_out_level*/1, /*blank_out_strings*/false, /*blank_out_comments=*/true
                    #        );
                    #    }
                    #}

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

            $scale = .25;
?>
        <style>

<?php
            if (!empty($_GET['iframe'])) {
?>
            #wrap {
                width: 300px; height: 400px; padding: 0; overflow: hidden;
                position: fixed;
                top: 0;
                right: -6em;
            }
            #frame { width: 800px; height: 800px; border: 1px solid black; }
            #frame {
                -ms-zoom: <?= $scale ?>;
                -moz-transform: scale(<?= $scale ?>);
                -moz-transform-origin: 0 0;
                -o-transform: scale(<?= $scale ?>);
                -o-transform-origin: 0 0;
                -webkit-transform: scale(<?= $scale ?>);
                -webkit-transform-origin: 0 0;
            }
<?php
            }

            if (!empty($_GET['scale_font'])) {
                $scale_font = $_GET['scale_font'];
?>
            body {
                font-size: <?= $scale_font * 100 ?>%;
            }
<?php
            }
?>

        </style>
</head>
<?php
        }

        { # <body>
?>
<body>
<?php
            # weird makeshift iframe for e.g. a little todo list in the corner
            if (!empty($_GET['iframe'])) {
?>
    <div id="wrap">
        <iframe id="frame" src="/db_viewer/table_view/index.php?sql=select id,name from task_stack&scale_font=3"></iframe>
    </div>
    <script>
        document.body.addEventListener('keydown',
            function(event){
                console.log(event);
                var KEY_I = 73;
                if (event.which == KEY_I
                    && event.altKey
                ) {
                    var iframe = document.getElementById('frame');
                    iframe.style.display = (iframe.style.display == 'none'
                                                ? 'initial'
                                                : 'none');
                }
            }
        );
    </script>
<?php
            }

            $store_queries_in_local_storage = true; #todo #fixme use config
            if ($store_queries_in_local_storage && $sql) {
?>
    <script>
        function get_sql_queries() {
            return JSON.parse(localStorage.getItem('sql_queries'));
        }

        function save_sql_in_local_storage() {
            var new_sql = '<?= Utility::esc_str_for_js($sql) ?>';
            var sql_queries = get_sql_queries();
            if (sql_queries === null) {
                sql_queries = [];
            }
            console.log("sql_queries before =", sql_queries);
            sql_queries.push(new_sql);
            console.log("sql_queries after =", sql_queries);
            localStorage.setItem('sql_queries', JSON.stringify(sql_queries));
        }

        save_sql_in_local_storage();
    </script> 
<?php
            }
?>

<?php
            if (Config::$config['table_view_tuck_away_query_box']) {
?>
    <p>
        <span   id="click_to_enter_query"
                onclick="$('#query_form_details').show(); $(this).hide()"
        >
            Click to Enter Query
        </span>
    </p>
<?php
            }
?>
    <div id="query_form_details">
<?php
            include("$trunk/table_view/html/top_menu.php");

            include("$trunk/table_view/html/query_form.php"); # form
?>
    </div>

<?php
            { # query_info box
                $show_query_info_box = $inferred_table || $sql;
                if ($show_query_info_box) {
?>
    <div id="query_info">
<?php
                }

                { # report inferred table, create link
                    if ($inferred_table) {
                        $maybe_minimal = $links_minimal_by_default
                                            ? '&minimal'
                                            : '';
?>
        <div id="inferred_table_info">
            Query seems to be with respect to the
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
        </div>
<?php
                    }
                }

                #todo maybe infinite scroll using OFFSET and LIMIT
                if ($sql) {
                    $rows = Db::sql($sql);
                    if (Config::$config['table_view_show_count']) {
?>
        <div id="row_count">
            Showing <?= count($rows) ?> Rows
        </div>
<?php
                    }
                }

                if ($show_query_info_box) {
?>
    </div>
<?php
                }
            }

            { # get & display query data (& provide js interface)

                if ($sql) {
                    include("$trunk/table_view/html/results_table.php"); # html
                }

                include("$trunk/table_view/js/table_view.js.php"); # js

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
        // #todo #fixme maybe move to js file?

        $(document).ready(function() {
            $('.popr').popr();

            // on click popup item
            $(document).on('click', '.popr-item', function(e){
                var elem = lastClickedElem;
                var popupItemElem = e.target;
                // #todo #fixme pass this more explicitly instead of using global var
                backlinkJoinTable = popupItemElem.innerHTML.trim();
                openBacklinkedJoin(elem);
            });

            // keypress:
            // textarea ctrl-enter submit
            // and show_hide_mode toggle
            $(document).on('keypress', 'body', function(e){
                var focusedElem = document.activeElement;
                // textarea ctrl-enter submit
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
                else if (focusedElem === document.getElementById('save_macro_input')) {
                    // don't interrupt typing in input for Show/Hide mode
                }
                // show_hide_mode toggle
                else {
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
