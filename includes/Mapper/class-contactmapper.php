<?php
/**
 * Contact mapper
 *
 * @package OmnisendPaidMerbershipsProPlugin
 */

declare(strict_types=1);

namespace Omnisend\PaidMembershipsProAddon\Mapper;

use Omnisend\PaidMembershipsProAddon\Actions\OmnisendAddOnAction;
use Omnisend\SDK\V1\Contact;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ContactMapper
 */
class ContactMapper {
	private const CUSTOM_PREFIX  = 'paid_memberships_pro';
	private const CONSENT_PREFIX = 'paid_memberships_pro';

	/**
	 * Get Contact object
	 *
	 * @param array  $mapped_fields
	 *
	 * @return Contact object
	 */
	public function get_omnisend_contact( array $mapped_fields ): Contact {
		$contact = new Contact();

		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;

		if ( 0 != $current_user->ID ) {
			$user_email = $mapped_fields['bemail'];
		}

		$contact->set_email( $user_email );
		$contact->set_phone( $mapped_fields['bphone'] );
		$contact->set_first_name( $mapped_fields['bfirstname'] ?? '' );
		$contact->set_last_name( $mapped_fields['blastname'] ?? '' );
		$contact->set_postal_code( $mapped_fields['bzipcode'] ?? '' );
		$contact->set_address( $mapped_fields['baddress1'] ?? '' );
		$contact->set_state( $mapped_fields['bstate'] ?? '' );
		$contact->set_country( $mapped_fields['bcountry'] ?? '' );
		$contact->set_city( $mapped_fields['bcity'] ?? '' );

			$contact->set_welcome_email( true );

		if ( isset( $mapped_fields['bconsentEmail'] ) || ! isset( $options['setting_field'] ) ) {
			$contact->set_email_consent( self::CONSENT_PREFIX );
			$contact->set_email_opt_in( $user_email );
			$contact->set_email_subscriber();
		} else {
			$contact->set_email_consent( self::CONSENT_PREFIX );
			$contact->set_email_opt_in( '' );
		}

		if ( isset( $mapped_fields['bconsentPhone'] ) || ! isset( $options['setting_field'] ) ) {
			$contact->set_phone_subscriber();
			$contact->set_phone_consent( self::CONSENT_PREFIX );
			$contact->set_phone_opt_in( $mapped_fields['bphone'] );
		} else {
			$contact->set_phone_consent( self::CONSENT_PREFIX );
			$contact->set_phone_opt_in( '' );
		}

		if ( isset( $mapped_fields['pmpro_level'] ) ) {
			$contact->add_custom_property( 'membership_level', $this->get_pmpro_level_name( (int) $mapped_fields['pmpro_level'] ) );
		}

		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}

	/**
	 * Get Contact object
	 *
	 * @param array $mapped_fields
	 * @param string|null $phone_number
	 *
	 * @return Contact object
	 */
	public function get_omnisend_update_contact( array $mapped_fields, string $phone_number = null ): Contact {
		$contact = new Contact();

		$contact->set_email( $mapped_fields['user_email'] );
		if ( isset( $phone_number ) ) {
			$contact->set_phone( $phone_number );
		}
		$contact->set_first_name( $mapped_fields['first_name'] ?? '' );
		$contact->set_last_name( $mapped_fields['last_name'] ?? '' );

		if ( ! isset( $options['setting_field'] ) ) {
			if ( isset( $mapped_fields['bconsentEmail'] ) ) {
				$contact->set_email_subscriber();
				$contact->set_email_consent( self::CONSENT_PREFIX );
			} else {
				$contact->set_email_consent( self::CONSENT_PREFIX );
				$contact->set_email_unsubscriber();
			}

			if ( isset( $mapped_fields['bconsentPhone'] ) ) {
				if ( isset( $phone_number ) ) {
					$contact->set_phone_consent( self::CONSENT_PREFIX );
					$contact->set_phone_subscriber();
				}

				if ( isset( $mapped_fields['bphone'] ) ) {
					$contact->set_phone_consent( self::CONSENT_PREFIX );
					$contact->set_phone_subscriber();
				}
			} else {
				$contact->set_phone_consent( self::CONSENT_PREFIX );
				$contact->set_phone_unsubscriber();
			}
		} else {
			$contact->set_email_consent( self::CONSENT_PREFIX );
			$contact->set_email_subscriber();

			$contact->set_phone_consent( self::CONSENT_PREFIX );
			$contact->set_phone_subscriber();
		}

		return $contact;
	}

	/**
	 * Get Contact object
	 *
	 * @param string $user_email
	 * @param string $membership_level
	 *
	 * @return Contact object
	 */
	public function get_omnisend_update_membership_level( string $user_email, string $membership_level ): Contact {
		$contact = new Contact();

		$contact->set_email( $user_email );
		if ( '' != $membership_level ) {
			$contact->add_custom_property( 'membership_level', $membership_level );
		}

		return $contact;
	}

	/**
	 * Get Contact object
	 *
	 * @param int $level_id
	 *
	 * @return string object
	 */
	public function get_pmpro_level_name( int $level_id ): string {
		$level = pmpro_getLevel( $level_id );

		if ( $level ) {
			return $level->name;
		}
		return '';
	}
}
