<?php
class ControllerCommonCachedImage extends Controller {
	public function index() {
        $data = array();
		if (!isset($this->request->get['image'])) {
            return new Action('error/not_found');
        }
        
        $image = $this->request->get['image'];

        $width = 0;
        $height = 0;
        if (strripos($image, '-') > 1 && strripos($image, '.') > strripos($image, '-')){
            $size_str = substr($image, strripos($image, '-') + 1, strripos($image, '.') - strripos($image, '-') - 1);            
            if (strripos($size_str, 'x') > 0){                
                $width = substr($size_str, 0, strripos($size_str, 'x'));
                $height = substr($size_str, strripos($size_str, 'x') + 1);
            }
        }

        if ($width && $height){
            $this->load->model('tool/image');
            $full_image = substr_replace($image, '', strripos($image, $size_str) - 1, strlen($size_str) + 1); // Уберем инфу о размере
            $this->model_tool_image->resize($full_image, $width, $height);
        }
        
        $file = DIR_IMAGE . 'cache/' . $image;
        if (!headers_sent()) {
            if (file_exists($file)) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: inline; filename="' . basename($file) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));

                if (ob_get_level()) {
                    ob_end_clean();
                }

                readfile($file, 'rb');

                exit();
            } else {
                return new Action('error/not_found');
            }
        } else {
            exit('Error: Headers already sent out!');
        }
        //$this->response->setOutput($this->load->view('error/not_found', $data));        
	}
}