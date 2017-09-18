<?php
class Wikiness {

    # sub {{objlinks}} as needed
    public static function replace_objlinks($txt, $fieldname='name', $plain_txt=false) {
        if (is_array(Config::$config['enable_objlinks_in_fields'])
            && in_array($fieldname, Config::$config['enable_objlinks_in_fields'])
        ) {
            $crud_api_uri = Config::$config['crud_api_uri']; 
            $txt = preg_replace_callback(
                '/{{\s*(?:(\w+):)?\s*([\w ]+)\s*}}/',
                function($match) use ($crud_api_uri, $plain_txt) {
                    $table = ($match[1]
                                ? $match[1]
                                : 'entity'); # start general by default - #todo #fixme use Config
                    $name = $match[2];
                    if ($plain_txt) {
                        return $name;
                    }
                    else {
                        $url = "$crud_api_uri?action=view&table=$table&name=$name";
                        { ob_start();
                            ?><a href="<?= $url ?>" target="_blank"><?= $name ?></a><?php
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
