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

        { # init: defines $db, DbViewer,
            # and Util (if not already present)

            $db_viewer_path = __DIR__;
            require_once("$db_viewer_path/init.php");
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
                    $sqlHasNoSpaces = (strpos(trim($sql), ' ') === false);
                    if (strlen($sql) > 0
                            && $sqlHasNoSpaces
                    ) {
                        $tablename = $sql;

                        $sql = "select * from "
                                        .DbUtil::quote_tablename($sql)
                                        ." limit 100";

                        # and order by time field if there is one
                        $requestVars['order_by_time'] = true;
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
                                        = DbViewer::would_be_minimal_fields(
                                                $tablename_no_quotes
                                        );
                        $minimal_fields = ($minimal
                                                                ? $would_be_minimal_fields
                                                                : null);
                }


                { # limit/offset/order_by_time stuff: #todo factor into fn

                    # limit, offset, query_wo_limit
                    $limit_info = DbUtil::infer_limit_info_from_query($sql);

                    $order_by_time = (isset($requestVars['order_by_time'])
                                        && $requestVars['order_by_time']);

                    { # passed in limit takes precedence
                        # over one already baked into the sql query
                        if (isset($requestVars['limit'])
                            || isset($requestVars['offset'])
                            || $order_by_time
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
                                #todo ensure that order gets stripped off too if there's an order var?
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

                            { # add limit/offset to sql query
                                if ($order_by_time) {
                                    $time_field = DbUtil::get_time_field(
                                                $tablename_no_quotes, $schemas_in_path);
                                    if ($time_field) {
                                        $sql .= "\norder by $time_field desc";
                                    }
                                }
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
            include("$db_viewer_path/html/links_and_scripts.php");
            include("$db_viewer_path/js/db_viewer_util.js.php");
            include("$db_viewer_path/style.css.php");
?>
</head>
<?php
        }

        { # <body>
?>
<body>
<?php
            include("$db_viewer_path/html/help.php");
            include("$db_viewer_path/html/query_form.php"); # form

            { # report inferred table, create link
                if ($inferred_table) {
?>
    <p> Query seems to be with respect to the
        <code><?= $inferred_table ?></code> table.

<?php
                    { # "create" link
                            if (isset($dash_links) && $dash_links) {
?>
        <a href="<?= $dash_path ?>?table=<?= $tablename_no_quotes ?>&minimal"
             target="_blank"
        >
            Create a new <code><?= $tablename_no_quotes ?></code>
        </a>
<?php
                            }
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

                    include("$db_viewer_path/html/results_table.php"); # html
                    include("$db_viewer_path/js/table_view.js.php"); # js
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
