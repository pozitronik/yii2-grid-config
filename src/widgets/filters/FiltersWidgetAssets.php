<?php
declare(strict_types = 1);

namespace pozitronik\grid_config\widgets\filters;

use yii\web\AssetBundle;

/**
 * Class FiltersWidgetAssets
 */
class FiltersWidgetAssets extends AssetBundle {
	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		$this->css = [
			'css/filters.css'
		];
		$this->js = [
			'js/filters.js'
		];
		parent::init();
	}
}