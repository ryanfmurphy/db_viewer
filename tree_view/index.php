<?php
    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
        require("$trunk/tree_view/vars.php");
        require("$trunk/tree_view/hash_color.php");
    }

    if (!$root_table || $edit_vars) {
        require("$trunk/tree_view/vars_form.php");
        die();
    }

    #todo #fixme do I need this header? #security
    header("Access-Control-Allow-Origin: <origin> | *");
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <style>

            body {
                font-family: sans-serif;
            }
            h1 {
                color: gray;
                font-weight: normal;
                text-align: center;
            }
            h1 span {
                font-weight: bold;
            }

            .node {
                cursor: pointer;
            }

            .node circle {
                fill: #fff;
                stroke: steelblue;
                stroke-width: 1.5px;
            }

            .node text {
                font: 10px sans-serif;
            }

            .link {
                fill: none;
                stroke: #ddd;
                stroke-width: 1.5px;
            }

            #edit_vars_link {
                text-decoration: none;
            }
            #edit_vars_link:hover {
                text-decoration: underline;
            }

            #summary {
                margin-left: .15em;
            }

            </style>
        </head>

        <body>
<?php
    { # Edit Tree Variables link
        $vars_for_edit_link = $requestVars;
        $vars_for_edit_link['edit_vars'] = 1;
?>
            <a id="edit_vars_link"
               href="<?= "?".http_build_query($vars_for_edit_link) ?>"
               target="_blank">
                Edit Tree Variables / Relationships
            </a>
<?php
    }

    function table_name_w_color($table) {
        { ob_start();
            $color = name_to_rgb($table);
?>
            <span style="color: <?= $color ?>">
                <?= $table ?>
            </span>
<?php
            $html = ob_get_clean();
        }
        return $html;
    }

    # come up with a short textual headline describing the view
    function tree_view_summary_txt($root_table, $parent_relationships) {
        $txt = table_name_w_color($root_table) . " → ";
        $tables = array();
        # use keys for uniqueness
        foreach ($parent_relationships as $relationship) {
            $table = $relationship['child_table'];
            $tables[
                table_name_w_color($table)
            ] = 1;
        }
        $txt .= implode(', ', array_keys($tables));
        return $txt;
    }

?>
            <h1>
                🌳 Tree View:
                <span id="summary">
                    <?= tree_view_summary_txt($root_table, $parent_relationships) ?>
                </span>
            </h1>
<?php
    if ($load_d3_via_cdn) {
?>
        <script src="//d3js.org/d3.v3.min.js"></script>
<?php
    }
    else {
?>
        <script src="<?= $d3_js_uri ?>"></script>
<?php
    }
?>
        <script>

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

    var margin = svg_tree.margin =
        {top: 20, right: 120, bottom: 20, left: 120};
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

function countTreeNodes(node) {
    var num_nodes = 1;
    var keys = ['children','_children'];
    for (var k=0; k < keys.length; k++) {
        var key = keys[k];
        if (key in node) {
            var children = node[key];
            for (var i=0; i < children.length; i++) {
                var child = children[i];
                num_nodes += countTreeNodes(child);
            }
        }
    }
    return num_nodes;
}

function getMaxNodeStrlen(node, name_cutoff) {
    var max_node_strlen = 0;

    // check this node's name directly
    var name = node._node_name;
    if (typeof name  === 'string') {
        // apply name cutoff if any
        if (name_cutoff
            && name.length > name_cutoff
        ) {
            name = node.name =
                name.slice(0,name_cutoff) + '...';
        }

        if (name.length > max_node_strlen) {
            max_node_strlen = name.length;
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
                    getMaxNodeStrlen(child),
                    max_node_strlen
                );
            }
        }
    }
    return max_node_strlen;
}

function setupTreeWithSize(root) {
    // figure out a good size
    //var num_nodes = root.children.length;
    var num_nodes = countTreeNodes(root);
    var height = Math.max(
        (num_nodes ** .75) * 12,
        defaults.height
    ) * 10;
    var width = undefined; // 2000;

    var name_cutoff = <?= $name_cutoff
                            ? (int)$name_cutoff
                            : 'undefined' ?>;
    var max_node_strlen = getMaxNodeStrlen(root, name_cutoff);

    // guess how much space the name needs
    var approx_max_node_width = max_node_strlen * 4;

    var level_width = Math.max(
        approx_max_node_width, defaults.level_width);

    setupTree(width, height, level_width);
}

