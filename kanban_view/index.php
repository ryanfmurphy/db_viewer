<html>
    <head>
        <style>
            body {
                font-family: sans-serif;
            }
<?php
    $list_width = 15;
    $list_height = 80;
    $list_margin = .7;
    $list_color = '#977';

    $item_height = 5;
    $item_margin = 1.5;
    $item_color = '#fdd';
?>
            .list {
                display: inline-block;
                vertical-align: top;
                width: <?= $list_width ?>rem;
                /*height: <?= $list_height ?>vh;*/
                min-height: <?= $list_height ?>vh;
                background: <?= $list_color ?>;
                margin: <?= $list_margin ?>rem;
            }
            .item {
                width: <?= $list_width - $item_margin ?>rem;
                height: <?= $item_height ?>rem;
                background: <?= $item_color ?>;
                margin: <?= $item_margin/2 ?>rem auto;
                text-align: center;
            }
            .item .txt {
                padding: 1rem;
            }
        </style>

        <script>
        var scope = {
            item_being_dragged: null
        };

        function allowDrop(ev) {
            console.log('allowDrop',ev);
            ev.preventDefault();
        }

        function drag(ev) {
            console.log('drag',ev);
            var txt = "Here is some text"; //ev.target.id;
            console.log('txt',txt);
            ev.dataTransfer.setData("Text", txt);
            var item = ev.target;
            console.log('item',item);
            scope.item_being_dragged = item;
        }

        function isList(elem) {
            return elem.classList.contains('list');
        }

        // search thru self and ancestors
        function findList(elem) {
            while (elem && !(isList(elem))) {
                elem = elem.parentNode;
            }
            return elem;
        }

        function drop(ev) {
            console.log('drop',ev);
            var data = ev.dataTransfer.getData("Text");
            console.log('data',data);
            var list = findList(ev.target);
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
        </script>

    </head>
    <body>
        <div class="list" ondrop="drop(event)" ondragover="allowDrop(event)">
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
        </div>
        <div class="list" ondrop="drop(event)" ondragover="allowDrop(event)">
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
        </div>
        <div class="list" ondrop="drop(event)" ondragover="allowDrop(event)">
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
        </div>
        <div class="list" ondrop="drop(event)" ondragover="allowDrop(event)">
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
            <div class="item" draggable="true" ondragstart="drag(event)">
                <div class="txt">Draggable</div>
            </div>
        </div>
    </body>
</html>
