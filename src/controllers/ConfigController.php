<?php
declare(strict_types = 1);

namespace pozitronik\widget\controllers;

use pozitronik\helpers\ArrayHelper;
use pozitronik\widgets\GridConfig;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class ConfigController
 */
class ConfigController extends Controller {

	/**
	 * @return string|Response
	 * @throws Throwable
	 * @throws InvalidConfigException
	 */
	public function actionApply() {
		$config = new GridConfig();
		$config->load(Yii::$app->request->post());
		$config->apply();
		return ($config->fromUrl)?$this->redirect($config->fromUrl):ArrayHelper::getValue(Yii::$app, 'gridConfig.defaultRedirect');
	}
}