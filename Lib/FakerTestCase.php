<?php

class FakerTestCase extends CakeTestCase
{
	public function setUp()
	{
		if (ClassRegistry::isKeySet('faker')) {
			$populator = ClassRegistry::getObject('faker');
			$populator->execute();
		}
	}
}