<?php
App::uses('Populator', 'CakeFaker.Lib/Faker/ORM/CakePHP');

class FakerTestFixture extends CakeTestFixture
{
    protected $generator;
    protected $populator;
    protected $seed;
    protected $model_name;
    protected $num_records;

    /**
     * FakerTestFixture constructor.
     */
    public function __construct()
    {
        $this->generator = \Faker\Factory::create();

        if ($this->seed = Configure::read('faker.seed')) {
            $this->generator->seed($this->seed);
        }

        $this->populator = new Populator($this->generator);

        parent::__construct();
    }

    public function insert($db)
    {
        // Just in case they have some hardcoded fixtures in place.
        if (!is_null($this->records)) {
            parent::insert($db);
        }

        $this->generate(null, null, null, true);
    }

    /**
     * Use this to generate test records.
     * Built this to use outside of test fixtures for on the fly test data generation.
     *
     * @param string $modelName CakePHP 2 model name.
     * @param int $numRecords Number of records to generate.
     * @param array|null $alterFields An array of column names with provided values.
     * @param bool $appendRecords Pass true if you just want to add more records rather than destroy records.
     */
    public function generate($modelName = null, $numRecords = null, array $alterFields = null, $appendRecords = false)
    {
        // Set up the params.
        $modelName = is_null($modelName) ? $this->model_name : $modelName;
        $numRecords = is_null($numRecords) ? $this->num_records : $numRecords;
        $alterFields = is_null($alterFields) ? $this->alterFields($this->generator) : array_merge($this->alterFields($this->generator), $alterFields);

        // Clean out previous saved data.
        if (!$appendRecords) {
            $model = \ClassRegistry::init($modelName);
            $model->deleteAll(array("{$modelName}.id IS NOT NULL"));
        }

        // Populate the table.
        $this->populator->addEntity($modelName, $numRecords, $alterFields);
        $this->populator->execute();
    }

    /**
     * @return \Faker\Generator
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @return mixed
     */
    public function getPopulator()
    {
        return $this->populator;
    }

    /**
     * @return mixed
     */
    public function getSeed()
    {
        return $this->seed;
    }

    /**
     * @param mixed $seed
     */
    public function setSeed($seed)
    {
        $this->seed = $seed;
    }

    protected function alterFields($generator)
    {
        return array();
    }
}
