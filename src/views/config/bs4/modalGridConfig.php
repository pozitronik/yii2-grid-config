<?php
declare(strict_types = 1);

/**
 * @var GridConfig $model
 * @var View $this
 */

use pozitronik\grid_config\GridConfig;
use yii\bootstrap4\Modal;
use yii\helpers\Html;
use yii\web\View;

?>

<?php Modal::begin([
	'id' => "grid-config-modal-{$model->grid->id}",
	'title' => '<div class="modal-title">Конфигурация:</div>',
	'footer' => Html::submitButton('<i class="glyphicon glyphicon-save"></i> Сохранить', ['class' => 'btn btn-success', 'form' => 'grid-config']),//post button outside the form
	'clientOptions' => ['backdrop' => false]
]); ?>
<?= $this->render("../GridCongForm", compact('model')) ?>
<?php Modal::end(); ?>
