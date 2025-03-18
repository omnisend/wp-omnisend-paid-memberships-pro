<?php
/**
 * Omnisend Addon Action
 *
 * @package OmnisendPaidMerbershipsProPlugin
 */

declare(strict_types=1);

namespace Omnisend\PaidMembershipsProAddon\Actions;

use PMPro_Field;
use Omnisend\PaidMembershipsProAddon\Service\OmnisendApiService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Omnisend Addon
 */
class OmnisendAddOnAction extends PMPro_Field {
	const EMAIL        = 'email';
	const PHONE_NUMBER = 'phone_number';

	/**
	 * Omnisend service
	 *
	 * @var OmnisendApiService
	 */
	private $omnisend_service;

	/**
	 * Creating an Action
	 */
	public function __construct() {
		$this->omnisend_service = new OmnisendApiService();
	}
}
