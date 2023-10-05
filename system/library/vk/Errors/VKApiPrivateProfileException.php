<?php


/**
 */
class VKApiPrivateProfileException extends VKApiException {

	/**
	 * VKApiPrivateProfileException constructor.
	 *
	 * @param VkApiError $error
	 */
	public function __construct(VkApiError $error) {
		parent::__construct(30, 'This profile is private', $error);
	}
}
