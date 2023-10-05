<?php


/**
 */
class VKApiParamPhotoException extends VKApiException {

	/**
	 * VKApiParamPhotoException constructor.
	 *
	 * @param VkApiError $error
	 */
	public function __construct(VkApiError $error) {
		parent::__construct(129, 'Invalid photo', $error);
	}
}
