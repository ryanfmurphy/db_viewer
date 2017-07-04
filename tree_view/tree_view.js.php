<?php
    # gets included from tree_view/index.php with certain vars already set
?>
        <script>

<?php
    require_once("$trunk/js/ajax.js");
?>

var table_info = <?= json_encode($table_info) ?>;

var svg_tree = {
    tree: undefined,
    root: undefined,
    svg: undefined,
    i: undefined,
    duration: undefined,
    diagonal: undefined,
    margin: undefined,
    width: undefined,
    height: undefined,
    level_width: undefined
};

var defaults = {
    width: 5000,
    height: 800,
    level_width: 180 
}

function setupTree(new_width, new_height, level_width) {
    if (new_width === undefined) new_width = defaults.width;
    if (new_height === undefined) new_height = defaults.height;
    if (level_width === undefined) level_width = defaults.level_width;

    var left_margin = <?= ($omit_root_node
                                ? 120
                                : 250) ?>;
    var margin = svg_tree.margin =
        {top: 20, right: 120, bottom: 20, left: left_margin};
    var width = svg_tree.width =
        new_width - margin.right - margin.left;
    var height = svg_tree.height =
        new_height - margin.top - margin.bottom;
    svg_tree.level_width = level_width;

    svg_tree.i = 0;
    svg_tree.duration = 750;

    svg_tree.tree = d3  .layout.tree()
                        .size([height, width]);

    svg_tree.diagonal = d3  .svg.diagonal()
                            .projection(function(d) { return [d.y, d.x]; });

    svg_tree.svg = d3.select("body").append("svg")
                     .attr("width", width + margin.right + margin.left)
                     .attr("height", height + margin.top + margin.bottom)
            .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")");
}

// <script>
// add elements of arr2 to elements of arr2, offsetting
// arr2 by off, so that arr2[0] aligns with arr1[off]
// destructively modifies arr1
function addArrayAtIndex(arr1, arr2, off) {
    for (var i=0; i<arr2.length; i++) {
        var arr1_idx = i + off
        if (arr1.length <= arr1_idx) {
            arr1[arr1_idx] = 0;
        }
        arr1[arr1_idx] += arr2[i];
    }
}

function countNodesByLevel(node, level) {
    if (level === undefined) level = 0;
    var num_nodes_by_level = [1];
    var keys = ['children','_children'];
    for (var k=0; k < keys.length; k++) {
        var key = keys[k];
        if (key in node) {
            var children = node[key];
            for (var i=0; i < children.length; i++) {
                var child = children[i];
                addArrayAtIndex(
                    num_nodes_by_level, // adds them to this
                    countNodesByLevel(child, level+1),
                    1 // offset addition by 1
                );
            }
        }
    }
    return num_nodes_by_level;
}

function numNodesInLargestLevel(node) {
    var num_nodes_by_level = countNodesByLevel(node, 0);
    console.log('num_nodes_by_level', num_nodes_by_level);
    var max_nodes_any_lev = 0;
    for (var i=0; i < num_nodes_by_level.length; i++) {
        num_nodes_this_lev = num_nodes_by_level[i];
        if (num_nodes_this_lev > max_nodes_any_lev) {
            max_nodes_any_lev = num_nodes_this_lev;
        }
    }
    return max_nodes_any_lev;
}

// numNodesInLargestLevel but also caring about the avg nodes in level
function treeHeightFactor(node) {
    var num_nodes_by_level = countNodesByLevel(node, 0);
    console.log('num_nodes_by_level', num_nodes_by_level);
    var max_nodes_any_lev = 0;
    for (var i=0; i < num_nodes_by_level.length; i++) {
        num_nodes_this_lev = num_nodes_by_level[i];
        if (num_nodes_this_lev > max_nodes_any_lev) {
            max_nodes_any_lev = num_nodes_this_lev;
        }
    }
    var avg_nodes_any_lev = max_nodes_any_lev / num_nodes_by_level.length;
    return (max_nodes_any_lev + avg_nodes_any_lev) / 2;
}

