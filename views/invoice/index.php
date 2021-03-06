<?php

use modernkernel\billing\models\Invoice;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\jui\DatePicker;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel modernkernel\billing\models\InvoiceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */


/* breadcrumbs */
$this->params['breadcrumbs'][] = $this->title;

/* misc */
$this->registerJs('$(document).on("pjax:send", function(){ $(".grid-view-overlay").removeClass("hidden");});$(document).on("pjax:complete", function(){ $(".grid-view-overlay").addClass("hidden");})');
//$js=file_get_contents(__DIR__.'/index.min.js');
//$this->registerJs($js);
//$css=file_get_contents(__DIR__.'/index.css');
//$this->registerCss($css);
?>
<div class="invoice-index">
    <div class="box box-primary">
        <div class="box-body">
            <?php Pjax::begin(); ?>
            <div class="table-responsive">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        //['class' => 'yii\grid\SerialColumn'],

                        'id',
                        //'account.fullname',
                        ['attribute' => 'fullname', 'value' => 'account.fullname'],
                        //['attribute' => 'subtotal', 'value' => function ($model){return Yii::$app->formatter->asCurrency($model->subtotal, $model->currency);}],
                        //['attribute' => 'discount', 'value' => function ($model){return Yii::$app->formatter->asCurrency($model->discount, $model->currency);}],
                        //['attribute' => 'tax', 'value' => function ($model){return Yii::$app->formatter->asCurrency($model->tax, $model->currency);}],
                        ['attribute' => 'total', 'value' => function ($model) {
                            return Yii::$app->formatter->asCurrency($model->total, $model->currency);
                        }],
                        // 'currency',
                        // 'status',
                        // 'created_at',
                        // 'updated_at',
                        ['attribute' => 'created_at', 'value' => 'created_at', 'format' => 'dateTime', 'filter' => DatePicker::widget(['model' => $searchModel, 'attribute' => 'created_at', 'dateFormat' => 'yyyy-MM-dd', 'options' => ['class' => 'form-control']])],
                        ['attribute' => 'status', 'value' => function ($model) {
                            return $model->getStatusColorText();
                        }, 'filter' => Invoice::getStatusOption(), 'format' => 'raw'],
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{view} {update}'
                        ],
                    ],
                ]); ?>
            </div>
            <?php Pjax::end(); ?>
            <p>
                <?= Html::a(Yii::t('billing', 'Create Invoice'), ['create'], ['class' => 'btn btn-success']) ?>
            </p>

        </div>
        <!-- Loading (remove the following to stop the loading)-->
        <div class="overlay grid-view-overlay hidden">
            <?= \modernkernel\fontawesome\Icon::widget(['icon' => 'refresh fa-spin']) ?>
        </div>
        <!-- end loading -->
    </div>
</div>
