<?php
/**
 * @author Harry Tang <harry@modernkernel.com>
 * @link https://modernkernel.com
 * @copyright Copyright (c) 2016 Modern Kernel
 */


namespace modernkernel\billing\controllers;


use common\models\Setting;
use Exception;
use modernkernel\billing\models\Invoice;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use yii\web\HttpException;

/**
 * Class PaypalController
 * @package modernkernel\billing\controllers
 */
class PaypalController extends Controller
{
    protected $apiContext = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if (Setting::getValue('paypalSandbox') == '1') {
            $client = Setting::getValue('paypalSandboxClientID');
            $secret = Setting::getValue('paypalSandboxSecret');
            $mode = 'sandbox';
        } else {
            $client = Setting::getValue('paypalClientID');
            $secret = Setting::getValue('paypalSecret');
            $mode = 'live';
        }
        if (isset($client, $secret, $mode)) {
            $this->apiContext = new ApiContext(
                new OAuthTokenCredential(
                    $client,
                    $secret
                )
            );
            $this->apiContext->setConfig([
                'mode' => $mode,
            ]);
        }
        parent::init(); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     * @param \yii\base\Action $action
     * @return bool
     * @throws InvalidConfigException
     */
    public function beforeAction($action)
    {
        if (empty($this->apiContext)) {
            throw new InvalidConfigException(Yii::$app->getModule('billing')->t('Unable to verify Paypal API configuration.'));
        }
        return parent::beforeAction($action); // TODO: Change the autogenerated stub
    }

    /**
     * create payment
     * @param string $id
     * @return \yii\web\Response
     * @throws HttpException
     */
    public function actionCreate($id)
    {
        $invoice = Invoice::findOne($id);
        if ($invoice->status == Invoice::STATUS_PENDING) {
            /* payer */
            $payer = new Payer();
            $payer->setPaymentMethod("paypal");

            /* items */
            $items = [];
            foreach ($invoice->items as $i => $item) {
                $items[$i] = new Item();
                $items[$i]->setName($item->name)
                    ->setCurrency($invoice->currency)
                    ->setQuantity($item->quantity)
                    ->setSku($item->id)// Similar to `item_number` in Classic API
                    ->setPrice($item->price);
            }

            /* item list */
            $itemList = new ItemList();
            $itemList->setItems($items);

            /* invoice details */
            $details = new Details();
            $details
                ->setShipping($invoice->shipping)
                ->setTax($invoice->tax)
                ->setSubtotal($invoice->subtotal);

            $amount = new Amount();
            $amount->setCurrency($invoice->currency)
                ->setTotal($invoice->total)
                ->setDetails($details);

            $transaction = new Transaction();
            $transaction->setAmount($amount)
                ->setItemList($itemList)
                ->setDescription(Yii::$app->getModule('billing')->t('Invoice #' . $invoice->id))
                ->setInvoiceNumber($invoice->id);


            $urls = new RedirectUrls();
            $urls->setReturnUrl(Yii::$app->urlManager->createAbsoluteUrl(['/billing/paypal/return', 'id' => $invoice->id]))
                ->setCancelUrl(Yii::$app->urlManager->createAbsoluteUrl(['/billing/invoice/show', 'id' => $invoice->id, 'cancel' => 'true']));

            $payment = new Payment();
            $payment->setIntent('sale')
                ->setPayer($payer)
                ->setRedirectUrls($urls)
                ->setTransactions([$transaction]);

            /* call */
            try {
                $payment->create($this->apiContext);
                $approvalUrl = $payment->getApprovalLink();
                return $this->redirect($approvalUrl);
            } catch (Exception $ex) {
                throw new HttpException(500, Yii::$app->getModule('billing')->t('Paypal API Error: {ERROR}', ['ERROR' => $ex->getMessage()]));
            }


        } else {
            Yii::$app->session->setFlash('error', Yii::$app->getModule('billing')->t('We can not process your payment right now.'));
            return $this->redirect(Yii::$app->urlManager->createUrl(['/billing/invoice/show', 'id' => $id]));
        }
    }


    /**
     * paypal return
     * @param string $id
     * @param string $paymentId
     * @param string $PayerID
     * @param string $token
     * @return \yii\web\Response
     * @throws HttpException
     */
    public function actionReturn($id, $paymentId, $PayerID, $token)
    {
        $invoice = Invoice::findOne($id);
        if ($invoice->status == Invoice::STATUS_PENDING) {
            $payment = Payment::get($paymentId, $this->apiContext);
            $execution = new PaymentExecution();
            $execution->setPayerId($PayerID);

            try {
                $result = $payment->execute($execution, $this->apiContext);
                /* $transaction id */
                $transactions = $payment->getTransactions();
                $relatedResources = $transactions[0]->getRelatedResources();
                $sale = $relatedResources[0]->getSale();
                $saleId = $sale->getId();
                /* update invoice */
                if (isset($result->state) && $result->state == 'approved') {
                    $invoice->status = Invoice::STATUS_PAID;
                    $invoice->payment_method = 'Paypal';
                    $invoice->payment_date = time();
                    $invoice->transaction = $saleId;
                    $invoice->save();
                    Yii::$app->session->setFlash('success', Yii::$app->getModule('billing')->t('Thank you for your payment. Your transaction has been completed.'));
                    unset($token);
                }
                return $this->redirect(Yii::$app->urlManager->createUrl(['/billing/invoice/show', 'id' => $invoice->id]));
            } catch (Exception $ex) {
                throw new HttpException(500, Yii::$app->getModule('billing')->t('Paypal API Error: {ERROR}', ['ERROR' => $ex->getMessage()]));
            }
        } else {
            Yii::$app->session->setFlash('error', Yii::$app->getModule('billing')->t('We can not process your payment right now.'));
            return $this->redirect(Yii::$app->urlManager->createUrl(['/billing/invoice/show', 'id' => $id]));
        }

    }
}


