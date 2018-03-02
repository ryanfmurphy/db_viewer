<?php

include('lib/preg_replace_callback_offset.php');

class SimpleParser {

    public $line_comment = '--'; # set for SQL
    public $start_block_comment = '/*';
    public $end_block_comment = '*/';
    public $start_braces=['(','['];
    public $end_braces=[')',']'];
    public $str_quotes=["'",'"'];
    public $str_escape_mode = 'double'; # 'double' or 'backslash'

    function __construct($vars=[]) {
        foreach ($vars as $varname => $val) {
            $this->{$varname} = $val;
        }
    }

    public function line_comment_regex() {
        $start_line_comment = preg_quote($this->line_comment,'/');
        return $start_line_comment.'[^\n]*$';
    }

    public function block_comment_regex() {
        $start_block_comment = preg_quote($this->start_block_comment,'/');
        $end_block_comment = preg_quote($this->end_block_comment,'/');
        return "$start_block_comment.*?$end_block_comment";

    }

    public function blank_out_comments__naive($txt) {
        $line_comment_regex = $this->line_comment_regex();
        $block_comment_regex = $this->block_comment_regex();

        $regex = "/$line_comment_regex|$block_comment_regex/ms";

        return preg_replace_callback(
            $regex,
            function($match){
                return str_repeat(' ',strlen($match[0]));
            },
            $txt
        );
    }

    public function blank_out_comments($txt) {
        # swap out the str contents so the regexes won't
        # be confused by special chars in strs
        list($txt_clean, $strs_to_swap_back_in) = $this->separate($txt);
        $txt_clean = $this->blank_out_comments__naive($txt_clean);
        $txt = $this->sub_strs_into_txt($txt_clean, $strs_to_swap_back_in);
        return $txt;
    }

    /*
    public function blank_out_block_comments__naive($txt) {
        # swap out the str contents so the regexes won't
        # be confused by special chars in strs
        list($txt_clean, $strs_to_swap_back_in) = $this->separate($txt);
        $start = preg_quote($this->start_block_comment,'/');
        $end = preg_quote($this->end_block_comment,'/');
        $regex = "/$start.*$end/s";
        $txt_clean = preg_replace_callback(
            $regex,
            function($match){
                return str_repeat(' ',strlen($match[0]));
            },
            $txt_clean
        );
        $txt = $this->sub_strs_into_txt($txt_clean, $strs_to_swap_back_in);
        return $txt;
    }

    public function blank_out_block_comments($txt) {
        # swap out the str contents so the regexes won't
        # be confused by special chars in strs
        list($txt_clean, $strs_to_swap_back_in) = $this->separate($txt);
        $txt_clean = $this->blank_out_block_comments__naive($txt_clean);
        $txt = $this->sub_strs_into_txt($txt_clean, $strs_to_swap_back_in);
        return $txt;
    }
    */

    #todo
    public function blank_out_inside_braces($txt) {
        for ($n=0; $n<strlen($txt); $n++) {
            $ch = $txt[$n];
            if (in_array($ch, $this->start_braces)) {
                
            }
            $start_brace = $this->start_braces[$n];
            $end_brace = $this->end_braces[$n];
        }
    }

    # separate contents of strings from the outside
    # returns [$txt, $strs] where $txt is the cleaned txt without the strs
    # and $strs is an array of offsets => str_contents
    public function separate_strings($txt) {
        $strs = [];
        foreach ($this->str_quotes as $quote) {
            $txt = preg_replace_callback_offset(
                "/".$quote."([^".$quote."]*)".$quote."/",
                function($match) use ($quote,&$strs) {
                    $inside_txt = $match[1][0];
                    $inside_pos = $match[1][1];
                    $strs[$inside_pos] = $inside_txt;
                    return $quote . str_repeat(' ',strlen($inside_txt)) . $quote;
                },
                $txt,
                PREG_OFFSET_CAPTURE
            );
        }
        ksort($strs); # make sure offsets are in order
        return [$txt, $strs];
    }

    # opposite of separate_strings()
    # strs is offset=>txt pairs to be subbed into txt
    public function sub_strs_into_txt($txt, $strs) {
        $i = 0;
        $ret = '';
        foreach ($strs as $offset => $str) {
            $len_to_offset = $offset - $i;
            $ret .= substr($txt, $i, $len_to_offset);
            $i += $len_to_offset;

            $ret .= $str;
            $i += strlen($str);
        }

        # add any piece of str leftover at the end
        $remaining_len = strlen($txt) - $i;
        $ret .= substr($txt, $i, $remaining_len);

        return $ret;
    }

    public function top_level($txt) {
        #$txt = $this->blank_out_strings($txt);
        $txt = $this->blank_out_comments($txt);
        return $txt;
    }

    public function separate($txt) {
        $separated = $this->separate_strings($txt);
        return $separated;
    }

}
