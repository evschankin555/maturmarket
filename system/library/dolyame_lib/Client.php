<?php

namespace Dolyame\Payment;

class Client
{
	protected $url = 'https://partner.dolyame.ru/v1/orders/';

	protected $login = '';
	protected $password = '';
	protected $certPath = '';
	protected $keyPath = '';

	public function __construct($login, $password) {
		$this->login = $login;
		$this->password = $password;
	}

	public function setCertPath($certPath)
	{
		$this->certPath = $certPath;
	}

	public function setKeyPath($keyPath)
	{
		$this->keyPath = $keyPath;
	}

	public function generateCorrelationId()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	public function create($data, $correlationId = '')
	{
		return $this->execute('create', $data, 'POST', $correlationId);
	}

	public function cancel($orderId, $correlationId = '')
	{
		$orderId = self::prepareOrderId($orderId);
		return $this->execute($orderId.'/cancel', [], 'POST', $correlationId);
	}

	public function commit($orderId, $data, $correlationId = '')
	{
		$orderId = self::prepareOrderId($orderId);
		return $this->execute($orderId.'/commit', $data, 'POST', $correlationId);
	}

	public function info($orderId, $correlationId = '')
	{
		$orderId = self::prepareOrderId($orderId);
		return $this->execute($orderId.'/info', [], 'GET', $correlationId);
	}

	public function refund($orderId, $data, $correlationId = '')
	{
		$orderId = self::prepareOrderId($orderId);
		return $this->execute($orderId.'/refund', $data, 'POST', $correlationId);
	}

	protected function execute($action, $data, $method, $correlationId)
	{
		if($correlationId === '') {
			$correlationId = $this->generateCorrelationId();
		}
		$headers = [
			"Content-Type: application/json",
			"X-Correlation-ID: $correlationId",
			"Authorization: Basic ".base64_encode("{$this->login}:{$this->password}")
		];

		$responseHeaders = '';
		if (!function_exists("curl_init")) {
			throw new \Exception("Curl error");
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url.$action);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$responseHeaders) {
			$responseHeaders .= $header;
			return strlen($header);
		});
		if ($this->certPath) {
			if (!file_exists($this->certPath)) {
				throw new \Exception('Cert path did\'t exist:'.$this->certPath);
			}
			if (!file_exists($this->keyPath)) {
				throw new \Exception('Key path did\'t exist:'.$this->keyPath);
			}
			if (!is_readable($this->certPath)) {
				throw new \Exception('Can\'t read cert file:'.$this->certPath);
			}

			if (!is_readable($this->keyPath)) {
				throw new \Exception('Can\'t read key file:'.$this->keyPath);
			}
			curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
			curl_setopt($ch, CURLOPT_SSLKEY, $this->keyPath);
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		if (!empty($data) || $method == 'POST') {
			$encodedData = $this->encode($data);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
		}

		$out = curl_exec($ch);

		$curlError = curl_error($ch);
		if ($curlError) {
			throw new \Exception($curlError);
		}


		$code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$response = json_decode($out, true);
		if ($code == 200) {
			return $response;
		} elseif ($code == 429) {
			$headers = $this->parseHeadersToArray($responseHeaders);
			sleep($headers['X-Retry-After']);
			return $this->execute($action, $data, $method, $correlationId);
		}

		$error = 'Error: ' . $code;


		if (isset($response['type']) && $response['type'] == 'error') {
			$error .= ' ' . $response['description'];
		}
		if (isset($response['message'])) {
			$error .= ' '.$response['message'];
		}
		if (!empty($response['details'])) {
			$list = array_map(
				function($key, $value){return "$key - $value";},
				array_keys($response['details']),
				array_values($response['details'])
			);
			$error .= ': '.implode($list);;
		}

		if (!$response) {
			$error .= $out;
		}
		throw new \Exception($error, $code);
	}

	protected function parseHeadersToArray($rawHeaders)
	{
		$lines = explode("\r\n", $rawHeaders);
		$headers = [];
		foreach($lines as $line) {
			if (strpos($line, ':') === false ){
				continue;
			}
			list($key, $value) = explode(': ', $line);
			$headers[$key] = $value;
		}
		return $headers;
	}

	protected function encode($data)
	{
		if (is_string($data)) {
			return $data;
		}
		$result = json_encode($data);
		$error = json_last_error();
		if ($error != JSON_ERROR_NONE) {
			throw new \Exception('JSON Error: ' . json_last_error_msg());
		}
		return $result;
	}

	public static function prepareOrderId($orderId)
	{
		$orderId = str_replace(['/','#','?','|',' '], ['-'], $orderId);
		return $orderId;
	}

}
