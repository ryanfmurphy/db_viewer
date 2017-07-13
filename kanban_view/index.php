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
    $list_width = 15;
    $list_height = 80;
    $list_margin = .7;
    $list_color = '#779';
    $list_border_radius = 16;

    $item_height = 5;
    $item_margin = 1.5;
    $item_color = '#ddf';
    $item_border_radius = 8;
?>
            .list {
                display: inline-block;
                vertical-align: top;
                width: <?= $list_width ?>rem;
                /*height: <?= $list_height ?>vh;*/
                min-height: <?= $list_height ?>vh;
                background: <?= $list_color ?>;
                margin: <?= $list_margin ?>rem;
                border-radius: <?= $list_border_radius ?>px;
                white-space: normal; /* undo nowrap in container */
            }
            .list_name {
                color: white;
                font-size: 110%;
            }
            .item {
                width: <?= $list_width - $item_margin ?>rem;
                min-height: <?= $item_height ?>rem;
                background: <?= $item_color ?>;
                margin: <?= $item_margin/2 ?>rem auto;
                text-align: center;
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
                var item = find.findItem(ev.target);
                console.log('item',item);
                scope.item_being_dragged = item;
            },

            drop: function(ev) {
                console.log('drop',ev);
                var data = ev.dataTransfer.getData("Text");
                console.log('data',data);
                var list = find.findList(ev.target);
                if (list) {
                    console.log('list', list);
                    var item = scope.item_being_dragged;
                    if (item) {
                        console.log('item',item);
                        list.appendChild(item);
                        item = null;
                    }
                }
                else {
                    console.log('no list to drop into');
                }
                ev.preventDefault();
            }

        }

        var is = {

            isItem: function(elem) {
                if (!elem || !elem.classList) {
                    console.log('isItem called w strange elem:', elem);
                    return undefined;
                }
                else {
                    return elem.classList.contains('item');
                }
            },

            isList: function(elem) {
                if (!elem || !elem.classList) {
                    console.log('isList called w strange elem:', elem);
                    return undefined;
                }
                else {
                    return elem.classList.contains('list');
                }
            }

        }

        // search thru self and ancestors
        var find = {

            findItem: function(elem) {
                while (elem && !is.isItem(elem)) {
                    elem = elem.parentNode;
                }
                return elem;
            },

            findList: function(elem) {
                while (elem && !is.isList(elem)) {
                    elem = elem.parentNode;
                }
                return elem;
            }

        }

        </script>

    </head>
    <body>
        <div class="lists">
<?php
    foreach ($lists as $list_name => $list) {
?>
            <div class="list" ondrop="dragging.drop(event)" ondragover="dragging.allowDrop(event)">
                <div class="list_name">
                    <h3><?= $list_name ?></h3>
                </div>
<?php
        foreach ($list as $item) {
?>
                <div class="item" draggable="true" ondragstart="dragging.drag(event)">
                    <div class="txt">
                        <?= $item['name'] ?>
                    </div>
                </div>
<?php
        }
?>
            </div>
<?php
    }
?>
        </div>
    </body>
</html>