// <script>
// i and j only count as half
function strlenVarWidth(str) {
    var num_short_ones = 0;
    for (var i=0; i < str.length; i++) {
        if (   str[i] == 'i' || str[i] == 'I'
            || str[i] == 'j' || str[i] == 'l'
        ) {
            num_short_ones++;
        }
    }
    return str.length - (num_short_ones/2);
}

function getMaxNodeStrlen(node, name_cutoff, strlen_fn) {
    var max_node_strlen = 0;
    if (strlen_fn === undefined) {
        strlen_fn = function(str) {
            return str.length;
        }
    }

    // check this node's name directly
    var name = node._node_name;
    if (typeof name  === 'string') {
        // apply name cutoff if any
        if (name_cutoff
            && strlen_fn(name) > name_cutoff
        ) {
            name = node._node_name =
                name.slice(0,name_cutoff) + '...';
        }

        var len = strlen_fn(name);
        if (len > max_node_strlen) {
            max_node_strlen = len;
        }
    }

    var keys = ['children','_children'];
    for (var k=0; k < keys.length; k++) {
        var key = keys[k];
        if (key in node) {
            var children = node[key];
            for (var i=0; i < children.length; i++) {
                var child = children[i];
                max_node_strlen = Math.max(
                    getMaxNodeStrlen(child, name_cutoff, strlen_fn),
                    max_node_strlen
                );
            }
        }
    }
    return max_node_strlen;
}

function setupTreeWithSize(root) {
    // #todo #fixme make a weighted count fn that cares about stars
    /*var num_nodes_updown = numNodesInLargestLevel(root);
    var height = Math.max(
        num_nodes_updown * 24,
        defaults.height
    );
    */
    var num_nodes_updown = treeHeightFactor(root);
    console.log('num_nodes_updown', num_nodes_updown);
    var height_factor_config = <?= Config::$config['tree_height_factor'] ?>;
    var height = Math.max(
        num_nodes_updown * 65 * height_factor_config,
        defaults.height
    );
    var width = undefined; // 2000;

    var name_cutoff = <?= $name_cutoff
                            ? (int)$name_cutoff
                            : 'undefined' ?>;
    var max_node_strlen = getMaxNodeStrlen(
        root, name_cutoff, strlenVarWidth
    );

    // guess how much space the name needs
    var approx_max_node_width = max_node_strlen * 9; //5.2;

    var level_width = Math.max(
        approx_max_node_width, defaults.level_width
    );

    setupTree(width, height, level_width);
}

<?php
    # build up a get_tree url to get the tree data as JSON
    # send all the tree vars from vars.php as GET vars
    $tree_vars = compact($tree_var_names);
    $get_tree_query_string = http_build_query($tree_vars);
?>
function treeDataUrl() {
    return "<?= "$get_tree_uri?$get_tree_query_string" ?>";
}

function createTree() {
    //setupTree();
    d3.json(
        treeDataUrl(),
        function(error, flare) {
            if (error) throw error;

            // #todo should this set the root on the svg_tree?
            root = svg_tree.root = flare;

            setupTreeWithSize(root);
            root.x0 = svg_tree.height / 2;
            root.y0 = 0;

            function collapse(d) {
                if (d.children) {
                    d._children = d.children;
                    d._children.forEach(collapse);
                    d.children = null;
                }
            }

            // #todo functionalize
<?php
    if (!$start_w_tree_fully_expanded) {
?>
            root.children.forEach(collapse);
<?php
    }
?>
            updateTree(root);
<?php
    if (!$start_w_tree_fully_expanded) {
?>
            setTimeout(function(){
                expandRootNodes();
            }, 500);
<?php
    }
?>

<?php
    if ($omit_root_node) {
?>
            ghostRootNode();
<?php
    }
?>
        }
    );

    d3  .select(self.frameElement)
        .style("height", "800px"); // #todo #fixme hard-coded height
}

// <script>
// the root node is the one the furthest to the left
// the one with the lowest X coordinate
function getRootNode() {
    var nodes = document.querySelectorAll('circle');
    var min_x = undefined;
    var root_node = undefined;
    for (var i=0; i<nodes.length; i++) {
        //console.log('i =',i);
        var node = nodes[i];
        //console.log('node =',node);
        var rect = node.getBoundingClientRect();
        //console.log('rect =',rect);
        var new_x = rect.left; //rect.x;
        //console.log('new_x =', new_x);
        if (min_x === undefined
            || new_x < min_x
        ) {
            //console.log("and that's good enough to make", node, 'the new root');
            //console.log('min_x was', min_x);
            root_node = node;
            min_x = new_x;
        }
    }
    console.log('root_node', root_node);
    return root_node;
}

