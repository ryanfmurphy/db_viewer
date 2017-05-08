<?php
    $root_table = isset($requestVars['root_table'])
                    ? $requestVars['root_table']
                    : null;

    $root_cond = isset($requestVars['root_cond'])
                 && $requestVars['root_cond']
                    ? $requestVars['root_cond']
                    : "$default_parent_field is null";

    $order_by_limit = isset($requestVars['order_by_limit'])
                        ? $requestVars['order_by_limit']
                        : null;

    $parent_relationships = isset($requestVars['parent_relationships'])
                                ? $requestVars['parent_relationships']
                                : array(
                                    array(
                                        'child_table' => $root_table,
                                        'parent_table' => $root_table,
                                        'parent_field' => $default_parent_field,
                                        'matching_field_on_parent' => '{{USE PRIMARY KEY}}',
                                    )
                                  );

    # make sure tables are filled out for all relationships
    # (default to root_table)
    /*
    foreach ($parent_relationships as $no => $relationship) {

        if (!isset($relationship['child_table'])
            || !$relationship['child_table']
        ) {
            $parent_relationships[$no]['child_table'] = $root_table;
        }

        if (!isset($relationship['parent_table'])
            || !$relationship['parent_table']
        ) {
            $parent_relationships[$no]['parent_table'] = $root_table;
        }

    }
    */

