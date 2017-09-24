CREATE TABLE tree (
    is_own_root boolean DEFAULT false NOT NULL,
    root_table text,
    root_cond text,
    sideline_tables text[],
    order_by_limit text,
    name_cutoff text,
    root_nodes_w_child_only boolean DEFAULT false NOT NULL,
    use_stars_for_node_size boolean DEFAULT false NOT NULL,
    vary_node_colors boolean DEFAULT true NOT NULL,
    tree_height_factor real DEFAULT NULL,
    start_w_tree_fully_expanded boolean DEFAULT true NOT NULL,
    sideline_tables text[] NOT NULL
)
INHERITS (entity_view);

CREATE TABLE tree_relationship (
    child_tables text[] not null
        check (array_length(child_tables,1) > 0),
    parent_tables text[] not null
        check (array_length(parent_tables,1) > 0),
    parent_field text,
    matching_field_on_parent text,
    condition text,
    order_by_limit text,
    parent_filter_field text,
    parent_filter_field_val text
)
INHERITS (entity_view);

