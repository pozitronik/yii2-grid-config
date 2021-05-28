<?php
declare(strict_types = 1);

namespace pozitronik\grid_config;

use kartik\base\BootstrapInterface;
use kartik\base\BootstrapTrait;
use pozitronik\helpers\ArrayHelper;
use pozitronik\helpers\BootstrapHelper;
use pozitronik\helpers\ReflectionHelper;
use pozitronik\users_options\models\UsersOptions;
use ReflectionClass;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\ViewContextInterface;
use yii\grid\Column;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * @property string $id -- используется для сохранения конфига, это ключ
 * @property string $saveUrl -- урл для постинга сохраняемого конфига
 * @property null|int $pageSize
 * @property null|int $maxPageSize -- максимальный лимит для задаваемого размера страницы
 * @property null|bool $floatHeader -- плавающий заголовок (если поддерживается)
 * @property null|string $fromUrl -- redirection url
 * @property GridView $grid -- конфигурируемый грид
 * @property array[] $columns -- все доступные колонки грида
 * @property array[] $visibleColumns -- все отображаемые колонки грида (с сохранением порядка сортировки)
 * @property string[]|null $visibleColumnsLabels -- сохранённый порядок отображаемых колонок в формате ['columnLabel'...]
 *
 * @property-read string[] $visibleColumnsItems -- набор строк заголовков для Sortable видимых колонок
 * @property-read string[] $hiddenColumnsItems -- набор строк заголовков для Sortable скрытых колонок
 *
 * @property-read string $viewPath
 * @property string $visibleColumnsJson -- виртуальный атрибут для передачи сериализованного набора данных из Sortable-виджета (https://github.com/lukasoppermann/html5sortable)
 *
 * @property null|int $user_id -- id пользователя, чьи настройки применяются к гриду (по умолчанию - текущий)
 */
class GridConfig extends Model implements ViewContextInterface, BootstrapInterface {
	use BootstrapTrait;

	private const DEFAULT_SAVE_URL = 'config/apply';
	public $user_id;

	private $_id = '';
	private $_columns;
	private $_fromUrl;
	private $_grid;
	private $_gridPresent = false;
	private $_saveUrl;
	private $_visibleColumnsJson = '';
	/**
	 * @var int|null
	 */
	private $_pageSize;
	private $_maxPageSize = 20;
	private $_visibleColumnsLabels;
	private $_floatHeader;

	/**
	 * @var null|UsersOptions
	 */
	private $_userOptions;

	/**
	 * {@inheritDoc}
	 */
	public function attributeLabels():array {
		return [
			'pageSize' => 'Максимальное количество записей на одной странице (0 - без ограничения)',
			'visibleColumnsLabels' => 'Выбор видимых колонок',
			'floatHeader' => 'Плавающий заголовок'
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function rules():array {
		return [
			[['id', 'fromUrl'], 'string'],
			[['pageSize'], 'integer'],
			[['pageSize'], 'filter', 'filter' => 'intval'],
			[['columns', 'visibleColumns', 'visibleColumnsLabels', 'visibleColumnsJson'], 'safe'],
			[['floatHeader'], 'boolean']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		$this->user_id = $this->user_id??Yii::$app->user->id;
		$this->_userOptions = new UsersOptions(['user_id' => $this->user_id]);
		$this->_saveUrl = $this->_saveUrl??ArrayHelper::getValue(Yii::$app->modules, 'gridсonfig.params.saveUrl', GridConfigModule::to(self::DEFAULT_SAVE_URL));
		$attributes = $this->_userOptions->get($this->formName().$this->id);
		$this->load($attributes, '');
		$this->nameColumns();
	}

	/**
	 * Для удобства конфигурации симулируем виджет
	 * @param array $config
	 * @return string
	 * @throws Throwable
	 * @noinspection PhpPossiblePolymorphicInvocationInspection -- мы можем обращаться к свойствам грида картика, если используется он, но опираемся на базовый грид
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public static function widget(array $config = []):string {
		$gridConfig = new self($config);

		/**
		 * Если мы используем GridView, поддерживающий расширенную конфигурацию лайаута, то кнопку настройки внедрим через эту конфигурацию
		 * Иначе просто модифицируем лайаут
		 */
		if ($gridConfig->grid->hasProperty('replaceTags')) {
			/*Добавим кнопку вызова модалки настроек*/
			$gridConfig->grid->replaceTags['{options}'] = $gridConfig->renderOptionsButton();
			/*Если позиция кнопки не сконфигурирована в гриде вручную, добавим её в самое начало*/
			if (0 === mb_substr_count($gridConfig->grid->panelHeadingTemplate, '{options}')) {
				$gridConfig->grid->panelHeadingTemplate = (BootstrapHelper::isBs4()?'<div class="float-left m-r-sm">{options}</div>':'<div class="pull-left m-r-sm">{options}</div>').$gridConfig->grid->panelHeadingTemplate;
			}
		} else {
			$gridConfig->grid->layout = $gridConfig->renderOptionsButton().$gridConfig->grid->layout;
		}

		$gridConfig->grid->columns = $gridConfig->visibleColumns;
		return $gridConfig->endGrid();
	}

	/**
	 * Очевидно
	 */
	public function endGrid():string {
		$this->grid::end();
		$bsView = $this->isBs4()?"bs4":"bs3";
		return Yii::$app->view->render("config/{$bsView}/modalGridConfig", ['model' => $this], $this);
	}

	/**
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	public function apply():void {
		$config = [];
		foreach ($this->activeAttributes() as $attributeName) {
			$storedAttributes = array_keys($this->attributeLabels());
			if ((null !== $attributeValue = ArrayHelper::getValue($this, $attributeName)) && (in_array($attributeName, $storedAttributes, true))) $config[$attributeName] = $attributeValue;
		}

		$this->_userOptions->set($this->formName().$this->id, $config);
	}

	/**
	 * @throws Throwable
	 */
	private function nameColumns():void {
		$namedColumns = [];
		$columnCount = 0;//нам важны только цифры колонок
		foreach ($this->columns as $column) {
			if (null === $label = $this->getColumnLabel($column)) $label = "Column #$columnCount";
			$namedColumns[$label] = $column;
			$columnCount++;
		}
		$this->columns = $namedColumns;
	}

	/**
	 * Кнопка вызова модалки конфигуратора
	 * @return string
	 * @throws Throwable
	 */
	public function renderOptionsButton():string {
		return Html::button($this->isBs4()?'<i class="fas fa-wrench"></i>':'<i class="glyphicon glyphicon-wrench"></i>', ['class' => 'btn btn-default', 'onclick' => new JsExpression("jQuery('#grid-config-modal-{$this->id}').modal('show')")]);
	}

	/**
	 * Метод, пытающийся из параметров колонки выудить такое же название, какое будет отображать грид
	 * @param array|object $column -- может быть конфиг массивом (тогда надо прогрузить), может быть уже готовая модель (если конфигуратор работает с гридом напрямую)
	 * @return string|null
	 * @throws Throwable
	 */
	private function getColumnLabel($column):?string {
		try {
			if (is_object($column)) {
				$columnModel = $column;
			} elseif (is_array($column)) {
				/** @var DataColumn $columnModel */
				$columnModel = Yii::createObject(array_merge([//конфигурируем модель колонки. Это может быть любой потомок класса Column, в котором есть своя реализация получения лейбла
					'class' => ArrayHelper::getValue($column, 'class', DataColumn::class),
					'grid' => $this->grid//из переданного имени класса грида генерируем модель этого грида - она нужна модели колонки
				], $column));
			} else {
				throw new InvalidConfigException('Конфигурация колонок грида задана неверно');
			}
			if (null === $getHeaderCellLabelReflectionMethod = ReflectionHelper::setAccessible($columnModel, 'getHeaderCellLabel')) return null;//поскольку метод getHeaderCellLabel, мы рефлексией хачим его доступность
			return (empty($label = $getHeaderCellLabelReflectionMethod->invoke($columnModel)) || '&nbsp;' === $label)?null:$label;//вызываем похаченный метод. Если имя колонки пустое, нужно вернуть null - вышестоящий метод подставит туда числовой идентификатор
		} /** @noinspection BadExceptionsProcessingInspection */ catch (Throwable $throw) {//если на каком-то этапе возникла ошибка, нужно фаллбечить
			return null;
		}
	}

	/**
	 * @return Column[]
	 * @throws Throwable
	 */
	public function getVisibleColumns():array {
		if (null === $this->visibleColumnsLabels) $this->visibleColumnsLabels = array_keys($this->columns);//при несозданном конфиге отобразим все колонки
//		if ([] === $this->visibleColumnsLabels) return [new DataColumn(['label' => 'Нет отображаемых колонок', 'grid' => $this->grid])];
		$result = [];
		foreach ($this->visibleColumnsLabels as $columnsLabel) {
			if (null !== $column = ArrayHelper::getValue($this->columns, $columnsLabel)) $result[] = $column;
		}
		return $result;
	}

	/**
	 * @return string|null
	 */
	public function getFromUrl():?string {
		return $this->_fromUrl??Yii::$app->request->url;
	}

	/**
	 * @param string|null $fromUrl
	 */
	public function setFromUrl(?string $fromUrl):void {
		$this->_fromUrl = $fromUrl;
	}

	/**
	 * @return GridView
	 * @throws InvalidConfigException
	 */
	public function getGrid():GridView {
		if (null === $this->_grid) {
			throw new InvalidConfigException('Параметр grid должен ссылаться на GridView');
		}
		return $this->_grid;
	}

	/**
	 * @param GridView $grid
	 */
	public function setGrid(GridView $grid):void {
		$this->_gridPresent = true;
		$this->_grid = $grid;
	}

	/**
	 * @return int|null
	 */
	public function getPageSize():?int {
		return ($this->_gridPresent && false !== $pagination = $this->grid->dataProvider->pagination)?$pagination->pageSize:$this->_pageSize;
	}

	/**
	 * @param int|null $pageSize
	 */
	public function setPageSize(?int $pageSize):void {
		$this->_pageSize = ($pageSize > $this->_maxPageSize && 0 === $pageSize)?$this->_maxPageSize:$pageSize;
		if ($this->_gridPresent && false !== $pagination = $this->grid->dataProvider->pagination) {
			$pagination->pageSize = $this->_pageSize;
			$pagination->pageSizeLimit = false;
		}
	}

	/**
	 * @return array[]
	 */
	public function getColumns():array {
		if (null === $this->_columns) {
			$this->_columns = ($this->_gridPresent)?$this->grid->columns:[];
		}
		return $this->_columns;
	}

	/**
	 * @param array[] $columns
	 */
	public function setColumns(array $columns):void {
		$this->_columns = $columns;
	}

	/**
	 * @return string
	 * @throws InvalidConfigException
	 */
	public function getId():string {
		if (!$this->_gridPresent) {
			return $this->_id;
		}
		/** @var object $gridClassName */
		$gridClassName = (new ReflectionClass($this->grid))->getName();
		if ($this->grid->id === $gridClassName::$autoIdPrefix.($gridClassName::$counter - 1)) {//ебать я хакир
			if (null === $this->_id) {
				throw new InvalidConfigException('Нужно задать параметр id для конфигурируемого GridView, либо для GridConfig');
			} else {
				$this->grid->id = $this->_id;
			}

		}
		return $this->grid->id;
	}

	/**
	 * @param string $id
	 */
	public function setId(string $id):void {
		$this->_id = $id;
	}

	/**
	 * @inheritDoc
	 */
	public function getViewPath():string {
		$class = new ReflectionClass($this);
		return dirname($class->getFileName()).DIRECTORY_SEPARATOR.'views';
	}

	/**
	 * @return string
	 * @throws Throwable
	 */
	public function getSaveUrl():string {
		if (null === $this->_saveUrl) {
			throw new InvalidConfigException('Не указан параметр saveUrl. Укажите его вручную или через Yii::$app->modules->gridconfig->params->saveUrl');//он должен содержать эндпойнт ajax-приёмника настроек
		}
		return $this->_saveUrl;
	}

	/**
	 * @param string $saveUrl
	 */
	public function setSaveUrl(string $saveUrl):void {
		$this->_saveUrl = $saveUrl;
	}

	/**
	 * Возвращает массив для грида сортировки видимых колонок
	 * @return string[]
	 * @throws Throwable
	 */
	public function getVisibleColumnsItems():array {
		$result = [];
		foreach ($this->visibleColumnsLabels as $columnsLabel) {//нельзя использовать $this->visibleColumns, т.к. геттер возвращает обработанные данные. Нельзя вычислять через visibleColumnsLabels, т.к. они могут содержать неактуальные колонки
			if (null !== ArrayHelper::getValue($this->columns, $columnsLabel)) $result[] = ['content' => $columnsLabel];
		}
		return $result;
	}

	/**
	 * Возвращает массив для грида сортировки скрытых колонок
	 * @return string[]
	 */
	public function getHiddenColumnsItems():array {
		$result = [];
		$hiddenColumns = array_diff_key($this->columns, array_flip($this->visibleColumnsLabels));
		foreach ($hiddenColumns as $label => $column) {
			$result[] = ['content' => $label];
		}
		return $result;
	}

	/**
	 * @return null|string[]
	 */
	public function getVisibleColumnsLabels():?array {
		return $this->_visibleColumnsLabels;
	}

	/**
	 * @param string[] $visibleColumnsLabels
	 */
	public function setVisibleColumnsLabels(?array $visibleColumnsLabels):void {
		$this->_visibleColumnsLabels = $visibleColumnsLabels;
	}

	/**
	 * @return string
	 */
	public function getVisibleColumnsJson():string {
		$result = [];
		foreach ($this->visibleColumnsLabels as $label) {
			$result[] = [
				'label' => $label
			];
		}
		return json_encode($result, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param string $visibleColumnsJson
	 */
	public function setVisibleColumnsJson(string $visibleColumnsJson):void {
		$labels = json_decode($visibleColumnsJson, true);
		$this->visibleColumnsLabels = ArrayHelper::getColumn($labels, 'label');
		$this->_visibleColumnsJson = $visibleColumnsJson;
	}

	/**
	 * @return int|null
	 */
	public function getMaxPageSize():?int {
		return $this->_maxPageSize;
	}

	/**
	 * @param int|null $maxPageSize
	 */
	public function setMaxPageSize(?int $maxPageSize):void {
		$this->_maxPageSize = $maxPageSize;
	}

	/**
	 * @return bool|null
	 */
	public function getFloatHeader():?bool {
		return $this->_floatHeader;
	}

	/**
	 * @param bool|null $floatHeader
	 * @noinspection PhpPossiblePolymorphicInvocationInspection -- учитываем, что тут может быть и картик и не картик
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function setFloatHeader(?bool $floatHeader):void {
		$this->_floatHeader = $floatHeader;
		if ($this->_gridPresent && false !== $this->grid->hasProperty('floatHeader')) {
			$this->grid->floatHeader = $this->_floatHeader;
		}
	}

}