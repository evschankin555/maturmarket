<?php
class ModelExtensionShippingMaturmarket extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/maturmarket');

		if ($address){
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_maturmarket_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
		}

		if (!$this->config->get('shipping_maturmarket_geo_zone_id')) {
			$status = true;
		} elseif ($query && $query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			if (isset($this->session->data['shipment_date'])){
				$shipmentDate = $this->session->data['shipment_date'];
			} else {
				$shipmentDate = date("d.m.Y");
			}

			//if ($address['city'] == 'Казань'){
			if (1 == 1) {  // Для всех городов сделаем одинаково пока
				if (time()<strtotime('20:00:00')){
					$minDate = date("d.m.Y", strtotime("+1 day"));
				} else {
					$minDate = date("d.m.Y", strtotime("+2 day"));
				}

				if (date('w', strtotime($minDate)) == 6 || (date('w', strtotime($minDate)) == 0) ){ // Если выпало на сб или вс, передвинем на ближайший понедельник
					$minDate = date("d.m.Y", strtotime("next monday"));
				}

				$maxDate = date("d.m.Y", strtotime($minDate . " + 7 day"));
				$daysOfWeekDisabled = [0,6];
			} else {
				if ( !((time()>strtotime('20:00:00')) && (date('w') == 2)) 
					&& !(date('w') == 3) 
				){
					$minDate = date("d.m.Y", strtotime("next wednesday"));
					$maxDate = date("d.m.Y", strtotime($minDate . " + 7 day"));
					$daysOfWeekDisabled = [0,1,2,4,5,6];
				} else { // Отдельное правило для периода с 20:00 вторника до 23:59 среды
					$minDate = date("d.m.Y", strtotime("next wednesday", strtotime("+2 day")));
					$maxDate = date("d.m.Y", strtotime("next wednesday", strtotime("+2 day")));
				}

				if (($shipmentDate<>$minDate) && ($shipmentDate<>$maxDate)){ // В зеленодольске не интервал, поэтому можно равняться только начальной или конечной дате
					$shipmentDate = $minDate;
				}
			}

			
			// Временный костыль для отключения праздников
			if ($minDate == '08.03.2023' || $minDate == '08.03.2023'){
				$minDate = '09.03.2023';
			}
			

			if (!$shipmentDate || (strtotime($shipmentDate) < strtotime($minDate))){
				$shipmentDate = $minDate;
			}
			if (strtotime($shipmentDate) > strtotime($maxDate)){
				$shipmentDate = $maxDate;
			}


			//$description = $this->language->get('text_description');
			$cost = 0;
			if ($this->cart->getSubTotal() < $this->config->get('shipping_maturmarket_free_from')) {
				$cost = $this->config->get('shipping_maturmarket_cost');
			}

			$city = $this->session->data['city'];
			if ($city['delivery_fix_cost']){
				$cost = $city['delivery_fix_cost'];
				//$description = sprintf($this->language->get('text_description_fix'), $city['name'], $cost);
			}

			$content = '
				<div class="ui-group">
					<label class="ui-label ui-label--uppercase">'.$this->language->get('text_shipment_date_label').'</label>
					<div class="flex">
						<div class="ui-field col-lg-6 margin_bottom_0">
							<input  type="text" class="ui-input date required" name="shipment_date" value="'.$shipmentDate.'" placeholder="'
							.$this->language->get('text_shipment_date_placeholder')
							.'" data-date-format="DD.MM.YYYY" id="input-shipment-date" />			
						</div>
					</div>
					<div class="shipping-text">'.$this->language->get('text_shipment_date_hint').'</div>
				</div>
				<script>
					$(".date").datetimepicker({
						language: "ru",
						format: "L",
						icons: {
							previous: "icon-datepicker icon-datepickerchevron-small-left",
							next: "icon-datepicker icon-datepickerchevron-small-right"
						},
						disabledDates: ["08.03.2023"],
						minDate: "'.$minDate.'",
						maxDate: "'.$maxDate.'"
						'.(isset($daysOfWeekDisabled) ? ', daysOfWeekDisabled: ['.implode(',', $daysOfWeekDisabled).']' : '').'
					})
				</script>
				';
			

			$quote_data = array();

			$quote_data['maturmarket'] = array(
				'code'         => 'maturmarket.maturmarket',
				'title'        => $this->language->get('text_title'),
				'cost'         => $cost,
				'text'         => $this->currency->format($cost, $this->session->data['currency']),
				//'description'  => $description,
				'content'	   => $content
			);

			$method_data = array(
				'code'       => 'maturmarket',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_maturmarket_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}