// hide the "root" node because in our "tree"
// all the 2nd-level nodes are really the "root nodes"
function ghostRootNode() {
    // HACK: using a timeout because all the nodes
    // start at the same place - wait for the spread
    setTimeout(function(){
        getRootNode().style.display = 'none'
    },500);
}

createTree();

function updateTree(source) {

    var tree = svg_tree.tree;
    var svg = svg_tree.svg;
    var diagonal = svg_tree.diagonal;

    // Compute the new tree layout.
    var nodes = tree.nodes(root).reverse(),
        links = tree.links(nodes);

    // Normalize for fixed-depth.
    nodes.forEach(function(d) {
        d.y = d.depth * svg_tree.level_width;
    });

    // Update the nodes…
    var node = svg  .selectAll("g.node")
                    .data(nodes, function(d) {
                        return  d.svg_node_id
                                || (d.svg_node_id = ++svg_tree.i);
                    });

    // Enter any new nodes at the parent's previous position.
    var nodeEnter = node.enter().append("g")
                        .attr("class", "node")
                        .attr("transform",
                            function(d) {
                                return "translate(" + source.y0 + "," + source.x0 + ")";
                            }
                        );

    nodeEnter.append("circle")
            .attr("r", 1e-6)
            .style("fill",  function(d) {
                                return d._children
                                    ? "lightsteelblue"
                                    : "#fff";
                            }
            )
            .on("click", toggleNodeExpansion);

    var vary_node_colors = <?= (int)Config::$config['vary_node_colors']; ?>;
    nodeEnter.append("text")
            .attr("x", function(d) {
                            // where the text goes
                            /*return d.children || d._children
                                        ? -10
                                        : 10;*/
                            // put all on the left
                            return -10;
                       })
            .attr("dy", ".35em")
            .style("font-size", function(d) {
                if ('stars' in d && d.stars !== null) {
                    var ems_for_2_stars = 1;
                    var multiplier = d.stars / 2;
                    var ems = ems_for_2_stars * multiplier;
                    return ems.toString() + 'em';
                }
                else {
                    return "1em";
                }
            })
            .attr("text-anchor", function(d) {
                                    // text justify
                                    /* return d.children || d._children
                                        ? "end"
                                        : "start"; */
                                    return "end";
                                 })
            .text(function(d) { return d._node_name; })
            .style("fill-opacity", 1e-6)
            .on("click", clickLabel)
            .attr("class", function(d) {
                return d._node_table && vary_node_colors
                    ? d._node_table + '_tbl_color'
                    : null;
            })
            .style("fill", function(d) {
                return d._node_color && vary_node_colors
                    ? d._node_color
                    : null;
            })
            ;

<?php
    $maybe_tree_transitions = ($do_tree_transitions
                                    ? '
                                        .transition()
                                        .duration(svg_tree.duration)
                                      '
                                    : '');
?>
    // Transition nodes to their new position.
    var nodeUpdate = node
            <?= $maybe_tree_transitions ?>
            .attr("transform",
                function(d) {
                    return "translate(" + d.y + "," + d.x + ")";
                }
            );

    nodeUpdate.select("circle")
            .attr("r", 4.5)
            .style("fill",
                function(d) {
                    return d._children
                                ? "lightsteelblue"
                                : "#fff";
                }
            );

    nodeUpdate.select("text")
            .style("fill-opacity", 1);

    // Transition exiting nodes to the parent's new position.
    var nodeExit = node .exit()
                        <?= $maybe_tree_transitions ?>
                        .attr("transform",
                            function(d) {
                                return "translate(" + source.y + "," + source.x + ")";
                            }
                        )
                        .remove();

    nodeExit.select("circle")
            .attr("r", 1e-6);

    nodeExit.select("text")
            .style("fill-opacity", 1e-6);

    // Update the links…
    var link = svg  .selectAll("path.link")
                    .data(links, function(d) {
                        return d.target.svg_node_id;
                    });

    // Enter any new links at the parent's previous position.
    link.enter().insert("path", "g")
                .attr("class", "link")
                .attr("d", function(d) {
                    var o = {
                        x: source.x0,
                        y: source.y0
                    };
                    return diagonal({
                        source: o,
                        target: o
                    });
                })
                // make "root" "connections" not show up
                .style('stroke', function(d) {
                    if (d.source._node_name == '') { // #todo use obj ref
                        return 'white';
                    }
                });

    // Transition links to their new position.
    link
            <?= $maybe_tree_transitions ?>
            .attr("d", diagonal);

    // Transition exiting nodes to the parent's new position.
    link.exit()
            <?= $maybe_tree_transitions ?>
            .attr("d", function(d) {
                var o = {x: source.x, y: source.y};
                return diagonal({source: o, target: o});
            })
            .remove();

    // Stash the old positions for transition.
    nodes.forEach(function(d) {
        d.x0 = d.x;
        d.y0 = d.y;
    });
}

