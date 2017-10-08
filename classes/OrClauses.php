<?php
# OrClauses
# ---------
# for fancier where clauses
# that contain OR conditions within
# the overall AND of the where_vars

# Note that as we add richer object modeling for the where clauses
# (the Predicate and OrClauses classes), this is getting a little strange:
# We have until now been using the key-value pairs as a simple/obvious
# PHP-native interface into the where clauses.  But now with these new
# objects, we are having to decide whether to store the LHS in the object
# or still use the key of the where_clauses array.  Currently we still
# use the key, for e.g. Predicates - so if you have "name LIKE '%John%'",
# the Predicate object only stores the 'LIKE' and the '%John%'; the
# "name" is still in the key of the where_vars.  But in the case of the
# OrClauses, it doesn't make sense to try to store a fieldname in the key:
# there could be more than one clause with more than one field!
# So at this point we break our assumption that the field will be in the key
# and instead add the OrClauses into the array as a sequential key.

class OrClauses {

    public $clauses;

    function __construct($clauses) {
        $this->clauses = $clauses;
        return $this;
    }

    function __toString() {
        return  '('
              . Db::build_where_clause($this->clauses, 'or', false)
              . ')';
    }

}
