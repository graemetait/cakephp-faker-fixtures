<?php

// namespace Faker\ORM\CakePHP;

App::uses('EntityPopulator', 'CakeFaker.Lib/Faker/ORM/CakePHP');

/**
 * Service class for populating a database using the CakePHP ORM.
 * A Populator can populate several tables using CakePHP Model classes.
 */
class Populator
{
    protected $generator;
    protected $entities = array();
    protected $quantities = array();

    public function __construct(\Faker\Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Add an order for the generation of $number records for $entity.
     *
     * @param mixed $entity A CakePHP Model classname, or a \Faker\ORM\CakePHP\EntityPopulator instance
     * @param int   $number The number of entities to populate
     */
    public function addEntity($entity, $number, $customColumnFormatters = array(), $customModifiers = array())
    {
        if ( ! $entity instanceof EntityPopulator) {
            $entity = new EntityPopulator($entity);
        }
        $entity->setColumnFormatters($entity->guessColumnFormatters($this->generator));
        if ($customColumnFormatters) {
            $entity->mergeColumnFormattersWith($customColumnFormatters);
        }
        // $entity->setModifiers($entity->guessModifiers($this->generator));
        if ($customModifiers) {
            $entity->mergeModifiersWith($customModifiers);
        }
        $class = $entity->getClass();
        $this->entities[$class] = $entity;
        $this->quantities[$class] = $number;
    }

    /**
     * Populate the database using all the Entity classes previously added.
     *
     * @return array A list of the inserted PKs
     */
    public function execute()
    {
        // var_dump($this->entities);

        $insertedEntities = array();

        foreach ($this->quantities as $class => $number) {
            for ($i=0; $i < $number; $i++) {
                $insertedEntities[$class][]= $this->entities[$class]->execute($class, $insertedEntities);
            }
        }

        return $insertedEntities;
    }

    protected function getConnection()
    {
        // use the first connection available
        $class = key($this->entities);

        if (!$class) {
            throw new \RuntimeException('No class found from entities. Did you add entities to the Populator ?');
        }

        // $peer = $class::PEER;

        return \Propel::getConnection($peer::DATABASE_NAME, \Propel::CONNECTION_WRITE);
    }

}
