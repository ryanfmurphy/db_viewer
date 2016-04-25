To-Do List
==========

* support many-to-many and back-linking relationships
    * build out the JS interface
        * alt-click on a primary_key, make a menu come up asking which connection you want
        * JS makes an AJAX and the associated data back
        * insert that data into the DOM, html `<table>` style
            * with a `rowspan` in the many-to-1 case

* change module/repo name to `db_viewer` - underscore not hyphen

* give a helpful error if user didn't fill out the `db_config.md`

* global scope won't work for CMP/SD - use static class variables

* start creating / running tests

* provide a way to togger `show_hide_mode` so user can hide/show table rows and columns

* color-code the sections in a round-robin style, with more color options
    * (not in the current hierarchical way)
    * allows adjacent sections of the same level to be different colors
        * currently if you open 2 adjacent fields, they will both be red and thus look confusing

