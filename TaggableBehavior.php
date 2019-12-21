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
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\validators\Validator;

/**
 * Class TaggableBehavior
 * @package sjaakp\taggable
 */
class TaggableBehavior extends Behavior
{
    /**
     * @var string tag names seperated by delimiter, ready for TagEditor
     */
    public $tags = '';

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
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (is_null($this->junctionTable))   {
            throw new InvalidConfigException('TaggableBehavior: property "junctionTable" is not set.');
        }
        if (is_null($this->tagClass))   {
            throw new InvalidConfigException('TaggableBehavior: property "tagClass" is not set.');
        }
        parent::init();
    }

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
        $tpk = 't.' . $tc::primaryKey()[0];  // tag pk name

        $tkn = new Expression($owner->db->quoteSql("{{j}}.[[{$this->tagKeyColumn}]]"));

        return $tc::find()->alias('t')
            ->innerJoin($this->junctionTable . ' j', [ $tpk => $tkn ])
            ->where([ "j.{$this->modelKeyColumn}" => $modelPk ])
            ->orderBy("j.{$this->orderKeyColumn}");
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasTag($name)
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;

        return ! is_null($this->getTagModels()->andWhere([ "t.{$this->nameAttribute}" => $name ])->one($owner->db));
    }

    /**
     * @param array $linkOptions
     * @return string   tag names as links, separated by separator
     */
    public function getTagLinks($linkOptions = [])
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;

        $links = array_filter(array_map(function($tag) use($linkOptions) {    // filter empty links @link https://www.php.net/manual/en/function.array-filter.php
            /* @var $tag ActiveRecord */
            return $tag->getLink($linkOptions);
        }, $this->getTagModels()->all($owner->db)));

        return implode($this->separator, $links);
    }

    /**
     * @inheritDoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @param $event \yii\base\Event
     * @throws \yii\db\Exception
     */
    public function afterFind($event)
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;

        $names = array_map(function($v) {
            /* @var $v ActiveRecord */
            return $v->getAttribute($this->nameAttribute);
        }, $this->getTagModels()->all($owner->db));

        $this->tags = implode($this->delimiter, $names);
    }

    /**
     * @param $event \yii\db\AfterSaveEvent
     * @throws \yii\db\Exception
     */
    public function afterSave($event)
    {
        $this->removeTags();    // remove old tags, if any

        if (empty($this->tags)) return;

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
        }, explode($this->delimiter, $this->tags));

        $this->insertTags($ids);
    }

    /**
     * @param $event \yii\base\Event
     * @throws \yii\db\Exception
     */
    public function beforeDelete($event)
    {
        $this->removeTags();
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
}
