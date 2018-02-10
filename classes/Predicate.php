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
        &$key, &$val_or_predicate
    ) {

        # automatically use @> instead of = for known array fields
        if (Config::$config['use_include_op_for_arrays']
            && DbUtil::field_is_array($key)
        ) {
            $val_or_predicate = new Predicate('@>', $val_or_predicate);
        }
        # automatically use like instead of = for known text fields
        elseif (Config::$config['use_like_op_for_text']
                && DbUtil::field_is_text($key)
        ) {
            $val_or_predicate = new Predicate(
                'LIKE',
                '%' . str_replace('%','\%',$val_or_predicate) . '%',
                true,
                Config::$config['like_op_use_lower'] ? 'lower' : null
            );
            if (Config::$config['like_op_use_lower']) {
                $key = "lower($key)";
            }
        }
        elseif (Config::$config['use_fulltext_op_for_tsvector']
                && DbUtil::field_is_tsvector($key)
        ) {
            $val_or_predicate = new Predicate('@@', "to_tsquery($val_or_predicate)");
        }

    }

}
