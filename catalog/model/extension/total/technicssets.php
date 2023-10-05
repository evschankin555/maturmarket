<?php
class ModelExtensionTotalTechnicssets extends Model {


	public function getTotal($total) {
		$this->load->language('extension/total/technicssets');

		$this->load->model('extension/module/technics');

		$setids = array();
		if (isset($this->session->data['technicssetid']) && $this->session->data['technicssetid']) {
			$setids = $this->session->data['technicssetid'];
		}

		$cartProducts = array();

		foreach ($this->cart->getProducts() as $product) {
			$cartProducts[$product['product_id']]['quantity'] = $product['quantity'];
			$cartProducts[$product['product_id']]['price'] = $product['price'];
		}



		foreach ($setids as  $setid) { 

			$technicssets = $this->model_extension_module_technics->getSetDiscount($total,$setid,$cartProducts);

			if (!empty($this->session->data['vouchers'])) {
				foreach ($this->session->data['vouchers'] as $voucher) {
					$technicssets += $voucher['amount'];
				}
			}

			if ($technicssets['discount']) {

				$setInfo = $this->model_extension_module_technics->getSetInfo($setid);

				$total['totals'][] = array(
					'code'       => 'technicssets',
					'title'      => $this->language->get('text_technicssets').' - '.$setInfo['title'],
					'value'      => -$technicssets['discount'],
					'sort_order' => $this->config->get('total_technicssets_sort_order')
				);

				$total['total'] -= $technicssets['discount'];
				$cartProducts = $technicssets['cartproducts'];
			}	
		}

	}

}
