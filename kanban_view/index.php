<?php
    $cur_view = 'kanban_view';
    include('../includes/init.php');

    $table = 'todo';
    $list_field = Config::$config['kanban_list_field'];
    $sort_order_type = 'float'; # other options: int
    $sort_order_field = 'sort_order';
    $include_nulls = true;
    $null_list_name = 'Inbox';
    $definite_sort_orders_only = true;
    $root_level_nodes_only = Config::$config['kanban_root_level_nodes_only'];
    $additional_lists_to_include = Config::$config['kanban_default_lists'];
    $wheres = Config::$config['kanban_wheres'];

    $primary_key_field = DbUtil::get_primary_key_field($table);

    #todo #fixme put in a class
    function get_lists_and_items(
        $table, $list_field, $sort_order_field, $include_nulls,
        $null_list_name, $definite_sort_orders_only, $root_level_nodes_only,
        $additional_lists_to_include, $wheres
    ) {

        # add additional lists
        $lists = array();
        foreach ($additional_lists_to_include as $list_name) {
            if (!isset($lists[$list_name])) {
                $lists[$list_name] = array();
            }
        }

        #todo turn this into DbUtil::get_rows_split_by_field
        { # get data from DB
            $table_q = DbUtil::quote_ident($table);
            $list_field_q = DbUtil::quote_ident($list_field);

            { # build wheres
                if (!$include_nulls) {
                    $wheres[] = "$list_field_q is not null";
                }
                if ($root_level_nodes_only) {
                    $default_parent_field = Config::$config['default_parent_field'];
                    $default_parent_field_q = DbUtil::quote_ident($default_parent_field);
                    $wheres[] = "$default_parent_field is null";
                }
                if ($definite_sort_orders_only) {
                    $sort_order_field_q = DbUtil::quote_ident($sort_order_field);
                    $wheres[] = "$sort_order_field_q is not null";
                }
            }

            $sql = "
                select * from $table_q
                " . (count($wheres) > 0
                        ? ' where '
                            . implode(' and ', $wheres)
                        : '')
                . "
                order by $list_field_q, $sort_order_field_q
                nulls first
            ";
            $rows = Db::sql($sql);

            # build lists
            foreach ($rows as $row) {
                $list_val = $row[$list_field];
                $list_key = ($list_val === null
                                ? $null_list_name
                                : $list_val);
                $lists[$list_key][] = $row;
            }
        }

        return $lists;
    }

    $lists = get_lists_and_items(
        $table, $list_field, $sort_order_field, $include_nulls, $null_list_name,
        $definite_sort_orders_only, $root_level_nodes_only, $additional_lists_to_include,
        $wheres
    );

?>
<html>
    <head>
        <style>
            body {
                font-family: sans-serif;
                background: #322;
            }
            .lists {
                text-align: center; /* center columns */
                white-space: nowrap; /* allow lists to go off screen */
            }
<?php
    # style vars
    $list_width = 18;
    $list_height = 90; # vh
    $list_margin = .7;
    $list_color = '#ddf';
    $list_border_radius = 8;
    $list_items_area_color = '#aac';
    $list_header_color = 'black';

    $item_height = 3;
    $item_margin = 1;
    $item_color = '#fff';
    $item_border_radius = 3;
?>
            .list {
                display: inline-block;
                vertical-align: top;
                width: <?= $list_width ?>rem;
                height: <?= $list_height ?>vh;
                background: <?= $list_color ?>;
                margin: <?= $list_margin ?>rem;
                margin-bottom: 0; /* so we don't scroll vertically */
                border-radius: <?= $list_border_radius ?>px;
                white-space: normal; /* undo nowrap in container */
                text-align: left;

                /* to prevent weird issue where you select a bunch of text
                   and then you drag and it looks like you're dragging all
                   the text */
                -moz-user-select: none; -webkit-user-select: none; -ms-user-select:none; user-select:none;-o-user-select:none;
            }
            .list_name {
                color: <?= $list_header_color ?>;
                font-size: 100%;
            }
            .list_name h3 {
                margin: .6rem;
            }
            .list_items {
                height: <?= $list_height - 10 ?>vh;
                overflow-x: hidden;
                overflow-y: auto;
                background: <?= $list_items_area_color ?>;
                position: relative; /* make it an offsetParent */
            }
            .list_items .item {
                width: <?= $list_width - $item_margin - .5 ?>rem;
                min-height: <?= $item_height ?>rem;
                background: <?= $item_color ?>;
                margin: <?= $item_margin/2 ?>rem auto;
                border-radius: <?= $item_border_radius ?>px;
                cursor: pointer;
            }
            .list_items a {
                display: block;
                text-decoration: none;
                color: black;
            }
            .item .txt {
                padding: 1rem;
            }
        </style>

        <script>

<?php
    require_once("../js/ajax.js");
