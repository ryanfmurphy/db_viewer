<html>
    <head>
        <style>
            body {
                font-family: sans-serif;
                background: #322;
            }
            .lists {
                text-align: center; /* center columns */
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
            }
            .item {
                width: <?= $list_width - $item_margin ?>rem;
                height: <?= $item_height ?>rem;
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
<?php
    $lists = array(
        array(
            array('name' => 'This'),
            array('name' => 'is'),
            array('name' => 'Draggable'),
        ),
        array(
            array('name' => 'These'),
            array('name' => 'too'),
        ),
        array(
            array('name' => 'These'),
            array('name' => 'are'),
            array('name' => 'so'),
            array('name' => 'Draggable'),
        ),
        array(
            array('name' => 'These'),
            array('name' => 'as'),
            array('name' => 'well'),
        ),
    );

?>
        <div class="lists">
<?php
    foreach ($lists as $list) {
?>
            <div class="list" ondrop="dragging.drop(event)" ondragover="dragging.allowDrop(event)">
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
