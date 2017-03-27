To-Do List
==========

* Weird colors need to be fixed:
    * try with bg=dark with no background image, then do a backjoin.
    * try with the plants in the bg, and then do a backjoin.
      Weird too-pale alternating colors.

* Allow nested backlink joins
    * (dynamically bind alt-clicks to newly alt-click-spawned columns)
    * in other words make alt-click menu come up on dynamically-created elements that have been opened,
      not just the ones that are rendered from the start

* function to convert DB Viewer macros into a SQL query

* allow infinite scroll via limit and offset?
    * when you fetch more rows at the bottom or top, incorporate all the joins you've done
        * but then you have to mark all those columns so you can collapse them

* store metadata in view
    * store source table on td's
    * store data type on td's
    * number/label the Joins for more robust/accurate macros

* in-place editing?

* colors
    * add more color options for open joins
    * automatically avoid same color for adjoining open-joins
    * avoid conflict when new join column is opened and given a new color:
      make sure the old color is taken off for that pivot column
      so that there is no CSS uncertainty about which will take precedence

* give a helpful error if user didn't fill out the `db_config.php`

* global scope won't work for CMP/SD - use static class variables
    * what was I using this for anyway?

* start creating / running tests

* in the DB Viewer Table View, when you type a table name that doesn't exist,
  don't just show the message, also re-render DB Viewer so they can try again

* generalize the time field so it doesn't have to be `time_added`

