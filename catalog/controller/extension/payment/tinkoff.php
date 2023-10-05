<?php

class ControllerExtensionPaymentTinkoff extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/tinkoff');
        $this->load->model('extension/payment/tinkoff');
        $url = $this->model_extension_payment_tinkoff->getPaymentUrl();
        $data = [
            'status' => 'error',
        ];

        if ($url) {
            $data = [
                'status' => 'success',
                'url' => $url,
            ];
        }

        return $this->load->view('extension/payment/tinkoff', $data);
    }

    public function callback()
    {
        $request = json_decode(file_get_contents("php://input"));
        $request->Success = $request->Success ? 'true' : 'false';
        $request = (array)$request;
        $this->load->model('extension/payment/tinkoff');

        if ($request['Token'] !== $this->model_extension_payment_tinkoff->getToken($request)) {
            die('NOTOK');
        }

        $this->load->model('checkout/order');
        $order = $this->model_checkout_order->getOrder($request['OrderId']);

        if (!$order) {
            die('NOTOK');
        }

        if ($request['Amount'] < (float)$order['total'] * 100) {
            die('NOTOK');
        }

        $shipping_code = explode('.', $order['shipping_code'])[0];
        $this->load->language('extension/shipping/'.$shipping_code);
        $shipping_order_status_id = $this->config->get('shipping_'.$shipping_code.'_order_status_id');

        switch ($request['Status']) {
            case 'AUTHORIZED':
                $status = $shipping_order_status_id ? $shipping_order_status_id : $this->config->get('payment_tinkoff_authorized');
                break;
            case 'CONFIRMED':
                $status = $shipping_order_status_id ? $shipping_order_status_id : $this->config->get('payment_tinkoff_confirmed');
                break;
            case 'CANCELED':
                $status = $this->config->get('payment_tinkoff_cancelled');
                break;
            case 'REJECTED':
                $status = $this->config->get('payment_tinkoff_rejected');
                break;
            case 'REVERSED':
                $status = $this->config->get('payment_tinkoff_reversed');
                break;
            case 'REFUNDED':
                $status = $this->config->get('payment_tinkoff_refunded');
                break;
            default:
                $status = null;
                break;
        }

        if (!$status) {
            die('NOTOK');
        }

        $this->load->model('checkout/order');
        $this->model_checkout_order->addOrderHistory((int)$request['OrderId'], $status);

        die('OK');
    }

    public function failure()
    {
        $this->load->language('extension/payment/tinkoff');
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $this->response->setOutput($this->load->view('extension/payment/tinkoff_failure', $data));
    }

    public function success()
    {
        $this->load->language('extension/payment/tinkoff');
        $this->cart->clear();
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        return $this->response->setOutput($this->load->view('extension/payment/tinkoff_success', $data));
    }
}