function nodeIsExpanded(d) {
    return d.children; // care about truthiness
}

function expandNode(d) {
    d.children = d._children;
    d._children = null;
    updateTree(d);
}
function collapseNode(d) {
    d._children = d.children;
    d.children = null;
    updateTree(d);
}

// toggle children on click.
function toggleNodeExpansion(d) {
    if (nodeIsExpanded(d)) {
        collapseNode(d)
    }
    else {
        expandNode(d)
    }
}

function expandRootNodes() {
    var rootRoot = svg_tree.root;
    var roots = rootRoot.children;
    for (var i=0; i < roots.length; i++) {
        var node = roots[i];
        expandNode(node);
    }
}

<?php
    { # figure out primary key fields for all needed tables
      # and provide array to JS
        $id_mode = Config::$config['id_mode'];

        $id_fields_by_table = array();
        $id_fields_by_table[$root_table] =
            DbUtil::get_primary_key_field($root_table);

        foreach ($parent_relationships as $relationship) {
            $child_table = $relationship['child_table'];
            $parent_table = $relationship['parent_table'];
            if (!isset($id_fields_by_table[$child_table])) {
                $id_fields_by_table[$child_table] =
                    DbUtil::get_primary_key_field($child_table);
            }
            if (!isset($id_fields_by_table[$parent_table])) {
                $id_fields_by_table[$parent_table] =
                    DbUtil::get_primary_key_field($parent_table);
            }
        }
    }
?>

// <script>
var id_fields_by_table = <?= json_encode($id_fields_by_table) ?>;


// Nest Mode - some crude UI to help easily nest nodes under other nodes

var nest_mode = false;
var add_parent_instead_of_move = null; // shift-N to add parent, n to move parent
var selected_nodes = [];

function get_alert_elem() {
    return document.getElementById('alert');
}

function do_alert(msg, color) {
    var alert_elem = get_alert_elem();
    alert_elem.innerHTML = msg;
    alert_elem.style.display = 'inline';
    alert_elem.style.background = color;
}

function doNestModeAlert(mode) {
    if (nest_mode === 'click_selected_nodes') {
        var action = (add_parent_instead_of_move
                        ? 'add a new parent to'
                        : 'move');
        do_alert('Nest mode: click a node to ' + action + ', or N to stop', 'orange');
    }
    else if (nest_mode === 'click_new_parent_or_select_more') {
        var action = (add_parent_instead_of_move
                        ? 'add'
                        : 'move');
        do_alert(
            'Click another node to ' + action + ' selected node(s) there,<br>\
            shift-click more nodes to select them them too, or N to cancel',
            'purple'
        );
    }
    else if (nest_mode === 'success') {
        do_alert('Success. Refresh to see changes.', 'green');
        nest_mode = 'click_selected_nodes';
        setTimeout(function() {
            doNestModeAlert(nest_mode);
        }, 750);
    }
    else if (nest_mode === 'error') {
        do_alert("Something went wrong", 'red');
        nest_mode = false;
        setTimeout(function() {
            doNestModeAlert(nest_mode);
        }, 1500);
    }
    else if (nest_mode === false) {
        do_alert('Nest mode disabled', 'blue');
        setTimeout(function() {
            get_alert_elem().style.display = 'none';
        }, 1500);
    }
}

