<?php
declare(strict_types = 1);

namespace pozitronik\grid_config\widgets\filters;

use kartik\base\Widget;
use yii\grid\GridView;

/**
 * Class FiltersWidget
 * @property string[] $filters
 * @property GridView $grid
 */
class FiltersWidget extends Widget {
	public array $filters;
	public GridView $grid;

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		FiltersWidgetAssets::register($this->getView());
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
}