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
    num_nodes_by_level = countNodesByLevel(node, 0);
    max_nodes_any_lev = 0;
    for (var i=0; i<num_nodes_by_level.length; i++) {
        num_nodes_this_lev = num_nodes_by_level[i];
        if (num_nodes_this_lev > max_nodes_any_lev) {
            max_nodes_any_lev = num_nodes_this_lev;
        }
    }
    return max_nodes_any_lev;
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
            name = node._node_name =
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
                    getMaxNodeStrlen(child, name_cutoff),
                    max_node_strlen
                );
            }
        }
    }
    return max_node_strlen;
}

function setupTreeWithSize(root) {
    var num_nodes_updown = numNodesInLargestLevel(root);
    var height = Math.max(
        num_nodes_updown * 15,
        defaults.height
    );
    var width = undefined; // 2000;

    var max_node_strlen = getMaxNodeStrlen(root, name_cutoff);

    // guess how much space the name needs
    var approx_max_node_width = max_node_strlen * 4;

    var level_width = Math.max(
        approx_max_node_width, defaults.level_width);

    setupTree(width, height, level_width);
}

