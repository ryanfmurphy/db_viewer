To-Do List
==========

* many-to-many back-linking relationships - work out kinks
    * when it joins a null value it should add a blank row
    * provide smart cellIndex that takes rowspan into consideration
      so joins within a backlink-expanded splice will work properly
    * once you have a rowspan, new joins should properly include it

* change module/repo name to `db_viewer` - underscore not hyphen

* give a helpful error if user didn't fill out the `db_config.md`

* global scope won't work for CMP/SD - use static class variables

* start creating / running tests

* provide a way to togger `show_hide_mode` so user can hide/show table rows and columns

* color-code the sections in a round-robin style, with more color options
    * (not in the current hierarchical way)
    * allows adjacent sections of the same level to be different colors
        * currently if you open 2 adjacent fields, they will both be red and thus look confusing

* when you fetch more rows, incorporate all the joins you've done
    * but then you have to mark all those columns so you can collapse them

* implement limit and offset




