<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Log class
*/
class Log {
	private $handle;
	
	/**
	 * Constructor
	 *
	 * @param	string	$filename
 	*/
    public function __construct($filename) {
        try {
            $this->handle = @fopen(DIR_LOGS . $filename, 'a');
            if ($this->handle === false) {
                throw new Exception("Unable to open or create the log file.");
            }
        } catch (Exception $e) {
            // Просто игнорируем ошибку, не выводим ее для пользователя
        }
    }
	
	/**
     * 
     *
     * @param	string	$message
     */
	public function write($message) {
		fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
	}
	
	/**
     * 
     *
     */
	public function __destruct() {
		fclose($this->handle);
	}
}