document.addEventListener('keypress', function(event){
    console.log(event);
    var n_code = 110;
    var N_code = 78;
    if (event.which == n_code
        || event.which == N_code
    ) {
        if (nest_mode) {
            nest_mode = false;
            doNestModeAlert(nest_mode);
        }
        else if (!nest_mode) {
            nest_mode = 'click_selected_nodes';
            add_parent_instead_of_move = (event.which == N_code); // #todo only allow if parent_field is an array
            doNestModeAlert(nest_mode);
        }
    }
});

// <script>
function removeChildFromNode(node, child, doUpdateTree) {
    if (doUpdateTree === undefined) doUpdateTree = true;
    console.log('removeChildFromNode, node=',node,'child=',child);
    var keys = ['children','_children'];
    for (var k=0; k < keys.length; k++) {
        var key = keys[k];
        if (key in node) {
            var children = node[key];
            for (var i = children.length - 1; i >= 0; i--) {
                console.log('checking child',i);
                var this_child = children[i];
                console.log('child=',child);
                if (child === this_child) {
                    console.log('deleting children['+i+']');
                    children.splice(i,1);
                    return node;
                }
            }
        }
    }
    if (doUpdateTree) {
        updateTree(node);
    }
}

function cloneSvgNode(obj) {
    var obj2 = {};
    for (var k in obj) {
        if (k != 'svg_node_id'
            && obj.hasOwnProperty(k)
        ) {
            obj2[k] = obj[k];
        }
    }
    console.log('obj2',obj2);
    return obj2;
}

function addChildToNode(node, child, doUpdateTree) {
    if (doUpdateTree === undefined) doUpdateTree = true;
    // #todo #fixme what if node isn't expanded? _children
    if (!node.hasOwnProperty('children')) {
        node.children = [];
    }
    node.children.push(
        cloneSvgNode(child)
    );
    if (doUpdateTree) {
        updateTree(node);
    }
}