<?php
    #todo #fixme find a better way to build this query string
    #            I have a pretty good pure-JS form -> query string fn
    #            but it doesn't handle arrays yet
?>
function treeDataUrl() {
    return "<?= $get_tree_uri ?>"
                +"?root_table=<?= urlencode($root_table) ?>"
                +"&root_cond=<?= urlencode($root_cond) ?>"
                +"&order_by_limit=<?= urlencode($order_by_limit) ?>"
                +"&root_nodes_w_child_only=<?= urlencode($root_nodes_w_child_only) ?>"
<?php
    foreach ($parent_relationships as $i => $parent_relationship) {
        $parent_field = urlencode($parent_relationship['parent_field']);
        $matching_field_on_parent = urlencode($parent_relationship['matching_field_on_parent']);
        $child_table = urlencode($parent_relationship['child_table']);
        $parent_table = urlencode($parent_relationship['parent_table']);
?>
                +"&parent_relationships[<?= $i ?>][parent_field]=<?= $parent_field ?>"
                +"&parent_relationships[<?= $i ?>][matching_field_on_parent]=<?= $matching_field_on_parent ?>"
                +"&parent_relationships[<?= $i ?>][child_table]=<?= $child_table ?>"
                +"&parent_relationships[<?= $i ?>][parent_table]=<?= $parent_table ?>"
<?php
    }
?>
    ;        
}

function createTree() {
    //setupTree();
    d3.json(
        treeDataUrl(),
        function(error, flare) {
            if (error) throw error;

            // #todo should this set the root on the svg_tree?
            root = flare;

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

<?php
    if (!$start_w_tree_fully_expanded) {
?>
            root.children.forEach(collapse);
<?php
    }
?>
            updateTree(root);

            ghostRootNode();
        }
    );

    d3  .select(self.frameElement)
        .style("height", "800px"); // #todo #fixme hard-coded height
}

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
        var new_x = rect.x;
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
                        //.on("click", clickLabel);

    nodeEnter.append("circle")
            .attr("r", 1e-6)
            .style("fill",  function(d) {
                                return d._children
                                    ? "lightsteelblue"
                                    : "#fff";
                            }
            )
            .on("click", clickNode);

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
            .style("stroke", function(d) {
                                return d._node_color
                                        ? d._node_color
                                        : 'black';
                             })
            ;

    // Transition nodes to their new position.
    var nodeUpdate = node//.transition()
            //.duration(svg_tree.duration)
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
    var nodeExit = node .exit()//.transition()
                        //.duration(svg_tree.duration)
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
    link//.transition()
            //.duration(svg_tree.duration)
            .attr("d", diagonal);

    // Transition exiting nodes to the parent's new position.
    link.exit()//.transition()
            //.duration(svg_tree.duration)
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

// toggle children on click.
function clickNode(d) {
    if (d.children) {
        d._children = d.children;
        d.children = null;
    } else {
        d.children = d._children;
        d._children = null;
    }
    updateTree(d);
}

<?php
    { # figure out primary key fields for all needed tables
      # and provide array to JS
        $id_mode = Config::$config['id_mode'];

        $id_fields_by_table = array();
        $id_fields_by_table[$root_table] =
            DbUtil::get_primary_key_field($id_mode, $root_table);

        foreach ($parent_relationships as $relationship) {
            $child_table = $relationship['child_table'];
            $parent_table = $relationship['parent_table'];
            if (!isset($id_fields_by_table[$child_table])) {
                $id_fields_by_table[$child_table] =
                    DbUtil::get_primary_key_field($id_mode, $child_table);
            }
            if (!isset($id_fields_by_table[$parent_table])) {
                $id_fields_by_table[$parent_table] =
                    DbUtil::get_primary_key_field($id_mode, $parent_table);
            }
        }
    }
?>

id_fields_by_table = <?= json_encode($id_fields_by_table) ?>;

// clicking the Label takes you to that object in db_viewer
function clickLabel(d) {

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
        var url = "<?= $obj_editor_uri ?>"
                        +"?table="+table
                        +"&edit=1"
                        +"&primary_key=" + d[id_field];
        window.open(url, '_blank');
    }
}

        </script>
    </body>
</html>

