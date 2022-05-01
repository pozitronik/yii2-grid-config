<?php
declare(strict_types = 1);

/**
 * @var GridConfig $model
 * @var View $this
 */

use kartik\sortable\Sortable;
use kartik\switchinput\SwitchInput;
use pozitronik\grid_config\GridConfig;
use pozitronik\grid_config\GridConfigAssets;
use yii\web\JsExpression;
use yii\web\View;
use yii\widgets\ActiveForm;

GridConfigAssets::register($this);
?>

<?php $form = ActiveForm::begin(['id' => 'grid-config', 'action' => $model->saveUrl]); ?>
<div class="row">
	<div class="col-md-12">
		<?= $form->field($model, 'id')->hiddenInput()->label(false) ?>
		<?= $form->field($model, 'fromUrl')->hiddenInput()->label(false) ?>
		<?= $form->field($model, 'pageSize')->textInput([
			'type' => 'number',
			'disabled' => null === $model->pageSize,
			'max' => $model->maxPageSize,
			'min' => 0
		]) ?>
		<?= $form->field($model, 'visibleColumnsJson')->hiddenInput(['id' => 'visibleColumnsJson'])->label(false) ?>

		<table class="grid-config-sortables">
			<tr>
				<th>Скрытые колонки</th>
				<th>Видимые колонки</th>
			</tr>
			<tr>
				<td>
					<?= Sortable::widget([
						'connected' => true,
						'items' => $model->hiddenColumnsItems,
						'options' => [
							'id' => 'hiddenColumnsItems'
						],
						'pluginEvents' => [
							/*По документации sortupdate должен вызываться для всех connected-виджетов одновременно, но это не работает (возможно из-за картиковской прослойки)*/
							'sortupdate' => new JsExpression("function(e) {
								updateJSON('#visibleColumnsJson', '#visibleColumnsItems');
							}")
						],
						'pluginOptions' => [
							'acceptFrom' => '#visibleColumnsItems',
						],
//						'itemOptions' => [
//							'onCLick' => new JsExpression("alert(this);")
//						]
					]) ?>
				</td>
				<td>
					<?= Sortable::widget([
						'connected' => true,
						'items' => $model->visibleColumnsItems,
						'options' => [
							'id' => 'visibleColumnsItems'
						],
						'pluginOptions' => [
							'itemSerializer' => new JsExpression("function(serializedItem, sortableContainer) {
								return itemSerializer(serializedItem, sortableContainer);
							}"),
							'acceptFrom' => '#hiddenColumnsItems',
//							'containerSerializer' => new JsExpression("function(serializedContainer) {return null}")
						],
						'pluginEvents' => [
							'sortupdate' => new JsExpression("function(e) {
								updateJSON('#visibleColumnsJson', this);
							}")
						]
					]) ?>
				</td>
			</tr>
		</table>


	</div>
</div>
<div class="row">
	<?php if ($model->grid->hasProperty('floatHeader')): ?>
		<div class="col-md-6">
			<?= $form->field($model, 'floatHeader')->widget(SwitchInput::class, [
				'tristate' => false,
				'pluginOptions' => [
					'size' => 'mini',
					'onText' => '<i class="fa fa-check"></i>',
					'offText' => null
				],
			]) ?>
		</div>
	<?php endif; ?>
	<div class="col-md-6">
		<?= $form->field($model, 'filterOnFocusOut')->widget(SwitchInput::class, [
			'tristate' => false,
			'pluginOptions' => [
				'size' => 'mini',
				'onText' => '<i class="fa fa-check"></i>',
				'offText' => null
			],
		]) ?>
	</div>
</div>
<?php ActiveForm::end(); ?>
