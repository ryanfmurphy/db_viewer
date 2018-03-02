<?php
/**
 * preg_replace_callback_offset()
 *
 * preg_replace_callback() with offset capturing.
 *
 * Usage:
 *
 *   Works exactly like preg_replace_callback() but differs in passing the matches to the callback function.
 *   Matches are as if using preg_match() with the PREG_OFFSET_CAPTURE flag:
 *
 *     $match[$captureGroup] = array(0 => (string) $matchedString, 1 => (int) $subjectStringOffset)
 *
 * @author hakre <http://hakre.wordpress.com>
 * @copyright Copyright (c) 2013 by hakre
 * @revision 4
 *
 * @link http://php.net/preg_replace_callback
 * @link http://php.net/preg_match
 *
 * @param string $pattern
 * @param callable $callback
 * @param string|array $subject
 * @param int $limit (optional)
 * @param int $count (optional)
 *
 * @return array|bool|mixed|string
 */
function preg_replace_callback_offset($pattern, $callback, $subject, $limit = -1, &$count = 0) {
    if (is_array($subject)) {
        foreach ($subject as &$subSubject) {
            $subSubject = preg_replace_callback_offset($pattern, $callback, $subSubject, $limit, $subCount);
            $count += $subCount;
        }
        return $subject;
    }
    if (is_array($pattern)) {
        foreach ($pattern as $subPattern) {
            $subject = preg_replace_callback_offset($subPattern, $callback, $subject, $limit, $subCount);
            $count += $subCount;
        }
        return $subject;
    }
    $limit  = max(-1, (int)$limit);
    $count  = 0;
    $offset = 0;
    $buffer = (string)$subject;
    while ($limit === -1 || $count < $limit) {
        $result = preg_match($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE, $offset);
        if (FALSE === $result) return FALSE;
        if (!$result) break;
        $pos     = $matches[0][1];
        $len     = strlen($matches[0][0]);
        $replace = call_user_func($callback, $matches);
        $buffer = substr_replace($buffer, $replace, $pos, $len);
        $offset = $pos + strlen($replace);
        $count++;
    }
    return $buffer;
}
