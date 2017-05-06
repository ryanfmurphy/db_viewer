<?php
    $root_table = isset($requestVars['root_table'])
                    ? $requestVars['root_table']
                    : null;

    $root_cond = isset($requestVars['root_cond'])
                 && $requestVars['root_cond']
                    ? $requestVars['root_cond']
                    : 'parent_id is null';

    $order_by_limit = isset($requestVars['order_by_limit'])
                        ? $requestVars['order_by_limit']
                        : null;

    /*
    # which field on the child is the "parent field" that determines the parent relationship?
    $parent_field = isset($requestVars['parent_field'])
                    && $requestVars['parent_field']
                        ? $requestVars['parent_field']
                        : 'parent_id';

    # which field on the parent matches the child's "parent field"
    $matching_field_on_parent = isset($requestVars['matching_field_on_parent'])
                                && $requestVars['matching_field_on_parent']
                                    ? $requestVars['matching_field_on_parent']
                                    : 'id';

    $parent_relationships = array(
        array(
            'parent_field' => $parent_field,
            'matching_field_on_parent' => $matching_field_on_parent,
        )
    );
    */

    $parent_relationships = isset($requestVars['parent_relationships'])
                        ? $requestVars['parent_relationships']
                        : null;

