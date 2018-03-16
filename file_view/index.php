<?php
    { # init
        $trunk = dirname(__DIR__);
        $cur_view = 'table_view';
        require("$trunk/includes/init.php");

        $filename = isset($requestVars['filename'])
                        ? $requestVars['filename']
                        : null;
        if ($filename === null) {
            die('need a filename to view');
        }

        $base_commit = isset($requestVars['base_commit'])
                            ? $requestVars['base_commit']
                            : 'HEAD';
        $commits_back = isset($requestVars['commits_back'])
                            ? $requestVars['commits_back']
                            : 0;

        { # get content
            $repo_trunk = Config::$config['fs_tree_default_root_dir'];
            chdir($repo_trunk);
            #$content = file_get_contents("$filename");

            $commit = "$base_commit~$commits_back";
            $commit_sha = trim(shell_exec("git rev-parse $commit"));
            $couldnt_parse_commit = ($commit_sha == $commit);
            $found_commit = !$couldnt_parse_commit;
            if ($found_commit) {
                $cmd = "git --no-pager show $commit:$filename";
                $content = shell_exec($cmd);
            }
        }

        { # git log
            $git_log = '';
            /*
            { # git logs since the commit
                $cmd = "git --no-pager log $commit..HEAD -- $filename";
                echo $cmd;
                $git_log .= shell_exec($cmd);
            }

            { # the commit itself
                $cmd = "git --no-pager show -p $commit -- $filename";
                $git_log .= "\n" . shell_exec($cmd);
            }

            { # git logs before
                $cmd = "git --no-pager log $commit~1 -- $filename";
                $git_log .= "\n" . shell_exec($cmd);
            }
            */

            { # git logs before
                $cmd = "git --no-pager log --oneline $base_commit -- $filename";
                $git_log = shell_exec($cmd);
            }

            $n_commits_back = 0; #$commits_back;
            $git_log = preg_replace_callback(
                #"/^commit (?P<sha>.*)$/m",
                "/^(?P<sha>\w+)\s+(?P<description>.*)$/m",
                function($match) use ($filename, &$n_commits_back) {
                    $sha = $match['sha'];
                    $description = $match['description'];
                    { ob_start();
?>
    <p>
        <a href="?filename=<?= $filename ?>&base_commit=HEAD&commits_back=<?= $n_commits_back ?>">
            commit <?= $sha ?> - <?= $description ?>
        </a>
    </p>
<?php
                        $html = ob_get_clean();
                    }
                    $n_commits_back++;
                    return $html;
                },
                $git_log
            );
        }
    }

    { # html
?>
<!DOCTYPE html>
<html>
<?php
    $page_title = "#$filename#";
?>
<head>
        <title><?= $page_title ?></title>
    <style>
        body {
            font-family: sans-serif;
        }
        #file_content {
            background: #f8f8f8;
            padding: .5em;
        }
        #file_content,
        #nav_buttons {
            margin: .5em;
        }
        #prev_commit_button,
        #next_commit_button {
            display: inline-block;
            padding: .5em;
            background: #f0f0ff;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h2><?= $filename ?></h2>
    <div id="nav_buttons">
<?php
    if ($found_commit) {
?>
        <div id="which_commit">
            Showing Commit <?= $commit ?>, aka <?= substr($commit_sha,0,8) ?>
        </div>
<?php
    }
    else {
?>
        <div id="which_commit" class="error">
            Can't find commit <?= $commit ?>
        </div>
<?php
    }
?>
        <a href="?filename=<?= $filename ?>&base_commit=HEAD&commits_back=<?= $commits_back+1 ?>" id="prev_commit_button">
            &lt;
        </a>
        <a href="?filename=<?= $filename ?>&base_commit=HEAD&commits_back=<?= $commits_back-1 ?>" id="next_commit_button">
            &gt;
        </a>
    </div>
<?php
    if (isset($content)) {
?>
    <div id="file_content">
        <pre><?= htmlentities($content) ?></pre>
    </div>
<?php
    }
?>
    <div id="git_log">
        <?= $git_log ?>
    </div>
</body>
</html>
<?php
    }
?>
