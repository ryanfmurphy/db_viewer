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
        #return $start_line_comment.'(?P<line_comment>[^\n]*)$';
        return $start_line_comment.'[^\n]*$';
    }

    public function block_comment_regex() {
        $start_block_comment = preg_quote($this->start_block_comment,'/');
        $end_block_comment = preg_quote($this->end_block_comment,'/');
        #return "$start_block_comment(?P<block_comment>.*?)$end_block_comment";
        return "$start_block_comment.*?$end_block_comment";
    }

    public function parse($txt,
        $blank_out_level=1, $blank_out_strings=false, $blank_out_comments=true,
        $include_subs=false
    ) {
        $line_comment_regex = $this->line_comment_regex();
        $block_comment_regex = $this->block_comment_regex();

        $regex = "$line_comment_regex|$block_comment_regex";
        foreach ($this->string_regexes() as $string_regex) {
            $regex .= "|$string_regex";
        }
        $brace_chars = array_merge($this->start_braces, $this->end_braces);
        $regex .= "|[".preg_quote(implode('',$brace_chars))."]";
        $regex = "/$regex/ms";
        #echo "regex = $regex\n";

        $nest_level = 0;
        $level_offsets = []; # keyed by level [start_idx,end_idx] pairs

        if ($include_subs) { # include actual CONTENT of subs, not just start_idx,end_idx pairs
            $subs = [];
        }

        $result = preg_replace_callback_offset(
            $regex,

            function($match)
            use (&$nest_level, &$level_offsets, $blank_out_level,
                 $blank_out_strings, $blank_out_comments, $include_subs, &$subs)
            {
                $match_txt = $match[0][0];
                $match_offset = $match[0][1];

                # braces - record the level_offsets about the start:stop nested levels
                # start brace offset is recorded in position [0] of that nest_level
                if (in_array($match_txt, $this->start_braces)) {
                    $nest_level++;
                    $level_offsets[$nest_level][0] = $match_offset;
                    return $match_txt;
                }
                # start brace offset is recorded in position [1] of that nest_level
                elseif (in_array($match_txt, $this->end_braces)) {
                    $level_offsets[$nest_level][1] = $match_offset;
                    $nest_level--;
                    return $match_txt;
                }

                # deal with strings
                foreach ($match as $key => $match_details) {
                    $match_txt_inner = $match_details[0];
                    $match_offset_inner = $match_details[1];
                    $no_match = ($match_offset_inner == -1);

                    if ($key === 0 || $no_match) continue;

                    # if this is a string match: "abc" 'abc'
                    if (strpos($key, 'str_content') === 0) {
                        if ($blank_out_strings
                            # don't blank out strings whose level is already being blanked out
                            && $nest_level < $blank_out_level
                        ) {
                            $quote_char = $match_txt[0];
                            if ($include_subs) { # save the text we're blanking out
                                                 # so we can sub it in again later
                                $subs[$match_offset_inner] = $match_txt_inner;
                            }
                            $blankness = str_repeat(' ',strlen($match_txt_inner));
                            return $quote_char . $blankness . $quote_char;
                        }
                        else {
                            return $match_txt; # if there's any match,just pass-thru
                        }
                    }
                }

                if (strpos($match_txt, $this->start_block_comment) === 0) {
                    #todo factor this common code between block and line comments
                    if ($blank_out_comments
                        # don't blank out comments whose level is already being blanked out
                        && $nest_level < $blank_out_level
                    ) {
                        if ($include_subs) { # save the text we're blanking out
                                             # so we can sub it in again later
                            $subs[$match_offset] = $match_txt;
                        }
                        return str_repeat(' ',strlen($match_txt)); # blankness
                    }
                    else {
                        return $match_txt; # if there's any match,just pass-thru
                    }
                }
                if (strpos($match_txt, $this->line_comment) === 0) {
                    if ($blank_out_comments
                        # don't blank out comments whose level is already being blanked out
                        && $nest_level < $blank_out_level
                    ) {
                        if ($include_subs) { # save the text we're blanking out
                                             # so we can sub it in again later
                            $subs[$match_offset] = $match_txt;
                        }
                        return str_repeat(' ',strlen($match_txt)); # blankness
                    }
                    else {
                        return $match_txt; # if there's any match,just pass-thru
                    }
                }

                #todo #fixme probably don't need this
                #            used to be how we blanked out comments
                #            but what should we return now if nothing else matches?
                return str_repeat(' ',strlen($match_txt));
            },

            $txt
        );

        if ($blank_out_level
            && isset($level_offsets[$blank_out_level])
        ) {
            $level_span = $level_offsets[$blank_out_level];
            #echo "level_span = ".print_r($level_span)."\n";
            $level_start = $level_span[0]+1; # leave open brace
            $level_len = $level_span[1] - $level_start;
            if ($include_subs) {
                $subs[$level_start] = substr($result, $level_start, $level_len);
            }
            $result = substr_replace(
                $result,
                str_repeat(' ', $level_len),
                $level_start, $level_len
            );
        }

        if ($include_subs) {
            ksort($subs); # make sure offsets are in order
            return [$result, $subs];
        }
        else {
            return $result;
        }
    }

    public function preg_match_parse(
        $pattern, $txt, &$matches,
        $blank_out_level=1, $blank_out_strings=false, $blank_out_comments=true
    ) {
        list($new_txt,$subs) = $this->parse($txt,
            $blank_out_level, $blank_out_strings, $blank_out_comments,
            true
        );

        return preg_match($pattern, $new_txt, /*&*/$matches);
    }

    public function preg_replace_callback_parse(
        $pattern, $fn, $txt,
        $blank_out_level=1, $blank_out_strings=false, $blank_out_comments=true
    ) {
        list($new_txt,$subs) = $this->parse($txt,
            $blank_out_level, $blank_out_strings, $blank_out_comments,
            true
        );

        $new_txt_replaced = preg_replace_callback_offset(
            $pattern,
            function($match) use ($fn, &$subs) {
                $match_txt = $match[0][0];
                $match_offset = $match[0][1];
                $match_len = strlen($match_txt);
                $match_offset_end = $match_offset + $match_len;

                $match_wo_offsets = array_map(
                    function($elt) {
                        return $elt[0]; # the txt but not the offset
                    },
                    $match
                );

                $replacement_txt = $fn($match_wo_offsets);
                # how much did the length change due to the replacement?
                $delta_len = strlen($replacement_txt) - $match_len;

                # adjust any subs that happen after the end of this match
                foreach ($subs as $sub_offset => $txt) {
                    if ($sub_offset >= $match_offset_end) { # sub is after match, thus affected
                        # move the sub based on the delta_len
                        unset($subs[$sub_offset]);
                        $new_offset = $sub_offset + $delta_len;
                        $subs[$new_offset] = $txt;
                    }
                    else {
                        $sub_offset_end = $sub_offset + strlen($txt);
                        #todo #fixme figure out what the ramifications of this are
                        assert($sub_offset_end <= $match_offset, 'sub must not overlap with replaced txt');
                    }
                }
                ksort($subs); # make sure offsets are in order

                return $replacement_txt;
            },
            $new_txt
        );
        return $this->put_subs_back_in_txt($new_txt_replaced, $subs);
    }

    private function string_regexes() {
        $regexes = [];
        $num = 0;
        foreach ($this->str_quotes as $quote) {
            if ($this->str_escape_mode == 'backslash') { # most common way to escape quotes
                $literal_backslash = '\\\\';
                $continue_str = "[^".$literal_backslash.$quote."]*";
                $escaped_quote = $literal_backslash.$quote;
            }
            elseif ($this->str_escape_mode == 'double') { # for SQL syntax
                $continue_str = "[^".$quote."]*";
                $escaped_quote = "$quote$quote";
            }
            elseif ($this->str_escape_mode == 'both') {
                # allow both 'double' and 'backslash' escape modes
                # to coexist, i.e. how MySQL does it
                $literal_backslash = '\\\\';
                $continue_str = "[^".$literal_backslash.$quote."]*";
                $escaped_quote = "(?:$literal_backslash$quote|$quote$quote)";
            }

            $escape_stuff = $this->str_escape_mode
                                ? "(?:$escaped_quote$continue_str)*"
                                : '';

            # build up overall regex for a 'string' / "string" / etc, with any escape logic
            $regexes[] = $quote."(?P<str_content$num>$continue_str$escape_stuff)".$quote;
            $num++;
        }
        return $regexes;
    }

    # undo any blanking out that parse() did
    # this requires that you use parse() with $include_subs=true
    # subs is offset=>txt pairs to be subbed into txt
    public function put_subs_back_in_txt($txt, $subs) {
        $i = 0;
        $ret = '';
        foreach ($subs as $offset => $str) {
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

}
