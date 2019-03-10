<?php

require_once(dirname(__FILE__) . '../../../assetpayments.php');
require_once(dirname(__FILE__) . '../../../assetpayments.cls.php');

class AssetPaymentsResultModuleFrontController extends ModuleFrontController
{

    public function postProcess()
    {

        $data = $_POST;
        $order_id = !empty($_GET['OrderId']) ? $_GET['OrderId'] : null;
        $order = new OrderCore(intval($order_id));
		
        if (!Validate::isLoadedObject($order)) {
            die('Заказ не найден');
        }

        $AssetPaymentsCls = new AssetPaymentsCls();
        $customer = new CustomerCore($order->id_customer);
		Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $order->id_cart . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder);
        
    }
}