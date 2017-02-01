<div id="help_legend">
    <h3 id="help_legend_header"
         onclick="$('#help_legend_details').toggle(); $('#help_legend').toggleClass('help_open')"
    >
        Help / Legend of Commands
    </h3>
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
</div>
