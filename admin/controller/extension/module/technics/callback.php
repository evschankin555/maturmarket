<?php    
class ControllerExtensionModuleTechnicsCallback extends Controller { 
	private $error = array();
  
  	public function index() {
		$this->load->language('extension/module/technics/callback');
		
		$this->document->setTitle($this->language->get('heading_title'));
		 
		$this->load->model('extension/theme/callback');

		$this->load->model('setting/store');
		
    		$this->getList();
  	}

  	public function update() {
		$this->load->language('extension/module/technics/callback');

    	$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('extension/theme/callback');

		$this->load->model('setting/store');
		
    	if (($this->request->server['REQUEST_METHOD'] == 'POST')) {


			$this->model_extension_theme_callback->editCallback($this->request->get['callback_id'], $this->request->post);



			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';
			
			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
			$this->response->redirect($this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . $url, true));
			
		}
    
    	$this->getForm();
  	}   

  

   
  	public function update_batch() { 
		$this->load->language('extension/module/technics/callback');

    	$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('extension/theme/callback');
		
    	if (($this->request->server['REQUEST_METHOD'] == 'POST') && isset($this->request->post['selected'])) {

			foreach ($this->request->post['selected'] as $callback_id) {
				$this->model_extension_theme_callback->editCallbacks($callback_id);
			}


			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';
			
			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
			
			$this->response->redirect($this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}
    
    	$this->getList();
  	}   

  	public function delete() {
		$this->load->language('extension/module/technics/callback');

    	$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('extension/theme/callback');
			
    	if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $callback_id) {
				$this->model_extension_theme_callback->deleteCallback($callback_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			
			$url = '';
			
			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}
			
			$this->response->redirect($this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . $url, true));
    	}
	
    	$this->getList();
  	}  
    
  	private function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'call_id';
		}
		
		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}
		
		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
				
		$url = '';
			
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      		'separator' => false
   		);

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get($this->language->get('heading_title')),
			'href'      => $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'),
      		'separator' => ' :: '
   		);
							
		$data['insert'] = $this->url->link('extension/module/technics/callback/insert', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');
		$data['delete'] = $this->url->link('extension/module/technics/callback/delete', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');	

		$data['callbacks'] = array();

		$dataF = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);
		
		$callbacks_total = $this->model_extension_theme_callback->getTotalCallbacks();
	
		$results = $this->model_extension_theme_callback->getCallbacks($dataF);

 
    		foreach ($results as $result) {

				$action = $this->url->link('extension/module/technics/callback/update', 'user_token=' . $this->session->data['user_token'] . '&callback_id=' . $result['call_id'] . $url, 'SSL');


			if ($result['status_id'] == '0'){
				$status = $this->language->get('status_wait');
			}else{
				$status = $this->language->get('status_done');
			}

			$store_name = $this->config->get('config_name');

			if ($result['store_id']) {
				$storeInfo = $this->model_setting_store->getStore((int)$result['store_id']);
				$store_name = $storeInfo['name'];
			}
						
			$data['callbacks'][] = array(
				'callback_id' => $result['call_id'],
				'name'            => $result['name'],
				'store_name'     => $store_name,
				'telephone'      => $result['telephone'],
				'date_added'     => date('j.m.Y - G:i', strtotime($result['date_added'])),
				'date_modified'  => date('j.m.Y - G:i', strtotime($result['date_modified'])),
				'comment'        => nl2br($result['comment']),
				'action'          => $action,
				'status'          => $status,
				'selected'        => isset($this->request->post['selected']) && in_array($result['call_id'], $this->request->post['selected']),
			);
		}	
	
		$data['heading_title'] = $this->language->get('heading_title');
		
		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['column_name'] = $this->language->get('column_name');
		$data['column_telephone'] = $this->language->get('column_telephone');
		$data['column_date_added'] = $this->language->get('column_date_added');

    		$data['text_comment'] = $this->language->get('text_comment');
    		$data['text_status'] = $this->language->get('text_status');
    		$data['text_added'] = $this->language->get('text_added');
    		$data['text_modified'] = $this->language->get('text_modified');
    		$data['text_action'] = $this->language->get('text_action');
    		$data['text_edit'] = $this->language->get('text_edit');
		
		$data['button_insert'] = $this->language->get('button_insert');
		$data['button_delete'] = $this->language->get('button_delete');



    		$data['status_wait'] = $this->language->get('status_wait');
    		$data['status_done'] = $this->language->get('status_done');
 
 		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
		
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		$data['sort_call_id'] = $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . '&sort=call_id' . $url, 'SSL');		
		$data['sort_store_id'] = $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . '&sort=store_id' . $url, 'SSL');
		$data['sort_name'] = $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, 'SSL');
		$data['sort_telephone'] = $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . '&sort=telephone' . $url, 'SSL');
		
		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}
												
		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $callbacks_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', 'SSL');
			
		$data['pagination'] = $pagination->render();

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['update'] = $this->url->link('extension/module/technics/callback/update_batch', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');	

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
		$data['column_left'] = $this->load->controller('common/column_left');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/callback_list', $data));				

	}
  

	 
  	private function validateForm() {
    	if (!$this->user->hasPermission('modify', 'extension/module/technics/callback')) {
      		$this->error['warning'] = $this->language->get('error_permission');
    	}

    	if ((strlen(utf8_decode($this->request->post['name'])) < 3) || (strlen(utf8_decode($this->request->post['name'])) > 64)) {
      		$this->error['name'] = $this->language->get('error_name');
    	}
		
		if (!$this->error) {
	  		return true;
		} else {
	  		return false;
		}
  	}    

  	private function validateDelete() {
    	if (!$this->user->hasPermission('modify', 'extension/module/technics/callback')) {
			$this->error['warning'] = $this->language->get('error_permission');
    	}	
			
		if (!$this->error) {
	  		return true;
		} else {
	  		return false;
		}  
  	}

  	private function getForm() {

 	   	$data['heading_title'] = $this->language->get('heading_title');
    		$data['button_save'] = $this->language->get('button_save');
    		$data['button_cancel'] = $this->language->get('button_cancel');

    		$data['text_id'] = $this->language->get('text_id');
    		$data['text_name'] = $this->language->get('text_name');
    		$data['text_telephone'] = $this->language->get('text_telephone');
    		$data['text_comment'] = $this->language->get('text_comment');
    		$data['text_status'] = $this->language->get('text_status');
    		$data['text_added'] = $this->language->get('text_added');
    		$data['text_modified'] = $this->language->get('text_modified');

    		$data['status_wait'] = $this->language->get('status_wait');
    		$data['status_done'] = $this->language->get('status_done');


  		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
      		'separator' => false
   		);
		$url = '';

   		$data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'),
      		'separator' => ' :: '
   		);

		$data['action'] = $this->url->link('extension/module/technics/callback/update', 'user_token=' . $this->session->data['user_token'] . '&callback_id=' . $this->request->get['callback_id'] . $url, 'SSL');

		$data['cancel'] = $this->url->link('extension/module/technics/callback', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL');

		$data['user_token'] = $this->session->data['user_token'];

    		if (isset($this->request->get['callback_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
      			$callback_info = $this->model_extension_theme_callback->getCallback($this->request->get['callback_id']);
    		}

		if (isset($this->request->post['comment'])) {
			$data['comment'] = $this->request->post['comment'];
		} elseif (isset($callback_info)) {
			$data['comment'] = $callback_info['comment'];
		} 

		$data['store_name'] = $this->config->get('config_name');

		if ($callback_info['store_id']) {
			$storeInfo = $this->model_setting_store->getStore((int)$callback_info['store_id']);
			$data['store_name'] = $storeInfo['name'];
		}


		if (isset($this->request->post['status_id'])) {
			$data['status_id'] = $this->request->post['status_id'];
		} elseif (isset($callback_info)) {
			$data['status_id'] = $callback_info['status_id'];
		} 

		$data['callback_id'] = $callback_info['call_id'];
		$data['name'] = $callback_info['name'];
		$data['telephone'] = $callback_info['telephone'];
		$data['date_added'] = $callback_info['date_added'];
		$data['date_modified'] = $callback_info['date_modified'];


		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
		$data['column_left'] = $this->load->controller('common/column_left');
				
		$this->response->setOutput($this->load->view('extension/module/technicscatalog/callback_form', $data));

	}

}
?>
