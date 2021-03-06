<?php
    # tree_view.js.php
    # gets included from tree_view/index.php with certain vars already set

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

    #todo #fixme - don't always assume a catchall entity table
    #              probably will need all this stuff in pure JS
    $table2update = 'entity';
    $parent_table = 'entity';
?>
        <script>

<?php
    # javascript includes directly from PHP
    require_once("$trunk/js/ajax.js");
    include("$trunk/js/popup.js");
    TreeView::include_js__get_tree_url();
?>

var start_w_tree_fully_expanded = <?= (int)$start_w_tree_fully_expanded ?>;
var omit_root_node = <?= (int)$omit_root_node ?>;
var default_name_cutoff = <?= $name_cutoff ?>;

var table_info = <?= json_encode($table_info) ?>;

var config = {
    tree_height_factor: <?= Config::$config['tree_height_factor'] ?>,
    add_child__interpret_complex_table_as_name: <?= Config::$config['add_child__interpret_complex_table_as_name']
                                                        ? 'true'
                                                        : 'false' ?>,
    vary_node_colors: <?= (int)(isset($requestVars['vary_node_colors'])
                                    ? $requestVars['vary_node_colors']
                                    : Config::$config['vary_node_colors']) ?>,
    default_values: <?= json_encode(Config::$config['default_values']) ?>,
    tree_view_filter_popup_options: <?= json_encode(Config::$config['tree_view_filter_popup_options']) ?>,
    custom_tree_node_color_fn: <?= Config::$config['custom_tree_node_color_fn']
                                        ? Config::$config['custom_tree_node_color_fn']
                                        : 'undefined' ?>
};

// ideally PHP would end here - pure JS from here forward

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

    var left_margin = (omit_root_node
                            ? 120
                            : 250);
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
    var height_factor_config = config.tree_height_factor;
    var height = Math.max(
        num_nodes_updown * 65 * height_factor_config,
        defaults.height
    );
    var width = undefined; // 2000;

    var name_cutoff = default_name_cutoff
                        ? default_name_cutoff
                        : undefined;
    var max_node_strlen = getMaxNodeStrlen(
        root, name_cutoff, strlenVarWidth
    );

    // guess how much space the name needs
    var approx_max_node_width = max_node_strlen * 9; //14

    var level_width = Math.max(
        approx_max_node_width, defaults.level_width
    );

    setupTree(width, height, level_width);
}

<?php
    # build up a get_tree url to get the tree data as JSON
    # send all the tree vars from vars.php as GET vars
    if (isset($root_id)) {
        # avoid super-long URL, backend can figure it out themselves
        $tree_vars = $_GET; /*array(
            'root_id' => $root_id,
        );
        if (isset($requestVars['use_default_view'])) {
            $tree_vars['use_default_view'] = $requestVars['use_default_view'];
        }*/
    }
    else {
        $tree_vars = compact($tree_var_names);
    }

    if (isset($_GET['xdebug_profile'])
        && $_GET['xdebug_profile']
    ) {
        $tree_vars['XDEBUG_PROFILE'] = true;
    }

    $get_tree_query_string = http_build_query($tree_vars);
?>
function treeDataUrl() {
    // todo POST instead
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
        var node = nodes[i];
        var rect = node.getBoundingClientRect();
        var new_x = rect.left; //rect.x;
        if (min_x === undefined
            || new_x < min_x
        ) {
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

    // start very small
    var start_radius = .000001; //1e-6
    console.log('start_radius', start_radius);
    nodeEnter.append("circle")
            .attr("r", start_radius) //1e-6)
            .style("fill",  function(d) {
                                return d._children
                                    ? "lightsteelblue"
                                    : "#fff";
                            }
            )
            .on("click", toggleNodeExpansion);

    var vary_node_colors = config.vary_node_colors;
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
                    return "1em"; // "1.5em";
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
                if (config.custom_tree_node_color_fn) {
                    custom_node_color = config.custom_tree_node_color_fn(d);
                    if (custom_node_color) {
                        return custom_node_color;
                    }
                }

                return d._node_color && vary_node_colors
                    ? d._node_color
                    : null;
            })
            ;

