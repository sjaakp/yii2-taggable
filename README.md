yii2-taggable
=============

#### Manage tags of ActiveRecords in PHP-framework YII 2.0 ####

This package contains five classes to handle the tagging of ActiveRecords with keywords or similar. The tags can be associated with or decoupled from a model (ActiveRecord), and can be sorted. Tags are manipulated with the excellent [jQuery tagEditor developed by Pixabay](http://goodies.pixabay.com/jquery/tag-editor/demo.html).

The four main classes of **yii2-taggable** are:

- **TagBehavior** - makes an ActiveRecord behave like a tag;
- **TaggableBehavior** - adds the handling of tags to an ActiveRecord;
- **TagEditor** - widget to manipulate tags;
- **TagSuggestAction** - feeds the autocomplete function of TagEditor with data.

There is also a class **TagEditorAsset**, which is a helper class for TagEditor. 

A demonstration of the **yii2-taggable** suit is [here](http://www.sjaakpriester.nl/software/taggable).

## Installation ##

The preferred way to install **yii2-taggable** is through [Composer](https://getcomposer.org/). Either add the following to the require section of your `composer.json` file:

`"sjaakp/yii2-taggable": "*"` 

Or run:

`$ php composer.phar require sjaakp/yii2-taggable "*"` 

You can manually install **yii2-taggable** by [downloading the source in ZIP-format](https://github.com/sjaakp/yii2-taggable/archive/master.zip).

## Setup ##

Suppose we have a class (ActiveRecord) `Article` of articles which can be tagged, and another class `Tag` to hold the tags.

Tag has at least the following attributes:

- `name`: to hold the actual tag keyword;
- `count`: to hold the number of `Article`'s associated with this tag.

#### Junction table ####

`Article` and `Tag` are linked with a junction table in a many-to-may relation. Let's call the table `article_tag`. It has the following fields:

- `model_id`: holds the primary key of an `Article`;
- `tag_id`: holds the primary key of a `Tag`;
- `ord`: holds the sorting order of a `Tag`.

#### TaggableBehavior ####

The class `Article` should be set up like this:

	<?php

	namespace app\models;

	use sjaakp\taggable\TaggableBehavior;

	class Article extends ActiveRecord    {

    	public function behaviors()
    	{
	        return [
	            'taggable' => [
	                'class' => TaggableBehavior::className(),
	                'tagClass' => Tag::className(),
	                'junctionTable' => 'article_tag',
	            ]
	        ];
	    }
		// ...
	}

#### TagBehavior ####

Class `Tag` looks something like this:

	<?php

	namespace app\models;

	use sjaakp\taggable\TagBehavior;

	class Tag extends ActiveRecord    {

    	public function behaviors()
    	{
	        return [
	            'tag' => [
	                'class' => TagBehavior::className(),
	                'junctionTable' => 'article_tag',
	            ]
	        ];
	    }

	    public function getArticles() {
	        return $this->hasMany(Article::className(), [ 'id' => 'model_id' ])
	            ->viaTable('article_tag', [ 'tag_id' => 'id' ]);
	    }

		// ...
	}

We've also defined a class method `getArticles()` to retrieve a query for the associated `Article`s. Refer to the [YII2 documentation](http://www.yiiframework.com/doc-2.0/yii-db-activequery.html#viaTable()-detail).

#### Article view ####

In the `Article` view we can now display the tags like so:

	<?php
		// ...
	/**
	 * @var yii\web\View $this
	 * @var ap\models\Article $model
	 */
	?>
	<div class="article-view">

		<!-- Display article title and body here. -->
		
		<h4>Tags</h4>
		<p><?= $model->tagLinks ?></p>
	</div>

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
	                'class' => TagSuggestAction::className(),
	                'tagClass' => Tag::className(),
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

	    <?= $form->field($model, 'editorTags')->widget(TagEditor::className(), [
	        'tagEditorOptions' => [
	            'autocomplete' => [
	                'source' => Url::toRoute(['tag/suggest'])
	            ],
	        ]
	    ]) ?>
		...

`editorTags` is also a new virtual attribute of `Article`, added to it by TaggableBehavior. `'tag/suggest'` is the base of the route to the `suggest` action in `TagController`, which we defined before. Learn more about the `tagEditorOptions` from [Pixabay](http://goodies.pixabay.com/jquery/tag-editor/demo.html).

## Modifications ##

The basic setup of **yii2-taggable** can be modified in a number of ways. Refer to the source files to see which other options are available. Some are:

- **$nameAttribute**: name attribute of the tag class. Defined in TagBehavior, TaggableBehavior, and TagSuggestAction. Default: `'name'`.
- **$countAttribute**: count attribute of the tag class. Holds the number of associated models. Defined in TaggableBehavior. Default: `'count'`.
- **$tagKeyAttribute** and **$modelKeyAttribute**: foreign key fields in the junction table. Defined in TagBehavior and TaggableBehavior. Defaults: `'tag_id'` and `'model_id'` respectively.


 