<?php
    if ($backend == 'db') {
?>
// <script>
// clicking the Label takes you to that object in db_viewer
function clickLabel(d) {
    console.log(d3.event.shiftKey);
    var table = ('_node_table' in d
                    ? d._node_table
                    : null);

    // Use _conn_table if possible because if it's here
    // it means the _node_table a polymorphic table read
    // from the 'relname' field due to the option
    // 'use_relname_for_tree_node_table'.
    // We may not have that table in id_fields_by_table
    // so the backend gives us '_conn_table' which is the
    // non-polymorphic base-table that we have in our hash
    var conn_table = ('_conn_table' in d
                        ? d._conn_table
                        : table);

    if (table && conn_table) {
        var id_field = id_fields_by_table[conn_table];
        if (nest_mode) {
            if (nest_mode == 'click_selected_nodes') {
                console.log('click_selected_nodes');
                selected_nodes = [d];
                nest_mode = 'click_new_parent_or_select_more';
                doNestModeAlert(nest_mode);
            }
            else if (nest_mode == 'click_new_parent_or_select_more') {
                var sub_mode = (d3.event.shiftKey
                                    ? 'select_more'
                                    : 'click_new_parent');

                if (sub_mode == 'click_new_parent') {
                    console.log('click_new_parent');
                    // #todo #factor into a function moveNodeUnderNewParent()
                    // selected_nodes var is already populated
                    var new_parent = d;
                    var num_succeeded = 0;
                    for (var i = 0; i < selected_nodes.length; i++) {
                        // #note could be adding a parent instead of moving
                        var node_to_move = selected_nodes[i]; // #todo #fixme
                        console.log('loop, i', i, 'node_to_move (or add to parent)', node_to_move);
<?php
    $new_parent_table = 'entity'; #todo #fixme - don't always assume a catchall entity table
                                  #              probably will need all this stuff in pure JS
?>
                        var primary_key_field = '<?= DbUtil::get_primary_key_field($new_parent_table) ?>';
                        var primary_key = node_to_move[id_field];
                        var parent_id_field = '<?= Config::$config['default_parent_field'] ?>';

                        var table_name = '<?= $new_parent_table ?>';

                        var url = "<?= $crud_api_uri ?>";

                        // #todo maybe #factor common code
                        // for parent_id_field stuff
                        var parent_field_is_array = <?= (int)DbUtil::field_is_array($default_parent_field) ?>;
                        var parent_id = new_parent[id_field];
                        /*var parent_field_val = (parent_field_is_array
                                                    ? '{'+parent_id+'}'
                                                    : parent_id);*/

                        // build up the AJAX data to update the node
                        var success_callback = null;
                        var data = null;

                        if (parent_field_is_array) {
                            // array field:
                            // if pressed shift-N, then add addl parent: use "add_to_array" action
                            //      else move parent by removing JUST the one existing parent and adding the new one
                            //      (allow other existing parents to remain)

                            var remove_child = !add_parent_instead_of_move;

                            data = {
                                action: 'add_to_array',
                                table: table_name,
                                primary_key: primary_key,
                                field_name: parent_id_field,
                                val_to_add: parent_id
                            };

                            // if we're moving the node, replace the existing id
                            if (remove_child) {
                                // #todo #fixme can we always depend on the key being 'id'
                                var existing_parent_id = node_to_move.parent.id;
                                data['val_to_replace'] = existing_parent_id;
                            }

                            // #todo could #factor success_callback back in between the 2 cases
                            success_callback = (function(node_to_move, parent_id_field) {
                                return function(xhttp) {
                                    var r = xhttp.responseText;
                                    nest_mode = 'success';
                                    doNestModeAlert(nest_mode);
                                    
                                    if (remove_child) {
                                        console.log('removing child');
                                        removeChildFromNode(node_to_move.parent, node_to_move, false);
                                    }
                                    else {
                                        console.log('not removing child');
                                    }
                                    addChildToNode(new_parent, node_to_move, false);

                                    num_succeeded++;
                                    if (num_succeeded == selected_nodes.length) {
                                        // #todo #performance - could find the leafiest common node to update at
                                        updateTree(svg_tree.root);
                                    }
                                }
                            })(node_to_move, parent_id_field);
                        }
                        // non-array field, simple update of parent field
                        // #todo make sure non-array parent field still works right
                        else {
                            var where_clauses = {};
                            where_clauses[primary_key_field] = primary_key

                            data = {
                                action: 'update_' + table_name,
                                where_clauses: where_clauses
                            };

                            data[parent_id_field] = parent_id;

                            success_callback = (function(node_to_move, parent_id_field) {
                                return function(xhttp) {
                                    var r = xhttp.responseText;
                                    nest_mode = 'success';
                                    doNestModeAlert(nest_mode);
                                    console.log('removing child');

                                    removeChildFromNode(node_to_move.parent, node_to_move, false);
                                    addChildToNode(new_parent, node_to_move, false);

                                    num_succeeded++;
                                    if (num_succeeded == selected_nodes.length) {
                                        // #todo #performance - could find the leafiest common node to update at
                                        updateTree(svg_tree.root);
                                    }
                                }
                            })(node_to_move, parent_id_field);
                        }

                        var error_callback = function(xhttp) {
                            var r = xhttp.responseText;
                            nest_mode = 'error'
                            doNestModeAlert(nest_mode);
                        }

                        doAjax("POST", url, data, success_callback, error_callback);
                    }
                }
                else if (sub_mode == 'select_more') {
                    console.log('select_more, adding', d);
                    selected_nodes.push(d);
                    nest_mode = 'click_new_parent_or_select_more';
                    doNestModeAlert(nest_mode);
                }
                else {
                    console.log('unknown sub_mode');
                }
            }
        }
        // link to obj_editor
        else {
            var url = "<?= $obj_editor_uri ?>"
                            +"?table="+table
                            +"&edit=1"
                            +"&primary_key=" + d[id_field];
            window.open(url, '_blank');
        }
    }
}
<?php
    }
    elseif ($backend == 'fs') {
?>
function clickLabel(d) {
    console.log('clickLabel');
}
<?php
    }
    else {
        die("unknown backend '$backend'");
    }
?>

        </script>
