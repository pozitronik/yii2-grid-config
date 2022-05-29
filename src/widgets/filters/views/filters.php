<?php
declare(strict_types = 1);
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\web\JsExpression;
use yii\web\View;

/**
 * @var View $this
 * @var array $filters
 * @var string $gridId
 */
?>
<div class="filters-widget-<?= $gridId?>">
<?= Select2::widget([
	'data' => $filters,
]) ?>
	<?= Html::button($this->isBs(4)
	?'<i class="fas fa-save"></i>'
	:'<i class="glyphicon glyphicon-save"></i>', ['class' => 'btn btn-default', 'onclick' => new JsExpression("jQuery('console.log(this)")]); ?>
</div>
