<?php

require_once(dirname(__FILE__) . '../../../assetpayments.php');
require_once(dirname(__FILE__) . '../../../assetpayments.cls.php');

	class AssetPaymentsCallbackModuleFrontController extends ModuleFrontController
	{
		public $display_column_left = false;
		public $display_column_right = false;
		public $display_header = false;
		public $display_footer = false;
		public $ssl = true;

		/**
		 * @see FrontController::postProcess()
		 */
		public function postProcess()
		{    
			$data = json_decode(file_get_contents("php://input"), true);
			$order = new OrderCore(intval($data['Order']['OrderId']));    

			$asset = new AssetPayments();
			$key = $asset->getOption('merchant');
			$secret = $asset->getOption('secret_key');
			
			$transactionId = $data['Payment']['TransactionId'];
			$signature = $data['Payment']['Signature'];
			$status = $data['Payment']['StatusCode'];
			
			$requestSign =$key.':'.$transactionId.':'.strtoupper($secret);
			$sign = hash_hmac('md5',$requestSign,$secret);
			
			$AssetPaymentsCls = new AssetPaymentsCls(); 
			
			if ($status == 1 && $sign == $signature) {				           

				list($orderId,) = explode(AssetPaymentsCls::ORDER_SEPARATOR, $data['Order']['OrderId']);
				$history = new OrderHistory();
				$history->id_order = $orderId;
				$history->changeIdOrderState((int)Configuration::get('PS_OS_PAYMENT'), $orderId);
				$history->addWithemail(true, array(
					'order_name' => $orderId
				)); 
				
			} 
			if ($status == 2 && $sign == $signature) {
				list($orderId,) = explode(AssetPaymentsCls::ORDER_SEPARATOR, $data['Order']['OrderId']);
				$history = new OrderHistory();
				$history->id_order = $orderId;
				$history->changeIdOrderState((int)Configuration::get('PS_OS_ERROR'), $orderId);
			}

			echo $AssetPaymentsCls->getAnswerToGateWay($data);
			exit();			
		}
	}