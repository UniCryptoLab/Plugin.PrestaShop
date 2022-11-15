<?php
/**
 * UNIPAYMENT
 * Copyright (c) 2019-2020 Forging Technologies, Inc. MIT license
 */
require_once(dirname(__FILE__) . '/vendor/autoload.php');	

use PrestaShop\PrestaShop\Core\Payment\PaymentOption; 

if (!defined('_PS_VERSION_'))
    exit;


class Unipayment extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'unipayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.2';
        $this->author = 'Unipayment';
        $this->need_instance = 1;
        $this->bootstrap = true;       
		
		
        $this->ps_versions_compliancy = array(
            'min' => '1.7',
            'max' => _PS_VERSION_
        );


        parent::__construct();
		
		$this->meta_title = $this->l('unipayment');
		$this->displayName = 'unipayment';
        $this->description = $this->l('UniPayment Gateway PrestaShop');	
		
		
		$this -> client_id = Configuration::get('UNIPAYMENT_CLIENT_ID');
		$this -> client_secret = Configuration::get('UNIPAYMENT_CLIENT_SECRET');		
		$this -> app_id = Configuration::get('UNIPAYMENT_APP_ID');
		
		$this -> IsSandbox = (Configuration::get('UNIPAYMENT_ENV') == 'live') ?false : true;
		$this -> uniPaymentClient = new \UniPayment\Client\UniPaymentClient();
		$this -> uniPaymentClient->getConfig()->setDebug(false);
		$this->uniPaymentClient->getConfig()->setIsSandbox($this -> IsSandbox);						

    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('adminOrder')            
        ) {
            return false;
        }

        return true;

    }
	
	
    public function hookPaymentOptions($params)
    {
        return $this->unipaymentPaymentOptions($params);
    }
	public function update_payment($cart_id, $invoice_id, $amount){
		$cart = new Cart((int)$cart_id);	
		
		$extra_vars['transaction_id'] = $invoice_id;
		$new_order_status = Configuration::get('PS_OS_PAYMENT');
		$this->validateOrder((int)$cart_id, (int)$new_order_status, (float)$amount, $this->displayName, null, $extra_vars, null, false, $cart->secure_key);										
	}
		

	public function returnsuccess($params){

		$notify_json = file_get_contents('php://input');
		$notify_ar = json_decode($notify_json, true);
		$cart_id =  $notify_ar['order_id'];
		$invoice_id =  $notify_ar['invoice_id'];		
		
		$this->uniPaymentClient->getConfig()->setClientId($this->client_id);
		$this->uniPaymentClient->getConfig()->setClientSecret($this->client_secret);
		$response = $this->uniPaymentClient->checkIpn($notify_json);			   
		$status = 'New';	
		
		if ($response['code'] == 'OK'){			
			$error_status = $notify_ar['error_status'];
			$status = $notify_ar['status'];						
			$amount = $notify_ar['price_amount'];
		}
		
				
		$processing_status = Configuration::get('UNIPAYMENT_PR_STATUS');					

		switch ($status) {
			case 'New':
				{					
					break;
				}
			case 'Paid': 				
				{
					if($processing_status == $status) $this->update_payment($cart_id, $invoice_id, $amount);
					$info_string  = 'Invoice : '.$invoice_id.' transaction detected on blockchain';
					error_log("    [Info] $info_string");																			
					break;
				}
                    
			case 'Confirmed':
				{
					if($processing_status == $status) $this->update_payment($cart_id, $invoice_id, $amount);
					$info_string  = 'Invoice : '.$invoice_id.' has changed to confirmed';
					error_log("    [Info] $info_string");															
					break;
				}
			case 'Complete':
				{
					
					if($processing_status == $status) $this->update_payment($cart_id, $invoice_id, $amount);
					$info_string  = 'Invoice : '.$invoice_id.' has changed to complete';
					error_log("    [Info] $info_string");										
					break;	
				}
				
                    
			case 'Invalid':
				{
					$error_string  = 'Invoice : '.$invoice_id.' has changed to invalid because of network congestion, please check the dashboard';
					error_log("    [Warning] $error_string");					
					break;				
				}
			case 'Expired':
				{
					$error_string  = 'Invoice : '.$invoice_id.' has chnaged to expired';
					if ($this->handle_expired_status == 1) {					
						$cart = new Cart((int)$cart_id);			
						$extra_vars['transaction_id'] = $invoice_id;
						$fail_order_status = Configuration::get('PS_OS_ERROR');
						$this->validateOrder((int)$cart_id, (int)$fail_order_status, (float)$amount, $this->displayName, null, $extra_vars, null, false, $cart->secure_key);		
					}
					
					error_log("    [Warning] $error_string");					
					break;                    
				}
			default:
				{
					error_log('    [Info] IPN response is an unknown message type. See error message below:');
					$error_string = 'Unhandled invoice status: ' . $payment_status;
					error_log("    [Warning] $error_string");
                }
		}
		 			
		
	}
	

	
    /**
     * Uninstall and clean the module settings
     *
     * @return	bool
     */
    public function uninstall()
    {
        parent::uninstall();

        Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'module_country` WHERE `id_module` = '.(int)$this->id);

        return (true);
    }
	
	public  function get_currencies ($fiat = false)		
    {	  
		if (empty($this -> uniPaymentClient))	 {
			$isSandbox = (Configuration::get('UNIPAYMENT_ENV') == 'live') ? false : true;
		  	$this -> uniPaymentClient = \UniPayment\Client\UniPaymentClient();
			$this -> uniPaymentClient->getConfig()->setDebug(false);
			$this->uniPaymentClient->getConfig()->setIsSandbox($isSandbox);
		};
		
		$currencies = array();	  		  	
		$apires = $this->uniPaymentClient->getCurrencies();
		if ($apires['code'] == 'OK') {
			foreach ($apires['data'] as $crow){
				if ($crow['is_fiat'] == $fiat) $currencies[$crow['code']] = $crow['code'];			 
			}		
		}
		return $currencies;
    }
	
	
    public function getContent()
    {
		if (Tools::isSubmit('submit' . $this->name)) {			
			$unipayment_name = Tools::getValue('unipayment_name');
			$saveOpt = false;
			$err_msg = '';
			if (empty(Tools::getValue('unipayment_client_id'))) $err_msg = 'Client ID must have value';			
			if (empty(Tools::getValue('unipayment_client_secret'))) $err_msg = 'Client Secret must have value';				
			if (empty(Tools::getValue('unipayment_app_id'))) $err_msg = 'Payment App ID must have value';
				
		
			
		if (empty($err_msg)) $saveOpt = true;
			
        if ($saveOpt)
		{							
			
			Configuration::updateValue('UNIPAYMENT_CLIENT_ID', pSQL(Tools::getValue('unipayment_client_id')));
			Configuration::updateValue('UNIPAYMENT_CLIENT_SECRET', pSQL(Tools::getValue('unipayment_client_secret')));						
			Configuration::updateValue('UNIPAYMENT_APP_ID', pSQL(Tools::getValue('unipayment_app_id')));
			
			Configuration::updateValue('UNIPAYMENT_C_SPEED', pSQL(Tools::getValue('unipayment_confirm_speed')));	
			Configuration::updateValue('UNIPAYMENT_PAY_CCY', pSQL(Tools::getValue('unipayment_pay_currency')));		
			Configuration::updateValue('UNIPAYMENT_PR_STATUS', pSQL(Tools::getValue('unipayment_processing_status')));
			Configuration::updateValue('UNIPAYMENT_HEXP_STATUS', pSQL(Tools::getValue('unipayment_handle_expired_status')));	
			Configuration::updateValue('UNIPAYMENT_ENV', pSQL(Tools::getValue('unipayment_environment')));																		
			$html = '<div class="alert alert-success">'.$this->l('Configuration updated successfully').'</div>';			
		}
		else
		{
				$html = '<div class="alert alert-warning">'.$this->l($err_msg).'</div>';			
		}
        }
		
		$hexp_list =  array('0'=>'No', '1' => 'Yes');
		$env_list =  array('test'=>'SandBox', 'live' => 'Live');		
		$confirm_speeds = array('low'=>'low', 'medium'=>'medium', 'high'=>'high');					
		$processing_statuses = array('Confirmed'=>'Confirmed', 'Complete'=>'Complete');				
		$pay_currencies = array_merge(array('-'=>'---'),$this->get_currencies());
		
		
		$environment = empty(Configuration::get('UNIPAYMENT_ENV')) ? 'test' : Configuration::get('UNIPAYMENT_ENV');
		$confirm_speed = empty(Configuration::get('UNIPAYMENT_C_SPEED')) ? 'medium' : Configuration::get('UNIPAYMENT_C_SPEED');		
		$processing_status = empty(Configuration::get('UNIPAYMENT_PR_STATUS')) ? 'Confirmed' : Configuration::get('UNIPAYMENT_PR_STATUS');
		$pay_currency = empty(Configuration::get('UNIPAYMENT_PAY_CCY')) ? '-' : Configuration::get('UNIPAYMENT_PAY_CCY');
		$handle_expired_status = empty(Configuration::get('UNIPAYMENT_HEXP_STATUS')) ? '1' : Configuration::get('UNIPAYMENT_HEXP_STATUS');		
		
		
			
		
        $data    = array(
            'base_url'    => _PS_BASE_URL_ . __PS_BASE_URI__,
            'module_name' => $this->name,            
			'unipayment_client_id' => Configuration::get('UNIPAYMENT_CLIENT_ID'),		        
			'unipayment_client_secret' => Configuration::get('UNIPAYMENT_CLIENT_SECRET'),
			'unipayment_app_id' => Configuration::get('UNIPAYMENT_APP_ID'),			
            'unipayment_environment' => $environment,	
            'unipayment_confirm_speed' => $confirm_speed,			
			'unipayment_processing_status' => $processing_status,
			'unipayment_pay_currency' => $pay_currency,						
			'unipayment_handle_expired_status' => $handle_expired_status,						
			'unipayment_confirmation' => $html,			
            'env_list' => $env_list,	
			'confirm_speeds' => $confirm_speeds,				
			'processing_statuses' => $processing_statuses,							
			'hexp_list' => $hexp_list,				
			'pay_currencies' => $pay_currencies,							
        );


        $this->context->smarty->assign($data);	
        $output = $this->display(__FILE__, 'tpl/admin.tpl');

        return $output;
    }
	
	
	
    public function unipaymentPaymentOptions($params)
    {

        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = [
            $this->unipaymentExternalPaymentOption(),
        ];
        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function unipaymentExternalPaymentOption()
    {
        $lang = Tools::strtolower($this->context->language->iso_code);
		$pay_currencies = $this->get_currencies();
		$pay_currency = Configuration::get('UNIPAYMENT_PAY_CCY');
		$url = $this->context->link->getModuleLink('unipayment', 'payment');
		
		if (isset($_GET['unipaymenterror'])) $errmsg = $_GET['unipaymenterror'];
        $this->context->smarty->assign(array(
			'action_url' => $url,						            
            'errmsg' => $errmsg,			
			'pay_currency' => $pay_currency,			
			'pay_currencies' => $pay_currencies,
        ));		
		
		
		
        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->l('Pay with Unipayment'))			
            ->setForm($this->context->smarty->fetch('module:unipayment/tpl/payment_infos.tpl'));

        return $newOption;
    }

    public function unipaymentPaymentReturnNew($params)
    {
        
        if ($this->active == false) {
            return;
        }
        $order = $params['order'];
        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }
		
		
        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,					
            'params' => $params,
            'total_to_pay' => Tools::displayPrice($order->total_paid, null, false),
            'shop_name' => $this->context->shop->name,
        ));
        return $this->fetch('module:' . $this->name . '/tpl/order-confirmation.tpl');
    }
	
	
	public function getUrl($pay_currency)
    {        				
		$cart = $this->context->cart;
		$customer = new Customer($cart->id_customer);
		
		
		$amount = number_format($cart->getOrderTotal(true, Cart::BOTH),2);
		$order_id = $cart->id;				
				
		$iaddress = new Address($cart->id_address_invoice);
		$icountry_code = Country::getIsoById($iaddress->id_country) ;
		$ps_currency  = new Currency((int)($cart->id_currency));		
		$currency_code = $ps_currency->iso_code;
		
		
		
		$confirm_speed = Configuration::get('UNIPAYMENT_C_SPEED');		
						
		$returnURL =  _PS_BASE_URL_.__PS_BASE_URI__.'index.php?controller=order-confirmation&id_cart='.(int)$cart->id.'&id_module='.(int)$Unipayment->id.'&id_order='.(int)$order_id.'&key='.$cart->secure_key;	
		
		
		$notifyURL  = $this->context->link->getModuleLink('unipayment', 'notify');
		
		
		$desc = 'Order No : '.$order_id;
		
		$lang = $this->context->language->locale;
		
		$this->uniPaymentClient->getConfig()->setClientId($this->client_id);
		$this->uniPaymentClient->getConfig()->setClientSecret($this->client_secret);		
		
		$createInvoiceRequest = new \UniPayment\Client\Model\CreateInvoiceRequest();
		$createInvoiceRequest->setAppId($this->app_id);
		$createInvoiceRequest->setPriceAmount($amount);
		$createInvoiceRequest->setPriceCurrency($currency_code);
		if (Configuration::get('UNIPAYMENT_P_CCY') != '-'){
			$pay_currency = Configuration::get('UNIPAYMENT_P_CCY');
			$createInvoiceRequest->setPayCurrency($pay_currency);			
		}		
		
		$createInvoiceRequest->setOrderId($order_id);
		$createInvoiceRequest->setConfirmSpeed(Configuration::get('UNIPAYMENT_C_SPEED'));
		$createInvoiceRequest->setRedirectUrl($returnURL);
		$createInvoiceRequest->setNotifyUrl($notifyURL);
		$createInvoiceRequest->setTitle($desc);
		$createInvoiceRequest->setDescription($desc);
		$createInvoiceRequest->setLang($lang);
		$response = $this->uniPaymentClient->createInvoice($createInvoiceRequest);
		
		if ($response['code'] == 'OK'){
			$payurl = $response->getData()->getInvoiceUrl();
			return  $payurl;
		}
		else {
			$errmsg = $response['msg'];	
			
			$checkout_type = Configuration::get('PS_ORDER_PROCESS_TYPE') ? 'order-opc' : 'order';
					$url = (_PS_VERSION_ >= '1.5' ? 'index.php?controller='.$checkout_type.'&' : $checkout_type.'.php?').'step=3&cgv=1&unipaymenterror='.$errmsg.'#unipayment-anchor';
			Tools::redirect($url);				
			exit;			
				
		}     
				
    }


}
