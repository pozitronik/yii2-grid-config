<?php
declare(strict_types = 1);

namespace pozitronik\grid_config;

use kartik\base\AssetBundle;

/**
 * Class FixKartikAssets
 */
class FixKartikAssets extends AssetBundle {
	/**
	 * @inheritdoc
	 */
	public function init():void {
		$this->sourcePath = __DIR__.'/assets';
		$this->css = ['css/fix_kartik.css'];
		parent::init();
	}
}








