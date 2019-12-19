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
 * Behavior for Yii 2.x ActiveRecords
 *
 * Adds tags to record.
 */

namespace sjaakp\taggable;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\validators\Validator;
use yii\helpers\Html;
use yii\helpers\Inflector;

/**
 * Class TaggableBehavior
 * @package sjaakp\taggable
 */
class TaggableBehavior extends Behavior
{
    /**
     * @var ActiveRecord class name of the tag record
     */
    public $tagClass;

    /**
     * @var string attribute in the tag table
     */
    public $nameAttribute = 'name';

    /**
     * @var string name of the junction table. Should be set.
     */
    public $junctionTable;

    /**
     * @var string column names in the junction table
     */
    public $tagKeyColumn = 'tag_id';
    public $modelKeyColumn = 'model_id';
    public $orderKeyColumn = 'ord';

    /**
     * @var string delimiter used in TagEditor
     */
    public $delimiter = ',';

    /**
     * @var string separator between tag links
     */
    public $separator = ', ';

    /**
     * @var null|callable function($tag) returning tag link based on $tag
     * If null (default): return simple HTML link.
     */
    public $renderLink;

    /**
     * @inheritdoc
     * @param $owner ActiveRecord
     * @link https://github.com/yiisoft/yii2/issues/5438
     * Actually, we should have a detach() too...
     */
    public function attach($owner)
    {
        parent::attach($owner);
        $validators = $owner->validators;
        $validator = Validator::createValidator('safe', $owner, 'tags');
        $validators[] = $validator;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTagModels()
    {
        // Cannot use hasMany()->viaTable() here because the result cannot be ordered.
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $modelPk = $owner->primaryKey;  // value

        $tc = $this->tagClass;
        $tpk = $tc::primaryKey()[0];  // tag pk name

        $tkn = new Expression($owner->db->quoteSql("{{j}}.[[{$this->tagKeyColumn}]]"));

        return $tc::find()->innerJoin($this->junctionTable . ' j', [ $tpk => $tkn ])
            ->where([ "j.{$this->modelKeyColumn}" => $modelPk ])
            ->orderBy("j.{$this->orderKeyColumn}");
    }

    /**
     * @return string   tag names separated by delimiter, ready for TagEditor
     */
    public function getTags()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;

        $names = array_map(function($v) {
            /* @var $v ActiveRecord */
            return $v->getAttribute($this->nameAttribute);
        }, $this->getTagModels()->all($owner->db));

        return implode($this->delimiter, $names);
    }

    /**
     * @param $tags string   tag names separated by delimiter, from TagEditor
     * @throws \yii\db\Exception
     */
    public function setTags($tags)
    {
        $this->removeTags();    // remove old tags, if any
        $tc = $this->tagClass;

        $ids = array_map(function($name) use ($tc) {
            $tag = $tc::findOne([ $this->nameAttribute => $name ] ); // does tag exist?

            if (is_null($tag))   {    // no, create
                /* @var $tag ActiveRecord */
                $tag = new $tc();
                $tag->setAttribute($this->nameAttribute, $name);
                $tag->save();
            }
            return $tag->primaryKey;
        }, explode($this->delimiter, $tags));

        $this->insertTags($ids);
    }

    /**
     * @return string   tag names as links, separated by separator
     * @throws \ReflectionException
     */
    public function getTagLinks()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $ctrl = Inflector::camel2id((new \ReflectionClass($this->tagClass))->getShortName());

        $links = array_map(function($tag) use($ctrl) {
            /* @var $tag ActiveRecord */
            return is_null($this->renderLink) ? Html::a($tag->getAttribute($this->nameAttribute), [ "/$ctrl/view", 'id' => $tag->primaryKey ] )
                : call_user_func($this->renderLink, $tag);
        }, $this->getTagModels()->all($owner->db));

        return implode($this->separator, $links);
    }

    /**
     * Remove owner's tag links from junction table, if any
     * @throws \yii\db\Exception
     * @return int number of rows affected
     */
    protected function removeTags()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $db = $owner->db;
        $modelPk = $owner->primaryKey;  // value

        return $db->createCommand()->delete($this->junctionTable, [
            $this->modelKeyColumn => $modelPk
        ])->execute();
    }

    /**
     * @param $tagIds
     * @throws \yii\db\Exception
     * @return int number of rows affected
     */
    protected function insertTags($tagIds)
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $db = $owner->db;
        $modelPk = $owner->primaryKey;  // value

        $rows = [];
        $ord = 0;

        foreach ($tagIds as $id)    {
            $rows[] = [
                $modelPk,
                $id,
                $ord
            ];
            $ord++;
        }

        $sql = $db->queryBuilder->batchInsert($this->junctionTable, [
            $this->modelKeyColumn,
            $this->tagKeyColumn,
            $this->orderKeyColumn
        ], $rows);

        return $db->createCommand($sql)->execute();
    }

    /**
     * @inheritDoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @param $event
     * @throws \yii\db\Exception
     */
    public function beforeDelete($event)
    {
        $this->removeTags();
    }
}
