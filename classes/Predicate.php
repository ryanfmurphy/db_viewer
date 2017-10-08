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
    function __construct($op, $val, $do_escape_val=true) {
        $this->op = $op;
        $this->val = $val;
        $this->do_escape_val = $do_escape_val;
        return $this;
    }
    function __toString() {
        $val = ($this->do_escape_val
                    ? Db::sql_literal($this->val)
                    : $this->val);
        return "$this->op $val";
    }
}
