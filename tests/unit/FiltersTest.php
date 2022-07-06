<?php
declare(strict_types = 1);

namespace unit;

use app\models\Users;
use Codeception\Test\Unit;
use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\ArrayHelper;

/**
 * Тесты загрузки/сохранения фильтров
 */
class FiltersTest extends Unit {

	public function testFiltersCreate():void {
		$user = Users::CreateUser()->saveAndReturn();
		$config = new GridConfig(['user_id' => $user->id]);
		$config->addFilter('Users-index-grid', 'first filter', [
			'username' => 'test',
			'id' => '1'
		]);
		$config->addFilter('Users-index-grid', 'second filter', [
			'username' => 'test2',
			'id' => '2'
		]);

		$filters = $config->getFiltersList('Users-index-grid');

		static::assertTrue(ArrayHelper::isEqual([
			'first filter' => [
				'username' => 'test',
				'id' => '1'
			],
			'second filter' => [
				'username' => 'test2',
				'id' => '2'
			]
		], $filters));
	}

}