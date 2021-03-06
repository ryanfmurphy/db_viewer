<?php
    #todo #fixme just leave the dynamic variables blank
    # and let vars.php fill them in
    # we shouldn't need any PHP here

    { # init
        $trunk = dirname(__DIR__);
        $cur_view = 'sql';
        require("$trunk/includes/init.php");
    }
?>

create or replace function tags_contain_any__sql(
    tags text[]
)
returns text as $$
    declare
        tag text;
        ret text = '(';
        first_loop boolean default true;
    begin
        if tags = '{}' then
            return null;
        else
            foreach tag in array tags loop
                if first_loop then
                    first_loop := false;
                else
                    ret := ret || ' or ';
                end if;
                ret := ret || ' tags @> array[' || quote_literal(tag::text) || '] ';
            end loop;
            return ret || ')';
        end if;
    end
$$ language plpgsql;

-- assumptions:
--      using parent_ids field
--      there's an entity_view table
create or replace function tree_url(
    row_id uuid
)
returns text as $$
    begin
        return (
            select
                'http://127.0.0.1:89/db_viewer/tree_view/index.php?'
                    || 'root_table='                   || coalesce(root_table, 'entity_view')
                    || '&root_cond='
                        || coalesce(root_cond,
                                    'id = ' || quote_literal(row_id)
                                        || coalesce(
                                            ' or ('
                                                || tags_contain_any__sql(tags)
                                                || ' and parent_ids = ''{}'' '
                                                ||  coalesce(
                                                        ' and array[relname] <@ ' || quote_literal(sideline_tables),
                                                        ''
                                                    )
                                                || ')'
                                                ,
                                            ''
                                           ),
                                    ''
                                   )
                    || '&name_cutoff='                 || coalesce(name_cutoff, 75::text)
                    || '&order_by_limit='              || coalesce(order_by_limit, '')
                    || '&root_nodes_w_child_only='     || coalesce(root_nodes_w_child_only::int::text, '')
                    || '&use_stars_for_node_size='     || coalesce(use_stars_for_node_size::int::text, '')
                    || '&vary_node_colors='            || coalesce(vary_node_colors::int::text, '')
                    || '&start_w_tree_fully_expanded=' || coalesce(start_w_tree_fully_expanded::int::text, '')
                    || '&tree_height_factor='          || coalesce(tree_height_factor::text, '')
                    || '&sideline_tables='             || coalesce(sideline_tables::text, '')
                    || tree_relationship_url_addons(id)
                        as url
            from tree
            where parent_ids @> array[row_id]
        );
    end
$$ language plpgsql;

create or replace function tree_relationship_url_addons(
    tree_id uuid
)
returns text as $$
    declare
        ret text := '';
        relationship tree_relationship;
        n integer := 0;
        child_table text;
        parent_table text;
    begin
        for relationship in
            select * from tree_relationship
            where parent_ids @> array[tree_id]
                  and not is_archived
        loop
            -- a new physical relationship for each child_table/parent_table combination
            foreach parent_table in array relationship.parent_tables loop
                foreach child_table in array relationship.child_tables loop
                    ret := ret
                        || '&parent_relationships[' || n || '][child_table]='
                            || coalesce(child_table,'')
                        || '&parent_relationships[' || n || '][parent_table]='
                            || coalesce(parent_table,'')
                        || '&parent_relationships[' || n || '][parent_field]='
                            || coalesce(relationship.parent_field,'<?= $default_parent_field ?>')
                        || '&parent_relationships[' || n || '][matching_field_on_parent]='
                            || coalesce(relationship.matching_field_on_parent,'<?= $primary_key_field ?>')
                        || '&parent_relationships[' || n || '][order_by_limit]='
                            || coalesce(relationship.order_by_limit,'')
                        || '&parent_relationships[' || n || '][condition]='
                            || coalesce(relationship.condition,'')
                    ;
                    n := n + 1;
                end loop;
            end loop;
        end loop;
        return ret;
    end
$$ language plpgsql;
