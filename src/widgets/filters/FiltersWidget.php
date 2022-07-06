<?php
declare(strict_types = 1);

namespace pozitronik\grid_config\widgets\filters;

use kartik\base\Widget;
use pozitronik\users_options\models\UsersOptions;
use yii\grid\GridView;
use Yii;

/**
 * Class FiltersWidget
 * @property string[] $filters
 * @property GridView $grid
 */
class FiltersWidget extends Widget {
	public array $filters;
	public GridView $grid;
	public int|null $user_id = null;

	private ?UsersOptions $_userOptions = null;

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		FiltersWidgetAssets::register($this->getView());
		$this->user_id = $this->user_id??Yii::$app->user->id;
		$this->_userOptions = new UsersOptions(['user_id' => $this->user_id]);
	}

	/**
	 * @inheritDoc
	 */
	public function run():string {
		return $this->render('filters', [
			'filters' => $this->filters,
			'gridId' => $this->grid->id
		]);
	}

	public function addFilter(string $name, array $filter) {
		$this->_userOptions->set('filters_'.$this->grid->id, [$name => $filter]);
	}

	public function getFiltersList() {
		$this->_userOptions->get('filters_'.$this->grid->id);
	}
}