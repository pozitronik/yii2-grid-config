<?php
declare(strict_types = 1);

namespace pozitronik\grid_config\controllers;

use pozitronik\grid_config\GridConfig;
use pozitronik\helpers\ArrayHelper;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
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
		return ($config->fromUrl)?$this->redirect($config->fromUrl):ArrayHelper::getValue(Yii::$app->modules, 'gridсonfig.params.defaultRedirect', Url::home());
	}

	/**
	 * Список сохранённых фильтров
	 */
	public function actionFiltersList() {
		$config = new GridConfig();

	}

	/**
	 * Сохранение фильтра
	 */
	public function actionFiltersSave() {

	}
}