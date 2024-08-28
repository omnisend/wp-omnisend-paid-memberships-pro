<?php
/**
 * Omnisend Api service
 *
 * @package OmnisendPaidMerbershipsProPlugin
 */

declare(strict_types=1);

namespace Omnisend\PaidMembershipsProAddon\Service;

use Omnisend\PaidMembershipsProAddon\Actions\OmnisendAddOnAction;
use Omnisend\PaidMembershipsProAddon\Mapper\ContactMapper;
use Omnisend\PaidMembershipsProAddon\Validator\ResponseValidator;
use Omnisend\SDK\V1\Omnisend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Omnisend API Service.
 */
class OmnisendApiService {
	/**
	 * Contact mapper.
	 *
	 * @var ContactMapper
	 */
	private $contact_mapper;

	/**
	 * Omnisend client
	 *
	 * @var Omnisend
	 */
	private $client;

	/**
	 * Response validator
	 *
	 * @var ResponseValidator
	 */
	private $response_validator;

	/**
	 * OmnisendApiService class constructor.OmnisendLifterLMSPlugin
	 */
	public function __construct() {
		$this->contact_mapper     = new ContactMapper();
		$this->response_validator = new ResponseValidator();
		$this->client             = Omnisend::get_client(
			OMNISEND_MEMBERSHIPS_ADDON_NAME,
			OMNISEND_MEMBERSHIPS_ADDON_VERSION
		);
	}

	/**
	 * Creates an Omnisend contact.
	 *
	 * @param array $form_data The form data.
	 *
	 * @return array Tracker data.
	 */
	public function create_omnisend_contact( array $form_data ): array {
		$contact  = $this->contact_mapper->create_contact( $form_data );
		$response = $this->client->save_contact( $contact );

		if ( ! $this->response_validator->is_valid( $response ) ) {
			return array();
		}

		return array(
			OmnisendAddOnAction::EMAIL        => $form_data['bemail'],
			OmnisendAddOnAction::PHONE_NUMBER => $form_data['bphone'],
		);
	}

	/**
	 * Update Omnisend contact by editing profile form.
	 *
	 * @param array $form_data The form data.
	 *
	 */
	public function create_omnisend_profile_contact( array $form_data ): void {
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;
		$response     = $this->client->get_contact_by_email( $user_email );
		$phone_number = $response->get_contact()->get_phone();

		$contact = $this->contact_mapper->update_profile_contact( $form_data, $phone_number );
		$this->client->save_contact( $contact );
	}

	/**
	 * get an Omnisend contact by email.
	 *
	 * @return array omnisend contact consent data.
	 */
	public function get_omnisend_contact_consent(): array {
		$current_user = wp_get_current_user();

		if ( isset( $current_user->user_email ) ) {
			$user_email = $current_user->user_email;
			$response   = $this->client->get_contact_by_email( $user_email );

			$contract_data['sms']   = $response->get_contact()->get_phone_status();
			$contract_data['email'] = $response->get_contact()->get_email_status();
		} else {
			$contract_data['sms']   = '';
			$contract_data['email'] = '';
		}

		return $contract_data;
	}

	/**
	 * Updates an Omnisend contact membership level.
	 *
	 * @param string $user_email The form data.
	 * @param string $membership_level The form data.
	 *
	 */
	public function update_membership_level( string $user_email, string $membership_level ): void {
		$contact = $this->contact_mapper->update_membership_level( $user_email, $membership_level );
		$this->client->save_contact( $contact );
	}
}
