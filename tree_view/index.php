<?php
    { # init: defines $db, TableView,
        # and Util (if not already present)
        $trunk = dirname(__DIR__);
        $cur_view = 'tree_view';
        require("$trunk/includes/init.php");
    }

    $root_table = isset($requestVars['root_table'])
                    ? $requestVars['root_table']
                    : null;

    if (!$root_table) {
?>
<html>
    <body>
        <form action="">
            <h2>Select one or more Root Nodes</h2>
            <div>
                <label>Root Table</label>
                <input name="root_table">
            </div>
            <div>
                <label>Root Condition</label>
                <input name="root_cond">
            </div>
            <div>
                <input type="submit">
            </div>
        </form>
    </body>
</html>
<?php
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
    height: undefined
};

function setupTree(new_width, new_height) {
    if (new_width === undefined) new_width = 960;
    if (new_height === undefined) new_height = 800;

    var margin = svg_tree.margin =
        {top: 20, right: 120, bottom: 20, left: 120};
    var width = svg_tree.width =
        new_width - margin.right - margin.left;
    var height = svg_tree.width =
        new_height - margin.top - margin.bottom;

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

function createTree() {
    setupTree();
    d3.json("get_tree.php"
                +"?root_table=<?= urlencode($root_table) ?>"
                +"&root_cond=<?= urlencode($root_cond) ?>",
            function(error, flare) {
                if (error) throw error;

                var width = undefined, height = undefined;
                //width = 2000, height = 5000;
                //setupTree(width, height);

                root = flare;
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
        .style("height", "800px");
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
        d.y = d.depth * 180;
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

