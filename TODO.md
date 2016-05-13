To-Do List
==========

* make a way to save "views"
    * as a SQL query?
    * or as a series of clicks/actions to replay in the view?
        * what fields/joins have been opened?
        * what columns and rows are hidden?

* in-place editing

* make alt-click menu come up on dynamically-created elements that have been opened
    * not just the ones that are rendered from the start

* implement limit and offset
    * when you fetch more rows at the bottom or top, incorporate all the joins you've done
        * but then you have to mark all those columns so you can collapse them

* colors
    * add more color options for open joins
    * automatically avoid same color for adjoining open-joins
    * avoid conflict when new join column is opened and given a new color:
      make sure the old color is taken off for that pivot column
      so that there is no CSS uncertainty about which will take precedence

* store source table on td's
* store data type on td's

* give a helpful error if user didn't fill out the `db_config.md`

* global scope won't work for CMP/SD - use static class variables
    * what was I using this for anyway?

* start creating / running tests

* change module/repo name to `db_viewer` - underscore not hyphen

