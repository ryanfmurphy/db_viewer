<?php
    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");

        # vars
        require("$trunk/tree_view/vars.php");

        $name_cutoff = isset($requestVars['name_cutoff'])
                            ? $requestVars['name_cutoff']
                            : null;
    }

    if (!$root_table) {
        require("$trunk/tree_view/vars_form.php");
    }

    $root_cond = isset($requestVars['root_cond'])
                 && $requestVars['root_cond']
                    ? $requestVars['root_cond']
                    : 'parent_id is null';
    #todo #fixme do I need this header? #security
    header("Access-Control-Allow-Origin: <origin> | *");
?>
<!DOCTYPE html>
<meta charset="utf-8">
<style>

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
    stroke: #ccc;
    stroke-width: 1.5px;
}

</style>
<body>
<script src="//d3js.org/d3.v3.min.js"></script>
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

function setupTreeWithSize(root) {
    // figure out a good size
    var num_nodes = root.children.length;
    var height = Math.max(
        num_nodes * 12, defaults.height);
    var width = undefined; // 2000;
    var max_node_strlen = 0;
    var approx_max_node_width = 0;
    var name_cutoff = <?= $name_cutoff
                            ? (int)$name_cutoff
                            : 'undefined' ?>;
    for (var i=0; i<root.children.length; i++) {
        var node = root.children[i];
        var name = node.name;

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
    }
    approx_max_node_width = max_node_strlen * 5;
    var level_width = Math.max(
        approx_max_node_width, defaults.level_width);
    setupTree(width, height, level_width);
}

function createTree() {
    //setupTree();
    d3.json("get_tree.php"
                +"?root_table=<?= urlencode($root_table) ?>"
                +"&root_cond=<?= urlencode($root_cond) ?>"
                +"&order_by_limit=<?= urlencode($order_by_limit) ?>"
                +"&parent_field=<?= urlencode($parent_field) ?>"
                +"&matching_field_on_parent=<?= urlencode($matching_field_on_parent) ?>"
            ,
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

                root.children.forEach(collapse);
                updateTree(root);
            }
    );

    d3  .select(self.frameElement)
        .style("height", "800px"); // #todo #fixme hard-coded height
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
                            return d.children || d._children
                                        ? -10
                                        : 10;
                       })
            .attr("dy", ".35em")
            .attr("text-anchor", function(d) {
                                    return d.children || d._children
                                        ? "end"
                                        : "start";
                                 })
            .text(function(d) { return d.name; })
            .style("fill-opacity", 1e-6)
            .on("click", clickLabel);
            ;

    // Transition nodes to their new position.
    var nodeUpdate = node.transition()
            .duration(svg_tree.duration)
            .attr("transform",  function(d) {
                                    return "translate(" + d.y + "," + d.x + ")";
                                }
            );

    nodeUpdate.select("circle")
            .attr("r", 4.5)
            .style("fill", function(d) {
                                return d._children
                                            ? "lightsteelblue"
                                            : "#fff";
                           }
            );

    nodeUpdate.select("text")
            .style("fill-opacity", 1);

    // Transition exiting nodes to the parent's new position.
    var nodeExit = node .exit().transition()
                        .duration(svg_tree.duration)
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
    var link = svg.selectAll("path.link")
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
            });

    // Transition links to their new position.
    link.transition()
            .duration(svg_tree.duration)
            .attr("d", diagonal);

    // Transition exiting nodes to the parent's new position.
    link.exit().transition()
            .duration(svg_tree.duration)
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

// clicking the Label takes you to that object in db_viewer
function clickLabel(d) {
    // #todo use TableView::obj_editor_url
    var url = "<?= $obj_editor_uri ?>"
                    +"?table=<?= $root_table ?>"
                    +"&edit=1"
                    +"&primary_key=" + d.id;
    window.open(url, '_blank');
}

</script>

