yii2-taggable
=============

#### Manage tags of ActiveRecords in PHP-framework Yii 2.0 ####

[![Latest Stable Version](https://poser.pugx.org/sjaakp/yii2-taggable/v/stable)](https://packagist.org/packages/sjaakp/yii2-taggable)
[![Total Downloads](https://poser.pugx.org/sjaakp/yii2-taggable/downloads)](https://packagist.org/packages/sjaakp/yii2-taggable)
[![License](https://poser.pugx.org/sjaakp/yii2-taggable/license)](https://packagist.org/packages/sjaakp/yii2-taggable)

This package contains five classes to handle the tagging of ActiveRecords with keywords or similar. The tags can be associated with or decoupled from a model (ActiveRecord), and can be sorted. Tags are manipulated with the excellent [jQuery tagEditor developed by Pixabay](http://goodies.pixabay.com/jquery/tag-editor/demo.html).

The four main classes of **yii2-taggable** are:

- **TagBehavior** - makes an ActiveRecord behave like a tag;
- **TaggableBehavior** - adds the handling of tags to an ActiveRecord;
- **TagEditor** - widget to manipulate tags;
- **TagSuggestAction** - feeds the autocomplete function of TagEditor with data.

There is also a class **TagEditorAsset**, which is a helper class for TagEditor. 

A demonstration of the **yii2-taggable** suit is [here](https://sjaakpriester.nl/software/taggable).

Notice that the API for version 2 is slightly different from that of version 1.

## Installation ##

The preferred way to install **yii2-taggable** is through [Composer](https://getcomposer.org/). Either add the following to the require section of your `composer.json` file:

`"sjaakp/yii2-taggable": "*"` 

Or run:

`composer require sjaakp/yii2-taggable "*"` 

You can manually install **yii2-taggable** by [downloading the source in ZIP-format](https://github.com/sjaakp/yii2-taggable/archive/master.zip).

## Setup ##

Suppose we have a class (ActiveRecord) `Article` of articles which can be tagged, and another class `Tag` to hold the tags.

Tag has at least the following attributes:

- `id`: primary key;
- `name`: to hold the actual tag keyword;

#### Junction table ####

`Article` and `Tag` are linked with a junction table in a many-to-many relation. 
Let's call the table `article_tag`. It has the following fields:

- `model_id`: holds the primary key value of an `Article`;
- `tag_id`: holds the primary key value of a `Tag`;
- `ord`: holds the sorting order of a `Tag`.

It doesn't need to have a primary key. It's a good idea to set indexes on both `model_id`
and `tag_id`.

#### TaggableBehavior ####

The class `Article` is *taggable*, and should be set up like this:

	<?php

	namespace app\models;

	use sjaakp\taggable\TaggableBehavior;

	class Article extends ActiveRecord    {
	
    	public function behaviors()
    	{
	        return [
	            'taggable' => [
	                'class' => TaggableBehavior::class,
	                'junctionTable' => 'article_tag',
	                'tagClass' => Tag::class,
	            ]
	        ];
	    }
		// ...
	}

#### TagBehavior ####

Class `Tag` behaves as a *tag*, and looks something like this:

	<?php

	namespace app\models;

	use sjaakp\taggable\TagBehavior;

	class Tag extends ActiveRecord    {

    	public function behaviors()
    	{
	        return [
	            'tag' => [
	                'class' => TagBehavior::class,
	                'junctionTable' => 'article_tag',
	                'modelClass' => Article::class,
	            ]
	        ];
	    }

		// ...
	}

#### Article view ####

In the `Article` view we can now display the tags like so:

	<?php
		// ...
	/**
	 * @var yii\web\View $this
	 * @var ap\models\Article $model
	 */
	?>

    <!-- Display article title and body here. -->
    
    <h4>Tags</h4>
    <p><?= $model->tagLinks ?></p>

`tagLinks` is a new virtual attribute, added to `Article` by `TaggableBehavior`.

#### Article update ####

To make creating and updating `Tag`s possible, we also have to set up `TagController`:

	<?php
	
	namespace app\controllers;
	
	use yii\web\Controller;
	use app\models\Tag;
	use sjaakp\taggable\TagSuggestAction;
	
	class TagController extends Controller	{
	
	    public function actions()    {
	        return [
	            'suggest' => [
	                'class' => TagSuggestAction::class,
	                'tagClass' => Tag::class,
	            ],
	        ];
	    }
	
		// ...
	}

#### TagEditor ####

In the `Article`'s update and create views we can now use the **TagEditor** widget. Add something like this to `views\article\_form.php`:

	<?php
	
	use yii\helpers\Url;
	use sjaakp\taggable\TagEditor;
	
	/**
	 * @var yii\web\View $this
	 * @var app\models\Article $model
	 * @var yii\widgets\ActiveForm $form
	 */
	?>
		...

	    <?= $form->field($model, 'tags')->widget(TagEditor::class, [
	        'clientOptions' => [
	            'autocomplete' => [
	                'source' => Url::toRoute(['tag/suggest'])
	            ],
	        ]
	    ]) ?>
		...

`tags` is also a new virtual attribute of `Article`, added to it by TaggableBehavior. 
`'tag/suggest'` is the base of the route to the `suggest` action in `TagController`, 
which we defined before. Learn more about the `clientOptions` from [Pixabay](https://goodies.pixabay.com/jquery/tag-editor/demo.html).

## Modifications ##

The basic setup of **yii2-taggable** can be modified in a number of ways. 
Refer to the source files to see which other options are available. Some are:

- **$nameAttribute**: name attribute of the tag class. 
  Defined in TaggableBehavior, and TagSuggestAction. Default: `'name'`.
- **$tagKeyColumn** and **$modelKeyColumn**: foreign key fields in the junction table. 
  Defined in TagBehavior and TaggableBehavior. 
  Defaults: `'tag_id'` and `'model_id'` respectively.
- **$orderColumn**: holds order information in the junction table. 
  Defined in TaggableBehavior.
- **$renderLink**: callable, `function($tag)`, returning the HTML code for a single
  tag link. Defined by TaggableBehavior. If not set (default), TaggableBehaviour renders
  tag links as a simple HTML a.


 
