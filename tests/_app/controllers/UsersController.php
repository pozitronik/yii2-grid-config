<?php
declare(strict_types = 1);

namespace app\controllers;

use app\models\Users;
use app\models\UsersSearch;
use Throwable;
use yii\db\StaleObjectException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class UsersController
 */
class UsersController extends Controller {

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function actionIndex():string {
		$searchModel = new UsersSearch();
		$dataProvider = $searchModel->search($this->request->queryParams);

		return $this->render('index', compact('searchModel', 'dataProvider'));
	}

	/**
	 * @param int $id
	 * @return string
	 * @throws NotFoundHttpException
	 */
	public function actionView(int $id):string {
		return $this->render('view', [
			'model' => $this->getModelByPKOrFail($id),
		]);
	}

	/**
	 * @return string|Response
	 */
	public function actionCreate() {
		$model = new Users();

		if ($this->request->isPost) {
			if ($model->load($this->request->post()) && $model->save()) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
		} else {
			$model->loadDefaultValues();
		}

		return $this->render('create', [
			'model' => $model,
		]);
	}

	/**
	 * @param int $id
	 * @return string|Response
	 * @throws NotFoundHttpException
	 */
	public function actionUpdate(int $id) {
		$model = $this->getModelByPKOrFail($id);

		if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
			return $this->redirect(['view', 'id' => $model->id]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}

	/**
	 * @param int $id
	 * @return Response
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
	public function actionDelete(int $id):Response {
		$this->getModelByPKOrFail($id)->delete();

		return $this->redirect(['index']);
	}

	/**
	 * @param mixed $pk
	 * @return Users
	 * @throws NotFoundHttpException
	 */
	protected function getModelByPKOrFail(mixed $pk):Users {
		return Users::findOne($pk)?:throw new NotFoundHttpException();
	}
}