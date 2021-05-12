<?php
declare(strict_types = 1);

/**
 * @var GridConfig $model
 * @var View $this
 */

use pozitronik\grid_config\GridConfig;
use pozitronik\grid_config\GridConfigAssets;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\web\View;

GridConfigAssets::register($this);
?>

<?php Modal::begin([
	'id' => "grid-config-modal-{$model->grid->id}",
	'header' => '<div class="modal-title">Конфигурация:</div>',
	'footer' => Html::submitButton('<i class="glyphicon glyphicon-save"></i> Сохранить', ['class' => 'btn btn-success', 'form' => 'grid-config']),//post button outside the form
	'clientOptions' => ['backdrop' => false]
]); ?>
<?= $this->render("../GridConfigForm", compact('model')) ?>
<?php Modal::end(); ?>
