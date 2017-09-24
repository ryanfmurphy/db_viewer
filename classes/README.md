Classes
=======

Db class
--------
The wrapper that calls the database functions to talk to the database.
Currently uses PDO.

Query class
-----------

The Query class is going to be a flexible way of storing SQL queries.

If it has not been parsed yet, a SQL query can be immediately stored 
as a single string variable.  It can also be parsed, meaning pieces of
the query are "stripped off" and stored in their own appropriate
property.

More to come on why I think this is a good idea, but some of the 
initial goals of this restructuring are:

* Pagination (chopping up result sets) in Tree View
* SQL-based Joins (as an option instead of "Inline"/Javascript Joins)