?>

        var dragging = {

            item_being_dragged: null,

            allowDrop: function(ev) {
                //console.log('allowDrop',ev);
                ev.preventDefault();
            },

            drag: function(ev) {
                console.log('drag',ev);
                var txt = "Here is some text";
                console.log('txt',txt);
                ev.dataTransfer.setData("Text", txt);
                var item = lists.findItem(ev.target);
                console.log('item',item);
                dragging.item_being_dragged = item;
            },

            // when you release an item an drop it into a new place
            drop: function(ev) {
                console.log('drop',ev);
                var data = ev.dataTransfer.getData("Text");
                console.log('data',data);
                var list_items_area = lists.findListItemsArea(ev.target);
                if (list_items_area) {
                    console.log('list_items_area', list_items_area);
                    var item = dragging.item_being_dragged;
                    if (item) {
                        console.log('item',item);
                        var place_in_list = dragging.decidePlaceInList(ev, list_items_area);
                        console.log('place_in_list',place_in_list);
                        lists.insertItem(item, list_items_area, place_in_list);
                        item = null;
                    }
                }
                else {
                    console.log('no list_items_area to drop into');
                }
                ev.preventDefault();
            },

            // decide where in the list to drop item
            decidePlaceInList: function(event, list_items_area) {
                console.log('decidePlaceInList, list_items_area = ', list_items_area);

                //var rel_y = dragging.getRelativeY(event, list_items_area);
                //console.log('  rel_y',rel_y);
                var our_y = dragging.getRelativeY(event, list_items_area);

                var list_items = lists.listItems(list_items_area);
                console.log('  list_items:', list_items);
                console.log('  looping thru ' + list_items.length + ' items');
                console.log('  to compare with our_y: ' + our_y);

                var this_y, last_y;
                for (var i=0; i < list_items.length; i++) {
                    var item = list_items[i];
                    this_y = item.offsetTop + item.offsetHeight/2;
                    console.log('    <item ' + i + '>.y ', this_y);
                    console.log('      offsetParent = ', item.offsetParent);
                    if (our_y < this_y) {
                        console.log('  found place to insert item:', i);
                        return i;
                    }

                    last_y = this_y;
                }
                console.log('  at the end, place =', i);
                return list_items.length;
            },
 
            getRelativeY: function(event, list_items_area) {
                console.log('  getRelativeY, event=',event,'list_items_area=',list_items_area);
                var area_top_Y = list_items_area.offsetTop;
                console.log('    area_top_Y', area_top_Y);
                var scroll_Y = list_items_area.scrollTop;
                console.log('    scroll_Y', scroll_Y);
                var our_Y = event.pageY;
                console.log('    our_Y', our_Y);
                var relative_Y = our_Y - (area_top_Y - scroll_Y);
                console.log('    relative_Y', relative_Y);
                return relative_Y;
            }

        };

        // <script>
        var lists = {

            listItems: function(list_items_area) {
                //console.log('listItems() - list_items_area =', list_items_area);
                return list_items_area.getElementsByClassName('item');
            },

            getListName: function(list_items_area) {
                var list_name = list_items_area.getAttribute('data-list_name');
                return (list_name == '<?= $null_list_name ?>'
                            ? null
                            : list_name);
            },

            nthItem: function(n, list_items_area) {
                //console.log('nthItem, n=',n);
                list_items = lists.listItems(list_items_area);
                //console.log('  list_items', list_items);
                var val = (list_items.length > n
                                ? list_items[n]
                                : null);
                //console.log('  return val:', val);
                return val;
            },

            // place is the index of where in the list it goes (in the UI)
            insertItem: function(item, list_items_area, place) {
                var sort_order = items.findOkSortOrderFor(list_items_area, place);
                var list_name = lists.getListName(list_items_area);
                updateData.moveToList(item, list_name, sort_order);

                if (sort_order !== undefined) {
                    item.setAttribute('data-sort_order', sort_order);
                }

                // #todo #fixme maybe put this UI move into the success fn of the ajax
                var node_to_insert_before = lists.nthItem(place, list_items_area);
                console.log('node_to_insert_before', node_to_insert_before);
                list_items_area.insertBefore(item, node_to_insert_before);
            },


            // search thru self and ancestors
            findItem: function(elem) {
                while (elem && !lists.isItem(elem)) {
                    elem = elem.parentNode;
                }
                return elem;
            },

            isItem: function(elem) {
                if (!elem || !elem.classList) {
                    console.log('isItem called w strange elem:', elem);
                    return undefined;
                }
                else {
                    return elem.classList.contains('item');
                }
            },

            // search thru self and ancestors
            findListItemsArea: function(elem) {
                while (elem && !lists.isListItemsArea(elem)) {
                    elem = elem.parentNode;
                }
                return elem;
            },

            isListItemsArea: function(elem) {
                if (!elem || !elem.classList) {
                    console.log('isList called w strange elem:', elem);
                    return undefined;
                }
                else {
                    return elem.classList.contains('list_items');
                }
            }

        };

        // <script>
        var items = {

            sort_order_type: '<?= $sort_order_type ?>',

            getSortOrder: function(item) {
                var txt = item.getAttribute('data-sort_order');
                var sort_order_type = items.sort_order_type;
                switch (sort_order_type) {
                    case 'float':   return parseFloat(txt);
                    case 'int':     return parseInt(txt);
                    default:        alert('invalid sort_order_type ' + sort_order_type
                                        + ' - must be float or int');
                }
            },

            findOkSortOrderFor(list_items_area, place) {
                console.log('findOkSortOrderFor()');
                var node_to_insert_before = lists.nthItem(place, list_items_area);
                console.log('  node_to_insert_before', node_to_insert_before);
                var node_to_insert_after = (place > 0
                                                ? lists.nthItem(place-1, list_items_area)
                                                : null);
                console.log('  node_to_insert_after', node_to_insert_after);

                if (node_to_insert_before && node_to_insert_after) {
                    var sort_order_A = items.getSortOrder(node_to_insert_after);
                    var sort_order_B = items.getSortOrder(node_to_insert_before);
                    var new_sort_order = (sort_order_A + sort_order_B) / 2.0;
                    if (items.sort_order_type == 'int') {
                        new_sort_order = Math.round(new_sort_order);
                    }
                    console.log('  sort_order_A',sort_order_A);
                    console.log('  sort_order_B',sort_order_B);
                    console.log('  new_sort_order',new_sort_order);
                    // squawk if there's no good sort_order (have to move stuff around)
                    if (new_sort_order == sort_order_A
                        || new_sort_order == sort_order_B
                    ) {
                        var warning_msg = "Warning - sort_orders are so close we might be losing information";
                        console.log(warning_msg);
                        alert(warning_msg);
                    }
                    console.log('  found sort_order between ' + sort_order_A
                        + ' and ' + sort_order_B + ': ' + new_sort_order);
                    return new_sort_order;
                }
                else if (node_to_insert_before) {
                    var new_sort_order = items.getSortOrder(node_to_insert_before) - 10;
                    console.log("  at the beginning, so new_sort_order =", new_sort_order);
                    return new_sort_order
                }
                else if (node_to_insert_after) {
                    var new_sort_order = items.getSortOrder(node_to_insert_after) + 10;
                    console.log("  at the end, so new_sort_order =", new_sort_order);
                    return new_sort_order
                }
                else {
                    return 0;
                }
            },

        };

        // <script>
        var updateData = {

            table: '<?= $table ?>',
            primary_key_field: '<?= $primary_key_field ?>',
            list_field: '<?= $list_field ?>',
            sort_order_field: '<?= $sort_order_field ?>',
            crud_api_uri: '<?= $crud_api_uri ?>',
            
            moveToList: function(item, list_name, sort_order) {

                var table = updateData.table;
                var primary_key_field = updateData.primary_key_field;
                var list_field = updateData.list_field;
                var sort_order_field = updateData.sort_order_field;
                var crud_api_uri = updateData.crud_api_uri;

                var primary_key = item.getAttribute('data-primary_key');

                var where_clauses = {};
                where_clauses[primary_key_field] = primary_key;

                var data = {
                    action: 'update_' + table,
                    where_clauses: where_clauses
                };
                data[list_field] = list_name;
                if (sort_order !== undefined) {
                    data[sort_order_field] = sort_order;
                }

                doAjax(
                    "POST", crud_api_uri, data,   
                    function(xhttp) {
                        var r = xhttp.responseText;
                        //alert('Success');
                    },
                    function(xhttp) {
                        var r = xhttp.responseText;
                        alert('Something went wrong');
                    }
                );

            }

        };

        </script>

    </head>
    <body style="position:relative">
        <div class="lists">
<?php
    foreach ($lists as $list_name => $list) {
?>
            <div class="list">
                <div class="list_name">
                    <h3><?= $list_name ?></h3>
                </div>
                <div class="list_items"
                     ondrop="dragging.drop(event)"
                     ondragover="dragging.allowDrop(event)"
                     data-list_name="<?= $list_name #todo #fixme what about the "null" list? ?>"
                >
<?php
        foreach ($list as $item) {
            $primary_key = $item[$primary_key_field];
            $sort_order = $item[$sort_order_field];
?>
                    <div class="item"
                         draggable="true"
                         ondragstart="dragging.drag(event)"
                         data-primary_key="<?= $primary_key ?>"
                         data-sort_order="<?= $sort_order ?>"
                    >
                        <div class="txt"
                             href="<?= TableView::obj_editor_url(null, $table, $primary_key) ?>"
                             onclick="window.open(this.getAttribute('href'))"
                        >
                                <?= $item['name'] ?>
                        </div>
                    </div>
<?php
        }
?>
                </div>
            </div>
<?php
    }
?>
        </div>
    </body>
</html>
