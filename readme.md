# CakePHP Faker Fixtures Plugin

Hacky CakePHP plugin to generate fixtures using Faker. Extends Cake's fixtures class, using it for schema generation, but allows the generation of records to be done by Faker

Work in progress, hacky, works for me, use at your own risk, etc.

## Installation

Relies on Composer to install and autoload Faker. Just add this to the require block of your composer.json.

		"burriko/cake-faker": "2.0.*@dev"

## Configure

1. Add the following line to your app/Config/bootstrap.php.

		CakePlugin::load('CakeFaker');

2. You'll need to change your fixtures to extend FakerTestFixture instead of CakeTestFixture. So the start of your fixtures files should be something like this.

		<?php
		App::uses('FakerTestFixture', 'CakeFaker.Lib');

		class UserFixture extends FakerTestFixture

3. Any test cases will need to extend FakerTestCase instead of CakeTestCase. If you're overriding setUp() in your test case you must make sure to call parent::setUp().
So the start of your test case files should be something like this.

		<?php
		App::uses('FakerTestCase', 'CakeFaker.Lib');

		class UserTest extends FakerTestCase

4. If you want Faker to generate the same fixtures each time it runs then you need to provide a seed. Add a line like this to Config/core.php or a similar config file.

		Configure::write('faker.seed', 8468155468);

## Usage

Mostly this is used in exactly the same way as Cake's fixtures, except that instead of specifying records you just tell Faker how many records to generate.

Here's an example of generating 5 records for the User model, importing the schema from the development database.

		<?php
		App::uses('FakerTestFixture', 'CakeFaker.Lib');

		class UserFixture extends FakerTestFixture
		{
			public $import = array('model' => 'User', 'connection' => 'development');

			protected
				$model_name = 'User',
				$num_records = 5;

			protected function alterFields($generator)
			{
				return array(
					'username' => function() use ($generator) { return $generator->bothify('n??##'); },
					'name'     => function() use ($generator) { return $generator->name; },
					'level'    => function() use ($generator) { return $generator->randomNumber(0, 1); }
				);
			}
		}

The alterFields() method can be used to specify column types for fields that Faker cannot guess. It's used in exactly the same way as the 3rd argument to addEntity() in this section of the Faker docs.
https://github.com/fzaninotto/Faker#populating-entities-using-an-orm-or-an-odm

If you have related models and would like the generated records that are linked by foreign keys then Faker will take care of this.  Just make sure to order the fixtures logically e.g. if a user has many posts, make sure that the user fixture comes before the post fixture in your test case, as the user IDs will need to be used by the post fixture.