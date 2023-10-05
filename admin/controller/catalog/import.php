<?php
class ControllerCatalogImport extends Controller {
	public function index() {
		$this->load->language('catalog/import');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('catalog/import', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['user_token'] = $this->session->data['user_token'];
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('catalog/import', $data));
	}

	public function history() {
		$this->load->language('catalog/import');
		
		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}
					
		$data['histories'] = array();
		
		$this->load->model('catalog/import');
		
		$results = $this->model_catalog_import->getImportHistory(($page - 1) * 10, 10);
		
		foreach ($results as $result) {
			$data['histories'][] = array(
				'import_history_id'    => $result['import_history_id'],
				'username'             => $result['username'],
				'supplier'             => $result['supplier'],
				'filename'             => $result['filename'],
				'date_added'           => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
				'status'               => $result['status']
			);
		}
		
		$history_total = $this->model_catalog_import->getTotalImportHistories();

		$pagination = new Pagination();
		$pagination->total = $history_total;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('catalog/import/history', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($history_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($history_total - 10)) ? $history_total : ((($page - 1) * 10) + 10), $history_total, ceil($history_total / 10));
				
		$this->response->setOutput($this->load->view('catalog/import_history', $data));
	}	
		
	public function upload() {
		$this->load->language('catalog/import');

		$json = array();

		// Check user has permission
		if (!$this->user->hasPermission('modify', 'catalog/import')) {
			$json['error'] = $this->language->get('error_permission');
		}

		// Check if there is an upload file already there
		$files = glob(DIR_UPLOAD . '*.tmp-import');

		foreach ($files as $file) {
			if (is_file($file) && (filectime($file) < (time() - 5))) {
				unlink($file);
			}
			
			if (is_file($file)) {
				$json['error'] = $this->language->get('error_import');
				
				break;
			}
		}

		if (!$json) {
			// Check for any import directories
			$directories = glob(DIR_UPLOAD . 'tmp-import-*');
			
			foreach ($directories as $directory) {
				if (is_dir($directory) && (filectime($directory) < (time() - 5))) {
					// Get a list of files ready to upload
					$files = array();
		
					$image_path = array($directory);
		
					while (count($image_path) != 0) {
						$next = array_shift($image_path);
		
						// We have to use scandir function because glob will not pick up dot files.
						foreach (array_diff(scandir($next), array('.', '..')) as $file) {
							$file = $next . '/' . $file;
		
							if (is_dir($file)) {
								$image_path[] = $file;
							}
		
							$files[] = $file;
						}
					}
		
					rsort($files);
		
					foreach ($files as $file) {
						if (is_file($file)) {
							unlink($file);
						} elseif (is_dir($file)) {
							rmdir($file);
						}
					}
		
					rmdir($directory);
				}
				
				if (is_dir($directory)) {
					$json['error'] = $this->language->get('error_import');
					
					break;
				}		
			}
			
			if (isset($this->request->files['file']['name'])) {
				if (substr($this->request->files['file']['name'], -5) != '.xlsx' 
						&& substr($this->request->files['file']['name'], -4) != '.xls'
						&& substr($this->request->files['file']['name'], -4) != '.xml') {
					$json['error'] = $this->language->get('error_filetype');
				}

				if ($this->request->files['file']['error'] != UPLOAD_ERR_OK) {
					$json['error'] = $this->language->get('error_upload_' . $this->request->files['file']['error']);
				}
			} else {
				$json['error'] = $this->language->get('error_upload');
			}

			if (!$json) {
				$this->session->data['import'] = token(10);
				$this->session->data['import_original_filename'] = $this->request->files['file']['name'];
				$this->session->data['supplier'] = $this->request->post['supplier'];
				
				$file = DIR_UPLOAD . $this->session->data['import'] . '.tmp-import';
				
				move_uploaded_file($this->request->files['file']['tmp_name'], $file);

				if (is_file($file)) {
					$this->load->model('catalog/import');
					
					$import_history_id = $this->model_catalog_import->addImportHistory($this->session->data['supplier'], $this->request->files['file']['name']);
					$this->session->data['import_history_id'] = $import_history_id;
					
					$json['text'] = $this->language->get('text_import');

					$json['next'] = str_replace('&amp;', '&', $this->url->link('catalog/import/import', 'user_token=' . $this->session->data['user_token'], true));		
				} else {
					$json['error'] = $this->language->get('error_file');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// Этот метод сам по себе ничего не делает, но оставил его, возможно можно будет его вызывать отдельно, если сами файлы будут загружаться как кто по-другому
	public function import() {
		$this->load->language('catalog/import');

		$json = array();
			
		if (isset($this->session->data['import_history_id'])) {
			$import_history_id = $this->session->data['import_history_id'];
		} else {
			$import_history_id = 0;
		}

		$this->load->model('catalog/import');
		$this->model_catalog_import->updateImportHistoryStatus($import_history_id, 'Начали импорт');
			
		if (!$this->user->hasPermission('modify', 'catalog/import')) {
			$json['error'] = $this->language->get('error_permission');
		}

		// Make sure the file name is stored in the session.
		if (!isset($this->session->data['import'])) {
			$json['error'] = $this->language->get('error_file');
		} elseif (!is_file(DIR_UPLOAD . $this->session->data['import'] . '.tmp-import')) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			$json['text'] = $this->language->get('text_unzip');

			$json['next'] = str_replace('&amp;', '&', $this->url->link('catalog/import/unzip', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function unzip() {
		$this->load->language('catalog/import');

		$json = array();

		if (isset($this->session->data['import_history_id'])) {
			$import_history_id = $this->session->data['import_history_id'];
		} else {
			$import_history_id = 0;
		}
		
		$this->load->model('catalog/import');
		$this->model_catalog_import->updateImportHistoryStatus($import_history_id, 'Начали распаковку');

		if (!$this->user->hasPermission('modify', 'catalog/import')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!isset($this->session->data['import'])) {
			$json['error'] = $this->language->get('error_file');
		} elseif (!is_file(DIR_UPLOAD . $this->session->data['import'] . '.tmp-import')) {
			$json['error'] = $this->language->get('error_file');
		}
		
		// Sanitize the filename
		if (!$json) {
			$file = DIR_UPLOAD . $this->session->data['import'] . '.tmp-import';
			
			/*
			// Unzip the files
			$zip = new ZipArchive();

			if ($zip->open($file)) {
				$zip->extractTo(DIR_UPLOAD . 'tmp-import-' . $this->session->data['import']);
				$zip->close();
			} else {
				$json['error'] = $this->language->get('error_unzip');
			}
			*/
			// Пока закомментировали распаковку, возможно в будущем будем пакетами загружать или просто архивными файлами. 
			// Всю логику оставил, чтобы потом просто можно было раскомментировать. А пока просто переместим файл
			$new_filename = DIR_UPLOAD . 'tmp-import-' . $this->session->data['import'] . '/' . $this->session->data['import'] . '.tmp-import';
			if (!is_dir(dirname($new_filename))) {
				mkdir(dirname($new_filename), 0775, true);
			}
			rename($file, $new_filename);

			// Remove Zip
			// unlink($file); - Удалять файл не будем, так как он уже перемещен

			$json['text'] = $this->language->get('text_save');

			$json['next'] = str_replace('&amp;', '&', $this->url->link('catalog/import/save', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($json['error'])){
			$this->model_catalog_import->updateImportHistoryStatus($import_history_id, 'Ошибка распаковки файла');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function save() {
		$this->load->language('catalog/import');

		$json = array();

		if (isset($this->session->data['import_history_id'])) {
			$import_history_id = $this->session->data['import_history_id'];
		} else {
			$import_history_id = 0;
		}

		$this->load->model('catalog/import');
		$this->model_catalog_import->updateImportHistoryStatus($import_history_id, 'Начали сохранение данных');
		
		if (!$this->user->hasPermission('modify', 'catalog/import')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!isset($this->session->data['import'])) {
			$json['error'] = $this->language->get('error_directory');
		} elseif (!is_dir(DIR_UPLOAD . 'tmp-import-' . $this->session->data['import'] . '/')) {
			$json['error'] = $this->language->get('error_directory') . ' = ' . DIR_UPLOAD . 'tmp-import-' . $this->session->data['import'] . '/';
		}

		if (!$json) {
			$directory = DIR_UPLOAD . 'tmp-import-' . $this->session->data['import'] . '/';
		
			$files = array();

			// Get a list of files ready to import
			$image_path = array($directory . '*');

			while (count($image_path) != 0) {
				$next = array_shift($image_path);

				foreach ((array)glob($next) as $file) {
					if (is_dir($file)) {
						$image_path[] = $file . '/*';
					}

					$files[] = $file;
				}
			}

			// Переместим папку в архив
			$archive_dirname = DIR_UPLOAD . 'archive/' . $import_history_id;
			if (!is_dir($archive_dirname)) {
				mkdir($archive_dirname, 0775, true);
			}


			foreach ($files as $file) {
				if (is_file($file)) {
					switch ($this->session->data['supplier']) {
						case $this->language->get('supplier_universal'):
							$this->parseUniversalXLS($file);
							break;
						case $this->language->get('supplier_sofiprofi'):
							$this->parseSofiProfiXLS($file);
							break;
						case $this->language->get('supplier_charm_descr'):
							$this->parseCharmDescrXLS($file);
							break;
						case $this->language->get('supplier_charm_quantity'):
							$this->parseCharmQuantityXLS($file);
							break;
						case $this->language->get('supplier_tigi'):
							$this->parseTIGIXLS($file);
							break;
						case $this->language->get('supplier_consumables'):
							$this->parseConsumablesXLS($file);
							break;
						case $this->language->get('supplier_proftochka'):
							$this->parseProfTochkaXLS($file);
							break;
						case $this->language->get('supplier_kapous'):
							$this->parseKapousXLS($file);
							break;
						case $this->language->get('supplier_kaaral'):
							$this->parseKaaralXLS($file);
							break;
						case $this->language->get('supplier_loreal'):
							$this->parseLorealXLS($file);
							break;
						case $this->language->get('supplier_matrix'):
							$this->parseMatrixXLS($file);
							break;
						case $this->language->get('supplier_arhipelag'):
							$this->parseArhipelagXLS($file);
							break;
						case $this->language->get('supplier_mio'):
							$this->parseMioXLS($file);
							break;
						case $this->language->get('supplier_uno'):
							$this->parseUnoXLS($file);
							break;
						case $this->language->get('supplier_dewal'):
							$this->parseDewalXLS($file);
							break;
						case $this->language->get('supplier_erfolg'):
							$this->parseErfolgXML($file);
							break;
						case $this->language->get('supplier_erfolg_changes'):
							$this->parseErfolgJSON($file);
							break;							
						case $this->language->get('supplier_keune'):
							$this->parseKeuneXLS($file);
							break;
						case $this->language->get('supplier_ollin'):
							$this->parseOllinXLS($file);
							break;
						case $this->language->get('supplier_kondor'):
							$this->parseKondorXLS($file);
							break;
						case $this->language->get('supplier_master_professional'):
							$this->parseMasterProfessionalXLS($file);
							break;
						case $this->language->get('supplier_promanicure'):
							$this->parsePromanicureXLS($file);
							break;
						case $this->language->get('supplier_lash116'):
							$this->parseLash116XLS($file);
							break;
						case $this->language->get('supplier_tefia'):
							$this->parseTefiaXLS($file);
							break;
					}
					
					// Так как файл пока не архивируется, то он один, сохраним его имя в сессии. Если будем делать загрузку архива, то сохранять не нужно, так как в папке они не будут изменяться
					rename($file, $archive_dirname . '/' . $this->session->data['import_original_filename']);
				}
			}
			
			// Удалим пустую временную папку
			rmdir($directory);
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_success');
		}

		$status = 'Успешная загрузка';
		if (isset($json['error'])){
			$this->log->write($json['error']);
			$status = 'Ошибка сохранения данных';
		}
		$this->model_catalog_import->updateImportHistoryStatus($import_history_id, $status);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function getManufacturerByName($manufacturerName, $languages) {
		$this->load->model('catalog/manufacturer');
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer['manufacturer_id'] = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
		}

		return $manufacturer;
	}

	private function getCategoryIdByManufacturerAndName($manufacturer, $categoryName, $filter_id){
		$manufacturer_id = $manufacturer['manufacturer_id'];
		$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

		if (empty($category['category_id'])){ 
			// Если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
			$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
			$category_id = 0;

			// А также добавим эту категорию в соответствия, но с пустой категорией, если еще не добавлена
			if (empty($category['import_name'])){
				$matching = array(
					'manufacturer_id'	=> $manufacturer_id,
					'category_id'		=> null,
					'import_name'		=> $categoryName
				);
				$category = $this->model_catalog_category->addCategoryMathings($matching);
			}
		} else {
			$category_id = $category['category_id'];
			$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
		}

		return $category_id;
	}

	private function parseSofiProfiXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'SofiProfi';
		$brandName = 'SofiProfi';
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}

		$filter_id = $this->getBrandFilter($languages, $brandName);
	
		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if (!$row[0] || $row[0] == 'ID товара'){
					continue;
				}

				if (!is_numeric($row[0])){ // Если тут текст, то это наименование категории
					$categoryName = trim($row[0]);
					
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категории в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[3]));
					$productShortDescription = htmlentities(trim($row[4]));
					$productDescription = htmlentities(trim($row[5]));
					$price = $row[6];
					$primeCost = $price * 0.85; // Себестоимость на 15% меньше
					$quantity = $row[7];
					$weight = $row[8];
					$size = explode('/', trim($row[10]));
					$consist = htmlentities(trim($row[11]));
					$image_remote_url = $row[12];
					$extra_image1 = $row[13];
					$extra_image2 = $row[14];

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $manufacturerName . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost,
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost; 
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseCharmDescrXLS($filename) {
		require_once(DIR_SYSTEM . 'library/PHPExcel.php');
		require_once(DIR_SYSTEM . 'library/simple_html_dom.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Charme Pro Line';
		$brandName = 'Charme Pro Line';
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}

		$filter_id = $this->getBrandFilter($languages, $brandName);

		$objPHPExcel = PHPExcel_IOFactory::load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		
		$category_id = 0;
		foreach ($objWorksheet->getRowIterator() as $rowIndex => $rowObject) {
			$row = array();
			$rowMeta = array();
			foreach ($rowObject->getCellIterator() as $cell) {
				$rowMeta[] = $cell;
				$row[] = $cell->getValue();
			}

			if ($row[0] == 'ID товара'){
				continue;
			}

			if (!is_numeric($row[0]) && $row[0] != ''){ // Если тут текст, то это наименование категории				
				$categoryName = trim($row[0]);

				$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

				if (empty($category['category_id'])){ 
					// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
					$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
					$category_id = 0;

					// А также добавим эту категории в соответсвтия, но с пустой категорией, если еще не добавлена
					if (empty($category['import_name'])){
						$matching = array(
							'manufacturer_id'	=> $manufacturer_id,
							'category_id'		=> null,
							'import_name'		=> $categoryName
						);
						$category = $this->model_catalog_category->addCategoryMathings($matching);
					}
					continue;
				} else {
					$category_id = $category['category_id'];
					$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
				}
				$productDescription = null; // В этой загрузке описание копируется от предыдущего товара
				$consist = null; // Состав также от предыдущего товара
			} else { // Иначе это товар				
				if ($category_id == 0){
					continue;
				}

				$SKU = trim($row[1]);
				$productName = htmlentities(trim($row[3]));
				
				$descrCellIndex = 4;
				if (!$objPHPExcel->getActiveSheet()->getCellByColumnAndRow( $descrCellIndex, $rowIndex )->isInMergeRange() || $objPHPExcel->getActiveSheet()->getCellByColumnAndRow( $descrCellIndex, $rowIndex )->isMergeRangeValueCell()) {
					// Cell is not merged cell
					// Заменять описание будем, только если это новая необъединенная ячейка, иначе будем брать предыдущее значение
					$productDescription = htmlentities(trim($row[4]));
					//$objPHPExcel->getActiveSheet()->getCellByColumnAndRow( $descrCellIndex, $rowIndex )->getCalculatedValue()
				}
				$productShortDescription = $productDescription;

				$price = $row[5];
				$quantity = 0;
				$weight = $row[7];
				$size = explode('/', $row[9]);

				$consistCellIndex = 10;
				if (!$objPHPExcel->getActiveSheet()->getCellByColumnAndRow( $consistCellIndex, $rowIndex )->isInMergeRange() || $objPHPExcel->getActiveSheet()->getCellByColumnAndRow( $consistCellIndex, $rowIndex )->isMergeRangeValueCell()) {
					// Cell is not merged cell
					// Заменять состав будем, только если это новая необъединенная ячейка, иначе будем брать предыдущее значение
					$consist = htmlentities(trim($row[10]));
				}

				$urlWithImages = trim($row[11]);
				$imageDomain = 'https://charme-pro.ru/';
				
				$image_remote_url = null;
				$extra_image1 = null;
				$extra_image2 = null;
				// Если ссылка на страницу заполнена, то пройдем по ней и возьмем урл фото
				if (!empty($urlWithImages)){
					$html = file_get_html($urlWithImages);
					if (!$html){
						$this->log->write('Не найдена страница с фото ' .$urlWithImages);
					} else {
						//$imgArray = array_map(fn($value): string => $value->href, $html->find('[itemprop=image]'));
						// Переписываю лямбду на обычную функцию ,потому что пока что на сервере стоит php 7.3
						$func = function($value): string {
							return $value->href;
						};
						$imgArray = array_map($func, $html->find('[itemprop=image]'));
						// конец

						if (!empty($imgArray[0])){
							$image_remote_url = $imageDomain . $imgArray[0];
						}
						if (!empty($imgArray[1])){
							$extra_image1 = $imageDomain . $imgArray[1];
						}
						if (!empty($imgArray[2])){
							$extra_image2 = $imageDomain . $imgArray[2];
						}
					}
				}

				$length = '';		
				$width = '';
				$height = '';
				/*if (!empty($size)){
					$length = $size[0];		
					$width = $size[1];
					$height = $size[2];
				} else {
					$length = '';		
					$width = '';
					$height = '';
				}*/
				
				$model = $manufacturerName . '-' . $SKU;

				$product = $this->model_catalog_product->getProductByModel($model);
			
				$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
				if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
					mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
				}

				if (!empty($image_remote_url)){
					if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
						$ext = pathinfo($image_remote_url)['extension'];
						$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
						file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
						$image = $image_path;
					} else {
						$image = $product['image'];
						$image_remote_url = $product['image_uploaded_from_url'];
					}
				} else {
					$image = 'no_image.png';
				}
				
				$product_images = array();
				
				if (!empty($extra_image1)){	
					$product_old_image = '';
					if (!empty($product)) {
						$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
					}

					if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
						$ext = pathinfo($extra_image1)['extension'];
						$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
						file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
						$product_images[] = array(
							'image' 					=> $image_path,
							'image_uploaded_from_url'	=> $extra_image1,
							'sort_order'				=> 0
						);
					} else {
						$product_images[] = $product_old_image;
					}
				}
				if (!empty($extra_image2)){
					$product_old_image = '';
					if (!empty($product)) {
						$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
					}

					if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
						$ext = pathinfo($extra_image2)['extension'];
						$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
						file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
						$product_images[] = array(
							'image' 					=> $image_path,
							'image_uploaded_from_url'	=> $extra_image2,
							'sort_order'				=> 0
						);
					} else {
						$product_images[] = $product_old_image;
					}
				}
				
				$product_description = array();
				foreach($languages as $language) {
					$product_description[$language['language_id']] = array(
							'name'             => $productName,
							'meta_title'       => $productName,
							'meta_h1'      	   => $productName,
							'meta_description' => $productName,
							'meta_keyword'     => $productName,
							'description'      => $productDescription,
							'tag'			   => '',
							'consist'		   => $consist
					);
				}

				$product_seo_url = array();
				foreach($languages as $language) {
					if (strtolower(trim($language['name'])) == 'ru') {
						$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
					} elseif (strtolower(trim($language['name'])) == 'en') {
						$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
					} else {
						$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
					}				
				}

				if (empty($product)){
					$product = array(
						'product_description'		=> $product_description,
						'model' 					=> $model,
						'sku' 						=> $SKU,
						'upc'						=> '',
						'ean'						=> '',
						'jan'						=> '',
						'isbn'						=> '',
						'mpn'						=> '',
						'location'					=> '',
						'product_store' 			=> array(0),
						'shipping'					=> 1,
						'price'						=> $price,
						'product_recurring'			=> array(),
						'tax_class_id'				=> 0,
						'date_available' 			=> date('Y-m-d'),
						'quantity' 					=> $quantity,				
						'minimum' 					=> 1,
						'subtract'					=> 1,
						'sort_order'				=> 1,
						'stock_status_id'			=> $stock_status_id,
						'status'					=> 1,
						'noindex' 					=> 1,						
						'weight'					=> $weight,
						'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
						'length' 					=> $length,
						'width' 					=> $width,
						'height' 					=> $height,
						'length_class_id'			=> $this->config->get('config_length_class_id'),
						'manufacturer_id'			=> $manufacturer_id,
						'main_category_id' 			=> $category_id,
						'product_category' 			=> array($category_id),
						'product_filter' 			=> array($filter_id),
						'product_attribute' 		=> array(),
						'product_option' 			=> array(),
						'product_discount' 			=> array(),
						'product_special' 			=> array(),
						'image'						=> $image,
						'image_uploaded_from_url'	=> $image_remote_url,
						'product_image'				=> $product_images,
						'product_related' 			=> array(),
						'product_related_article'   => array(),
						'points' 					=> '',
						'product_reward' 			=> array(),
						'product_seo_url' 			=> $product_seo_url,
						'product_layout'			=> array(),
						'prime_cost'				=> 0, // себестоимость грузится в отдельном файле
						'import_history_id'			=> $this->session->data['import_history_id'],
						'brand_name'				=> $brandName
					);
					$this->model_catalog_product->addProduct($product);				
				} else {
					$product_id = $product['product_id'];

					$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
					foreach($languages as $language) {
						$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
											$product_old_description[$language['language_id']]['meta_title'] : $productName;
						$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
											$product_old_description[$language['language_id']]['meta_h1'] : $productName;
						$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
											$product_old_description[$language['language_id']]['meta_description'] : $productName;
						$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
											$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
						$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
											$product_old_description[$language['language_id']]['tag'] : '';							
					}

					$product_filter = $this->model_catalog_product->getProductFilters($product_id);
					if (!in_array($filter_id, $product_filter)){
						$product_filter[] = $filter_id;
					}

					$product['product_description'] = $product_description;
					$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
					$product['product_filter'] = $product_filter;
					$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
					$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
					$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
					$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
					$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
					$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
					$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
					$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
					$product['product_seo_url'] = $product_seo_url;
					$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
					$product['product_store'] = array(0);
					$product['price'] = $price;
					$product['quantity'] = $quantity;
					$product['weight'] = $weight;
					$product['length'] = $length;
					$product['width'] = $width;
					$product['height'] = $height;
					$product['main_category_id'] = $category_id;
					$product['product_category'] = array($category_id);
					$product['image'] = $image;
					$product['product_image'] = $product_images;
					$product['image_uploaded_from_url'] = $image_remote_url;
					$product['prime_cost'] = $product['prime_cost']; // Оставим старое значение, так как оно грузится из другого файла
					$product['import_history_id'] = $this->session->data['import_history_id'];
					$product['brand_name'] = $brandName;

					$this->model_catalog_product->editProduct($product_id, $product);
				}
			}
		}
				
	}

	private function parseCharmQuantityXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLS.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Charme Pro Line';
		$brandName = 'Charme Pro Line';
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$this->log->write('Не найден поставщик "' . $manufacturerName . '". Загрузка остатков отменена.');
			return;
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		if ( $xls = SimpleXLS::parse($filename) ) {
			foreach ( $xls->rows() as $r => $row ) {
				if (empty($row[1]) || $row[1] == '№'){
					continue;
				}

				$SKU = trim($row[5]);
				$productName = trim($row[11]);
				$quantity = $row[53];
				$primeCost = $row[63];
				
				$model = $manufacturerName . '-' . $SKU;

				$product = $this->model_catalog_product->getProductByModel($model);

				if (empty($product)){
					$this->log->write(' Не найден товар с кодом "' . $model . '" (' . $productName . ')');
					continue;
				} else {
					$product_id = $product['product_id'];

					$product['product_description'] = $this->model_catalog_product->getProductDescriptions($product_id);
					$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
					$product['product_filter'] = $this->model_catalog_product->getProductFilters($product_id);
					$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
					$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
					$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
					$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
					$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
					$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
					$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
					$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
					$product['product_seo_url'] = $this->model_catalog_product->getProductSeoUrls($product_id);
					$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
					$product['product_store'] = array(0);
					$product['price'] = $product['price'];
					$product['quantity'] = $quantity;
					$product['weight'] = $product['weight'];
					$product['length'] = $product['length'];
					$product['width'] = $product['width'];
					$product['height'] = $product['height'];
					$product['main_category_id'] = $this->model_catalog_product->getProductMainCategoryId($product_id);
					$product['product_category'] = $this->model_catalog_product->getProductCategories($product_id);
					$product['image'] = $product['image'];
					$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
					$product['image_uploaded_from_url'] = $product['image_uploaded_from_url'];
					$product['prime_cost'] = $primeCost; 
					$product['import_history_id'] = $this->session->data['import_history_id'];
					$product['brand_name'] = $brandName;

					$this->model_catalog_product->editProduct($product_id, $product);
				}
			}
		} else {
			$this->log->write(SimpleXLS::parseError());
		}
	}

	private function parseTIGIXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Matur Market';
		$brandName = 'TIGI';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ($row[0] == 'ID товара' || (!trim($row[0]) && !trim($row[1]))){
					continue;
				}

				if (!is_numeric($row[0]) && $row[0] != ''){ // Если тут текст, то это наименование категории
					$categoryName = trim($row[0]);
					
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категории в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities($this->mb_ucfirst(mb_strtolower(trim($row[3]))));				
					$productShortDescription = htmlentities(trim($row[4]));
					$productDescription = htmlentities(trim($row[4]));
					$primeCost = $row[5];
					$price = $row[6];
					$quantity = $row[7];
					$weight = null;
					$size = null;//explode('/', trim($row[10]));
					$consist = htmlentities(trim($row[10]));
					$image_remote_url = $row[11];
					$extra_image1 = $row[12];
					$extra_image2 = $row[13];

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						$image = $manufacturer_image_path . '/' . $image_remote_url . '.png';
					} else {
						$image = 'no_image.png';
					}
					$image_remote_url = null;
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image1 . '.png',
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					if (!empty($extra_image2)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image2 . '.png',
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseConsumablesXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Matur Market';
		$brandName = 'Matur Market';
		$modelPrefix = $manufacturerName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ($row[0] == 'ID товара' || (!trim($row[0]) && !trim($row[1]))){
					continue;
				}

				if (!is_numeric($row[0]) && $row[0] != ''){ // Если тут текст, то это наименование категории
					$categoryName = trim($row[0]);
					
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категории в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[3]));
					$productShortDescription = htmlentities(trim($row[4]));
					$productDescription = htmlentities(trim($row[4]));
					$primeCost = $row[5];
					$price = $row[6];
					$quantity = $row[7];
					$weight = $row[8];
					$size = explode('/', trim($row[9]));
					$consist = trim($row[10]);
					$image_remote_url = $row[11];
					$extra_image1 = $row[12];
					$extra_image2 = $row[13];

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						$image = $manufacturer_image_path . '/' . $image_remote_url . '.png';
					} else {
						$image = 'no_image.png';
					}
					$image_remote_url = null;
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image1 . '.png',
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					if (!empty($extra_image2)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image2 . '.png',
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseProfTochkaXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Prof точка';
		$brandName = 'BB one';
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}

		$filter_id = $this->getBrandFilter($languages, $brandName);
	
		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!$row[0] && !$row[1]) || $row[1] == 'Артикул'){
					continue;
				}
				if (!is_numeric($row[0]) && $row[0] != ''){ // Если тут текст, то это наименование категории
					$categoryName = trim($row[0]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категории в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[3]));
					$productShortDescription = htmlentities(trim($row[4]));
					$productDescription = htmlentities(trim($row[5]));
					$price = $row[6];
					$primeCost = $price * 0.85; // Себестоимость на 15% меньше
					$quantity = $row[7];
					$weight = (int)$row[8] / 1000;
					$size = explode('*', explode(' ', trim($row[10]))[0]); // убираем "см", который через пробел и делим по знаку *		
					$consist = htmlentities(trim($row[11]));		
					$image_remote_url = $row[12];
					$extra_image1 = $row[13];
					$extra_image2 = $row[14];

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $manufacturerName . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							if (file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url))){
								$image = $image_path;
							} else {
								$image = 'no_image.png';
								$image_remote_url = '';
							}
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseKapousXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'NASTYA Prof';
		$brandName = 'Kapous';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Номенклатура'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = $row[5];
					$primeCost = $price * 0.85; // Себестоимость на 15% меньше
					$quantity = $row[4];
					$weight = 0;
					$size = null;
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;
					$consist = '';

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						$image = $manufacturer_image_path . '/' . $image_remote_url;
					} else {
						$image = 'no_image.png';
					}
					$image_remote_url = null;
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image1,
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					if (!empty($extra_image2)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image2,
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseKaaralXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'NASTYA Prof';
		$brandName = 'Kaaral';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Ценовая группа/ Номенклатура/ Характеристика номенклатуры'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = $row[5];
					$primeCost = $price * 0.85; // Себестоимость на 15% меньше
					$quantity = $row[4];
					$weight = 0;
					$size = null;
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;
					$consist = '';

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseLorealXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Matur Market';
		$brandName = 'Loreal';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Номенклатура'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[5]) * 1.1; // Цена на 10% больше, чем в файле
					$primeCost = (int)trim($row[5]) * 0.95;; // Себестоимость на 5% меньше, чем в файле
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseMatrixXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		$manufacturerName = 'Matur Market';
		$brandName = 'Matrix';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Номенклатура'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[5]) * 1.25; // Цена на 25% больше, чем в файле
					$price_for_masters = (int)trim($row[5]) * 1.1; // Цена для мастеров на 10% больше, чем в файле
					$primeCost = (int)trim($row[5]) * 1; // Себестоимость сейчас без скидки
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
					$product_special = array();
					$product_special[] = array(
						'customer_group_id'		=> $customer_group_id,
						'priority'				=> 1,
						'price'					=> $price_for_masters,
						'date_start'			=> '',
						'date_end'				=> ''
					);

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> $product_special,
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
						foreach($old_product_special as $product_special_item) {
							if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
								continue;
							}
							$product_special[] = $product_special_item;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $product_special;
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseArhipelagXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Архипелаг Синтез';
		$brandName = 'Septanaizer';
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Номенклатура'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[5]); 
					$primeCost = (int)trim($row[5]) * 0.85; // Себестоимость на 15% меньше, чем в файле
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $manufacturerName . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						$image = $manufacturer_image_path . '/' . $image_remote_url;
					} else {
						$image = 'no_image.png';
					}
					$image_remote_url = null;
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image1,
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					if (!empty($extra_image2)){	
						$product_images[] = array(
							'image' 					=> $manufacturer_image_path . '/' . $extra_image2,
							'image_uploaded_from_url'	=> null,
							'sort_order'				=> 0
						);
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseMioXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Matur Market';
		$brandName = 'MiO Nails';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Номенклатура'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = $row[5];
					$primeCost = $price; // Себестоимость пока равна цене продажи
					$quantity = $row[4];
					$weight = 0;
					$size = null;
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;
					$consist = '';

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseUnoXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Matur Market';
		$brandName = 'UNO';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Номенклатура'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = $row[5];
					$primeCost = $price; // Себестоимость пока равна цене продажи
					$quantity = $row[4];
					$weight = 0;
					$size = null;
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;
					$consist = '';

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}
	
	private function parseDewalXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		$manufacturerName = 'Matur Market';
		$brandName = 'DEWAL cosmetics';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Номенклатура'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[6]); // Цена РРЦ
					$price_for_masters = (int)trim($row[5]); // Цена для мастеров
					$primeCost = (int)trim($row[5]) * 0.85; // Себестоимость на 15% меньше, чем в файле
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[8];
					$extra_image1 = $row[9];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
					$product_special = array();
					$product_special[] = array(
						'customer_group_id'		=> $customer_group_id,
						'priority'				=> 1,
						'price'					=> $price_for_masters,
						'date_start'			=> '',
						'date_end'				=> ''
					);

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> $product_special,
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
						foreach($old_product_special as $product_special_item) {
							if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
								continue;
							}
							$product_special[] = $product_special_item;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $product_special;
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseErfolgXML($filename) {
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$this->load->model('localisation/city');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		$manufacturerName = 'Erfolg';		
		
		$manufacturer = $this->getManufacturerByName($manufacturerName, $languages);
		$manufacturer_id = $manufacturer['manufacturer_id'];		

		$xml = simplexml_load_file($filename);
		
		$category_names = array();
		
		foreach ($xml->shop->categories->category as $category) {
			$external_category_id = (string)$category['id'];	
			$category_names[$external_category_id] = $category;
		}

		$city_names = array('Казань', 'Зеленодольск', 'Бугульма', 'Альметьевск', 'Лениногорск', 'Арск', 'Набережные Челны', 'Нижнекамск', 'Чистополь', 'Елабуга', 'Другой населенный пункт Республики Татарстан');
		$product_city = array();
		foreach($city_names as $city_name) {
			$city_id = $this->model_localisation_city->getCityByName(trim($city_name))['city_id'];
			if (!$city_id) {
				$this->log->write('Не найден город ' . $city_name);
			} else {
				$product_city[] = $city_id; 
			}
		}

		$categories = array();

		foreach ($xml->shop->offers->offer as $offer) {
			$series = trim((string)$offer->series);

			$category_id = null;
			
			$category_id_with_series = (string)$offer->categoryId . '-' . $series;
			if (array_key_exists($category_id_with_series, $categories)) {
				$category_id = $categories[$category_id_with_series]; // Сначала поищем в кеш-массиве
			}

			if (!$category_id && isset($category_id)){ // 0 - означает, что категорию уже искали в БД и не нашли
				continue;
			}

			$brandName = trim((string)$offer->vendor) ? trim((string)$offer->vendor) : 'FarmaVita';
			$filter_id = $this->getBrandFilter($languages, $brandName);

			if (!$category_id){ // Если из кеша пришел null, то поищем в БД
				$category_name_with_series = $series ? $category_names[(string)$offer->categoryId] . '-' . $series : $category_names[(string)$offer->categoryId];
				$category_id = $this->getCategoryIdByManufacturerAndName($manufacturer, $category_name_with_series, $filter_id);
				$categories[$category_id_with_series] = $category_id; // Кладем в кеш (даже если 0 - это означает, что категорию уже искали в БД и не нашли)
				if (!$category_id){ // Если и в БД не нашли соответствие для такой категории, то пропустим ее
					continue;
				}
			}

			$SKU = trim((string)$offer['id']);
			$productName = htmlentities(trim((string)$offer->name));
			$productShortDescription = htmlentities(trim((string)$offer->description));
			$productDescription = htmlentities(trim((string)$offer->description));
			$price = (int)trim((string)$offer->oldprice) * 1.05; // Цена РРЦ
			if (!$price){
				$this->log->write('Для товара с offer_id = ' . $SKU . ' не указана цена РРЦ (oldprice)');
				continue;
			}
			$price_for_masters = (int)trim((string)$offer->price) * 1; // Цена для мастеров
			if ($price_for_masters > $price) {
				$price_for_masters = $price;
			}
			$primeCost = $price_for_masters / 1 * 0.85; // Себестоимость на 15% меньше, чем в файле
			$quantity = (int)trim((string)$offer->count);
			$weight = (float)trim((string)$offer->weight);
			$consist = null;
			$size = null;	
			$image_remote_url = trim((string)$offer->picture[0]);
			$extra_image1 = trim((string)$offer->picture[1]);
			$extra_image2 = trim((string)$offer->picture[2]);

			if (!empty($size) && count($size) > 0 && !empty($size[0])){
				$length = $size[0];		
				$width = $size[1];
				$height = $size[2];
			} else {
				$length = '';		
				$width = '';
				$height = '';
			}
			
			$modelPrefix = $manufacturerName . '-' . $brandName;

			$model = $modelPrefix . '-' . $SKU;

			$product = $this->model_catalog_product->getProductByModel($model);
		
			$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
			if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
				mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
			}

			if (!empty($image_remote_url)){
				if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
					$ext = pathinfo($image_remote_url)['extension'];
					$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
					file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
					$image = $image_path;
				} else {
					$image = $product['image'];
					$image_remote_url = $product['image_uploaded_from_url'];
				}
			} else {
				$image = 'no_image.png';
			}
						
			$product_images = array();
			
			if (!empty($extra_image1)){	
				$product_old_image = '';
				if (!empty($product)) {
					$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
				}

				if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
					$ext = pathinfo($extra_image1)['extension'];
					$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
					file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
					$product_images[] = array(
						'image' 					=> $image_path,
						'image_uploaded_from_url'	=> $extra_image1,
						'sort_order'				=> 0
					);
				} else {
					$product_images[] = $product_old_image;
				}
			}
			if (!empty($extra_image2)){
				$product_old_image = '';
				if (!empty($product)) {
					$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
				}

				if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
					$ext = pathinfo($extra_image2)['extension'];
					$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
					file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
					$product_images[] = array(
						'image' 					=> $image_path,
						'image_uploaded_from_url'	=> $extra_image2,
						'sort_order'				=> 0
					);
				} else {
					$product_images[] = $product_old_image;
				}
			}
			
			$product_description = array();
			foreach($languages as $language) {
				$product_description[$language['language_id']] = array(
						'name'             => $productName,
						'meta_title'       => $productName,
						'meta_h1'      	   => $productName,
						'meta_description' => $productName,
						'meta_keyword'     => $productName,
						'description'      => $productDescription,
						'tag'			   => '',
						'consist'		   => $consist
				);
			}

			$product_seo_url = array();
			foreach($languages as $language) {
				if (strtolower(trim($language['name'])) == 'ru') {
					$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
				} elseif (strtolower(trim($language['name'])) == 'en') {
					$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
				} else {
					$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
				}				
			}

			$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
			$product_special = array();
			$product_special[] = array(
				'customer_group_id'		=> $customer_group_id,
				'priority'				=> 1,
				'price'					=> $price_for_masters,
				'date_start'			=> '',
				'date_end'				=> ''
			);

			if (empty($product)){
				$product = array(
					'product_description'		=> $product_description,
					'model' 					=> $model,
					'sku' 						=> $SKU,
					'upc'						=> '',
					'ean'						=> '',
					'jan'						=> '',
					'isbn'						=> '',
					'mpn'						=> '',
					'location'					=> '',
					'product_store' 			=> array(0),
					'shipping'					=> 1,
					'price'						=> $price,
					'product_recurring'			=> array(),
					'tax_class_id'				=> 0,
					'date_available' 			=> date('Y-m-d'),
					'quantity' 					=> $quantity,				
					'minimum' 					=> 1,
					'subtract'					=> 1,
					'sort_order'				=> 1,
					'stock_status_id'			=> $stock_status_id,
					'status'					=> 1,
					'noindex' 					=> 1,						
					'weight'					=> $weight,
					'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
					'length' 					=> $length,
					'width' 					=> $width,
					'height' 					=> $height,
					'length_class_id'			=> $this->config->get('config_length_class_id'),
					'manufacturer_id'			=> $manufacturer_id,
					'main_category_id' 			=> $category_id,
					'product_category' 			=> array($category_id),
					'product_filter' 			=> array($filter_id),
					'product_attribute' 		=> array(),
					'product_option' 			=> array(),
					'product_discount' 			=> array(),
					'product_special' 			=> $product_special,
					'image'						=> $image,
					'image_uploaded_from_url'	=> $image_remote_url,
					'product_image'				=> $product_images,
					'product_related' 			=> array(),
					'product_related_article'   => array(),
					'points' 					=> '',
					'product_reward' 			=> array(),
					'product_seo_url' 			=> $product_seo_url,
					'product_layout'			=> array(),
					'prime_cost'				=> $primeCost, 
					'import_history_id'			=> $this->session->data['import_history_id'],
					'brand_name'				=> $brandName,
					'product_city'				=> $product_city
				);
				$this->model_catalog_product->addProduct($product);				
			} else {
				$product_id = $product['product_id'];

				$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
				foreach($languages as $language) {
					$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
										$product_old_description[$language['language_id']]['meta_title'] : $productName;
					$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
										$product_old_description[$language['language_id']]['meta_h1'] : $productName;
					$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
										$product_old_description[$language['language_id']]['meta_description'] : $productName;
					$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
										$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
					$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
										$product_old_description[$language['language_id']]['tag'] : '';							
				}

				$product_filter = $this->model_catalog_product->getProductFilters($product_id);
				if (!in_array($filter_id, $product_filter)){
					$product_filter[] = $filter_id;
				}

				$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
				foreach($old_product_special as $product_special_item) {
					if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
						continue;
					}
					$product_special[] = $product_special_item;
				}

				$product['product_description'] = $product_description;
				$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
				$product['product_filter'] = $product_filter;
				$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
				$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
				$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
				$product['product_special'] = $product_special;
				$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
				$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
				$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
				$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
				$product['product_seo_url'] = $product_seo_url;
				$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
				$product['product_store'] = array(0);
				$product['price'] = $price;
				$product['quantity'] = $quantity;
				$product['weight'] = $weight;
				$product['length'] = $length;
				$product['width'] = $width;
				$product['height'] = $height;
				$product['main_category_id'] = $category_id;
				$product['product_category'] = array($category_id);
				$product['image'] = $image;
				$product['product_image'] = $product_images;
				$product['image_uploaded_from_url'] = $image_remote_url;
				$product['prime_cost'] = $primeCost;
				$product['import_history_id'] = $this->session->data['import_history_id'];
				$product['brand_name'] = $brandName;
				$product['product_city'] = $product_city;
				$product['status'] = 1;

				$this->model_catalog_product->editProduct($product_id, $product);
			}
		}		
	}

	private function parseErfolgJSON($filename) {
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$this->load->model('localisation/city');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		$manufacturerName = 'Erfolg';		
		
		$manufacturer = $this->getManufacturerByName($manufacturerName, $languages);
		$manufacturer_id = $manufacturer['manufacturer_id'];	
		$categories = array();	
		$last_upload = null;

		$city_names = array('Казань', 'Зеленодольск', 'Бугульма', 'Альметьевск', 'Лениногорск', 'Арск', 'Набережные Челны', 'Нижнекамск', 'Чистополь', 'Елабуга', 'Другой населенный пункт Республики Татарстан');
		$product_city = array();
		foreach($city_names as $city_name) {
			$city_id = $this->model_localisation_city->getCityByName(trim($city_name))['city_id'];
			if (!$city_id) {
				$this->log->write('Не найден город ' . $city_name);
			} else {
				$product_city[] = $city_id; 
			}
		}

		$json_offers = json_decode(file_get_contents($filename)); 

		foreach ($json_offers as $offer) {
			$series = trim((string)$offer->series);
			$SKU = trim((string)$offer->id);

			$category_id = null;
			$category_id_with_series = (string)$offer->category_id . '-' . $series;
			if (array_key_exists($category_id_with_series, $categories)) {
				$category_id = $categories[$category_id_with_series]; // Сначала поищем в кеш-массиве
			}

			if (!$category_id && isset($category_id)){ // 0 - означает, что категорию уже искали в БД и не нашли
				continue;
			}

			$brandName = trim((string)$offer->vendor) ? trim((string)$offer->vendor) : 'FarmaVita';
			$filter_id = $this->getBrandFilter($languages, $brandName);

			if (!$category_id){ // Если из кеша пришел null, то поищем в БД
				$category_name_with_series = $series ? (string)$offer->category_name . '-' . $series : (string)$offer->category_name;
				$category_id = $this->getCategoryIdByManufacturerAndName($manufacturer, $category_name_with_series, $filter_id);
				$categories[$category_id_with_series] = $category_id; // Кладем в кеш (даже если 0 - это означает, что категорию уже искали в БД и не нашли)
				if (!$category_id){ // Если и в БД не нашли соответствие для такой категории, то пропустим ее
					continue;
				}
			}

			$productName = htmlentities(trim((string)$offer->name));
			$productShortDescription = htmlentities(trim((string)$offer->description));
			$productDescription = htmlentities(trim((string)$offer->description));
			$price = (int)trim((string)$offer->oldprice) * 1.05; // Цена РРЦ
			if (!$price){
				$this->log->write('Для товара с offer_id = ' . $SKU . ' не указана цена РРЦ (oldprice)');
				continue;
			}
			$price_for_masters = (int)trim((string)$offer->price) * 1; // Цена для мастеров
			if ($price_for_masters > $price) {
				$price_for_masters = $price;
			}
			$primeCost = $price_for_masters / 1 * 0.85; // Себестоимость на 15% меньше, чем в файле
			$quantity = (int)trim((string)$offer->count);
			$weight = (float)trim((string)$offer->weight);
			$consist = null;
			$size = null;	
			$image_remote_url = trim((string)$offer->picture[0]);
			$extra_image1 = isset($offer->picture[1]) ? trim((string)$offer->picture[1]) : null;
			$extra_image2 = isset($offer->picture[2]) ? trim((string)$offer->picture[2]) : null;

			if (!empty($size) && count($size) > 0 && !empty($size[0])){
				$length = $size[0];		
				$width = $size[1];
				$height = $size[2];
			} else {
				$length = '';		
				$width = '';
				$height = '';
			}
			
			if (!$last_upload || (DateTime::createFromFormat('Y-m-d H:i:s', (string)$offer->edited) > $last_upload)) {
				$last_upload = DateTime::createFromFormat('Y-m-d H:i:s', (string)$offer->edited); 
			}

			$modelPrefix = $manufacturerName . '-' . $brandName;

			$model = $modelPrefix . '-' . $SKU;

			$product = $this->model_catalog_product->getProductByModel($model);
		
			$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
			if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
				mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
			}

			if (!empty($image_remote_url)){
				if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
					$ext = pathinfo($image_remote_url)['extension'];
					$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
					file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
					$image = $image_path;
				} else {
					$image = $product['image'];
					$image_remote_url = $product['image_uploaded_from_url'];
				}
			} else {
				$image = 'no_image.png';
			}
						
			$product_images = array();
			
			if (!empty($extra_image1)){	
				$product_old_image = '';
				if (!empty($product)) {
					$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
				}

				if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
					$ext = pathinfo($extra_image1)['extension'];
					$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
					file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
					$product_images[] = array(
						'image' 					=> $image_path,
						'image_uploaded_from_url'	=> $extra_image1,
						'sort_order'				=> 0
					);
				} else {
					$product_images[] = $product_old_image;
				}
			}
			if (!empty($extra_image2)){
				$product_old_image = '';
				if (!empty($product)) {
					$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
				}

				if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
					$ext = pathinfo($extra_image2)['extension'];
					$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
					file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
					$product_images[] = array(
						'image' 					=> $image_path,
						'image_uploaded_from_url'	=> $extra_image2,
						'sort_order'				=> 0
					);
				} else {
					$product_images[] = $product_old_image;
				}
			}
			
			$product_description = array();
			foreach($languages as $language) {
				$product_description[$language['language_id']] = array(
						'name'             => $productName,
						'meta_title'       => $productName,
						'meta_h1'      	   => $productName,
						'meta_description' => $productName,
						'meta_keyword'     => $productName,
						'description'      => $productDescription,
						'tag'			   => '',
						'consist'		   => $consist
				);
			}

			$product_seo_url = array();
			foreach($languages as $language) {
				if (strtolower(trim($language['name'])) == 'ru') {
					$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
				} elseif (strtolower(trim($language['name'])) == 'en') {
					$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
				} else {
					$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
				}				
			}

			$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
			$product_special = array();
			$product_special[] = array(
				'customer_group_id'		=> $customer_group_id,
				'priority'				=> 1,
				'price'					=> $price_for_masters,
				'date_start'			=> '',
				'date_end'				=> ''
			);

			if (empty($product)){
				$product = array(
					'product_description'		=> $product_description,
					'model' 					=> $model,
					'sku' 						=> $SKU,
					'upc'						=> '',
					'ean'						=> '',
					'jan'						=> '',
					'isbn'						=> '',
					'mpn'						=> '',
					'location'					=> '',
					'product_store' 			=> array(0),
					'shipping'					=> 1,
					'price'						=> $price,
					'product_recurring'			=> array(),
					'tax_class_id'				=> 0,
					'date_available' 			=> date('Y-m-d'),
					'quantity' 					=> $quantity,				
					'minimum' 					=> 1,
					'subtract'					=> 1,
					'sort_order'				=> 1,
					'stock_status_id'			=> $stock_status_id,
					'status'					=> 1,
					'noindex' 					=> 1,						
					'weight'					=> $weight,
					'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
					'length' 					=> $length,
					'width' 					=> $width,
					'height' 					=> $height,
					'length_class_id'			=> $this->config->get('config_length_class_id'),
					'manufacturer_id'			=> $manufacturer_id,
					'main_category_id' 			=> $category_id,
					'product_category' 			=> array($category_id),
					'product_filter' 			=> array($filter_id),
					'product_attribute' 		=> array(),
					'product_option' 			=> array(),
					'product_discount' 			=> array(),
					'product_special' 			=> $product_special,
					'image'						=> $image,
					'image_uploaded_from_url'	=> $image_remote_url,
					'product_image'				=> $product_images,
					'product_related' 			=> array(),
					'product_related_article'   => array(),
					'points' 					=> '',
					'product_reward' 			=> array(),
					'product_seo_url' 			=> $product_seo_url,
					'product_layout'			=> array(),
					'prime_cost'				=> $primeCost, 
					'import_history_id'			=> $this->session->data['import_history_id'],
					'brand_name'				=> $brandName,
					'product_city'				=> $product_city
				);
				$this->model_catalog_product->addProduct($product);				
			} else {
				$product_id = $product['product_id'];

				$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
				foreach($languages as $language) {
					$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
										$product_old_description[$language['language_id']]['meta_title'] : $productName;
					$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
										$product_old_description[$language['language_id']]['meta_h1'] : $productName;
					$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
										$product_old_description[$language['language_id']]['meta_description'] : $productName;
					$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
										$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
					$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
										$product_old_description[$language['language_id']]['tag'] : '';							
				}

				$product_filter = $this->model_catalog_product->getProductFilters($product_id);
				if (!in_array($filter_id, $product_filter)){
					$product_filter[] = $filter_id;
				}

				$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
				foreach($old_product_special as $product_special_item) {
					if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
						continue;
					}
					$product_special[] = $product_special_item;
				}

				$product['product_description'] = $product_description;
				$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
				$product['product_filter'] = $product_filter;
				$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
				$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
				$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
				$product['product_special'] = $product_special;
				$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
				$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
				$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
				$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
				$product['product_seo_url'] = $product_seo_url;
				$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
				$product['product_store'] = array(0);
				$product['price'] = $price;
				$product['quantity'] = $quantity;
				$product['weight'] = $weight;
				$product['length'] = $length;
				$product['width'] = $width;
				$product['height'] = $height;
				$product['main_category_id'] = $category_id;
				$product['product_category'] = array($category_id);
				$product['image'] = $image;
				$product['product_image'] = $product_images;
				$product['image_uploaded_from_url'] = $image_remote_url;
				$product['prime_cost'] = $primeCost;
				$product['import_history_id'] = $this->session->data['import_history_id'];
				$product['brand_name'] = $brandName;
				$product['product_city'] = $product_city;
				$product['status'] = 1;

				$this->model_catalog_product->editProduct($product_id, $product);
			}
		
		}		

		$settings = $this->model_setting_setting->getSetting('import_settings');
		$last_upload_setting_exists = $settings['erfolg_last_upload'];

		if ($last_upload){
			if ($last_upload_setting_exists){
				$this->model_setting_setting->editSettingValue('import_settings', 'erfolg_last_upload', $last_upload->format('Y-m-d H:i:s'));
			} else {
				$this->model_setting_setting->addSettingValue('import_settings', 'erfolg_last_upload', $last_upload->format('Y-m-d H:i:s'));
			}
		}
	}

	private function parseKeuneXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Matur Market';
		$brandName = 'KEUNE';
		$modelPrefix = $manufacturerName . '-' . $brandName;

		$manufacturer = $this->getManufacturerByName($manufacturerName, $languages);
		$manufacturer_id = $manufacturer['manufacturer_id'];
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {				
				if ((!trim($row[1]) && !trim($row[2])) || trim($row[2]) == 'НАИМЕНОВАНИЕ'){
					continue;
				}	
				if (!trim($row[2])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[1]);
					$category_id = $this->getCategoryIdByManufacturerAndName($manufacturer, $categoryName, $filter_id);				
					if ($category_id == 0){
						continue;
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[5]) * 1.1; // Цена на 10% больше, чем в файле
					$primeCost = (int)trim($row[5]) * 0.95; // Себестоимость на 5% меньше, чем в файле
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[6];
					$extra_image1 = $row[7];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseOllinXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		$manufacturerName = 'Астория Косметик Казань';
		$brandName = 'OLLIN';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Ценовая группа/ Номенклатура/ Характеристика номенклатуры'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[6]) * 1.0; // Цена РРЦ
					$price_for_masters = (int)trim($row[5]) * 1.0; // Цена для мастеров
					$primeCost = (int)trim($row[5]) * 0.85; // Себестоимость на 15% меньше, чем в файле
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[8];
					$extra_image1 = $row[9];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
					$product_special = array();
					$product_special[] = array(
						'customer_group_id'		=> $customer_group_id,
						'priority'				=> 1,
						'price'					=> $price_for_masters,
						'date_start'			=> '',
						'date_end'				=> ''
					);

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> $product_special,
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
						foreach($old_product_special as $product_special_item) {
							if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
								continue;
							}
							$product_special[] = $product_special_item;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $product_special;
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseKondorXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		$manufacturerName = 'Астория Косметик Казань';
		$brandName = 'KONDOR';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Ценовая группа/ Номенклатура/ Характеристика номенклатуры'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[6]) * 1.0; // Цена РРЦ
					$price_for_masters = (int)trim($row[5]) * 1.0; // Цена для мастеров
					$primeCost = (int)trim($row[5]) * 0.85; // Себестоимость на 15% меньше, чем в файле
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[8];
					$extra_image1 = $row[9];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
					$product_special = array();
					$product_special[] = array(
						'customer_group_id'		=> $customer_group_id,
						'priority'				=> 1,
						'price'					=> $price_for_masters,
						'date_start'			=> '',
						'date_end'				=> ''
					);

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> $product_special,
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
						foreach($old_product_special as $product_special_item) {
							if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
								continue;
							}
							$product_special[] = $product_special_item;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $product_special;
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseMasterProfessionalXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'NASTYA Prof';
		$brandName = 'MASTER Professional';
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Ценовая группа/ Номенклатура/ Характеристика номенклатуры'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = $row[5];
					$primeCost = $price * 0.85; // Себестоимость на 15% меньше
					$quantity = $row[4];
					$weight = 0;
					$size = null;
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = null;
					$consist = '';

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parsePromanicureXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Promanicure';
		
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$brandName = $xlsx->getCell(0, 'D6');
			$modelPrefix = $manufacturerName . '-' . $brandName;
			$filter_id = $this->getBrandFilter($languages, $brandName);
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[1])) || trim($row[1]) == 'Код'){
					continue;
				}
				if (!is_numeric($row[1]) && $row[1] != ''){ // Если тут текст, то это наименование категории
					$categoryName = trim($row[1]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[3]));
					$productShortDescription = htmlentities(trim($row[5]));
					$productDescription = htmlentities(trim($row[5]));
					$price = $row[7];
					$primeCost = $price * 0.9; // Себестоимость на 10% меньше
					$quantity = $row[6];
					$weight = 0;
					$size = null;
					$image_remote_url = $row[8];
					$extra_image1 = $row[9];
					$extra_image2 = null;
					$consist = '';

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseLash116XLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;

		$manufacturerName = 'Lash116';
		
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$brandName = $xlsx->getCell(0, 'D6');
			$modelPrefix = $manufacturerName . '-' . $brandName;
			$filter_id = $this->getBrandFilter($languages, $brandName);
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {		
				if ((!trim($row[1])) || trim($row[1]) == 'Код'){
					continue;
				}

				if (trim($row[2])){ // Если тут НЕ пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар								
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);						
					$productName = htmlentities(trim($row[3]));
					$productShortDescription = htmlentities(trim($row[5]));
					$productDescription = htmlentities(trim($row[5]));
					$quantity = $row[6];
					$price = $row[7];
					$primeCost = $price * 0.8; // Себестоимость на 20% меньше
					$weight = 0;
					$size = null;
					$image_remote_url = $row[8];
					$extra_image1 = $row[9];
					$extra_image2 = null;
					$consist = '';

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> array(),
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $this->model_catalog_product->getProductSpecials($product_id);
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseTefiaXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$this->load->model('localisation/city');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		$manufacturerName = 'Matur Market';
		$brandName = 'TEFIA';
		$city_names = array('Самара');
		$modelPrefix = $manufacturerName . '-' . $brandName;
		$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
		if (empty($manufacturers)){
			$manufacturer_description = array();
			foreach($languages as $language) {
				$manufacturer_description[$language['language_id']] = array(
						'meta_title'       => $manufacturerName,
						'meta_h1'      	   => $manufacturerName,
						'meta_description' => $manufacturerName,
						'meta_keyword'     => $manufacturerName,
						'description'      => $manufacturerName
				);
			}

			$manufacturer = array(
				'manufacturer_description'			=> $manufacturer_description,
				'name'								=> $manufacturerName,
				'manufacturer_store' 				=> array(0),
				'image' 							=> '',
				'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
				'noindex'							=> 1,
				'manufacturer_layout'				=> array(),
				'sort_order' 						=> '',
				'product_related' 					=> array(),
				'article_related' 					=> array(),
				'manufacturer_seo_url' 				=> array()
			);

			$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
		} else {
			$manufacturer = $manufacturers[0];
			$manufacturer_id = $manufacturer['manufacturer_id'];
		}
	
		$filter_id = $this->getBrandFilter($languages, $brandName);

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ((!trim($row[2])) || trim($row[2]) == 'Ценовая группа/ Номенклатура/ Характеристика номенклатуры'){
					continue;
				}
				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$price = (int)trim($row[6]) * 1.0; // Цена РРЦ
					$price_for_masters = (int)trim($row[5]) * 1.0; // Цена для мастеров
					$primeCost = (int)trim($row[5]) * 1.0; // Себестоимость пока равна цене мастера
					$quantity = $row[4];
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[8];
					$extra_image1 = $row[9];
					$extra_image2 = null;

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . $this->make_chpu($productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $manufacturer_id . '-' . strtolower(trim($language['name'])) . '-' . $this->make_chpu($productName, true);
						}				
					}

					$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
					$product_special = array();
					$product_special[] = array(
						'customer_group_id'		=> $customer_group_id,
						'priority'				=> 1,
						'price'					=> $price_for_masters,
						'date_start'			=> '',
						'date_end'				=> ''
					);

					$product_city = array();
					foreach($city_names as $city_name) {
						$product_city[] = $this->model_localisation_city->getCityByName(trim($city_name))['city_id'];
					}

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> $product_special,
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName,
							'product_city'				=> $product_city
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
						foreach($old_product_special as $product_special_item) {
							if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
								continue;
							}
							$product_special[] = $product_special_item;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $product_special;
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;
						$product['product_city'] = $product_city;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function parseUniversalXLS($filename) {
		require_once(DIR_SYSTEM . 'library/SimpleXLSX.php');
		$this->load->model('catalog/category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$this->load->model('customer/customer_group');
		$this->load->model('localisation/city');
		$languages = $this->model_localisation_language->getLanguages();
		$stock_status_id = 5;
		$MASTER_CUSTOMER_GROUP_NAME = 'Мастер';

		if ( $xlsx = SimpleXLSX::parse($filename) ) {
			$manufacturerName = trim($xlsx->getCell(0, 'C2'));
			$our_comission = (int)$xlsx->getCell(0, 'C3');
			$brandName = trim($xlsx->getCell(0, 'C5'));
			$city_names = explode(',', $xlsx->getCell(0, 'C6'));
			$oblast_code = trim($xlsx->getCell(0, 'F2'));
			$modelPrefix = $manufacturerName . '-' . $brandName . '-' . $oblast_code;
			$manufacturers = $this->model_catalog_manufacturer->getManufacturers(["filter_name" => $manufacturerName]);
			
			$product_city = array();
			foreach($city_names as $city_name) {
				$city_id = $this->model_localisation_city->getCityByName(trim($city_name))['city_id'];
				if (!$city_id) {
					$this->log->write('Не найден город ' . $city_name);
				} else {
					$product_city[] = $city_id; 
				}
			}

			if (empty($manufacturers)){
				$manufacturer_description = array();
				foreach($languages as $language) {
					$manufacturer_description[$language['language_id']] = array(
							'meta_title'       => $manufacturerName,
							'meta_h1'      	   => $manufacturerName,
							'meta_description' => $manufacturerName,
							'meta_keyword'     => $manufacturerName,
							'description'      => $manufacturerName
					);
				}

				$manufacturer = array(
					'manufacturer_description'			=> $manufacturer_description,
					'name'								=> $manufacturerName,
					'manufacturer_store' 				=> array(0),
					'image' 							=> '',
					'thumb' 							=> $this->model_tool_image->resize('no_image.png', 100, 100),
					'placeholder'						=> $this->model_tool_image->resize('no_image.png', 100, 100),
					'noindex'							=> 1,
					'manufacturer_layout'				=> array(),
					'sort_order' 						=> '',
					'product_related' 					=> array(),
					'article_related' 					=> array(),
					'manufacturer_seo_url' 				=> array()
				);

				$manufacturer_id = $this->model_catalog_manufacturer->addManufacturer($manufacturer);
			} else {
				$manufacturer = $manufacturers[0];
				$manufacturer_id = $manufacturer['manufacturer_id'];
			}
		
			$filter_id = $this->getBrandFilter($languages, $brandName);
		
			$category_id = 0;
			foreach ( $xlsx->rows() as $r => $row ) {
				if ($r<9 || (!trim($row[1]) && !trim($row[2]))){  // Пропустим шапку
					continue;
				}

				if (!trim($row[1])){ // Если тут пусто, то это наименование категории
					$categoryName = trim($row[2]);
					$category = $this->model_catalog_category->getCategoryByManufacturerAndImportName($manufacturer_id, $categoryName);

					if (empty($category['category_id'])){ 
						// Убрал создание категории, а вместо этого, если не нашли соответствия, то ругаемся и не грузим ни категорию, ни ее товары
						$this->log->write('Не найдено соответствие категории "' . $categoryName . '" для поставщика ' . $manufacturer['name'] . '. Категория и ее товары не загружены! ');
						$category_id = 0;

						// А также добавим эту категорию в соответсвтия, но с пустой категорией, если еще не добавлена
						if (empty($category['import_name'])){
							$matching = array(
								'manufacturer_id'	=> $manufacturer_id,
								'category_id'		=> null,
								'import_name'		=> $categoryName
							);
							$category = $this->model_catalog_category->addCategoryMathings($matching);
						}
						continue;
					} else {
						$category_id = $category['category_id'];
						$this->addBrandFilterToCategoryIfNotExists($category_id, $filter_id);
						$this->addCitiesToCategoryIfNotExists($category_id, $product_city);
					}
				} else { // Иначе это товар
					if ($category_id == 0){
						continue;
					}

					$SKU = trim($row[1]);
					$productName = htmlentities(trim($row[2]));
					$productShortDescription = htmlentities(trim($row[3]));
					$productDescription = htmlentities(trim($row[3]));
					$quantity = $row[4];					 
					$price_for_masters = (int)trim($row[5]); // Цена для мастеров
					$price = (int)trim($row[6]); // Цена РРЦ
					$primeCost = $price_for_masters * (100 - $our_comission)/100;
					$weight = 0;
					$consist = null;
					$size = null;	
					$image_remote_url = $row[7];
					$extra_image1 = $row[8];
					$extra_image2 = $row[9];

					if (!empty($size) && count($size) > 0 && !empty($size[0])){
						$length = $size[0];		
						$width = $size[1];
						$height = $size[2];
					} else {
						$length = '';		
						$width = '';
						$height = '';
					}
					$model = $modelPrefix . '-' . $SKU;

					$product = $this->model_catalog_product->getProductByModel($model);
				
					$manufacturer_image_path = 'catalog/manufacturers/' . $manufacturer_id;
					if (!is_dir(DIR_IMAGE . $manufacturer_image_path)) {
						mkdir(DIR_IMAGE . $manufacturer_image_path, 0775, true);
					}

					if (!empty($image_remote_url)){
						if (empty($product) || ($product['image_uploaded_from_url'] != $image_remote_url) || !is_file(DIR_IMAGE . $product['image'])){
							$ext = pathinfo($image_remote_url)['extension'];
							if (!$ext) {
								$this->log->write('Error loading product with model = ' . $model);
							}
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($image_remote_url));
							$image = $image_path;
						} else {
							$image = $product['image'];
							$image_remote_url = $product['image_uploaded_from_url'];
						}
					} else {
						$image = 'no_image.png';
					}
								
					$product_images = array();
					
					if (!empty($extra_image1)){	
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image1);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image1)['extension'];
							if (!$ext) {
								$this->log->write('Error loading product with model = ' . $model);
							}
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image1));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image1,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					if (!empty($extra_image2)){
						$product_old_image = '';
						if (!empty($product)) {
							$product_old_image = $this->model_catalog_product->getProductImageByUploadedURL($product['product_id'], $extra_image2);
						}

						if (empty($product_old_image) || !is_file(DIR_IMAGE . $product_old_image['image'])){
							$ext = pathinfo($extra_image2)['extension'];
							if (!$ext) {
								$this->log->write('Error loading product with model = ' . $model);
							}
							$image_path = $manufacturer_image_path . '/' . token(10) . '.' . ($ext ? $ext : 'png');
							file_put_contents(DIR_IMAGE . $image_path, file_get_contents($extra_image2));
							$product_images[] = array(
								'image' 					=> $image_path,
								'image_uploaded_from_url'	=> $extra_image2,
								'sort_order'				=> 0
							);
						} else {
							$product_images[] = $product_old_image;
						}
					}
					
					$product_description = array();
					foreach($languages as $language) {
						$product_description[$language['language_id']] = array(
								'name'             => $productName,
								'meta_title'       => $productName,
								'meta_h1'      	   => $productName,
								'meta_description' => $productName,
								'meta_keyword'     => $productName,
								'description'      => $productDescription,
								'tag'			   => '',
								'consist'		   => $consist
						);
					}

					$product_seo_url = array();
					foreach($languages as $language) {
						if (strtolower(trim($language['name'])) == 'ru') {
							$product_seo_url[0][$language['language_id']] = $this->make_chpu($model . '-' . $productName);
						} elseif (strtolower(trim($language['name'])) == 'en') {
							$product_seo_url[0][$language['language_id']] = $this->make_chpu($model . '-en-' . $productName, true);
						} else {
							$product_seo_url[0][$language['language_id']] = $this->make_chpu($model . '-' . strtolower(trim($language['name'])) . '-' . $productName, true);
						}				
					}

					$customer_group_id = $this->model_customer_customer_group->getCustomerGroupByName($MASTER_CUSTOMER_GROUP_NAME)['customer_group_id'];
					$product_special = array();
					if ($price_for_masters <> $price) {
						$product_special[] = array(
							'customer_group_id'		=> $customer_group_id,
							'priority'				=> 1,
							'price'					=> $price_for_masters,
							'date_start'			=> '',
							'date_end'				=> ''
						);
					}					

					if (empty($product)){
						$product = array(
							'product_description'		=> $product_description,
							'model' 					=> $model,
							'sku' 						=> $SKU,
							'upc'						=> '',
							'ean'						=> '',
							'jan'						=> '',
							'isbn'						=> '',
							'mpn'						=> '',
							'location'					=> '',
							'product_store' 			=> array(0),
							'shipping'					=> 1,
							'price'						=> $price,
							'product_recurring'			=> array(),
							'tax_class_id'				=> 0,
							'date_available' 			=> date('Y-m-d'),
							'quantity' 					=> $quantity,				
							'minimum' 					=> 1,
							'subtract'					=> 1,
							'sort_order'				=> 1,
							'stock_status_id'			=> $stock_status_id,
							'status'					=> 1,
							'noindex' 					=> 1,						
							'weight'					=> $weight,
							'weight_class_id' 			=> $this->config->get('config_weight_class_id'),
							'length' 					=> $length,
							'width' 					=> $width,
							'height' 					=> $height,
							'length_class_id'			=> $this->config->get('config_length_class_id'),
							'manufacturer_id'			=> $manufacturer_id,
							'main_category_id' 			=> $category_id,
							'product_category' 			=> array($category_id),
							'product_filter' 			=> array($filter_id),
							'product_attribute' 		=> array(),
							'product_option' 			=> array(),
							'product_discount' 			=> array(),
							'product_special' 			=> $product_special,
							'image'						=> $image,
							'image_uploaded_from_url'	=> $image_remote_url,
							'product_image'				=> $product_images,
							'product_related' 			=> array(),
							'product_related_article'   => array(),
							'points' 					=> '',
							'product_reward' 			=> array(),
							'product_seo_url' 			=> $product_seo_url,
							'product_layout'			=> array(),
							'prime_cost'				=> $primeCost, 
							'import_history_id'			=> $this->session->data['import_history_id'],
							'brand_name'				=> $brandName,
							'product_city'				=> $product_city
						);
						$this->model_catalog_product->addProduct($product);				
					} else {
						$product_id = $product['product_id'];

						$product_old_description = $this->model_catalog_product->getProductDescriptions($product_id);
						foreach($languages as $language) {
							$product_description[$language['language_id']]['meta_title'] = isset($product_old_description[$language['language_id']]['meta_title']) ? 
												$product_old_description[$language['language_id']]['meta_title'] : $productName;
							$product_description[$language['language_id']]['meta_h1'] = isset($product_old_description[$language['language_id']]['meta_h1']) ? 
												$product_old_description[$language['language_id']]['meta_h1'] : $productName;
							$product_description[$language['language_id']]['meta_description'] = isset($product_old_description[$language['language_id']]['meta_description']) ? 
												$product_old_description[$language['language_id']]['meta_description'] : $productName;
							$product_description[$language['language_id']]['meta_keyword'] = isset($product_old_description[$language['language_id']]['meta_keyword']) ? 
												$product_old_description[$language['language_id']]['meta_keyword'] : $productName;
							$product_description[$language['language_id']]['tag'] = isset($product_old_description[$language['language_id']]['tag']) ? 
												$product_old_description[$language['language_id']]['tag'] : '';							
						}

						$product_filter = $this->model_catalog_product->getProductFilters($product_id);
						if (!in_array($filter_id, $product_filter)){
							$product_filter[] = $filter_id;
						}

						$old_product_special = $this->model_catalog_product->getProductSpecials($product_id);
						foreach($old_product_special as $product_special_item) {
							if ($product_special_item['customer_group_id'] == $customer_group_id && $product_special_item['priority'] == 1){
								continue;
							}
							$product_special[] = $product_special_item;
						}

						$product['product_description'] = $product_description;
						$product['product_recurring'] = $this->model_catalog_product->getRecurrings($product_id);
						$product['product_filter'] = $product_filter;
						$product['product_attribute'] = $this->model_catalog_product->getProductAttributes($product_id);
						$product['product_option'] = $this->model_catalog_product->getProductOptions($product_id);
						$product['product_discount'] = $this->model_catalog_product->getProductDiscounts($product_id);
						$product['product_special'] = $product_special;
						$product['product_image'] = $this->model_catalog_product->getProductImages($product_id);
						$product['product_related'] = $this->model_catalog_product->getProductRelated($product_id);
						$product['product_related_article'] = $this->model_catalog_product->getArticleRelated($product_id);
						$product['product_reward'] = $this->model_catalog_product->getProductRewards($product_id);
						$product['product_seo_url'] = $product_seo_url;
						$product['product_layout'] = $this->model_catalog_product->getProductLayouts($product_id);
						$product['product_store'] = array(0);
						$product['price'] = $price;
						$product['quantity'] = $quantity;
						$product['weight'] = $weight;
						$product['length'] = $length;
						$product['width'] = $width;
						$product['height'] = $height;
						$product['main_category_id'] = $category_id;
						$product['product_category'] = array($category_id);
						$product['image'] = $image;
						$product['product_image'] = $product_images;
						$product['image_uploaded_from_url'] = $image_remote_url;
						$product['prime_cost'] = $primeCost;
						$product['import_history_id'] = $this->session->data['import_history_id'];
						$product['brand_name'] = $brandName;
						$product['product_city'] = $product_city;
						$product['status'] = 1;

						$this->model_catalog_product->editProduct($product_id, $product);
					}
				}
			}
		} else {
			$this->log->write(SimpleXLSX::parseError());
		}		
	}

	private function make_chpu($value, $with_translit = false) {
		$converter = array(
			'а' => 'a',    'б' => 'b',    'в' => 'v',    'г' => 'g',    'д' => 'd',
			'е' => 'e',    'ё' => 'e',    'ж' => 'zh',   'з' => 'z',    'и' => 'i',
			'й' => 'y',    'к' => 'k',    'л' => 'l',    'м' => 'm',    'н' => 'n',
			'о' => 'o',    'п' => 'p',    'р' => 'r',    'с' => 's',    'т' => 't',
			'у' => 'u',    'ф' => 'f',    'х' => 'h',    'ц' => 'c',    'ч' => 'ch',
			'ш' => 'sh',   'щ' => 'sch',  'ь' => '',     'ы' => 'y',    'ъ' => '',
			'э' => 'e',    'ю' => 'yu',   'я' => 'ya',
		);
	
		$value = mb_strtolower($value);
		if ($with_translit){
			$value = strtr($value, $converter);
			$value = mb_ereg_replace('[^-0-9a-z]', '-', $value);
		} else {
			$value = preg_replace('/[^a-zA-Zа-яА-Я0-9-]/ui', '-', $value);
		}

		$value = mb_ereg_replace('[-]+', '-', $value);
		$value = trim($value, '-');	
	
		return $value;
	}

	private function mb_ucfirst($str) {
		$fc = mb_strtoupper(mb_substr($str, 0, 1));
		return $fc.mb_substr($str, 1);
	}

	private function getBrandFilter($languages, $filterName){
		$this->load->model('catalog/filter');

		$filter = $this->model_catalog_filter->getFilters(array('filter_name' => $filterName));

		if (empty($filter)){
			$filterDescription = array();
			foreach($languages as $language) {
				$filterDescription[$language['language_id']] = array('name' => $filterName);
			}
			$filter = array(
				'filter_id'					=> 0,
				'sort_order'				=> 1,
				'filter_description' 		=> $filterDescription
			);

			$filterGroup = $this->model_catalog_filter->getFilterGroupByName('Бренд');
			if (empty($filterGroup)){
				$filterGroupDescription = array();
				foreach($languages as $language) {
					$filterGroupDescription[$language['language_id']] = array('name' => 'Бренд');
				}
				$filterGroup = array(
					'sort_order'					=> 1,
					'filter_group_description' 		=> $filterGroupDescription,
					'filter'						=> array($filter)
				);
				$this->model_catalog_filter->addFilter($filterGroup);
			} else {
				$filterGroupId = $filterGroup['filter_group_id'];
				
				$filterList = $this->model_catalog_filter->getFilterDescriptions($filterGroupId);
				$filterList[] = $filter;
				$filterGroup['filter'] = $filterList;

				$filterGroupDescriptionList = $this->model_catalog_filter->getFilterGroupDescriptions($filterGroupId);
				$filterGroup['filter_group_description'] = $filterGroupDescriptionList;

				$this->model_catalog_filter->editFilter($filterGroupId, $filterGroup);
			}

			$filter = $this->model_catalog_filter->getFilters(array('filter_name' => $filterName))[0];
		} else {
			$filter = $filter[0];
		}

		return $filter['filter_id'];
	}

	private function addBrandFilterToCategoryIfNotExists($category_id, $filter_id){
		$this->load->model('catalog/category');

		while($category_id){
			$filterIDList = $this->model_catalog_category->getCategoryFilters($category_id);
			if (!in_array($filter_id, $filterIDList)){
				$this->model_catalog_category->addCategoryFilters($category_id, $filter_id);
			}
			$category_id = $this->model_catalog_category->getCategory($category_id)['parent_id'];
		}
	}

	private function addCitiesToCategoryIfNotExists($category_id, $cities){
		$this->load->model('catalog/category');

		while($category_id){
			$currentCitiesList = $this->model_catalog_category->getCategoryCities($category_id);
			foreach ($cities as $city_id) {
				if (!in_array($city_id, $currentCitiesList)){
					$this->model_catalog_category->addCategoryToCity($category_id, $city_id);
				}
			}
			$category_id = $this->model_catalog_category->getCategory($category_id)['parent_id'];
		}
	}
}