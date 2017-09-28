<?php
class Wikiness {

    # sub {{objlinks}} as needed
    public static function replace_objlinks($txt, $fieldname='name', $plain_txt=false) {
        if (is_array(Config::$config['enable_objlinks_in_fields'])
            && in_array($fieldname, Config::$config['enable_objlinks_in_fields'])
        ) {
            $crud_api_uri = Config::$config['crud_api_uri']; 

            # replace objlinks
            $txt = preg_replace_callback(
                '/{{\s*(?:(\w+):)?\s*([^}]+)\s*}}/',
                function($match) use ($crud_api_uri, $plain_txt) {
                    $table = ($match[1]
                                ? $match[1]
                                : 'entity'); # start general by default - #todo #fixme use Config
                    $name = $match[2];
                    if ($plain_txt) {
                        return $name;
                    }
                    else {
                        $url = "$crud_api_uri?action=view&table=".urlencode($table)
                                                        ."&name=".urlencode($name);
                        { ob_start();
                            ?><a href="<?= $url ?>" target="_blank"><?= $name ?></a><?php
                          return ob_get_clean();
                        }
                    }
                },
                $txt
            );

            # replace markdown-style links - #todo #fixme put this somewhere else?
            # replace objlinks
            $txt = preg_replace_callback(
                '/  \[
                        ([^]]+)
                    \] \s*
                    \(
                        ([^)]+)
                    \)
                 /x',
                function($match) use ($crud_api_uri, $plain_txt) {
                    $title = $match[1];
                    $url = $match[2];
                    if ($plain_txt) {
                        return $title;
                    }
                    else {
                        { ob_start();
                            ?><a href="<?= $url ?>" target="_blank"><?= $title ?></a><?php
                          return ob_get_clean();
                        }
                    }
                },
                $txt
            );
        }
        return $txt;
    }

}
