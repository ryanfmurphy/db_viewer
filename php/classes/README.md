Classes
=======

Db class
--------
The wrapper that calls the database functions to talk to the database.
Currently uses PDO.

Also contains functions for building up SQL queries.

DbUtil class
------------
The DbUtil class contains functions that deal with SQL Queries and
DB-related functionalities, but are not foundational enough to include
in the DB class.  Some of these functions are helpers to `table_view`,
for example assisting in the Inline Join functionality.

Examples include:

* Functions for determining the type of a field (as explicitly declared
  in `db_config`, as opposed to looking at the schema to determine type)
    * `field_is_array()`
    * `field_is_json()`
    * `field_is_text()`
* Function `get_table_fields()` for getting the columns of a SQL table

Query class (not yet in use)
----------------------------

The Query class is going to be a flexible way of storing SQL queries.

If it has not been parsed yet, a SQL query can be immediately stored 
as a single string variable.  It can also be parsed, meaning pieces of
the query are "stripped off" and stored in their own appropriate
property.

More to come on why I think this is a good idea, but some of the 
initial goals of this restructuring are:

* Pagination (chopping up result sets) in Tree View
* SQL-based Joins (as an option instead of "Inline"/Javascript Joins)
