<?php


/**
 */
class VKApiFriendsAddEnemyException extends VKApiException {

	/**
	 * VKApiFriendsAddEnemyException constructor.
	 *
	 * @param VkApiError $error
	 */
	public function __construct(VkApiError $error) {
		parent::__construct(176, 'Cannot add this user to friends as you put him on blacklist', $error);
	}
}