<?php
    #todo turn more pure-JS-like
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

    var radius = 6;
    nodeUpdate.select("circle")
            .attr("r", radius)
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

</script>
<script>

var id_fields_by_table = <?= json_encode($id_fields_by_table) ?>;


// Nest Mode - UI to nest nodes under other nodes
var nest_mode = false;
var add_parent_instead_of_move = null; // shift-N to add parent, n to move parent
var leave_nest_mode_after_move = false; // when chosen by popup: yes, by keyboard: no

// selection
var selected_nodes = [];     // the d3 nodes that are selected
var selected_dom_nodes = []; // the DOM elements of the selected nodes
                             // (so we can take off the selected class etc)

var obj_editor_uri = <?= Utility::quot_str_for_js($obj_editor_uri) ?>;
var crud_api_uri = <?= Utility::quot_str_for_js($crud_api_uri) ?>;

// keyboard commands
var kb = {
    n_code: 110,
    N_code: 78,
    d_code: 100, // wanted delete but some browsers have DEL go back a page??
    l_code: 108, // copy Links
};

var url_field_links = <?= json_encode(Config::$config['url_field_links']) ?>;


function get_alert_elem() {
    return document.getElementById('alert');
}

function do_alert(msg, color) {
    var alert_elem = get_alert_elem();
    alert_elem.innerHTML = msg;
    alert_elem.style.display = 'block';
    alert_elem.style.background = color;
}

function deselectAllNodes() {
    for (var i=0; i < selected_dom_nodes.length; i++) {
        var node = selected_dom_nodes[i];
        node.classList.remove('selected');
    }
    selected_nodes = [];
    selected_dom_nodes = [];
}

function abortNestMode() {
    nest_mode = false;
    deselectAllNodes();
}

</script>
<script>

// to avoid conflicting nest mode alert timeouts
// e.g. if you click something and it changes the message
// but there was already a timeout, it may disappear too soon
// therefore, keep a seq_number so the timeout only happens
// if a later request has not been made
var nest_mode_alert_seq_number = 0;

function doNestModeAlert(mode) {
    if (nest_mode === 'click_selected_nodes') {
        var action = (add_parent_instead_of_move
                        ? 'add a new parent to'
                        : 'move');
        do_alert('Nest mode: click a node to ' + action + ', or N to stop', 'orange');
        nest_mode_alert_seq_number++;
    }
    else if (nest_mode === 'click_new_parent_or_select_more') {
        var action = (add_parent_instead_of_move
                        ? 'add'
                        : 'move');
        do_alert('\
            Click another node to ' + action + ' selected node(s) there,<br>\
            shift-click more nodes to select them them too,<br>\
            D to detach/delete, or N to cancel<br>\
            ',
            'purple'
        );
        nest_mode_alert_seq_number++;
    }
    else if (nest_mode === 'success') {
        do_alert('Success. Refresh to see changes.', 'green');
        nest_mode = (leave_nest_mode_after_move
                        ? false
                        : 'click_selected_nodes');

        nest_mode_alert_seq_number++;
        var success_alert_timeout_seq_number = nest_mode_alert_seq_number;
        setTimeout(function() {
            if (nest_mode_alert_seq_number == success_alert_timeout_seq_number) {
                doNestModeAlert(nest_mode);
            }
        }, 750);
    }
    else if (nest_mode === 'error') {
        do_alert("Something went wrong", 'red');
        abortNestMode();

        nest_mode_alert_seq_number++;
        var error_alert_timeout_seq_number = nest_mode_alert_seq_number;
        setTimeout(function() {
            if (nest_mode_alert_seq_number == error_alert_timeout_seq_number) {
                doNestModeAlert(nest_mode);
            }
        }, 1500);
    }
    else if (nest_mode === 'deleted') {
        do_alert('Node(s) detached', 'darkred');
        abortNestMode();

        nest_mode_alert_seq_number++;
        var nodes_deleted_alert_timeout_seq_number = nest_mode_alert_seq_number;
        setTimeout(function() {
            if (nest_mode_alert_seq_number == nodes_deleted_alert_timeout_seq_number) {
                get_alert_elem().style.display = 'none';
            }
        }, 1500);
    }
    else if (nest_mode === false) {
        do_alert('Nest mode disabled', 'blue');

        nest_mode_alert_seq_number++;
        var disappear_alert_timeout_seq_number = nest_mode_alert_seq_number;
        setTimeout(function() {
            if (nest_mode_alert_seq_number == disappear_alert_timeout_seq_number) {
                get_alert_elem().style.display = 'none';
            }
        }, 1500);
    }
}

