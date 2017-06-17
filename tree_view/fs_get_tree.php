<?php
    function fs_get_tree($dir_path) {
        #echo "top of fs_get_tree, dir_path=$dir_path\n";
        {   $old_cwd = getcwd();
            chdir($dir_path);
            #echo "  did chdir to '$dir_path'\n";

            {
                $fh = opendir('.');
                #echo "  opendir\n";

                $results = array();
                $n = 0;
                $dirs_to_crawl = array();
                while (true) {
                    #echo "    loop\n";
                    if ($n > 500) {
                        #echo "--- n > LIMIT (n=$n), break\n";
                        break;
                    }
                    $n++;
                    $file = readdir($fh);
                        #echo "    file = '$file'\n";
                    if (!$file) {
                        break;
                    }
                    if ($file == '.'
                        || $file == '..'
                        || $file == '.git' #todo #fixme don't assume
                    ) {
                        #echo "--- . or .., continuing\n";
                        continue;
                    }
                    if (is_dir($file)) {
                        #echo "--- adding $file to dirs, n=$n\n";
                        $dirs_to_crawl[] = $file;
                    }
                    $results[$file] = null;
                }
                closedir($fh);
            }

            #echo "\nnow looping thru dirs_to_crawl\n";
            foreach ($dirs_to_crawl as $dir) {
                #echo "  dir = $dir\n";
                $results[$dir] = fs_get_tree($dir);
            }

            chdir($old_cwd);
        }

        return $results;
    }

    # put it in 'children' as an array
    # instead of straight in key as assoc kv pairs
    function fs_prep_data_for_json($assoc_tree, $root_name=null) {
        $results = array(
            '_node_name' => ($root_name === null
                                ? ''
                                : $root_name),
            'children' => array(),
        );
        if (is_array($assoc_tree)) {
            foreach ($assoc_tree as $key => $subtree) {
                $results['children'][] = fs_prep_data_for_json(
                    $subtree, $key
                );
            }
        }
        return $results;
    }
?>
