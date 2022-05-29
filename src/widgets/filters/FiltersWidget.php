<?php
declare(strict_types = 1);

namespace pozitronik\grid_config\widgets\filters;

use kartik\base\Widget;

/**
 * Class FiltersWidget
 */
class FiltersWidget extends Widget {

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
		return $this->render('filters', []);
	}
}