</script>

<script>

function startNestMode(event) {
    nest_mode = 'click_selected_nodes';

    // #todo only allow this if parent_field is an array
    add_parent_instead_of_move = (event
                                  && event.which == kb.N_code);

    doNestModeAlert(nest_mode);

    selected_nodes = [];
    selected_dom_nodes = [];

    var from_popup = !event;
    leave_nest_mode_after_move = from_popup;
}

function toggleNestMode(event) {
    if (nest_mode) {
        abortNestMode();
        doNestModeAlert(nest_mode);
    }
    else if (!nest_mode) {
        startNestMode(event);
    }
}

function detachSelectedNodes() {
    if (selected_nodes.length > 0) {
        for (var i=0; i < selected_nodes.length; i++) {
            var node = selected_nodes[i];
            detachNodeFromParent(node, false, true);
        }
        nest_mode = 'deleted';
    }
    else {
        alert("You haven't selected any nodes to delete");
        nest_mode = 'error';
    }
    doNestModeAlert(nest_mode);
}

function copyLinksOfSelectedNodes() {
    if (selected_nodes.length > 0) {
        var link_txts = [];
        var domain = <?= Utility::quot_str_for_js((isset($_SERVER['HTTPS'])
                                                     && $_SERVER['HTTPS']
                                                        ? 'https'
                                                        : 'http') . '://' . $_SERVER['HTTP_HOST']
                                                   ) ?>;
        for (var i=0; i < selected_nodes.length; i++) {
            var node = selected_nodes[i];
            var this_link = '[' + node.name + '](' + domain + get_tree_url(node.id) + ')';
            link_txts.push(this_link);
        }
        console.log('Markdown Links of Nodes URLs:');
        console.log(link_txts.join(' -> '));
    }
    else {
        alert("You haven't selected any nodes to copy Links of");
        nest_mode = 'error';
    }
    doNestModeAlert(nest_mode);
}

