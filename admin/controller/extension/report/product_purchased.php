<?php
class ControllerExtensionReportProductPurchased extends Controller {
	public function index() {
		$this->load->language('extension/report/product_purchased');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('report_product_purchased', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/report/product_purchased', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/report/product_purchased', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true);

		if (isset($this->request->post['report_product_purchased_status'])) {
			$data['report_product_purchased_status'] = $this->request->post['report_product_purchased_status'];
		} else {
			$data['report_product_purchased_status'] = $this->config->get('report_product_purchased_status');
		}

		if (isset($this->request->post['report_product_purchased_sort_order'])) {
			$data['report_product_purchased_sort_order'] = $this->request->post['report_product_purchased_sort_order'];
		} else {
			$data['report_product_purchased_sort_order'] = $this->config->get('report_product_purchased_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/report/product_purchased_form', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/report/product_purchased')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
		
	public function report() {
		$this->load->language('extension/report/product_purchased');

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$filter_manufacturer_id = $this->request->get['filter_manufacturer_id'];
		} else {
			$filter_manufacturer_id = 0;
		}

		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = '';
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = '';
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$this->load->model('extension/report/product');

		$data['products'] = array();

		$filter_data = array(
			'filter_date_start'	     => $filter_date_start,
			'filter_date_end'	     => $filter_date_end,
			'filter_order_status_id' => $filter_order_status_id,
			'filter_manufacturer_id' => $filter_manufacturer_id,
			'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                  => $this->config->get('config_limit_admin')
		);

		$product_total = $this->model_extension_report_product->getTotalPurchased($filter_data);

		$results = $this->model_extension_report_product->getPurchased($filter_data);

		foreach ($results as $result) {
			$data['products'][] = array(
				'name'     => $result['name'],
				'model'    => $result['model'],
				'quantity' => $result['quantity'],
				'total'    => $this->currency->format($result['total'], $this->config->get('config_currency'))
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('catalog/manufacturer');

		$data['manufacturers'] = $this->model_catalog_manufacturer->getManufacturers();

		$url = '';

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . $this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		$data['download_url'] = $this->url->link('extension/report/product_purchased/download', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('report/report', 'user_token=' . $this->session->data['user_token'] . '&code=product_purchased' . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

		$data['filter_manufacturer_id'] = $filter_manufacturer_id;
		$data['filter_date_start'] = $filter_date_start;
		$data['filter_date_end'] = $filter_date_end;
		$data['filter_order_status_id'] = $filter_order_status_id;

		return $this->load->view('extension/report/product_purchased_info', $data);
	}

	public function download() {
		require_once(DIR_SYSTEM . 'library/PHPExcel.php');

		$manufacturer = null;
		if (isset($this->request->get['filter_manufacturer_id'])) {
			$filter_manufacturer_id = $this->request->get['filter_manufacturer_id'];
			$this->load->model('catalog/manufacturer');
			$manufacturer = $this->model_catalog_manufacturer->getManufacturer($filter_manufacturer_id);
		} else {
			$filter_manufacturer_id = 0;
		}

		if (isset($this->request->get['filter_date_start'])) {
			$filter_date_start = $this->request->get['filter_date_start'];
		} else {
			$filter_date_start = '';
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filter_date_end = $this->request->get['filter_date_end'];
		} else {
			$filter_date_end = '';
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = 0;
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$this->load->model('extension/report/product');

		$filter_data = array(
			'filter_date_start'	     => $filter_date_start,
			'filter_date_end'	     => $filter_date_end,
			'filter_order_status_id' => $filter_order_status_id,
			'filter_manufacturer_id' => $filter_manufacturer_id
		);

		$product_total = $this->model_extension_report_product->getTotalPurchased($filter_data);

		$results = $this->model_extension_report_product->getPurchased($filter_data);

		$products = array();

		$i = 1;
		foreach ($results as $result) {
			$products[] = array(
				'num'		=> $i++,
				'brand_name'=> $result['brand_name'],
				'name'     => $result['name'],
				'sku'    => $result['sku'],
				'quantity' => $result['quantity'],
				'total'    => $result['prime_cost_total']
			);
		}
 			
		// Возьмем шаблон
		$doc = PHPExcel_IOFactory::load(DIR_SYSTEM . 'library/template/Шаблон отчета по продажам.xlsx');
		
		// set active sheet 
		$doc->setActiveSheetIndex(0);

		// Опишем нужные координаты
		$PRODUCTS_COUNT = count($products);
		$CREATE_DATE_CELL = 'C4';
		$MANUFACTURER_CELL = 'C5';
		$PERIOD_CELL = 'C6';
		$TABLE_START_ROW = 9;
		// Следующие координаты вычисляются автоматически
		$TABLE_LEFT_TOP_CELL = 'A' . $TABLE_START_ROW;
		$SUM_CELL = 'F' . ($TABLE_START_ROW + $PRODUCTS_COUNT);
		$SUM_RANGE = 'F' . $TABLE_START_ROW . ':F' . ($TABLE_START_ROW + $PRODUCTS_COUNT - 1);
		$TEXT_FORMAT_RANGE = 'B' . $TABLE_START_ROW . ':D' . ($TABLE_START_ROW + $PRODUCTS_COUNT - 1);

		
		// Заполним шапку
		$doc->getActiveSheet()->SetCellValue($CREATE_DATE_CELL, date("d.m.Y"));
		$doc->getActiveSheet()->SetCellValue($MANUFACTURER_CELL, ($filter_manufacturer_id ? $manufacturer['name'] : 'по всем магазинам'));

		$period = (!$filter_date_start && !$filter_date_end) ? 'весь период' 
								: ($filter_date_start ? ' c ' . $filter_date_start : '') . ($filter_date_end ? ' по ' . $filter_date_end : '');
		$period = trim($period);
		$doc->getActiveSheet()->SetCellValue($PERIOD_CELL, $period);

		// Заполним таблицу
		if ($PRODUCTS_COUNT>1){
			$doc->getActiveSheet()->insertNewRowBefore($TABLE_START_ROW + 1, $PRODUCTS_COUNT - 1); 
		}
		$doc->getActiveSheet()->fromArray($products, null, $TABLE_LEFT_TOP_CELL);
		$doc->getActiveSheet()->SetCellValue($SUM_CELL, '=SUM(' . $SUM_RANGE . ')');

		$doc->getActiveSheet()->getStyle($TEXT_FORMAT_RANGE)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		
		//save our workbook as this file name
		
		$period = (!$filter_date_start && !$filter_date_end) ? $period : 'период ' . $period;
		$filename = 'Продажи ' . ($filter_manufacturer_id ? 'магазина ' . $manufacturer['name'] : 'по всем магазинам') . ' за ' . $period . '.xlsx';
		//mime type
		header('Content-Type: application/vnd.ms-excel');
		//tell browser what's the file name
		header('Content-Disposition: attachment;filename="' . $filename . '"');
		
		header('Cache-Control: max-age=0'); //no cache
		//save it to Excel5 format (excel 2003 .XLS file), change this to 'Excel2007' (and adjust the filename extension, also the header mime type)
		//if you want to save it as .XLSX Excel 2007 format
		
		$objWriter = PHPExcel_IOFactory::createWriter($doc, 'Excel2007');
		
		//force user to download the Excel file without writing it to server's HD
		$objWriter->save('php://output');
		//$this->response->setOutput($this->load->view('sale/order_invoice', $data));
	}
}