<?php

// namespace Faker\ORM\CakePHP;

App::uses('ColumnTypeGuesser', 'CakeFaker.Lib/Faker/ORM/CakePHP');

use \Faker\Provider\Base;

/**
 * Service class for populating a table through a CakePHP Model class.
 */
class EntityPopulator
{
    protected $class;
    protected $columnFormatters = array();
    protected $modifiers = array();

    /**
     * Class constructor.
     *
     * @param string $class A CakePHP Model classname
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function setColumnFormatters($columnFormatters)
    {
        $this->columnFormatters = $columnFormatters;
    }

    public function getColumnFormatters()
    {
        return $this->columnFormatters;
    }

    public function mergeColumnFormattersWith($columnFormatters)
    {
        $this->columnFormatters = array_merge($this->columnFormatters, $columnFormatters);
    }

    public function guessColumnFormatters(\Faker\Generator $generator)
    {
        $formatters = array();
        $class = $this->class;
        $cake_model = \ClassRegistry::init($class);
        // var_dump($cake_model->schema());
        $nameGuesser = new \Faker\Guesser\Name($generator);
        $columnTypeGuesser = new ColumnTypeGuesser($generator);
        foreach ($cake_model->schema() as $fieldName => $fieldMeta) {
            if ($cake_model->isForeignKey($fieldName)) {
                $relatedClass = $this->findRelatedClass($cake_model->belongsTo, $fieldName);
                $formatters[$fieldName] = function($inserted) use ($relatedClass) { return isset($inserted[$relatedClass]) ? $inserted[$relatedClass][mt_rand(0, count($inserted[$relatedClass]) - 1)] : null; };
                continue;
            }

            if ($fieldName == $cake_model->primaryKey)
                continue;

            if ($formatter = $nameGuesser->guessFormat($fieldName)) {
                $formatters[$fieldName] = $formatter;
                continue;
            }
            if ($formatter = $columnTypeGuesser->guessFormat($fieldMeta)) {
                $formatters[$fieldName] = $formatter;
                continue;
            }
        }

        return $formatters;
    }

    protected function findRelatedClass(&$belongsTo, $fieldName)
    {
        foreach ($belongsTo as $association) {
            if ($fieldName == $association['foreignKey']) {
                return $association['className'];
            }
        }
        // throw exception if no association found?
    }

    protected function isColumnBehavior($columnMap)
    {
        foreach ($columnMap->getTable()->getBehaviors() as $name => $params) {
            $columnName = Base::toLower($columnMap->getName());
            switch ($name) {
                case 'nested_set':
                    $columnNames = array($params['left_column'], $params['right_column'], $params['level_column']);
                    if (in_array($columnName, $columnNames)) {
                        return true;
                    }
                    break;
                case 'timestampable':
                    $columnNames = array($params['create_column'], $params['update_column']);
                    if (in_array($columnName, $columnNames)) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    public function setModifiers($modifiers)
    {
        $this->modifiers = $modifiers;
    }

    public function getModifiers()
    {
        return $this->modifiers;
    }

    public function mergeModifiersWith($modifiers)
    {
        $this->modifiers = array_merge($this->modifiers, $modifiers);
    }

    public function guessModifiers(\Faker\Generator $generator)
    {
        $modifiers = array();
        $class = $this->class;
        $peerClass = $class::PEER;
        $tableMap = $peerClass::getTableMap();
        foreach ($tableMap->getBehaviors() as $name => $params) {
            switch ($name) {
                case 'nested_set':
                    $modifiers['nested_set'] = function($obj, $inserted) use ($class, $generator) {
                        if (isset($inserted[$class])) {
                            $queryClass = $class . 'Query';
                            $parent = $queryClass::create()->findPk($generator->randomElement($inserted[$class]));
                            $obj->insertAsLastChildOf($parent);
                        } else {
                            $obj->makeRoot();
                        }
                    };
                    break;
                case 'sortable':
                    $modifiers['sortable'] = function($obj, $inserted) use ($class, $generator) {
                        $maxRank = isset($inserted[$class]) ? count($inserted[$class]) : 0;
                        $obj->insertAtRank(mt_rand(1, $maxRank + 1));
                    };
                    break;
            }
        }

        return $modifiers;
    }

    /**
     * Insert one new record using the Entity class.
     */
    public function execute($class, $insertedEntities)
    {
        $obj = \ClassRegistry::init($this->class);
        $obj->create();

        foreach ($this->getColumnFormatters() as $column => $format) {
            if (null !== $format) {
                $obj->data[$this->class][$column] = is_callable($format) ? $format($insertedEntities, $obj) : $format;
            }
        }
        foreach ($this->getModifiers() as $modifier) {
            $modifier($obj, $insertedEntities);
        }

        $obj->save($obj->data);

        return $obj->getLastInsertId();
    }

}
