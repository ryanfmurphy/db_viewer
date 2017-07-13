<?php
    $cur_view = 'kanban_view';
    include('../includes/init.php');

    #todo #fixme put in a class
    function get_lists_and_items() {

        #todo turn this into DbUtil::get_rows_split_by_field
        { # get data from DB
            $list_field = 'kanban_list';
            $include_nulls = true;
            $null_list_name = 'Inbox';

            $list_field_q = DbUtil::quote_ident($list_field);

            $rows = Db::sql("
                select * from todo
                " . ($include_nulls
                        ? ""
                        : " where $list_field_q is not null ")
                . "
                order by $list_field_q
                nulls first
            ");

            # build lists
            $lists = array();
            foreach ($rows as $row) {
                $list_val = $row[$list_field];
                $list_key = ($list_val === null
                                ? $null_list_name
                                : $list_val);
                $lists[$list_key][] = $row;
            }
        }

        # add additional lists
        $additional_lists_to_include = array(
            'Inbox',
            'On Deck',
            'On Deck Partially Completed',
            'Working On',
            'QA',
            'Done',
        );
        foreach ($additional_lists_to_include as $list_name) {
            if (!isset($lists[$list_name])) {
                $lists[$list_name] = array();
            }
        }

        return $lists;
    }

    $lists = get_lists_and_items();
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
                /*height: <?= $list_height ?>vh;*/
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
            .item .txt {
                padding: 1rem;
            }
        </style>

        <script>
        var scope = {
            item_being_dragged: null
        };

        var dragging = {

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
                scope.item_being_dragged = item;
            },

            drop: function(ev) {
                console.log('drop',ev);
                var data = ev.dataTransfer.getData("Text");
                console.log('data',data);
                var list_items_area = lists.findListItemsArea(ev.target);
                if (list_items_area) {
                    console.log('list_items_area', list_items_area);
                    var item = scope.item_being_dragged;
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

        }

        var lists = {

            listItems: function(list_items_area) {
                //console.log('listItems() - list_items_area =', list_items_area);
                return list_items_area.getElementsByClassName('item');
            },

            nthItem: function(n, list_items_area) {
                console.log('nthItem, n=',n);
                list_items = lists.listItems(list_items_area);
                console.log('  list_items', list_items);
                var val = (list_items.length > n
                                ? list_items[n]
                                : null);
                console.log('  return val:', val);
                return val;
            },

            insertItem: function(item, list_items_area, place) {
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

        }

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
                >
<?php
        foreach ($list as $item) {
?>
                    <div class="item"
                         draggable="true"
                         ondragstart="dragging.drag(event)"
                    >
                        <div class="txt">
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
