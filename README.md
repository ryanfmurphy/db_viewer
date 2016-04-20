DB Viewer - database table view with inline dynamic joins
=========================================================

This program provides a PHP-HTML-Javascript web interface
for a SQL Database, allowing you to type in queries and view
the results in a table format.  You can hide/show rows and
columns, and click on key fields to join in data from new
tables.

join-splice
-----------
* click a header / field name that corresponds to an id in another table
  and see the data from that table get automatically spliced into your view
* can connect in the following ways:
    * connect fields named `<table>_id` to the `<table>_id` of the `<table>` table
        * or if `$id_mode == "id_only"`, to the `id` of the `<table>` table
    * connect fields named `<table>_iid` to the `<table>_iid` of the `<table>` table - #todo test
        * or if `$id_mode == "iid_only"`, to the `iid` of the `<table>` table
    * connect fields named `<table>` to the `name` field of the `<table>` table

show and hide columns and rows
------------------------------
* click a column to hide it, shift-click to show again
* alt-click a row to hide it, shift-alt-click to show again

