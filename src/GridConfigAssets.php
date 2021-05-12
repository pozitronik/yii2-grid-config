<?php
declare(strict_types = 1);

namespace pozitronik\grid_config;

use kartik\base\AssetBundle;

/**
 * Class GridConfigAssets
 */
class GridConfigAssets extends AssetBundle {
	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		$this->css = ['css/grid_config.css'];
		$this->js = ['js/grid_config.js'];
		parent::init();
	}
}








