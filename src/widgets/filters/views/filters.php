<?php
declare(strict_types = 1);
use kartik\bs4dropdown\ButtonDropdown;
use kartik\select2\Select2;
use yii\web\JsExpression;
use yii\web\View;

/**
 * @var View $this
 * @var array $filters
 * @var string $gridId
 */
?>
<div class="filters-widget-<?= $gridId ?> float-left w-25">
	<?= Select2::widget([
		'name' => "filters-widget-select-{$gridId}",
		'data' => $filters,
		'options' => [
			'class' => 'float:left'
		],
		'addon' => [
			'append' => [
				'content' => ButtonDropdown::widget([
					'encodeLabel' => false,
					'label' => '<i class="fa fa-filter"></i>',
					'dropdown' => [
						'items' => [
							[
								'label' => '<i class="fas fa-save"></i> Сохранить текущий',
								'url' => '#',
								'linkOptions' => [
									'onclick' => new JsExpression("")
								],
								'encode' => false,
							],
							[
								'label' => '<i class="fas fa-check"></i> Применить выбранный',
								'url' => '#',
								'linkOptions' => [
									'onclick' => new JsExpression("")
								],
								'encode' => false],
							[
								'label' => '<i class="fas fa-minus"></i> Удалить выбранный',
								'url' => '#',
								'linkOptions' => [
									'onclick' => new JsExpression("")
								],
								'encode' => false
							]
						]
					]
				]),
				'asButton' => true
			]
		]
	]) ?>
</div>
