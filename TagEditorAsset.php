<?php
/**
 * MIT licence
 * Version 1.0
 * Sjaak Priester, Amsterdam 13-05-2015.
 *
 * @link http://goodies.pixabay.com/jquery/tag-editor/demo.html
 *
 */

namespace sjaakp\taggable;

use yii\web\AssetBundle;

class TagEditorAsset extends AssetBundle {
    public $sourcePath = '@bower/jquery-tag-editor';
    public $css = [
        'jquery.tag-editor.css'
    ];
    public $js = [
        'jquery.caret.min.js'
    ];
    public $depends = [
        'yii\jui\JuiAsset',
    ];
    public $publishOptions = [
        'except' => [ '*.html', '*.md', '*.json' ]
    ];

    public function init()    {
        parent::init();

        $this->js[] = YII_DEBUG ? 'jquery.tag-editor.js' : 'jquery.tag-editor.min.js';
    }
}