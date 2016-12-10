<?php
/**
 * @author Harry Tang <harry@modernkernel.com>
 * @link https://modernkernel.com
 * @copyright Copyright (c) 2016 Modern Kernel
 */

use common\models\Setting;
use harrytang\hosting\models\search\Invoice;
use modernkernel\fontawesome\Icon;
use yii\bootstrap\ButtonDropdown;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $info [] */
/* @var $model modernkernel\billing\models\Invoice */

$this->params['breadcrumbs'][] = ['label' => Yii::t('billing', 'Invoices'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

/* misc */
//$js=file_get_contents(__DIR__.'/index.min.js');
//$this->registerJs($js);
//$css=file_get_contents(__DIR__.'/index.css');
//$this->registerCss($css);
?>
<div class="invoice-view">
    <div class="hidden box box-info">
        <div class="box-body">
            <div class="table-responsive">
                <?= DetailView::widget([
                    'model' => $model,
                    'attributes' => [
                        'id',
                        'id_account',
                        'subtotal',
                        'discount',
                        'tax',
                        'total',
                        'currency',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ]) ?>
            </div>
            <p>
                <?= Html::a(Yii::t('billing', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('billing', 'Delete'), ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data' => [
                        'confirm' => Yii::t('billing', 'Are you sure you want to delete this item?'),
                        'method' => 'post',
                    ],
                ]) ?>
            </p>
        </div>
    </div>

    <section class="invoice">
        <!-- title row -->
        <div class="row">
            <div class="col-xs-12">
                <h2 class="page-header">
                    <img src="/images/logo-mini.svg" class="img-responsive"
                         style="max-height: 24px; vertical-align: bottom; display: inline-block"
                         alt="<?= Yii::$app->name ?>"/> <?= Yii::$app->name ?>
                    <small class="pull-right"><?= Yii::$app->getModule('billing')->t('Date: {DATE}', ['DATE' => Yii::$app->formatter->asDate($model->created_at)]) ?></small>
                </h2>
            </div>
            <!-- /.col -->
        </div>
        <!-- info row -->
        <div class="row invoice-info">
            <div class="col-sm-4 invoice-col">
                <?= Yii::$app->getModule('billing')->t('From') ?>
                <address>
                    <div><strong><?= Setting::getValue('merchantName') ?></strong></div>
                    <div><?= Setting::getValue('merchantAddress') ?></div>
                    <div><?= Setting::getValue('merchantCity') ?><?= !empty($state = Setting::getValue('merchantState')) ? ', ' . $state : '' ?><?= !empty($zip = Setting::getValue('merchantZip')) ? ', ' . $zip : '' ?><?= !empty($country = Setting::getValue('merchantCountry')) ? ', ' . $country : '' ?></div>
                    <div><?= Yii::$app->getModule('billing')->t('Phone:') ?> <?= Setting::getValue('merchantPhone') ?></div>
                    <div><?= Yii::$app->getModule('billing')->t('Email:') ?> <?= Setting::getValue('merchantEmail') ?></div>
                </address>
            </div>
            <!-- /.col -->
            <div class="col-sm-4 invoice-col">
                <?= Yii::$app->getModule('billing')->t('To') ?>
                <address>
                    <div><strong><?= $info['f_name'] ?> <?= $info['l_name'] ?></strong></div>
                    <?php if (!empty($info['address'])): ?>
                        <div><?= $info['address'] ?></div><?php endif; ?>
                    <?php if (!empty($info['address2'])): ?>
                        <div><?= $info['address2'] ?></div><?php endif; ?>
                    <?php if (!empty($info['city'])): ?>
                        <div><?= $info['city'] ?><?= !empty($info['state']) ? ', ' . $info['state'] : '' ?><?= !empty($info['zip']) ? ', ' . $info['zip'] : '' ?><?= !empty($info['country']) ? ', ' . $info['country'] : '' ?></div><?php endif; ?>
                    <div><?= Yii::$app->getModule('billing')->t('Phone:') ?> <?= $info['phone'] ?></div>
                    <div><?= Yii::$app->getModule('billing')->t('Email:') ?> <?= $info['email'] ?></div>
                </address>
            </div>
            <!-- /.col -->
            <div class="col-sm-4 invoice-col">
                <div><b><?= $this->title ?></b></div>
                <div><br/></div>
                <div><b><?= Yii::$app->getModule('billing')->t('Account:') ?></b> <?= $model->account->id ?></div>
                <div class="<?= empty($model->payment_method)?'hidden':'' ?>"><b><?= $model->getAttributeLabel('payment_method') ?>: </b> <?= $model->payment_method ?></div>
                <div class="<?= empty($model->payment_date)?'hidden':'' ?>"><b><?= $model->getAttributeLabel('payment_date') ?>: </b> <?= Yii::$app->formatter->asDate($model->payment_date) ?></div>
                <div class="<?= empty($model->transaction)?'hidden':'' ?>"><b><?= $model->getAttributeLabel('transaction') ?>: </b> <?= $model->transaction ?></div>
                <div class="no-print">
                    <b><?= Yii::$app->getModule('billing')->t('Status:') ?></b> <?= $model->statusText ?></div>
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <!-- Table row -->
        <div class="row">
            <div class="col-xs-12 table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th><?= Html::encode('#') ?></th>
                        <th><?= Yii::$app->getModule('billing')->t('Product') ?></th>
                        <th><?= Yii::$app->getModule('billing')->t('Price') ?></th>
                        <th><?= Yii::$app->getModule('billing')->t('Subtotal') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($model->items as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= $item->name ?> <span
                                        class="badge <?= $item->quantity == 1 ? 'hidden' : '' ?>"><?= $item->quantity ?></span>
                            </td>
                            <td><?= Yii::$app->formatter->asCurrency($item->price, $model->currency) ?></td>
                            <td><?= Yii::$app->formatter->asCurrency($item->quantity * $item->price, $model->currency) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->

        <div class="row">
            <!-- accepted payments column -->
            <div class="col-xs-6">
                <p class="lead" style="margin-bottom: 0">
                    <?= Yii::$app->getModule('billing')->t('Online Methods:') ?>
                </p>
                <div class="text-muted" style="font-size: 2.0em;">
                    <?= Icon::widget(['icon' => 'cc-paypal']) ?>
                    <?= Icon::widget(['icon' => 'cc-visa']) ?>
                    <?= Icon::widget(['icon' => 'cc-mastercard']) ?>
                    <?= Icon::widget(['icon' => 'cc-amex']) ?>
                    <?= Icon::widget(['icon' => 'cc-discover']) ?>
                </div>
                <p class="lead" style="margin-bottom: 0">
                    <?= Yii::$app->getModule('billing')->t('Bank Transfer:') ?>
                </p>
                <p class="text-muted well well-sm no-shadow" style="margin-top: 10px;">
                    <?= nl2br(Setting::getValue('merchantBank')) ?>
                </p>
            </div>
            <!-- /.col -->
            <div class="col-xs-6">

                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                        <tr>
                            <th style="width:50%"><?= Yii::$app->getModule('billing')->t('Subtotal:') ?></th>
                            <td><?= Yii::$app->formatter->asCurrency($model->subtotal, $model->currency) ?></td>
                        </tr>
                        <tr>
                            <th><?= Yii::$app->getModule('billing')->t('Discount:') ?></th>
                            <td><?= Yii::$app->formatter->asCurrency($model->discount, $model->currency) ?></td>
                        </tr>
                        <tr>
                            <th><?= Yii::$app->getModule('billing')->t('Tax:') ?></th>
                            <td><?= Yii::$app->formatter->asCurrency($model->tax, $model->currency) ?></td>
                        </tr>
                        <tr>
                            <th><?= Yii::$app->getModule('billing')->t('Total:') ?></th>
                            <td><?= Yii::$app->formatter->asCurrency($model->total, $model->currency) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="text-center no-print">
                <?php if (in_array($model->status, [Invoice::STATUS_PENDING])): ?>
                    <?=
                    ButtonDropdown::widget([
                        'containerOptions' => ['class' => ''],
                        'options' => ['class' => 'btn btn-success'],
                        'label' => Yii::$app->getModule('billing')->t('Pay Now'),
                        'dropdown' => [
                            'items' => [
                                ['label' => 'Paypal / Credit Card', 'url' => '#paypal'],
                                ['label' => 'Bank Wire', 'url' => '#bank'],
                            ],
                        ],
                    ]);
                    ?>
                <?php endif; ?>
                </div>
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->


    </section>
</div>