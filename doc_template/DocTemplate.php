<?php

class DocTemplate {

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

    #todo don't assume type email
    public function renderTextBlocks(
        $vars, $editable=false, $section=null
    ) {
        $textBlocks = $this->getContentModule_Collection($section);
        $firstTime = true;
        { ob_start();
            foreach ($textBlocks as $textBlock) {
                # more prominent 1st header
                $headerTag = ($firstTime
                                    ? '<h1>'
                                    : '<h2>');
?>
        <tr>
            <td <?= $textBlock->td_attrs ?>>
                <?= $textBlock->render(
                        $vars, $headerTag, $editable
                    )
                ?>
            </td>
        </tr>
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
        var text_block_id = this.getAttribute('text_block_id');
        //var text_block_field = this.getAttribute('text_block_field');
        //var text_block_value = this.val();

        //if (text_block_field == 'txt') {
        //    concat_paragraphs();
        //}

        event.stopPropagation();
        window.open("/db_viewer/obj_editor/index.php?edit=1&table=content_module&primary_key=" + text_block_id);
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
