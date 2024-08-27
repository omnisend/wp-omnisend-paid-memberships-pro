<?php
/**
 * Omnisend Response Validator
 *
 * @package OmnisendPaidMerbershipsProPlugin
 */

declare(strict_types=1);

namespace Omnisend\PaidMembershipsProAddon\Validator;

use Omnisend\SDK\V1\CreateContactResponse;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ResponseValidator
 *
 * @package Omnisend\PaidMembershipsProAddon\Validator
 */
class ResponseValidator {

	/**
	 * Validates response.
	 *
	 * @param CreateContactResponse $response
	 *
	 * @return bool
	 */
	public function is_valid( CreateContactResponse $response ): bool {
		if ( ! empty( $response->get_wp_error()->get_error_message() ) ) {
			error_log( 'Error in after_submission: ' . $response->get_wp_error()->get_error_message()); // phpcs:ignore

			return false;
		}

		if ( empty( $response->get_contact_id() ) ) {
			return false;
		}

		return true;
	}
}
