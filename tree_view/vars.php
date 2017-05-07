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
                                        'parent_field' => $default_parent_field,
                                        'matching_field_on_parent' => '{{USE PRIMARY KEY}}',
                                    )
                                  );

