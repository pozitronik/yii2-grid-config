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
use yii\grid\ActionColumn;
use yii\grid\Column;
use yii\grid\DataColumn;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * @property string $id Используется для сохранения конфига, это ключ
 * @property string $saveUrl Урл для постинга сохраняемого конфига
 * @property null|int $pageSize Размер страницы пагинатора
 * @property int $minPageSize Минимальное значение для задаваемого размера страницы
 * @property int $maxPageSize Максимальный лимит для задаваемого размера страницы
 * @property null|bool $floatHeader Плавающий заголовок (если поддерживается связанным GridView)
 * @property null|bool $filterOnFocusOut Фильтрация при потере фокуса любым фильтром
 *
 * @property null|string $fromUrl Redirection url
 * @property GridView $grid Конфигурируемый грид
 * @property array[] $columns Все доступные колонки грида
 * @property array[] $visibleColumns Все отображаемые колонки грида (с сохранением порядка сортировки)
 * @property string[]|null $visibleColumnsLabels Сохранённый порядок отображаемых колонок в формате ['columnLabel'...]
 * @property string[]|null $defaultAttributes Колонки (в формате ['attributeName'...], которые должны выводиться по умолчанию (если свой набор не сконфигурирован)
 *
 * @property-read string[] $visibleColumnsItems Набор строк заголовков для Sortable видимых колонок
 * @property-read string[] $hiddenColumnsItems Набор строк заголовков для Sortable скрытых колонок
 *
 * @property-read string $viewPath Путь к шаблонам компонента
 * @property string $visibleColumnsJson Виртуальный атрибут для передачи сериализованного набора данных из Sortable-виджета (https://github.com/lukasoppermann/html5sortable)
 *
 * @property null|int $user_id id пользователя, чьи настройки применяются к гриду (по умолчанию - текущий)
 */
class GridConfig extends Model implements ViewContextInterface, BootstrapInterface {
	use BootstrapTrait;

	/*Константа для включения колонки управления в атрибуты по умолчанию*/
	public const ACTION_COLUMN_ATTRIBUTE = '@action_column';
	private const DEFAULT_SAVE_URL = 'config/apply';
	public $user_id;

	private ?string $_id = null;
	private ?array $_columns = null;
	private ?string $_fromUrl = null;
	private ?GridView $_grid = null;
	private bool $_gridPresent = false;
	private ?string $_saveUrl = null;
	private string $_visibleColumnsJson = '';
	private ?int $_pageSize = null;
	private int $_minPageSize = 1;
	private int $_maxPageSize = 20;
	private array $_defaultGridParams = [];

	/**
	 * @var string[]|null
	 */
	private ?array $_visibleColumnsLabels = null;
	private ?bool $_floatHeader = null;
	private ?bool $_filterOnFocusOut = null;
	private ?array $_defaultAttributes = null;

	private ?UsersOptions $_userOptions = null;

	/**
	 * {@inheritDoc}
	 */
	public function attributeLabels():array {
		return [
			'pageSize' => "Максимальное количество записей на одной странице ({$this->_minPageSize} – {$this->_maxPageSize})",
			'visibleColumnsLabels' => 'Выбор видимых колонок',
			'floatHeader' => 'Плавающий заголовок',
			'filterOnFocusOut' => 'Фильтровать при изменении фильтров',
			'defaultColumns' => 'Поля по умолчанию'
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function rules():array {
		return [
			[['id', 'fromUrl'], 'string'],
			[['pageSize'], 'integer', 'min' => $this->minPageSize, 'max' => $this->maxPageSize],
			[['pageSize'], 'filter', 'filter' => 'intval'],
			[['columns', 'visibleColumns', 'visibleColumnsLabels', 'visibleColumnsJson', 'defaultColumns'], 'safe'],
			[['floatHeader', 'filterOnFocusOut'], 'boolean']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function init():void {
		parent::init();
		$this->user_id = $this->user_id??Yii::$app->user->id;
		$this->_userOptions = new UsersOptions(['user_id' => $this->user_id]);
		$this->_saveUrl = $this->_saveUrl??GridConfigModule::param('saveUrl', GridConfigModule::to(self::DEFAULT_SAVE_URL));
		$this->_minPageSize = GridConfigModule::param('minPageSize', $this->_minPageSize);
		$this->_maxPageSize = GridConfigModule::param('maxPageSize', $this->_maxPageSize);
		$this->_defaultGridParams = GridConfigModule::param('defaultGridParams', $this->_defaultGridParams);

		/*Если параметры не были установлены при вызове виджета или в пользовательских настройках, то взять из конфига*/
		$this->_filterOnFocusOut = $this->_filterOnFocusOut??ArrayHelper::getValue($this->_defaultGridParams, 'filterOnFocusOut', $this->_filterOnFocusOut);
		$this->_floatHeader = $this->_floatHeader??ArrayHelper::getValue($this->_defaultGridParams, 'floatHeader', $this->_floatHeader);

		if ($this->_gridPresent) $this->load($this->_userOptions->get($this->formName().$this->id), '');
		$this->nameColumns();
	}

	/**
	 * Для удобства конфигурации симулируем виджет
	 * @param array $config
	 * @return string
	 * @throws Throwable
	 */
	public static function widget(array $config = []):string {
		$gridConfig = new self($config);
		$gridConfig->injectOptionsButton();
		$gridConfig->grid->columns = $gridConfig->visibleColumns;
		return $gridConfig->endGrid();
	}

	/**
	 * Если используется GridView, поддерживающий расширенную конфигурацию лайаута, то кнопку настройки внедрим через эту конфигурацию
	 * Иначе просто модифицируем лайаут
	 * @return void
	 * @throws InvalidConfigException
	 * @throws Throwable
	 * @noinspection PhpUndefinedFieldInspection
	 */
	private function injectOptionsButton():void {
		if ($this->grid->hasProperty('replaceTags')) {
			/*Добавим кнопку вызова модалки настроек*/
			$this->grid->replaceTags['{options}'] = $this->renderOptionsButton();
			/*Если позиция кнопки не сконфигурирована в гриде вручную, добавим её в самое начало*/
			if (0 === mb_substr_count($this->grid->panelHeadingTemplate, '{options}')) {
				$this->grid->panelHeadingTemplate = (BootstrapHelper::isBs4()?'<div class="float-left m-r-sm">{options}</div>':'<div class="pull-left m-r-sm">{options}</div>').$this->grid->panelHeadingTemplate;
			}
		} else {
			$this->grid->layout = $this->renderOptionsButton().$this->grid->layout;
		}
	}

	/**
	 * Очевидно
	 */
	public function endGrid():string {
		$this->grid::end();
		$bsView = $this->isBs(4)?"bs4":"bs3";
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
	 * Получает уникальное имя колонки для привязки настроек
	 * @throws Throwable
	 */
	private function nameColumns():void {
		$namedColumns = [];
		$columnCount = 0;//нам важны только цифры колонок
		foreach ($this->columns as $column) {
			if (null === $label = $this->getColumnLabel($column)) $label = "Column #$columnCount";
			$checkLabel = $label;
			$columnCopy = 0;
			while (isset($namedColumns[$checkLabel])) {
				/**
				 * В случае, если в гриде имеется несколько разных колонок с одинаковым label, к генерируемому имени добавляем привязанный атрибут и порядковый
				 * номер "дубля". Брать название атрибута сразу нельзя (неудобно и несовместимо со старыми версиями), использовать решение вроде spl_object_hash
				 * для получения уникального идентификатора, тоже нельзя - конфиги будут слетать при любом изменении конфигурации.
				 **/
				$checkLabel = $label.' ('.$this->getColumnAttribute($column).'_'.$columnCopy.')';
				$columnCopy++;
			}
			$label = $checkLabel;
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
		return Html::button($this->isBs(4)
			?'<i class="fas fa-wrench"></i>'
			:'<i class="glyphicon glyphicon-wrench"></i>', ['class' => 'btn btn-default', 'onclick' => new JsExpression("jQuery('#grid-config-modal-{$this->id}').modal('show')")]);
	}

	/**
	 * @param object|array $column
	 * @return Column
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private function getColumn(object|array $column):Column {
		if (is_object($column)) {
			return $column;
		}
		if (is_array($column)) {
			return Yii::createObject(array_merge([//конфигурируем модель колонки. Это может быть любой потомок класса Column, в котором есть своя реализация получения лейбла
				'class' => ArrayHelper::getValue($column, 'class', DataColumn::class),
				'grid' => $this->grid//из переданного имени класса грида генерируем модель этого грида - она нужна модели колонки
			], $column));
		}
		throw new InvalidConfigException('Конфигурация колонок грида задана неверно');
	}

	/**
	 * Метод, пытающийся из параметров колонки выудить такое же название, какое будет отображать грид
	 * @param object|array $column
	 * @return string|null
	 * @throws Throwable
	 */
	private function getColumnLabel(object|array $column):?string {
		try {
			$columnModel = $this->getColumn($column);
			if (is_a($columnModel, ActionColumn::class)) {
				/** @var ActionColumn $columnModel */
				return $columnModel->header;//возвращаем заголовок для ActionColumn
			}
			if (null === $getHeaderCellLabelReflectionMethod = ReflectionHelper::setAccessible($columnModel, 'getHeaderCellLabel')) return null;//поскольку метод getHeaderCellLabel приватный, мы рефлексией хачим его доступность
			return (empty($label = $getHeaderCellLabelReflectionMethod->invoke($columnModel)) || '&nbsp;' === $label)?null:$label;//вызываем похаченный метод. Если имя колонки пустое, нужно вернуть null - вышестоящий метод подставит туда числовой идентификатор
		} /** @noinspection BadExceptionsProcessingInspection */ catch (Throwable) {//если на каком-то этапе возникла ошибка, нужно фаллбечить
			return null;
		}
	}

	/**
	 * Метод, возвращающий атрибут, с которым связана колонка, если есть
	 * @param object|array $column
	 * @return string|null
	 */
	private function getColumnAttribute(object|array $column):?string {
		try {
			$columnModel = $this->getColumn($column);
			if (is_a($columnModel, ActionColumn::class)) {
				return null;//У ActionColumn нет атрибута
			}
			/** @var DataColumn $column */
			return $column->attribute;
		} /** @noinspection BadExceptionsProcessingInspection */ catch (Throwable) {//если на каком-то этапе возникла ошибка, нужно фаллбечить
			return null;
		}
	}

	/**
	 * @return array|null
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private function getDefaultAttributesLabels():?array {
		if (null === $this->_defaultAttributes) return null;
		return array_intersect_key($this->getColumnsLabels(), array_flip($this->_defaultAttributes));
	}

	/**
	 * Returns an array of all attributes labels in ['attribute' => 'label'] format
	 * @return string[]
	 * @throws InvalidConfigException
	 * @throws Throwable
	 */
	private function getColumnsLabels():array {
		$result = [];
		foreach ($this->columns as $column) {
			$columnModel = $this->getColumn($column);
			if (is_a($columnModel, ActionColumn::class)) {
				$result[static::ACTION_COLUMN_ATTRIBUTE] = $columnModel->header;
			} else {
				if ((null !== $columnAttribute = $this->getColumnAttribute($columnModel))) {
					$result[$columnAttribute] = $this->getColumnLabel($columnModel);
				}
			}

		}
		return $result;
	}

	/**
	 * @return Column[]
	 * @throws Throwable
	 */
	public function getVisibleColumns():array {
		if (null === $this->visibleColumnsLabels) {
			$this->visibleColumnsLabels = $this->getDefaultAttributesLabels()??array_keys($this->columns);
		}
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
		$this->_pageSize = $pageSize < $this->_minPageSize ? $this->_minPageSize : ($pageSize > $this->_maxPageSize ? $this->_maxPageSize : $pageSize);
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
			return $this->_id??throw new InvalidConfigException('Нужно указать id, либо указать привязку к GridView.');
		}
		/** @var object $gridClassName */
		$gridClassName = (new ReflectionClass($this->grid))->getName();
		if ($this->grid->id === $gridClassName::$autoIdPrefix.($gridClassName::$counter - 1)) {//ima fukkin haxxor
			if (null === $this->_id) {
				throw new InvalidConfigException('Нужно задать параметр id для конфигурируемого GridView, либо для GridConfig');
			}
			$this->grid->id = $this->_id;

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
		$this->_visibleColumnsLabels = array_map(static fn($label):string => trim($label, '.'), $visibleColumnsLabels);
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
	 * @return int
	 */
	public function getMinPageSize():int {
		return $this->_minPageSize;
	}

	/**
	 * @return int
	 */
	public function getMaxPageSize():int {
		return $this->_maxPageSize;
	}

	/**
	 * @param int $minPageSize
	 */
	public function setMinPageSize(int $minPageSize):void {
		$this->_minPageSize = $minPageSize;
	}

	/**
	 * @param int $maxPageSize
	 */
	public function setMaxPageSize(int $maxPageSize):void {
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
	 * @noinspection PhpUndefinedFieldInspection
	 */
	public function setFloatHeader(?bool $floatHeader):void {
		$this->_floatHeader = $floatHeader;
		if ($this->_gridPresent && null !== $this->_floatHeader && false !== $this->grid->hasProperty('floatHeader')) {
			$this->grid->floatHeader = $this->_floatHeader;
		}
	}

	/**
	 * @return bool|null
	 */
	public function getFilterOnFocusOut():?bool {
		return $this->_filterOnFocusOut;
	}

	/**
	 * @param bool|null $filterOnFocusOut
	 */
	public function setFilterOnFocusOut(?bool $filterOnFocusOut):void {
		$this->_filterOnFocusOut = $filterOnFocusOut;
		if ($this->_gridPresent && null !== $this->_filterOnFocusOut) {
			$this->grid->filterOnFocusOut = $this->_filterOnFocusOut;
		}
	}

	/**
	 * @return string[]|null
	 */
	public function getDefaultAttributes():?array {
		return $this->_defaultAttributes;
	}

	/**
	 * @param string[]|null $defaultAttributes
	 */
	public function setDefaultAttributes(?array $defaultAttributes):void {
		$this->_defaultAttributes = $defaultAttributes;
	}

}
