<?php
/**
 * sjaakp/yii2-taggable
 * ----------
 * Manage tags of ActiveRecords in PHP-framework Yii 2.x
 * Version 2.0
 * Copyright (c) 2019
 * Sjaak Priester, Amsterdam
 * MIT License
 * https://github.com/sjaakp/yii2-taggable
 * https://sjaakpriester.nl
 *
 * InputWidget for Yii 2.x
 *
 * Wrapper for jQuery tagEditor by PixaBay.
 * @link https://goodies.pixabay.com/jquery/tag-editor/demo.html
 */

namespace sjaakp\taggable;

use yii\web\AssetBundle;

/**
 * Class TagEditorAsset
 * @package sjaakp\taggable
 */
class TagEditorAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . 'assets';
    public $css = [
        'jquery.tag-editor.css'
    ];
    public $js = [
        'jquery.caret.min.js',
        'jquery.tag-editor.min.js'
    ];
    public $depends = [
        'yii\jui\JuiAsset',
    ];
}