document.addEventListener('keypress', function(event){
    console.log(event);

    if (event.which == kb.n_code
        || event.which == kb.N_code
    ) {
        toggleNestMode(event);
    }
    // detach selected node
    else if (event.which == kb.d_code
            && nest_mode
    ) {
        detachSelectedNodes();
    }
    // copy markdown Links of selected nodes' URLs to console
    else if (event.which == kb.l_code) {
        copyLinksOfSelectedNodes();
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
                var this_child = children[i];
                if (child === this_child) {
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

// DB + UI operation
// either removes the parent_id from the child,
// or if do_delete == true, delete it altogether
function detachNodeFromParent(node, do_delete, do_nest_mode) {
    console.log('detachNodeFromParent, node=', node);
    var parent = node.parent;
    console.log('  parent=', parent);
    
    // #todo #fixme don't assume a catchall table to update
    var primary_key_field = '<?= DbUtil::get_primary_key_field($table2update) ?>';
    var primary_key = node[primary_key_field];
    var parent_id_field = '<?= Config::$config['default_parent_field'] ?>';
    var table_name = '<?= $table2update ?>';
    var url = "<?= $crud_api_uri ?>";

    // #todo maybe #factor common code
    // for parent_id_field stuff
    var parent_field_is_array =
        <?= (int)DbUtil::field_is_array($default_parent_field) ?>;
    var parent_primary_key_field = '<?= DbUtil::get_primary_key_field($parent_table) ?>';
    var parent_id = parent[parent_primary_key_field];

    // build up the AJAX data to update the node
    var data = null;

    // actually delete the row
    if (do_delete) {
        var where_clauses = {};
        where_clauses[primary_key_field] = primary_key;
        data = {
            action: 'delete_' + table_name,
            where_clauses: where_clauses
        };
    }
    // array case: remove this one parent value from array
    else if (parent_field_is_array) {
        data = {
            action: 'remove_from_array',
            table: table_name,
            primary_key: primary_key,
            field_name: parent_id_field,
            val_to_remove: parent_id
        };
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

        data[parent_id_field] = null;
    }

    var success_callback = (function(node, parent_id_field) {
        return function(xhttp) {
            var r = xhttp.responseText;
            if (do_nest_mode) {
                nest_mode = 'success'; // #todo #fixme - 'deleted' ?
                doNestModeAlert(nest_mode);
            }
            
            console.log('removing child');
            removeChildFromNode(node.parent, node, false);

            updateTree(svg_tree.root);
        }
    })(node, parent_id_field);

    var error_callback = function(xhttp) {
        var r = xhttp.responseText;
        nest_mode = 'error'
        doNestModeAlert(nest_mode);
    }

    doAjax("POST", url, data, success_callback, error_callback);
}

function deleteNodeFromParent(node) {
    return detachNodeFromParent(node, true, false);
}

function keyIsClonable(k) {
    return (k != 'svg_node_id'
            && k != 'parent'
            && k != 'x'
            && k != 'y'
            && k != 'x0'
            && k != 'y0'
            && k != 'depth');
}

// #todo #fixme - rename to cloneD3Object?
function cloneSvgNode(obj) {
    var obj2 = {};
    for (var k in obj) {
        if (keyIsClonable(k)
            && obj.hasOwnProperty(k)
        ) {
            obj2[k] = obj[k];
        }
    }
    console.log('obj2',obj2);
    return obj2;
}

function cloneObj(obj) { // util
    var obj2 = {};
    for (var k in obj) {
        if (obj.hasOwnProperty(k)) {
            obj2[k] = obj[k];
        }
    }
    return obj2;
}

function setNodeName(node, name) {
    node._node_name = name;
    node.name = name; // #todo #fixme don't assume name_field
}

function cloneCleanNode(node) {
    var new_node = cloneSvgNode(node);
    setNodeName(new_node, null);
    delete new_node._children;
    delete new_node._node_table;
    delete new_node._node_color;

    new_node.children = [];

    var id_field = idFieldForNode(node);
    if (id_field) {
        delete new_node[id_field];
    }

    return new_node;
}

function deepCloneSvgNode(obj) {
    var obj2 = {};
    for (var k in obj) {
        if (keyIsClonable(k)
            && obj.hasOwnProperty(k)
        ) {
            if ((k == 'children'
                 || k == '_children')
                && obj[k] !== null
            ) {
                obj2[k] = deepCloneArrayOfSvgNodes(obj[k]);
            }
            else {
                obj2[k] = obj[k];
            }
        }
    }
    console.log('obj2',obj2);
    return obj2;
}

function deepCloneArrayOfSvgNodes(node_array) {
    var array2 = [];
    for (var i=0; i < node_array.length; i++) {
        var node = node_array[i];
        var new_node = deepCloneSvgNode(node);
        array2.push(new_node);
    }
    return array2;
}

// <script>
function addChildToNode(node, child, doUpdateTree,
                        cloneAllChildren // used when copying a node to a new parent
) {
    if (doUpdateTree === undefined) doUpdateTree = true;
    if (cloneAllChildren === undefined) cloneAllChildren = false;

    // #todo #fixme what if node isn't expanded? _children
    if (!node.hasOwnProperty('children')) {
        node.children = [];
    }

    // #todo #fixme - sometimes we are double-cloning, e.g. on Add Child
    //                because the caller sometimes clones before calling
    var newChild = (cloneAllChildren
                        ? deepCloneSvgNode(child)
                        : cloneSvgNode(child));
    node.children.push(newChild);

    if (doUpdateTree) {
        updateTree(node);
    }
}

// <script>
// invoked on clicking popup option
function addChildWithPrompt(node_to_add_to, ask_type) {
    var default_table = getNodeTable(node_to_add_to);
    var table = default_table;
    var name;
    if (ask_type) {
        table = prompt('Type/Table of new node:', table);
        if (config.add_child__interpret_complex_table_as_name) {
            var complex_table_name = (table.indexOf(' ') !== -1);
            if (complex_table_name) {
                name = table;
                table = default_table;
            }
        }
    }
    if (name === undefined) {
        name = prompt('Name of new node:');
    }

    if (name) {
        var url = crud_api_uri;
        if (!table) {
            table = default_table;
        }
        var id_field = idFieldForNode(node_to_add_to);

        var data = cloneObj(config.default_values);
        data['action'] = 'create_' + table; // #todo #fixme use new CRUD api: {action: 'create', table: table}
        data['name'] = name;                      // #todo #fixme use name field
        var parent_id = node_to_add_to[id_field];
        data['parent_ids'] = '{'+parent_id+'}';   // #todo generalize 'parent_ids' field
        
        success_callback = function(xhttp) {
            var response = JSON.parse(xhttp.responseText);
            if (response) {
                if (Array.isArray(response)
                    && response.length == 1
                ) {
                    var response_obj = response[0];
                    console.log('response_obj', response_obj);

                    var new_node = cloneCleanNode(node_to_add_to);
                    setNodeName(new_node, name);
                    new_node[id_field] = response_obj[id_field];
                    new_node._node_table = table;
                    new_node._node_color = 'green';

                    addChildToNode(node_to_add_to, new_node, true, false);
                }
                else {
                    alert("Error: unexpected response format");
                }
            }
            else {
                alert("Error: couldn't parse response");
            }
        };
        error_callback = function() {
            alert('Something went wrong');
        };
        doAjax("POST", url, data, success_callback, error_callback);
    }
}

function changeNodeText(svg_g_node, new_text) {
    var texts = svg_g_node.getElementsByTagName('text');
    if (texts.length != 1) {
        console.log('WARNING - assumed <g> would only have 1 <text> node, but it has', texts.length);
    }
    var text_node = texts[0];
    text_node.textContent = new_text;
}

// invoked on clicking popup option
function renameNode(node, clicked_node) {
    var name = prompt('Rename node:', node._node_name);

    if (name) {
        var url = crud_api_uri;
        var table = getConnTable(node); // #todo #fixme should this be getNodeTable()?
        var id_field = idFieldForNode(node);

        // #todo #fixme don't assume a catchall table to update
        var primary_key_field = '<?= DbUtil::get_primary_key_field($table2update) ?>';
        var primary_key = node[primary_key_field];
        var table_name = '<?= $table2update ?>';


        // build up the AJAX data to update the node
        var where_clauses = {};
        where_clauses[primary_key_field] = primary_key;
        var data = {
            action: 'update_' + table,
            where_clauses: where_clauses
        };
        data['name'] = name; // #todo #fixme use name_field
        
        success_callback = function(xhttp) {
            var response = JSON.parse(xhttp.responseText);
            if (response) {
                if (Array.isArray(response)
                    && response.length == 1
                ) {
                    var response_obj = response[0];
                    console.log('response_obj', response_obj);

                    setNodeName(node, name);
                    changeNodeText(clicked_node, name);
                }
                else {
                    alert("Error: unexpected response format");
                }
            }
            else {
                alert("Error: couldn't parse response");
            }
        };
        error_callback = function() {
            alert('Something went wrong');
        };
        doAjax("POST", url, data, success_callback, error_callback);
    }
}

</script>
<script>

function getNodeTable(d) {
    var table = ('_node_table' in d
                    ? d._node_table
                    : null);
    return table;
}

function getConnTable(d) {
    var table = getNodeTable(d);

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
    return conn_table;
}

function idFieldForNode(d) {
    var conn_table = getConnTable(d);
    if (conn_table) {
        var id_field = id_fields_by_table[conn_table];
        return id_field;
    }
}

function editNode(d, clicked_node) {
    var table = getNodeTable(d);
    var id_field = idFieldForNode(d);
    var url = obj_editor_uri
                    +"?table="+table
                    +"&edit=1"
                    +"&primary_key=" + d[id_field];
    window.open(url, '_blank');
}

function viewNodeDetails(d, clicked_node) {
    var table = getNodeTable(d);
    var id_field = idFieldForNode(d);
    var url = crud_api_uri
                    +"?action=view"
                    +"&table="+table
                    +"&"+id_field+"="+d[id_field];
    console.log('url to open =',url);
    window.open(url, '_blank');
}

function viewTreeForNode(d, clicked_node) {
    var id_field = idFieldForNode(d);
    var primary_key = d[id_field];
    return viewTree(primary_key, clicked_node);
}

function viewTree(primary_key, clicked_node) {
    var url = get_tree_url(primary_key);
    window.open(url, '_blank');
}

function selectNode(d, clicked_node) {
    //selected_nodes = [d];
    //selected_dom_nodes = [clicked_node];
    selected_nodes.push(d);
    selected_dom_nodes.push(clicked_node);
    clicked_node.classList.add('selected');

    nest_mode = 'click_new_parent_or_select_more';
    doNestModeAlert(nest_mode);
}

function getPopupOptions(d, clicked_node) {
    //var type_name = getNodeTable(d);
    var popup_options = [
        {   name: 'Add Child', callback: function() {
                                                    addChildWithPrompt(d, true);
                                                    //closePopup(); // clicked Add Child
                                                } },
        {   name: 'Rename', callback: function(){
                                        renameNode(d, clicked_node);
                                        //closePopup(); // clicked Rename
                                      } },
        {   name: 'View Details', callback: function(){
                                                viewNodeDetails(d, clicked_node);
                                                //closePopup(); // clicked View Details
                                            } },
        {   name: 'Edit Details', callback: function(){
                                                editNode(d, clicked_node);
                                                //closePopup(); // clicked Edit Details
                                            } },
        {   name: 'Copy ID',
            callback: function(event){
                {   // replace text of Copy ID with the actual ID so user can copy it
                    // and select the text
                    var popup = document.getElementById('popup');
                    var copy_id_elt = null;
                    var childNodes = popup.childNodes;
                    for (var n=0; n < childNodes.length; n++) {
                        var child = childNodes[n];
                        if (child.innerText == 'Copy ID') {
                            copy_id_elt = child;
                            break;
                        }
                    }
                    if (copy_id_elt) {
                        // swap in an <input> with the ID pasted into it - #todo use primary_key_field
                        copy_id_elt.innerHTML = 'Copied: <input id="copy_id_input" value="' + d.id + '">';

                        // select the text of the <input>
                        var copy_id_input = document.getElementById('copy_id_input');
                        copy_id_input.focus();
                        copy_id_input.select();

                        // copy to clipboard
                        document.execCommand('copy'); // #todo #fixme make optional to avoid permission lock
                    }
                    else {
                        console.log("Couldn't find copy_id_elt to replace text with id",d.id);
                    }
                }

                event.stopPropagation(); // prevent click from hitting body and triggering closePopup
                //closePopup(); // clicked Copy ID
            }
        }
    ];

    // URL field links
    for (var url_field_name in url_field_links) {
        if (url_field_links.hasOwnProperty(url_field_name)) {
            var url_field = url_field_links[url_field_name];
            if (url_field in d) {
                var url = d[url_field];
                if (url) {
                    popup_options.push(
                        {
                            name: url_field_name,
                            callback: function(){
                                //document.location = url;
                                window.open(url, '_blank'); // #todo #fixme make sure this works all the time
                                                            // e.g. does a popup blocker prevent it ever?
                            }
                        }
                    );
                }
            }
        }
    }

    // append more options
    popup_options.push.apply(popup_options, [
        {   name: 'Local Tree', callback:  function(){
                                                viewTreeForNode(d, clicked_node);
                                                //closePopup(); // clicked Local Tree
                                           } },
        {   name: 'Parent Tree', callback:  function(){
                                                var url = crud_api_uri;
                                                var primary_key = d.id; // #todo #fixme generalize
                                                var success_callback = function(xhttp){
                                                    var response = JSON.parse(xhttp.responseText);
                                                    if (response) {
                                                        var parent_key = response;
                                                        var parent_tree_url = get_tree_url(parent_key);
                                                        // #todo #fixme this seems to be considered a "popup"
                                                        // and gets blocked in some browsers
                                                        // figure out best practice
                                                        window.open(parent_tree_url, '_blank');
                                                    }
                                                    else {
                                                        alert('error: invalid response');
                                                    }
                                                };
                                                var error_callback = function(xhttp){
                                                    alert('error');
                                                };
                                                var data = {
                                                    action: 'get_parent',
                                                    primary_key: primary_key
                                                };
                                                doAjax("POST", url, data, success_callback, error_callback);
                                           } },
        {   name: 'Select/Move', callback: function(){
                                            startNestMode();
                                            selectNode(d, clicked_node);
                                            //closePopup(); // clicked Select/Move
                                           } },
        {   name: 'Detach', callback: function(){
                                        detachNodeFromParent(d, false, false);
                                        //closePopup(); // clicked Detach
                                      } },
        {   name: 'Delete', callback: function(){
                                        deleteNodeFromParent(d);
                                        //closePopup(); // clicked Delete
                                      } }
    ]);

    // filter popup_options (if you chose to remove some)
    if (config.tree_view_filter_popup_options) {
        var filtered_popup_options = [];
        for (var i = 0; i < config.tree_view_filter_popup_options.length; i++) {
            option_to_include = config.tree_view_filter_popup_options[i];
            for (var j = 0; j < popup_options.length; j++) {
                existing_option = popup_options[j];
                if (existing_option.name == option_to_include) {
                    filtered_popup_options.push(existing_option);
                }
            }
        }
        popup_options = filtered_popup_options;
    }
    return popup_options;
}

function openTreeNodePopup(d, event, clicked_node) {
    var popup_options = getPopupOptions(d, clicked_node);
    openPopup(popup_options, event, clicked_node);
}

<?php
    if ($backend == 'db') {
        #todo #fixme - don't always assume a catchall entity table
        #              probably will need all this stuff in pure JS
        $new_parent_table = 'entity';
?>
// <script>
// clicking the Label takes you to that object in db_viewer
// #todo #fixme #todo split up this huge function
function clickLabel(d) {
    console.log('event', d3.event);

    var clicked_elem = this;
    var clicked_node = clicked_elem.parentElement;

    console.log('top of editNode');
    console.log('clicked_node', clicked_node);
    var table = getNodeTable(d);
    var conn_table = getConnTable(d);

    if (table && conn_table) {
        var id_field = idFieldForNode(d);
        if (nest_mode) {
            // #todo #factor - handleNestModeClick()
            if (nest_mode == 'click_selected_nodes') {
                console.log('click_selected_nodes');
                selectNode(d, clicked_node);
            }
            else if (nest_mode == 'click_new_parent_or_select_more') {
                var sub_mode = (d3.event.shiftKey
                                    ? 'select_more'
                                    : 'click_new_parent');

                if (sub_mode == 'click_new_parent') {
                    console.log('click_new_parent');
                    // #todo #factor into a function moveNodesUnderNewParent()
                    // selected_nodes var is already populated
                    var new_parent = d;
                    var primary_key_field =
                        '<?= DbUtil::get_primary_key_field($new_parent_table) ?>';
                    var parent_id_field =
                        '<?= Config::$config['default_parent_field'] ?>';
                    var table_name = '<?= $new_parent_table ?>'; // #todo use _node_table
                    var url = "<?= $crud_api_uri ?>";

                    // #todo maybe #factor common code
                    // for parent_id_field stuff
                    var parent_field_is_array =
                        <?= (int)DbUtil::field_is_array($default_parent_field) ?>;
                    var parent_id = new_parent[id_field];

                    var error_callback = function(xhttp) {
                        var r = xhttp.responseText;
                        nest_mode = 'error'
                        doNestModeAlert(nest_mode);
                    }

                    if (parent_field_is_array) {
                        // array field:
                        // if pressed shift-N, then add addl parent: use "add_to_array" action
                        //      else move parent by removing JUST the one existing parent
                        //      and adding the new one (allow other existing parents to remain)

                        var num_succeeded = 0;
                        var remove_child = !add_parent_instead_of_move;

                        for (var i = 0; i < selected_nodes.length; i++) {
                            // #note could be adding a parent instead of moving
                            var node_to_move = selected_nodes[i];
                            console.log('loop, i', i, 'node_to_move (or add to parent)',
                                        node_to_move);

                            var primary_key = node_to_move[id_field];

                            // build up the AJAX data to update the node
                            var success_callback = null;
                            var data = null;

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
                                if (existing_parent_id) {
                                    data['val_to_replace'] = existing_parent_id;
                                }
                            }

                            // #todo could #factor success_callback back in between the 2 cases
                            success_callback = (function(node_to_move, parent_id_field) {
                                return function(xhttp) {
                                    var r = xhttp.responseText;
                                    nest_mode = 'success';
                                    doNestModeAlert(nest_mode);
                                    
                                    if (remove_child) {
                                        console.log('removing child');
                                        removeChildFromNode(node_to_move.parent,
                                                            node_to_move, false);
                                    }
                                    else {
                                        console.log('not removing child');
                                    }
                                    addChildToNode(new_parent, node_to_move, false,
                                                   add_parent_instead_of_move);

                                    num_succeeded++;
                                    if (num_succeeded == selected_nodes.length) {
                                        // #todo #performance - could find the leafiest common node to update at
                                        updateTree(svg_tree.root);
                                        deselectAllNodes();
                                    }
                                }
                            })(node_to_move, parent_id_field);

                            doAjax("POST", url, data, success_callback, error_callback);
                        }
                    }
                    else {
                        // non-array field, simple update of parent field

                        var num_succeeded = 0;

                        for (var i = 0; i < selected_nodes.length; i++) {
                            // #note could be adding a parent instead of moving
                            var node_to_move = selected_nodes[i];
                            console.log('loop, i', i, 'node_to_move (or add to parent)',
                                        node_to_move);

                            var primary_key = node_to_move[id_field];

                            // build up the AJAX data to update the node
                            var success_callback = null;
                            var data = null;

                            var where_clauses = {};
                            where_clauses[primary_key_field] = primary_key;

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

                            doAjax("POST", url, data, success_callback, error_callback);
                        }
                    }
                }
                else if (sub_mode == 'select_more') {
                    console.log('select_more, adding', d);

                    // #todo #factor
                    selected_nodes.push(d);
                    selected_dom_nodes.push(clicked_node);
                    clicked_node.classList.add('selected');

                    nest_mode = 'click_new_parent_or_select_more';
                    doNestModeAlert(nest_mode);
                }
                else {
                    console.log('unknown sub_mode');
                }
            }
        }
        else {
            openTreeNodePopup(d, d3.event, clicked_node);
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
