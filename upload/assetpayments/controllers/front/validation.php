<?php

include(__DIR__.'/../../assetpayments.cls.php');

class AssetPaymentsValidationModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        parent::postProcess();

        global $cookie, $link;

        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $language = Language::getIsoById((int)$cookie->id_lang);
        $language = (!in_array($language, array('ua', 'en', 'ru'))) ? 'ru' : $language;
        $language = strtoupper($language);

        $currency = new CurrencyCore($cart->id_currency);
        $payCurrency = $currency->iso_code;
        $asset = new AssetPayments();
        $assetCls = new AssetPaymentsCls();
        $total = $cart->getOrderTotal();

        $option = array();
		
		//****Required variables****//	
		$option['TemplateId'] = $asset->getOption('template_id');
		$option['CustomMerchantInfo'] = 'PrestaShop '.(defined('_PS_VERSION_')?_PS_VERSION_:'');
		$option['MerchantInternalOrderId'] = $cart->id;
		$option['StatusURL'] = $link->getModuleLink('assetpayments', 'callback');
		$option['ReturnURL'] = $link->getModuleLink('assetpayments', 'result');
		$option['IpAddress'] = '';
		$option['AssetPaymentsKey'] = $asset->getOption('merchant');
		$option['Amount'] = $total;
		$option['Currency'] = $payCurrency;

        //****Customer data and address****//
		
		$address = new AddressCore($cart->id_address_invoice);
        if ($address) {
            $customer = new CustomerCore($address->id_customer);
			$country_iso = Country::getIsoById($address->id_country);
			if ($country_iso == '' || strlen($country) > 3) {
				$country_iso = 'USA';
			}
            
            $option['FirstName'] = $address->firstname .' ' . $address->lastname;
            $option['LastName'] = $address->lastname;
            $option['Email'] = $customer->email;
            $option['Phone'] = $address->phone;
            $option['City'] = $address->city;
            $option['Address'] = $address->address1 . ', ' . $address->address2 . ', ' . $address->city . ', ' . $country_iso;
			$option['CountryISO'] = $country_iso;
        }
		
		//****Adding cart details****//
		foreach ($cart->getProducts() as $product) {
			
			$anyproduct = new Product($product['id_product'], true, $this->context->language->id, $this->context->shop->id);
			$images = $anyproduct->getImages($this->context->language->id); 
			$list_image = array();
				foreach ($images as $img) {
					$image['cover'] = (bool)$img['cover'];
					$image['url'] = $this->context->link->getImageLink($anyproduct->link_rewrite, $img['id_image'], 'home_default');
					$image['position'] = $img['position'];
					array_push($list_image,$image);
				}			
			
		$option['Products'][] = array(
				'ProductId' => $product['id_product'],
				'ProductName' => str_replace(["'", '"', '&#39;'], ['', '', ''], htmlspecialchars_decode($product['name'])),
				'ProductPrice' => $product['total_wt'],
				'ProductItemsNum' => $product['quantity'],
				'ImageUrl' => $this->context->link->getImageLink($anyproduct->link_rewrite, $img['id_image'], 'home_default'),
			);
			$order_total += $product['total_wt'] * $product['quantity'];
		}	
			
		//****Adding shipping method****//
		$shipping_price = $total - $order_total;
	
		$option['Products'][] = array(
				"ProductId" => '00000',
				"ProductName" => 'Delivery',
				"ProductPrice" => $shipping_price,
				"ImageUrl" => 'https://assetpayments.com/dist/css/images/delivery.png',
				"ProductItemsNum" => 1,
			);
        
		$asset->validateOrder((int)$cart->id, _PS_OS_PREPARATION_, $total, $asset->displayName);
		
        $option['MerchantInternalOrderId'] = $asset->currentOrder;

        $url = AssetPaymentsCls::URL;
		$data = base64_encode( json_encode($option) );

        $this->context->smarty->assign(array('fields' => $data, 'url' => $url));
        $this->setTemplate('module:assetpayments/views/templates/front/redirect.tpl');
    }
}
