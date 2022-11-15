<?php
class UnipaymentPaymentModuleFrontController extends ModuleFrontController
{

    public $ssl = true;
    public $isLogged = false;
    public $display_column_left = false;
    public $display_column_right = false;
    public $service;
    protected $ajax_refresh = false;
    protected $css_files_assigned = array();
    protected $js_files_assigned = array();
    
    public function __construct()
    {
		
        $this->controller_type = 'modulefront';
        
        $this->module = Module::getInstanceByName(Tools::getValue('module'));
        if (! $this->module->active) {
            Tools::redirect('index');
        }
        $this->page_name = 'module-' . $this->module->name . '-' . Dispatcher::getInstance()->getController();
		
        
        parent::__construct();
    }

    public function postProcess()
    {
		
		$Unipayment = new Unipayment();
		$pay_currency = empty($_POST['pay_currency']) ? '' : $_POST['pay_currency'];		
		$url = $Unipayment->getUrl($pay_currency);
		Tools::redirect(Tools::safeOutput($url, ''));				                                
    }
    

}
