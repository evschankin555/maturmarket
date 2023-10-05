<?php 
class ControllerExtensionModuleCallback extends Controller { 
	private $error = array();
 
	public function index() {
   		$this->language->load('extension/module/callback');
		$json = array();
  
//    	$this->document->setTitle($this->language->get('heading_title'));

//    	$data['heading_title'] = $this->language->get('heading_title');

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['action']) && $this->config->get('theme_technics_callback_status')) {
			if ($this->validate()) {
				$data = array();
				if (isset($this->request->post['name'])) {
  		    			$data['name'] = $this->request->post['name'];
				} else {
      					$data['name'] = '';
    				}
				if (isset($this->request->post['phone'])) {
      					$data['phone'] = $this->request->post['phone'];
				} else {
      					$data['phone'] = '';
    				}
				if (isset($this->request->post['comment'])) {
      					$data['comment'] = $this->request->post['comment'];
				} else {
      					$data['comment'] = '';
    				}
    			$data['comment'] .= "\n\nWas sent from - ";
				if ($this->request->server['HTTP_REFERER']) {
      					$data['comment'] .= $this->request->server['HTTP_REFERER'];
				} else {
      					$data['comment'] .= "Unknown";
    				}   
				
				$this->load->model('extension/module/callback');
				$results = $this->model_extension_module_callback->addCallback($data);
				if ($this->config->get('theme_technics_callback_email_alert')) {
					$this->sendMail($data);
				}
				$json['success'] = $this->language->get('ok');
			}else{
				$json['warning'] = $this->error;
			}

			return $this->response->setOutput(json_encode($json));
		}
	
			// Captcha
			if ($this->config->get('theme_technics_config_captcha_cb'))  {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('theme_technics_config_captcha_cb'));

			} else {
				$data['captcha'] = '';
			}

     		$data['sendthis'] = $this->language->get('sendthis');
     		$data['namew'] = $this->language->get('namew');
     		$data['phonew'] = $this->language->get('phonew');	
     		$data['sendw'] = $this->language->get('sendw');
     		$data['cancel'] = $this->language->get('cancel');	
     		$data['comment'] = $this->language->get('comment');	

			if ($this->config->get('theme_technics_callback_pdata')) {
				$this->load->language('extension/theme/technics');
				$this->load->model('catalog/information');

				$information_info = $this->model_catalog_information->getInformation($this->config->get('theme_technics_callback_pdata'));

				if ($information_info) {
					$data['text_technics_pdata'] = sprintf($this->language->get('text_technics_pdata'), $this->language->get('sendw'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('theme_technics_callback_pdata'), true), $information_info['title'], $information_info['title']);
				} else {
					$data['text_technics_pdata'] = '';
				}
			} else {
				$data['text_technics_pdata'] = '';
			}

			return $this->load->view('extension/module/callback', $data);		
//			$this->response->setOutput($this->load->view('extension/module/callback', $data));			
				
  	}

  	private function validate() {
   		$this->language->load('extension/module/callback');
   	 	if ((strlen(utf8_decode($this->request->post['name'])) < 1) || (strlen(utf8_decode($this->request->post['name'])) > 32)) {
      			$this->error['name'] = $this->language->get('mister');
    		}
    		if ((strlen(utf8_decode($this->request->post['phone'])) < 3) || (strlen(utf8_decode($this->request->post['phone'])) > 32)) {
      			$this->error['phone'] = $this->language->get('wrongnumber');
    		}
		
			// Captcha
			if ($this->config->get('captcha_' . $this->config->get('theme_technics_config_captcha_cb') . '_status')) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('theme_technics_config_captcha_cb') . '/validate');

				if ($captcha) {
					$this->error['captcha'] = $captcha;
				}
			}

    		if (!$this->error) {
     	 		return true;
    		} else {
     			return false;
   	 	}
	}
  	private function sendMail($data) {
		$subject = $this->language->get('subject');
		$text 	= $this->language->get('text_1');
		$text 	.= $this->language->get('subject') . ":\n";
		$text 	.= $this->language->get('firstname') . $data['name'] . "\n";
		$text 	.= $this->language->get('phone') . $data['phone'] . "\n";
		$text 	.= $this->language->get('comment') . $data['comment'] . "\n";

		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
		$mail->setTo($this->config->get('config_email'));
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($this->config->get('config_name'));
		$mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
		$mail->setText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
		$mail->send();

		// Send to additional alert emails
		$emails = explode(',', $this->config->get('config_mail_alert_email'));
				
		foreach ($emails as $email) {
			if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$mail->setTo($email);
				$mail->send();
			}
		}	

	}
}
?>
