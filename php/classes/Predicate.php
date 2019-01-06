<?php
# Predicate
# ---------
# for fancier where clauses
# represents an operator and a value
# e.g. if one of your $where_vars key-val pairs is:
#   'name' => 'John'
# you could instead do:
#   'name' => new Predicate('LIKE', '%John%');
# to use the LIKE operator
class Predicate {

    public $op;
    public $val;
    public $do_escape_val;
    public $fn; # fn to wrap around value (optional)

    function __construct(
        $op, $val, $do_escape_val=true, $fn=null
    ) {
        $this->op = $op;
        $this->val = $val;
        $this->do_escape_val = $do_escape_val;
        $this->fn = $fn;
        return $this;
    }

    function __toString() {
        $val = ($this->do_escape_val
                    ? Db::sql_literal($this->val)
                    : $this->val);
        if ($this->fn) {
            $val = "$this->fn($val)";
        }
        return "$this->op $val";
    }

    # given a val_or_predicate,
    # optionally "loosely interpret it",
    # e.g. to use the LIKE operator or @> operator
    static function loosely_interpret_where_clause(
        # either of these may be changed
        &$key, &$val_or_predicate,
        $table = null
    ) {

        # early returns:

        # don't mess with clauses that have sequential key (self-contained Predicates)
        # e.g. if val_or_predicate is an OrClauses object it won't have an assoc key
        if (is_int($key)) {
            do_log("loosely_interpret_where_clause: no key, leaving alone\n");
            return null;
        }
        # probably mostly the same cases, but...
        # don't mess with clauses whose values are already objects
        elseif (is_object($val_or_predicate)) {
            do_log("loosely_interpret_where_clause: already object, leaving alone\n");
            return null;
        }

        # if passed above conditions, continue to modify predicate...

        # we'll fill these in, then replace the passed in vars:
        #   $new_key
        #   $new_val_or_predicate

        # use @> instead of = for known array fields
        if (Config::$config['use_include_op_for_arrays']
            && DbUtil::field_is_array($key)
        ) {
            $new_val_or_predicate = new Predicate('@>', $val_or_predicate);
        }
        # use like instead of = for known text fields
        elseif (Config::$config['use_like_op_for_text']
                && DbUtil::field_is_text($key)
        ) {
            $new_val_or_predicate = new Predicate(
                'LIKE',
                '%' . str_replace('%','\%',$val_or_predicate) . '%',
                true,
                Config::$config['like_op_use_lower'] ? 'lower' : null
            );
            if (Config::$config['like_op_use_lower']) {
                $new_key = "lower($key)";
            }
        }
        # use "@@" for known ts_vector fields
        elseif (Config::$config['use_fulltext_op_for_tsvector']
                && DbUtil::field_is_tsvector($key)
        ) {
            $new_val_or_predicate = new Predicate(
                '@@', "to_tsquery(".Db::sql_literal($val_or_predicate).")",
                false # no SQL escape, we handle it here
            );
        }

        # is this a match on the name field?
        # add aliases match possibility
        $name_field = DbUtil::get_name_field($table);
        $aliases_field = Config::$config['aliases_field'];
        if ($key == $name_field
            && $aliases_field
        ) {
            # get name value
            $name = $val_or_predicate;
            $name_quot = Db::sql_literal($name);

            # create aliases match predicate
            $aliases_pred = new Predicate(
                '@>', 'array['.$name_quot.']',
                false # no need to escape val
            );
            # wrap name clause and aliases clause in OR
            $or_clauses = new OrClauses(array(
                ($new_key ? $new_key : $key)
                    =>
                    ($new_val_or_predicate
                        ? $new_val_or_predicate
                        : $val_or_predicate),
                $aliases_field => $aliases_pred,
            ));

            $new_key = null;
            $new_val_or_predicate = $or_clauses;
        }


        # destructively set passed-in vars
        if (isset($new_key)) {
            $key = $new_key;
        }
        if (isset($new_val_or_predicate)) {
            $val_or_predicate = $new_val_or_predicate;
        }
    }

}
