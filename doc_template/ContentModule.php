<?php

class ContentModule {

    #todo #fixme - ensure that txt is always saved with no more than 2 \n's at once
    #              1 \n   => <br>
    #              2 \n's => <p>...</p>

    public function getParagraphs($vars=null) {
        $txt = (is_array($vars)
                    ? self::subMustacheVars($vars, $this->txt)
                    : $this->getTxt());
        return explode("\n\n", $txt);
    }

    #todo #fixme don't assume doc_template type 'email'
    public function render($vars, $headerTag='<h1>', $editable=false) {
        #$doc_template = DocTemplate::get1($this->doc_template_id);
        switch ($this->type) {
            case 'include_element':
                return $this->renderIncludeElement(
                    $vars, null, $editable
                );

            default:
                #return $this;
                return $this->renderTextBlock(
                    $vars, $headerTag, $editable
                );
        }
    }

    # for type = include_element
    public function renderIncludeElement($vars, $unused=null, $editable=false) {
        $element2include = $this->getTxt();
        $content = DocTplElements::$element2include($vars);
        if ($editable) {
            ob_start();
?>
        <div class="include_element editable"
             text_block_id="<?= $this->getTextBlockId() ?>"
        >
            <?= $content ?>
        </div>
<?php
            return ob_get_clean();
        }
        else {
            return $content;
        }
    }

    public static function subMustacheVars($vars, $body) {
        return preg_replace_callback(
            "/
                {{\s*
                    (\w+)
                \s*}}
            /x",
            function($match) use ($vars) {
                $varname = $match[1];
                return $vars[$varname];
            }, 
            $body
        );
    }

}

?>
