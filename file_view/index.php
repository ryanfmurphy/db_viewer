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
            $repo_trunk = $trunk; #todo #fixme
            chdir($repo_trunk);
            #$content = file_get_contents("$filename");

            $commit = "$base_commit~$commits_back";
            $cmd = "git show $commit:$filename";
            $content = shell_exec($cmd);

            $content = htmlentities($content);
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
    <div id="file_content">
        <pre><?= $content ?></pre>
    </div>
    <div id="nav_buttons">
        <a href="?filename=<?= $filename ?>&base_commit=HEAD&commits_back=<?= $commits_back+1 ?>" id="prev_commit_button">
            &lt;
        </a>
        <a href="?filename=<?= $filename ?>&base_commit=HEAD&commits_back=<?= $commits_back-1 ?>" id="next_commit_button">
            &gt;
        </a>
    </div>
</body>
</html>
<?php
    }
?>
