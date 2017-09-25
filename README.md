DB Viewer - database table view with inline dynamic joins
=========================================================

Description / Summary
---------------------

This program provides a PHP-HTML-Javascript web interface
for interacting with a SQL Database, allowing you to:

* (in [Table View](/table_view/README.md))

    * type in Queries and view/browse the results in a table format.

    * you can hide/show rows and columns, and click on key fields (e.g. headers/<th>'s)to join in data from new tables.

        * _Coming Soon: Click on a header and it will sort by that column._
          However, there are already options to sort by a certain column, e.g. a timestamp descending.

* (in [Tree View](/table_view/README.md))

    * see a Tree View of your SQL data, with expandable/collapsable Nodes
      - powered by (d3.js)[https://d3.js]

    * customize a definition of the Root Conditions and other-table Relationships
      that reflect a Tree or Hierarchy of rows within your Database

* (in [Object Editor](/obj_editor/README.md))

    * Create (Insert) or Edit (Update) Rows in your Database via your web browser.

