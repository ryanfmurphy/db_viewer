<?php

# DocTemplate is below, a child class of ContentContainer

$trunk = dirname(__DIR__);
$cur_view = 'doc_template';
require_once("$trunk/includes/init.php");
require_once('ContentModule.class.php');

# a ContentContainer contains ContentModules
class ContentContainer {

    public static function getIdField() {
        $id_mode = Config::$config['id_mode'];
        return DbUtil::get_primary_key_field($id_mode, 'content_container');
    }

    public function getId() {
        $field_name = self::getIdField();
        return $this->$field_name;
    }

    public static function get1($id) {
        $id = Db::quote($id);
        $id_field = self::getIdField();
        $is_archived_field = Config::$config['is_archived_field'];
        $false_value = (Config::$config['db_type'] == 'pgsql'
                            ? 'false'
                            : 0);
        $sql = "
            select *
            from content_container
            where $id_field = $id
            ".($is_archived_field
                    ? "and $is_archived_field = $false_value"
                    : '')."
        ";
        $rows = Db::sql($sql, PDO::FETCH_CLASS, get_called_class());
        #todo maybe factor into Db::get1?
        if (count($rows) > 0) {
            return $rows[0];
        }
        else {
            do_log('ContentContainer::get1() could not get obj');
            return false;
        }
    }

    public static function getByName($name) {
        $name = Db::quote($name);
        $is_archived_field = Config::$config['is_archived_field'];
        $false_value = (Config::$config['db_type'] == 'pgsql'
                            ? 'false'
                            : 0);
        $sql = "
            select *
            from content_container
            where name = $name
            ".($is_archived_field
                    ? "and $is_archived_field = $false_value"
                    : '')."
        ";
        $rows = Db::sql($sql, PDO::FETCH_CLASS, get_called_class());
        #todo maybe factor into Db::get1?
        if (count($rows) > 0) {
            return $rows[0];
        }
        else {
            do_log('ContentContainer::getByName()');
            return false;
        }
    }

    public static function contentModuleClass() {
        # can be overloaded to get your custom DocTemplate subclass
        # to use a custom ContentModule class
        return 'ContentModule';
    }

    public function getContentModule_Collection($section=null) {
        $docTemplateId = Db::sql_literal($this->getId());
        if ($section) {
            $section = Db::sql_literal($section);
        }
        $is_archived_field = Config::$config['is_archived_field'];
        $false_value = (Config::$config['db_type'] == 'pgsql'
                            ? 'false'
                            : 0);
        $sql = "
            select * from content_module
            where doc_template_id = $docTemplateId
              -- and is_retired = 0
                ".($section
                        ? "and section = $section"
                        : "") . "
                ".($is_archived_field
                        ? "and $is_archived_field = $false_value"
                        : '')."
            order by sort_order
        ";

        # figure out class name for ContentModule
        $includeFileName = __DIR__ . "/content_modules/$this->name.php";
        if (file_exists($includeFileName)) {
            include_once($includeFileName);
            $contentModuleClass = $this->name . '_ContentModule';
            if (!class_exists($contentModuleClass)) {
                $contentModuleClass = call_user_func(array(
                                        get_called_class(), 'contentModuleClass'));
            }
        }
        else {
            $contentModuleClass = call_user_func(array(
                                        get_called_class(), 'contentModuleClass'));
        }

        return Db::sql( $sql,
                        PDO::FETCH_CLASS,
                        $contentModuleClass );
    }

    public function renderContentModules(
        $vars, $editable=false, $section=null
    ) {
        $contentModules = $this->getContentModule_Collection($section);
        $firstTime = true;
        { ob_start();
            foreach ($contentModules as $contentModule) {
                # more prominent 1st header
                $headerTag = ($firstTime
                                    ? '<h1>'
                                    : '<h2>');
?>
                <?= $contentModule->render(
                        $vars, $headerTag, $editable
                    )
                ?>
<?php
                $firstTime = false;
            }
            return ob_get_clean();
        }
    }

    public static function editableJs() {
        $obj_editor_uri = Config::$config['obj_editor_uri'];
        ob_start();
?>

    <?= TableView::obj_editor_url__js($obj_editor_uri) ?>

    <script>

    $('.editable').click(function(event){
        var content_module_id = this.getAttribute('content_module_id');

        event.stopPropagation();
        
        edit_url = obj_editor_url('content_module', content_module_id);
        window.open(edit_url);
    });

    </script>
<?php
        return ob_get_clean();
    }

    public static function editableCss() {
?>
    <style>
        .editable {
            cursor: pointer;
        }
        .editable:hover {
            background-color: yellow;
        }
        .include_element.editable:hover {
            /*border: solid 1px blue;
            background-color: initial;*/
            background-color: rgba(256,256,0,.2);
        }
        .editable > textarea {
            position: absolute;
            height: 100%;
            width: 100%;
            left: 0;
            top: 0;
        }
    </style>
<?php
    }

}

# a DocTemplate is a kind of ContentContainer that is
# used to template out documents, e.g. websites
class DocTemplate extends ContentContainer {
}

?>
