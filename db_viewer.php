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
            $inlineCss = $cmp;
        }

        {   # init: defines $db, DbViewer,
            # and Util (if not already present)

            #note: other larger programs that have their own db setup
                # can integrate with DbViwer by providing their own
                # Util class with a sql() function that takes a $query
                # and returns an array of rows, each row an array

            require_once('init.php');
        }

        { # vars
            { # url & resource setup - jquery etc
                {
                    if (!isset($js_path)) { # allow js_path to be specified in config
                        $js_path = ($cmp ? '/js/shared' : '/js');
                    }
                    $jquery_url = "$js_path/jquery-1.12.3.js";
                }
                if ($cmp) {
                    #todo move out
                    $maybe_url_php_ext = ""; # no .php on end of url
                }
                else {
                    $maybe_url_php_ext = ".php"; # .php on end of url
                }

                $poprJsPath = ($cmp ? '/js/shared/' : '');
            }

            { # get sql query (if any) from incoming request
                $sql = (isset($requestVars['sql'])
                            ? $requestVars['sql']
                            : null);

                { # just tablename? turn to select statement
                    $sqlHasNoSpaces = (strpos($sql, ' ') === false);
                    if (strlen($sql) > 0 && $sqlHasNoSpaces) {
                        $sql = "select * from $sql";
                    } 
                }
            }

            $inferred_table = DbViewer::infer_table_from_query($sql);
        }

        { # render the header row html <th>'s
            # factored into a function because
            # the <th>'s are repeated every so many rows
            # so it's easier to see what column you're on
            function headerRow(&$rows, $rowN) {
                $firstRow = current($rows);
?>
	<tr data-row="<?= $rowN ?>">
<?php
                foreach ($firstRow as $fieldName => $val) {
?>
		<th class="popr" data-id="1">
			<?= $fieldName ?>
		</th>
<?php
                }
?>
	</tr>
<?php
            }
        }
    }

    { # html
?>
<!DOCTYPE html>
<html>
<?php
        { # <head> (including js)
?>
<head>
<?php
            $trunk = __DIR__;
            include("$trunk/html/links_and_scripts.php");
            include("$trunk/js/inline_js.php");
            include("$trunk/dynamic_style.php");
?>
</head>
<?php
        }

        { # <body>
?>
<body>
<?php
            include('html/query_form.php'); # form

            { # inferred table
                if ($inferred_table) {
?>
    <p> Query seems to be with respect to the
        <code><?= $inferred_table ?></code>
        table.

<?php
                    if (isset($dash_links) && $dash_links) {
?>
        <a href="/dash/index.php?table=<?= $inferred_table ?>" target="_blank">
            Create a new
            <code><?= $inferred_table ?></code>
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
                    $rows = Util::sql($sql,'array');

                    include('html/results_table.php'); # html
                    include('js/inline_js_2.php'); # js
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
        <!-- #todo dynamically populate with relevant tables -->
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
                //console.log('elem',elem);
                //console.log('backlinkJoinTable',backlinkJoinTable);
                openBacklinkedJoin(elem);
            });

            // show_hide_mode toggle - #todo add ctrl-enter
            $(document).on('keypress', 'body', function(e){
                var focusedElem = document.activeElement;
                if (queryBoxElem() === focusedElem) { // Ctrl-Click
                    var Enter_code = 13;
                    if (e.ctrlKey
                        && e.which == Enter_code
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
        }
?>
</html>
<?php
    }
?>
