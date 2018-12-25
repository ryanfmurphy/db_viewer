<div id="top_menu">
    <h3  class="top_menu_item"
         onclick="$('#help_legend_details').toggle(); $('#top_menu').toggleClass('top_menu_open')"
    >
        Help
    </h3>
    <h3  class="top_menu_item"
         onclick="$('#macro_details').toggle(); $('#top_menu').toggleClass('top_menu_open')"
    >
        Macros
    </h3>
    <h3  class="top_menu_item"
         onclick="$('#recent_queries').toggle(); $('#top_menu').toggleClass('top_menu_open')"
    >
        Recent Queries
    </h3>

    <div id="top_menu_contents">
        <div id="help_legend_details" style="display:none">
            <h4>Joining More Data to your Table</h4>
            <ul>
                <li>
                    <h5>Clicking a <b>Header Cell</b> of a "whatever"_id field does a <b>Join</b> to that table</h5>
                    <p>It looks in the database for all the rows from that table (the "whatever" table) which have that "whatever"_id, and expands your table to incorporate that data</p>
                </li>
                <li>
                    <h5>Alt-Clicking a <b>Header Cell</b> of a "whatever"_id field lets you choose a table to do a <b>Backwards Join</b> to</h5>
                    <p>It first looks in the database for all the tables that have that "whatever_id" field, then it gives you a Popup Menu to choose which table you want to join to.</p>
                    <p>Then it looks in that table for all rows that have that "whatever"_id, and expands your table to incorporate that data</p>
                    <p>Note that this can expand your existing rows by joining them to multiple rows.  The alternating light and dark shading of the rows will help you see which new rows go with which old ones.</p>
                </li>
            </ul>
            <h4>Hiding Columns and Rows</h4>
            <ul>
                <li>
                    <h5>Pressing <b>H</b> toggles "Show/Hide Mode"</h5>
                    <p>You'll see an window saying Show/Hide Mode is enabled.</p>
                    <p>Then you can click any <b>Data Cell</b> of any column to <b>Hide / Fold Away</b> that column</p>
                    <p>The columns that have hidden data under them will be shaded.  Clicking them again will <b>Show</b> the hidden columns.</p>
                </li>
            </ul>
        </div>

        <div id="macro_details" style="display:none">
            <h4>Load Stored Macro</h4>
            <select onchange="loadMacroFromSelect(this, event)">
                <option>- choose macro -</option>
    <?php
        $macro_names = TableView::get_macro_names();
        foreach ($macro_names as $macro_name) {
    ?>
                <option><?= $macro_name ?></option>
    <?php
        }
    ?>
            </select>

            <h4>Save Current State as Macro</h4>
            <input onkeypress="saveCurrentMacroOnEnter(this, event)"
                   placeholder="Name"
                   id="save_macro_input"
            >
        </div>

        <div id="recent_queries" style="display:none">
            <h4>Recent Queries</h4>
            <ul id="recent_query_list">
            </ul>
            <script>
            function get_sql_queries() { // #fixme dup
                return JSON.parse(localStorage.getItem('sql_queries'));
            }

            function populate_recent_query_list() {
                var elem = document.getElementById('recent_query_list');
                var sql_queries = get_sql_queries();

                // build html
                var li_elems = [];
                // loop backwards
                var first_idx = sql_queries.length - 1;
                var min_idx = Math.max(first_idx - 10, 0);
                for (var n = first_idx; n >= min_idx; n--) {
                    var sql_query = sql_queries[n];
                    var sql_query_esc = encodeURI(sql_query)
                                            .replace(/"/g,"&quot;");
                    var table_view_uri = "<?= Config::$config['table_view_uri'] ?>";
                    var href = table_view_uri + "?sql=" + sql_query_esc;
                    var li_html = "<li>"
                                    + "<a href=\"" + href + "\">"
                                        + sql_query
                                    + "</a>"
                                + "</li>";
                    console.log("li_html = " + li_html);
                    li_elems.push(li_html);
                }

                // write to DOM
                elem.innerHTML = li_elems.join('');
            }
            populate_recent_query_list();
            </script>
        </div>

    </div>

</div>
