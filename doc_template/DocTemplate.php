<?php

class DocTemplate {

    public static function getByName($name) {
        $name = Db::quote($name);
        #todo better error checking for if no rows, maybe use Db::get1?
        $sql = "
            select *
            from doc_template
            where name = $name
        ";
        return Db::sql($sql, PDO::FETCH_CLASS, 'DocTemplate')[0];
    }

    public function getContentModule_Collection($section=null) {
        $docTemplateId = Db::sql_literal($this->id);
        if ($section) {
            $section = Db::sql_literal($section);
        }
        $sql = "
            select * from content_module
            where doc_template_id = $docTemplateId
              -- and is_retired = 0
              " . ($section
                    ? "and section = $section"
                    : "") . "
            order by sort_order
        ";

        # figure out class name for ContentModule
        $includeFileName = __DIR__ . "/content_modules/$this->name.php";
        if (file_exists($includeFileName)) {
            include_once($includeFileName);
            $contentModuleClass = $this->name . '_ContentModule';
            if (!class_exists($contentModuleClass)) {
                $contentModuleClass = 'ContentModule';
            }
        }
        else {
            $contentModuleClass = 'ContentModule';
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
        ob_start();
?>
    <script>

    $('.editable').click(function(event){
        var content_module_id = this.getAttribute('content_module_id');
        //var content_module_field = this.getAttribute('content_module_field');
        //var content_module_value = this.val();

        //if (content_module_field == 'txt') {
        //    concat_paragraphs();
        //}

        event.stopPropagation();
        window.open("/db_viewer/obj_editor/index.php?edit=1&table=content_module&primary_key=" + content_module_id);
    });

    function concat_paragraphs() {

    };

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
            border: solid 1px blue;
            background-color: yellow;
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

?>
