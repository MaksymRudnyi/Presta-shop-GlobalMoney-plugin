<?php
/*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
// die('asfsdfasdfsd');
class GlobalmoneyValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'globalmoney')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer))
			Tools::redirect('index.php?controller=order&step=1');

		$currency = $this->context->currency;

		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$send_sum = (int)($total * 100);

        $config = Configuration::getMultiple(array('GLOBAL_MONEY_DETAILS', 'GLOBAL_MONEY_OWNER','GLOBAL_MONEY_ADDRESS'));

		if ((int)$currency->iso_code_num != 980){
            $kurs = (float)$config['GLOBAL_MONEY_ADDRESS'];
		    $send_sum = (int)(($total * $kurs) * 100);
		}

		$mailVars = array(
			'{globalmoney_owner}' => Configuration::get('GLOBAL_MONEY_OWNER'),
			'{globalmoney_details}' => nl2br(Configuration::get('GLOBAL_MONEY_DETAILS')),
			'{globalmoney_address}' => nl2br(Configuration::get('GLOBAL_MONEY_ADDRESS'))
		);

        $service_id = (int)$config['GLOBAL_MONEY_OWNER'];
        $return_link = $config['GLOBAL_MONEY_DETAILS'];

		$this->module->validateOrder($cart->id, Configuration::get('PS_OS_PREPARATION'), $total, $this->module->displayName);
		Tools::redirect('http://globalmoney.ua/pay-card/?order_num='.$this->module->currentOrder.'&order_sum='.$send_sum.'&service_id='.$service_id.'&wallet=0&card=1&comment=1&redirect_uri='.$return_link);
//		Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}
