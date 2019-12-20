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
 * Behavior for Yii 2.x ActiveRecord
 *
 * Lets record act as tag.
 */

namespace sjaakp\taggable;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Inflector;

/**
 * Class TagBehavior
 * @package sjaakp\taggable
 */
class TagBehavior extends Behavior
{
    /**
     * @var string name of the junction table. Should be set.
     */
    public $junctionTable;

    /**
     * @var string column names in the junction table
     */
    public $tagKeyColumn = 'tag_id';
    public $modelKeyColumn = 'model_id';

    /**
     * @var ActiveRecord class name
     */
    public $modelClass;

    /**
     * @var string attribute in the tag table
     */
    public $nameAttribute = 'name';

    /**
     * @var null|callable function($tag) returning tag link based on $tag
     * If null (default): return simple HTML link.
     */
    public $renderLink;

    /**
     * @var array model class where() condition, format like QueryInterface::where()
     * @link https://www.yiiframework.com/doc/api/2.0/yii-db-queryinterface#where()-detail
     */
    public $condition = [];

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (is_null($this->junctionTable))   {
            throw new InvalidConfigException('TagBehavior: property "junctionTable" is not set.');
        }
        if (is_null($this->modelClass))   {
            throw new InvalidConfigException('TagBehavior: property "modelClass" is not set.');
        }
        parent::init();
    }

    /**
     * @return \yii\db\ActiveQuery
     * @throws \yii\base\InvalidConfigException
     */
    public function getModels()
    {
        /** @var $owner ActiveRecord */
        $owner = $this->owner;
        $tagPk = $owner->primaryKey()[0];  // tag pk name

        $mc = $this->modelClass;
        $modelPk = $mc::primaryKey()[0];  // model pk name

        return $owner->hasMany($this->modelClass, [ $modelPk => $this->modelKeyColumn ])->where($this->condition)
            ->viaTable($this->junctionTable, [ $this->tagKeyColumn => $tagPk ]);
    }

    /**
     * @return int
     * @throws \yii\db\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function getModelCount()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $db = $owner->db;
        if (empty($this->condition))    {   // if no condition, just count junctions (more efficient, I guess)
            $tagPk = $owner->primaryKey;  // value

            $sql = $db->quoteSql("SELECT COUNT(*) FROM {{%{$this->junctionTable}}} WHERE [[{$this->tagKeyColumn}]] = $tagPk");
            return $db->createCommand($sql)->queryScalar();
        }
        return $this->getModels()->count('*', $db);
    }

    /**
     * @return string   tag name as link
     * @throws \ReflectionException
     */
    public function getLink()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;
        $ctrl = Inflector::camel2id((new \ReflectionClass($owner))->getShortName());

        return is_null($this->renderLink) ? Html::a($owner->getAttribute($this->nameAttribute), [ "/$ctrl/view", 'id' => $owner->primaryKey ] )
            : call_user_func($this->renderLink, $owner);
    }

    /**
     * Remove tag's model links from junction table
     * @throws \yii\db\Exception
     * @return int number of rows affected
     */
    protected function removeModels()
    {
        /* @var $owner ActiveRecord */
        $owner = $this->owner;

        return $owner->db->createCommand()->delete($this->junctionTable, [
            $this->tagKeyColumn => $owner->primaryKey   // value
        ])->execute();
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
        $this->removeModels();
    }
}
