<?php

App::uses('Populator', 'CakeFaker.Lib/Faker/ORM/CakePHP');

class FakerTestFixture extends CakeTestFixture
{
	public function insert($db)
	{
		$generator = \Faker\Factory::create();
		if ($seed = Configure::read('faker.seed'))
			$generator->seed($seed);

		if ( ! ClassRegistry::isKeySet('faker'))
			ClassRegistry::addObject('faker', new Populator($generator));
		$populator = ClassRegistry::getObject('faker');

		$populator->addEntity($this->model_name, $this->num_records, $this->alterFields($generator));
	}

	protected function alterFields($generator)
	{
		return array();
	}
}