<?php
class ControllerExtensionModuleTechnicsTechnicscart extends Controller {
	public function fastadd2cart() {
		$this->load->language('checkout/cart');
		$this->load->language('checkout/checkout');

		$json = array();

		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);
//var_dump($this->request->post);die;
		if (isset($this->request->post['product_id']) && $product_info) { 
			if (isset($this->request->post['quantity']) && ((int)$this->request->post['quantity'] >= $product_info['minimum'])) {
				$quantity = (int)$this->request->post['quantity'];
			} else {
				$quantity = $product_info['minimum'] ? $product_info['minimum'] : 1;
			}

			if (isset($this->request->post['option'])) {
				$option = array_filter($this->request->post['option']);
			} else {
				$option = array();
			}

			$product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);

			foreach ($product_options as $product_option) {
				if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
					$json['error']['option'][$product_option['product_option_id']] = sprintf($this->language->get('error_required'), $product_option['name']);
				}
			}

			if (isset($this->request->post['recurring_id'])) {
				$recurring_id = $this->request->post['recurring_id'];
			} else {
				$recurring_id = 0;
			}

			$recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);

			if ($recurrings) {
				$recurring_ids = array();

				foreach ($recurrings as $recurring) {
					$recurring_ids[] = $recurring['recurring_id'];
				}

				if (!in_array($recurring_id, $recurring_ids)) {
					$json['error']['recurring'] = $this->language->get('error_recurring_required');
				}
			}
			if(!$product_info['quantity'] && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))){
					$json['error']['error_stock'] = $this->language->get('error_stock');
			}

			if(!isset($this->request->post['comment'])){//Отправляем на страницу товара если есть невыбранные опции
					if ($json && isset($this->request->post['redirect']) && $this->request->post['redirect']) {
						$json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']));
					}
					$this->response->addHeader('Content-Type: application/json');
					$this->response->setOutput(json_encode($json));	
					return;
			}

		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

			if ($product['minimum'] > $product_total) {
				$json['redirect'] = $this->url->link('checkout/cart');
				$json['error']['popup']['qty'] = 'true';
			}
		}


		$buy_click = array();
		if($this->config->get('theme_technics_buy_click')){
			$buy_click = $this->config->get('theme_technics_buy_click');
		}
		
		if ((utf8_strlen(trim($this->request->post['name'])) < 1) || (utf8_strlen(trim($this->request->post['name'])) > 32) && $buy_click['firstname']['required'] && $buy_click['firstname']['status']) {
			$json['error']['popup']['name'] = $this->language->get('error_firstname');
		}
		if ((utf8_strlen($this->request->post['phone']) < 3) || (utf8_strlen($this->request->post['phone']) > 32) && $buy_click['telephone']['required'] && $buy_click['telephone']['status']) {
			$json['error']['popup']['phone'] = $this->language->get('error_telephone');
		}
		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL) && $buy_click['email']['required'] && $buy_click['email']['status']) {
			$json['error']['popup']['email'] = $this->language->get('error_email');
		}
		if (!$this->request->post['comment'] && $buy_click['comment']['required'] && $buy_click['comment']['status']) {
			$json['error']['popup']['comment'] = $this->language->get('error_comment');
		}
			// Captcha
		if ($this->config->get($this->config->get('theme_technics_config_captcha_fo') . '_status') ) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('theme_technics_config_captcha_fo') . '/validate');

			if ($captcha) {
					$json['error']['popup']['captcha'] = $captcha;
			}
		}

			if (!$json) {

				if (isset($this->request->post['product_id'])) {
					$this->cart->clear();
					$this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id);
				}
//				$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('checkout/cart'));

				// Unset all shipping and payment methods
				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);

				// Totals
				$this->load->model('setting/extension');
				
				$order_data = array();

				$totals = array();
				$taxes = $this->cart->getTaxes();
				$total = 0;
		
				// Because __call can not keep var references so we put them into an array. 			
				$total_data = array(
					'totals' => &$totals,
					'taxes'  => &$taxes,
					'total'  => &$total
				);

				$this->load->model('setting/extension');

				$sort_order = array();

				$results = $this->model_setting_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
				}

				array_multisort($sort_order, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get('total_' . $result['code'] . '_status')) {
						$this->load->model('extension/total/' . $result['code']);

						// We have to put the totals in an array so that they pass by reference.
						$this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
					}
				}

				$sort_order = array();

				foreach ($totals as $key => $value) {
					$sort_order[$key] = $value['sort_order'];
				}

				array_multisort($sort_order, SORT_ASC, $totals);

				$order_data['totals'] = $totals;
