<?php
#todo will obj_editor accept "schema.table" format for table?

{ # pre-HTML logic, PHP functions, etc

    { # init: trunk, includes & requestVars, edit mode

        { # trunk, includes & requestVars
            $trunk = dirname(__DIR__);
            $cur_view = 'obj_editor';
            require("$trunk/includes/init.php");
            $requestVars = array_merge($_GET, $_POST);
        }

        { # table name & default values
            $table = isset($requestVars['table'])
                        ? $requestVars['table']
                        : null;
            # remove quotes
            $table = str_replace('"','',$table);

            # default values specified in config
            $defaultValues = isset($default_values_by_table[$table])
                                ? $default_values_by_table[$table]
                                : array();
            # pass requestVars in as default values
            $defaultValues = array_merge(
                $defaultValues,
                #todo remove weird field names from other get vars
                $requestVars
            );
        }

        { # edit mode?
            $edit = (isset($requestVars['edit'])
                    && $requestVars['edit']);

            if ($edit) {
                if (isset($_GET['primary_key'])) {

                    $primary_key_field = DbUtil::get_primary_key_field($id_mode, $table);
                    $primary_key = $_GET['primary_key'];

                    $thisRow = TableView::select_by_pk($table, $primary_key_field, $primary_key);
                    if ($thisRow) {
                        $defaultValues = array_merge( $defaultValues,
                                                      $thisRow );
                    }
                    else {
                        die("couldn't find/edit the row with $primary_key_field = $primary_key");
                    }
                }
                else {
                    die("can't edit with no primary_key");
                }
            }
        }
    }

    { # prep logic - get fields from db

        { # vars
            $schemas_in_path = DbUtil::schemas_in_path($search_path);
            #$schemas_val_list = DbUtil::val_list_str($schemas_in_path);

            { # choose background
                $background_image_url = TableView::choose_background_image(
                    $table, $backgroundImages
                );

                include("$trunk/includes/background_image_settings.php");
            }

            # minimal_fields_by_table
            $would_be_minimal_fields
                = Config::$config['would_be_minimal_fields']
                    = TableView::would_be_minimal_fields($table);

            $only_include_these_fields =
                $minimal
                    ? $would_be_minimal_fields
                    : null;
        }

        { # get fields from db

            $nonexistentTable = false;
            if ($table) { # get fields of table from db

                #DbUtil::get_table_fields(
                #    $table, $schemas_in_path
                #);

                #todo #fixme a lot of this is duplicate of DbUtil::get_table_fields
                $get_columns_sql = DbUtil::get_columns_sql(
                    $table, $schemas_in_path
                );
                if ($db_type == 'sqlite') {
                    $rawFieldsRows = Db::sql($get_columns_sql);
                    $fieldsRows = array();
                    foreach ($rawFieldsRows as $rawFieldsRow) {
                        $row['table_schema'] = 'public';
                        $row['column_name'] = $rawFieldsRow['name'];
                        $fieldsRows[] = $row;
                    }
                }
                else {
                    $fieldsRows = Db::sql($get_columns_sql);
                }

                #todo #fixme can still #factor more of this (use exising code in DbUtil)
                if (count($fieldsRows) > 0) {
                    { # group by schema
                        $fieldsRowsBySchema = array();

                        #todo #fixme Warning: Invalid argument supplied for foreach()
                        foreach ($fieldsRows as $fieldsRow) {
                            $schema = $fieldsRow['table_schema'];
                            $fieldsRowsBySchema[$schema][] = $fieldsRow;
                        }
                    }

                    { # choose 1st schema that applies
                        if ($schemas_in_path) {
                            $schema = null;
                            foreach ($schemas_in_path as $schema_in_path) {
                                if (isset($fieldsRowsBySchema[$schema_in_path])) {
                                    $schema = $schema_in_path;
                                    break;
                                }
                            }
                            if ($schema === null) {
                                die("Whoops!  Couldn't select a DB schema for table $table");
                            }
                        }
                    }

                    { # get just the column_names
                        $fields = array_map(
                            function($x) {
                                return $x['column_name'];
                            },
                            $fieldsRowsBySchema[$schema]
                        );
                    }

                    { # so we can give a warning/notice about it later
                        $multipleTablesFoundInDifferentSchemas =
                            $fieldsRowsBySchema
                            && count(array_keys($fieldsRowsBySchema)) > 1;
                    }
                }
                else { # no rows
                    $nonexistentTable = true;
                }
            }
        }
    }

    { # PHP functions: echoFormFieldHtml*, jsStringify, echoSelectTableInputHtml*, doSkipField

        function doSelectForInput($name) {
            #global $fields_to_make_selects;
            $fields_to_make_selects = Config::$config['fields_to_make_selects'];
            return in_array($name,
                $fields_to_make_selects
            );
        }

        function echoFormFieldHtml($name, $defaultValues=array()) {
            { # vars
                $custom_select_magic_value = Config::$config['custom_select_magic_value'];
                $magic_null_value = Config::$config['magic_null_value'];
                $inputTag = (in_array($name, Config::$config['fields_to_make_textarea'])
                                ? "textarea"
                                : "input");
            }
            { # html
?>
            <div class="formInput" remove="true">
                <label for="<?= $name ?>"
                       onclick="removeFormField(getFormRow(this))"
                >
                    <?= $name ?> 
                </label>
<?php
                { ob_start(); # what attrs go inside the <input>
?>
                    name="<?= $name ?>"
<?php
                    $commonInputAttrs = ob_get_clean();
                }
        
                if (doSelectForInput($name)) {
                    #todo check quoting on table/field
                    list($join_table, $join_field) = DbUtil::choose_table_and_field($name);
                    $objs = Db::sql("
    select
        name,
        $join_field as value
    from $join_table
                    ");
?>
                <div class="select_from_options">
<?php
                    { ob_start();
                        # build up the <option>s first in a buffer
                        # we wrap these in an ob_start because
                        # we want to figure out whether one of these options
                        # matches the default value (if any)
                        # if not, we will put the default value into the custom <input>
                        $found_default_in_options = false;
                        foreach ($objs as $obj) {
                            $txt = htmlentities($obj['name']);
                            $value = $obj['value'];
?>
                        <option value="<?= $value ?>"
<?php
                            if (isset($defaultValues[$name])
                                && $defaultValues[$name] == $value
                            ) {
                                $found_default_in_options = true;
?>
                                selected="selected"
<?php
                            }
?>
                        >
                            <?= $txt ?>
                        </option>
<?php
                        }
                        $non_custom_options = ob_get_clean();
                    }
?>
                    <select
<?php
                    if (isset($defaultValues[$name])
                        && !$found_default_in_options
                    ) {
                        # this special attribute is read by the JS later
                        # and populated into the custom <input>
?>
                        data-custom_value="<?= htmlentities($defaultValues[$name]) ?>"
<?php
                    }
?>
                        <?= $commonInputAttrs ?>
                        onchange="handleCustomValueInputForSelect(this)"
                    >
                        <option value="<?= $custom_select_magic_value ?>">
                            custom
                        </option>
                        <?= $non_custom_options ?>
                    </select>
                </div>
<?php
                }
                else {
                    # split off <input> and <textarea> cases
                    if ($inputTag == 'input') {
?>
                <input
                    <?= $commonInputAttrs ?>
                    autocapitalize="none"
<?php
                    if (isset($defaultValues[$name])) {
?>
                    value="<?= htmlentities($defaultValues[$name]) ?>"
<?php
                    }
?>
                />
<?php
                    }
                    elseif ($inputTag == 'textarea') {
?>
                <textarea
                    <?= $commonInputAttrs ?>
                ><?php
                        if (isset($defaultValues[$name])) {
                            echo $defaultValues[$name];
                        }
                ?></textarea>
<?php
                    }
                    else {
                        die("unknown inputTag: '$inputTag'");
                    }
                }

                if ($name == 'duration') {
?>
            <span id="durationTimer" onclick="startTimer()">⧖</span>
<?php
                }
                elseif ($name == 'done_time') {
?>
            <span id="doneTime" onclick="setDoneTime()">⌚</span>
<?php
                }
?>
        </div>
<?php
            }
        }

        function jsStringify($txt) {
            // add backslashes at end of line
            $txt = str_replace("\n", "\\n\\"."\n", $txt);
            // escape single quotes
            $txt = str_replace("'", "\\'", $txt);
            // fill-in {{vars}}
            $txt = preg_replace(
                       "/
                           {{
                               ( [A-Za-z0-9_]+ )
                           }}
                       /x",
                       "'+\\1+'",
                       $txt
                   );
            return "'$txt'";
        }

        function echoFormFieldHtml_JsFormat($name, $defaultValues=array()) {
            { ob_start();
                echoFormFieldHtml("{{".$name."}}", $defaultValues);
                $txt = ob_get_clean();
            }
            echo jsStringify($txt);
        }

        function echoSelectTableInputHtml() {
?>
                <input  id="selectTable"
                        list="tableList"
                        placeholder="select table"
                        onkeypress="selectTableOnEnter(event)"
                        autocapitalize="none"
                />
<?php
        }

        function echoSelectTableInputHtml_JsFormat() {
            { ob_start();
                echoSelectTableInputHtml();
                $txt = ob_get_clean();
            }
            echo jsStringify($txt);
        }

        function doSkipField($fieldName, $only_include_these_fields=null) {
            $fields2exclude = Config::$config['obj_editor_exclude_fields'];
            if (in_array($fieldName, $fields2exclude)) {
                return true;
            }

            if (is_array($only_include_these_fields)
                && !in_array($fieldName, $only_include_these_fields)
            ) {
                return true;
            }

            return false;
        }
    }

}

{ # CSS, JS, HTML
?>

<!DOCTYPE html>
<html>
    <head>
<?php
    $page_title = $table
                    ? "{".$table."}"
                    : "{DB Viewer}";
?>
        <title><?= $page_title ?></title>

        <style type="text/css">
            <?php include("$trunk/obj_editor/style.css.php") ?>
        </style>

        <script>
            <?php include("$trunk/obj_editor/main.js.php") ?>
        </script>

        <!--<meta name="viewport" content="width=device-width, initial-scale=1">-->
        <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"
              name="viewport"
        />
    </head>
    <body>
<?php
    { # HTML for header stuff: table header/input etc
?>
        <datalist id="tableList">
<?php
        foreach (DbUtil::get_tables_array() as $table_name) {
?>
            <option value="<?= $table_name ?>">
<?php
        }
?>
        </datalist>

        <p id="whoami">
            <a id="choose_table_link" href="<?= $obj_editor_uri ?>">
                choose table
            </a>
        </p>
        <div id="table_header">
<?php
        if ($nonexistentTable) {
?>
            <p id="nonexistent_table">
                Table <code><?= $table ?></code> doesn't exist
            </p>
<?php
            $table = null;
        }
?>
            <div id="table_header_top">
                <h1>
<?php
        if ($table) {
            #todo #fixme make this not get small when editing it in mobile_travel_mode
            #todo #fixme make this not lose its id when editing in mobile_travel_mode
?>
                <code id="table_name" onclick="becomeSelectTableInput(this)">
                    <?= $table ?>
                </code>
<?php
        }
        else {
?>
                    <?= echoSelectTableInputHtml() ?>
<?php
        }
?>
                    <span id="its_a_table">table</span>
                </h1>
<?php
        if ($table) {
            $maybe_minimal = $links_minimal_by_default
                                ? '&minimal'
                                : '';
?>
                <a  id="view_all_link" class="link" target="_blank"
                    href="<?= Db::view_query_url($table, $maybe_minimal) ?>">
                    view all
                </a>
<?php
            if (!$mobile_travel_mode) {
?>
                <span   id="clear_fields_link" class="link" target="_blank"
                        onclick="clearAllFields()">
                    clear <span class="details">all fields</span>
                </span>

<?php
            }
            if ($mobile_travel_mode) {
                #todo #fixme why are these target blank?
?>
                <nobr id="prev_next_row_links">
                    <span   id="first_row_link" class="link" target="_blank"
                            onclick="goToFirstRow()">
                        &lt;&lt;
                    </span>
                    <span   id="prev_row_link" class="link" target="_blank"
                            onclick="goToPrevRow()">
                        &lt;
                    </span>
                    <span   id="next_row_link" class="link" target="_blank"
                            onclick="goToNextRow()">
                        &gt;
                    </span>
                    <span   id="last_row_link" class="link" target="_blank"
                            onclick="goToLastRow()">
                        &gt;&gt;
                    </span>
                    <span   id="clear_fields_link" class="link" target="_blank"
                            onclick="clearAllFields()">
                        clear
                    </span>
                </nobr>
<?php
            }
            if (Config::$config['include_create_child_button']) {
?>
                <span class="link" onclick="changeToCreateChildForm()">
                    create child
                </span>
<?php
            }
            #todo #fixme this trello link should be a plugin, not part of the code code
            if (in_array('trello_card_url', $fields)
                && isset($defaultValues['trello_card_url'])
                && $defaultValues['trello_card_url']
            ) {
?>
                <a target="_blank" class="link" href="<?= $defaultValues['trello_card_url'] ?>">
                    trello
                </a>
<?php
            }
        }
?>
            </div>
<?php
        if ($multipleTablesFoundInDifferentSchemas) {
?>
            <div id="multipleTablesWarning">
                FYI: tables named
                <code><?= $table ?></code>
                were found in more than one schema.

                This one is <code><?= "$schema.$table" ?></code>.
            </div>
<?php
        }
?>
        </div>
<?php
    }

    { # main HTML content
        if ($table) {

            { # the form
?>
        <form id="mainForm" target="_blank">
<?php
                { # create form fields
                    $fields = TableView::prep_fields($fields);
                    foreach ($fields as $name) {
                        if (doSkipField($name, $only_include_these_fields)) {
                            continue;
                        }
                        echoFormFieldHtml($name, $defaultValues);
                    }
?>
            <script>
                focusFirstFormField(); // doesn't seem to work on mobile :/ boohoo
            </script>
<?php
                }

                { # dynamically add a new field
?>
            <div class="formInput" id="addNewFieldDiv">
                <span id="addNewField"
                      onclick="openAddNewField()"
                >
                    +
                </span>
            </div>
<?php
                }

                { # submit buttons
?>

            <div id="submits">
<?php
                    if ($mobile_travel_mode) {
?>
                <input onclick="return saveLocallyButtonClickHandler('<?= $crud_api_uri ?>', scope.table_name, event)" <?php # create ?>
                    value="Save Locally" type="submit" id="save_locally_button"
                />
                <input onclick="return saveStoredRowsClickHandler('<?= $crud_api_uri ?>', scope.table_name, event)" <?php # create ?>
                    value="Save Stored Rows to DB" type="submit" id="save_locally_button"
                />
                <!-- #todo check if there's old stored rows and show / hide this -->
                <input onclick="return recoverOldStoredRowsClickHandler('<?= $crud_api_uri ?>', scope.table_name, event)" <?php # create ?>
                    value="Recover Old Stored Rows" type="submit" id="recover_old_rows_button"
                />
                <!-- #todo check if there's stored rows / old stored rows and show / hide this -->
                <input onclick="return clearStoredRowsClickHandler('<?= $crud_api_uri ?>', scope.table_name, event)" <?php # create ?>
                    value="Clear Stored Rows" type="submit" id="clear_stored_rows_button"
                />
<?php
                    }

                    if ($edit) {
?>
                <input onclick="return updateButtonClickHandler('<?= $crud_api_uri ?>', scope.table_name, event)" <?php # update ?>
                    value="Update" type="submit" id="update_button"
                />
<?php
                    }
                    else {
?>
                <input onclick="return createButtonClickHandler('<?= $crud_api_uri ?>', scope.table_name, event)" <?php # create ?>
                    value="Create" type="submit" id="create_button"
                />
<?php
                    }
?>
                <input onclick="viewButtonClickHandler('<?= $crud_api_uri ?>', event, scope.table_name)" <?php # view ?>
                    value="View" type="submit" id="view_button"
                />
<?php
                    $disable_delete_button = Config::$config['disable_delete_button'];
                    if ($edit
                        && !$disable_delete_button
                    ) {
                        #todo #fixme make archive_instead_of_delete affect this button
?>
                <input onclick="return deleteButtonClickHandler('<?= $crud_api_uri ?>', scope.table_name, event)"
                    value="Delete" type="submit" id="delete_button"
                />
<?php
                    }
?>
            </div>
<?php
                }
?>
        </form>
<?php
            }
        }
        else {
            include("choose_table.php");
        }
    }
?>
    </body>
</html>
<?php
}
?>
