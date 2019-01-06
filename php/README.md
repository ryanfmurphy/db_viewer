DB Viewer - database table view with inline dynamic joins
=========================================================

Description / Summary
---------------------

DB Viewer uses PHP/HTML/Javascript to provide a web interface
for interacting with a SQL Database, allowing you to type
queries and browse results in a table, edit and insert rows,
and view hierarchical data in a tree view.

DB Viewer has 3 main views:

* [Table View](/table_view/README.md)

    * Type in Queries and view/browse the results in a table format.

    * You can hide/show rows and columns

    * Click on header fields (e.g. headers / `<th>`'s) to join in data from new tables.

    * There are options to automatically order by timestamp descending.

* in [Object Editor](/obj_editor/README.md)

    * Create (Insert) or Edit (Update) Rows in your Database via your web browser.

* [Tree View](/table_view/README.md)

    * See a Tree View of your SQL data, with expandable/collapsable Nodes
      - powered by (d3.js)[https://d3.js]

    * Customize a definition of the Root Conditions and other-table Relationships
      that reflect a Tree or Hierarchy of rows within your Database.
      For example, suppose you have a `todo` table and a `note` table, where
      notes can be nested under todos, and todos can also be nested under todos.
      You could define a Tree View that has 2 relationships:

        * `todo -> todo`, meaning a `todo` row can have a `todo` as a child.
        * `todo -> note`, meaning a `todo` row can have a `note` as a child.

    * Or, use [Table/View Inheritance](/docs/Inheritance.md) in your schema
      to allow a heterogenous Tree of different types of Rows to be expressed/queried
      as if the nodes were really all from a single Table or View.
      For instance, let's take the same example as above, with `todo`s and `note`s.
      You could create a base table called `entity` that both `todo` and `note` are
      a subtype of, and therefore instead of having to specify a tree view that has
      the proper relationships between `todo` and `note`, you could just use the
      default tree view `entity -> entity`, and all relevant objects will show up in the
      tree.