//confirm				
				if ($this->customer->isLogged()) {
					$this->load->model('account/customer');

					$customer_info = $this->model_account_customer->getCustomer($this->customer->getId());

					$order_data['customer_id'] = $this->customer->getId();
					$order_data['customer_group_id'] = $customer_info['customer_group_id'];
					$order_data['firstname'] = $customer_info['firstname'];
					$order_data['lastname'] = $customer_info['lastname'];
					$order_data['email'] = $customer_info['email'];
					$order_data['telephone'] = $customer_info['telephone'];
					$order_data['fax'] = $customer_info['fax'];
					$order_data['custom_field'] = json_decode($customer_info['custom_field'], true);
				} else {
					$order_data['customer_id'] = 0;
					$order_data['customer_group_id'] = 0;
					$order_data['firstname'] = $this->request->post['name'];
					$order_data['lastname'] = '';
					$order_data['email'] = $this->request->post['email'];
					$order_data['telephone'] = $this->request->post['phone'];
					$order_data['fax'] = '';
					$order_data['custom_field'] = array();
					$this->session->data['guest']['firstname'] = $this->request->post['name'];
					$this->session->data['guest']['lastname'] = '';
				}				
				$order_data['payment_firstname'] = $order_data['firstname'];
				$order_data['payment_lastname'] = '';
				$order_data['payment_company'] = '';
				$order_data['payment_address_1'] = '';
				$order_data['payment_address_2'] = '';
				$order_data['payment_city'] = '';
				$order_data['payment_postcode'] = '';
				$order_data['payment_zone'] = '';
				$order_data['payment_zone_id'] = '';
				$order_data['payment_country'] = '';
				$order_data['payment_country_id'] = '';
				$order_data['payment_address_format'] = '';
				$order_data['payment_custom_field'] = '';	

				$order_data['payment_method'] = 'fast_order';
				$order_data['payment_code'] = '';
				
					$order_data['shipping_firstname'] = $order_data['firstname'];
					$order_data['shipping_lastname'] = '';
					$order_data['shipping_company'] = '';
					$order_data['shipping_address_1'] = '';
					$order_data['shipping_address_2'] = '';
					$order_data['shipping_city'] = '';
					$order_data['shipping_postcode'] = '';
					$order_data['shipping_zone'] = '';
					$order_data['shipping_zone_id'] = '';
					$order_data['shipping_country'] = '';
					$order_data['shipping_country_id'] = '';
					$order_data['shipping_address_format'] = '';
					$order_data['shipping_custom_field'] = array();
					
				$order_data['shipping_method'] = 'fast_order';
				$order_data['shipping_code'] = '';	

				if(!$this->request->post['email']){
					$order_data['email'] = 'no_email@noemail.ru'; 
				}
				
				$order_data['products'] = array();

				foreach ($this->cart->getProducts() as $product) {
					$option_data = array();

					foreach ($product['option'] as $option) {
						$option_data[] = array(
							'product_option_id'       => $option['product_option_id'],
							'product_option_value_id' => $option['product_option_value_id'],
							'option_id'               => $option['option_id'],
							'option_value_id'         => $option['option_value_id'],
							'name'                    => $option['name'],
							'value'                   => $option['value'],
							'type'                    => $option['type']
						);
					}

					$order_data['products'][] = array(
						'product_id' => $product['product_id'],
						'name'       => $product['name'],
						'model'      => $product['model'],
						'option'     => $option_data,
						'download'   => $product['download'],
						'quantity'   => $product['quantity'],
						'subtract'   => $product['subtract'],
						'price'      => $product['price'],
						'total'      => $product['total'],
						'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
						'reward'     => $product['reward'],
						'prime_cost' => $product['prime_cost']
					);
				}

				// Gift Voucher
				$order_data['vouchers'] = array();

				if (!empty($this->session->data['vouchers'])) {
					foreach ($this->session->data['vouchers'] as $voucher) {
						$order_data['vouchers'][] = array(
							'description'      => $voucher['description'],
							'code'             => token(10),
							'to_name'          => $voucher['to_name'],
							'to_email'         => $voucher['to_email'],
							'from_name'        => $voucher['from_name'],
							'from_email'       => $voucher['from_email'],
							'voucher_theme_id' => $voucher['voucher_theme_id'],
							'message'          => $voucher['message'],
							'amount'           => $voucher['amount']
						);
					}
				}

				$order_data['comment'] = $this->request->post['comment'];
				$order_data['total'] = $total_data['total'];
				
					$order_data['affiliate_id'] = 0;
					$order_data['commission'] = 0;
					$order_data['marketing_id'] = 0;
					$order_data['tracking'] = '';
					
				$order_data['language_id'] = $this->config->get('config_language_id');
				$order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
				$order_data['currency_code'] = $this->session->data['currency'];
				$order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
				$order_data['ip'] = $this->request->server['REMOTE_ADDR'];
				
				$order_data['forwarded_ip'] = '';
				$order_data['user_agent'] = '';
				$order_data['accept_language'] = '';
				
				$order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
				$order_data['store_id'] = $this->config->get('config_store_id');
				$order_data['store_name'] = $this->config->get('config_name');
				
				if ($order_data['store_id']) {
					$order_data['store_url'] = $this->config->get('config_url');
				} else {
					if ($this->request->server['HTTPS']) {
						$order_data['store_url'] = HTTPS_SERVER;
					} else {
						$order_data['store_url'] = HTTP_SERVER;
					}
				}
				
				$this->load->model('checkout/order');

				$this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);
			
				$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1);
//var_dump($order_data);die;
				if (isset($this->session->data['order_id'])) {
					$this->cart->clear();

					// Add to activity log
					if ($this->config->get('config_customer_activity')) {
						$this->load->model('account/activity');

						if ($this->customer->isLogged()) {
							$activity_data = array(
								'customer_id' => $this->customer->getId(),
								'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
								'order_id'    => $this->session->data['order_id']
							);

							$this->model_account_activity->addActivity('order_account', $activity_data);
						} else {
							$activity_data = array(
								'name'     => $this->session->data['guest']['firstname'] . ' ' . $this->session->data['guest']['lastname'],
								'order_id' => $this->session->data['order_id']
							);

							$this->model_account_activity->addActivity('order_guest', $activity_data);
						}
					}

				}
					$json['success'] = '1';				
					$json['total'] = 0;
					$json['redirect'] = $this->url->link('checkout/success');
			} elseif(!isset($json['error']['popup'])) {
				$json['redirect'] = str_replace('&amp;', '&', $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']));
			}
//		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
		
	}
}