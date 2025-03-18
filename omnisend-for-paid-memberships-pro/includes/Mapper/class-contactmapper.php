<?php
/**
 * Contact mapper
 *
 * @package OmnisendPaidMerbershipsProPlugin
 */

declare(strict_types=1);

namespace Omnisend\PaidMembershipsProAddon\Mapper;

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
	 * Create/update Omnisend contact
	 *
	 * @param array $mapped_fields
	 *
	 * @return Contact object
	 */
	public function create_contact( array $mapped_fields ): Contact {
		$contact = new Contact();

		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;
		$options      = get_option( 'omnisend_pmp_options' );

		if ( 0 != $current_user->ID ) {
			$user_email = $mapped_fields['bemail'];
		}

		$contact->set_email( $user_email );
		$contact->set_phone( $mapped_fields['bphone'] ?? '' );
		$contact->set_first_name( $mapped_fields['bfirstname'] ?? '' );
		$contact->set_last_name( $mapped_fields['blastname'] ?? '' );
		$contact->set_postal_code( $mapped_fields['bzipcode'] ?? '' );
		$contact->set_address( $mapped_fields['baddress1'] ?? '' );
		$contact->set_state( $mapped_fields['bstate'] ?? '' );
		$contact->set_country( $mapped_fields['bcountry'] ?? '' );
		$contact->set_city( $mapped_fields['bcity'] ?? '' );
		$contact->set_welcome_email( true );

		if ( isset( $options['setting_field'] ) ) {
			if ( isset( $mapped_fields['bconsentEmail'] ) ) {
				$contact->set_email_subscriber();
				$contact->set_email_consent( self::CONSENT_PREFIX );
			} else {
				$contact->set_email_consent( self::CONSENT_PREFIX );
				$contact->set_email_unsubscriber();
			}

			if ( isset( $mapped_fields['bconsentPhone'] ) ) {
				$contact->set_phone_consent( self::CONSENT_PREFIX );
				$contact->set_phone_subscriber();
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

		if ( isset( $mapped_fields['pmpro_level'] ) ) {
			$contact->add_custom_property( 'membership_levels', $this->get_pmpro_level_names( $current_user->ID ) );
		}

		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}

	/**
	 * Create all users as Omnisend contacts as non-subscribers
	 *
	 * @param array $user_info
	 *
	 * @return Contact object
	 */
	public function create_contact_from_user_info( array $user_info ): Contact {
		$contact      = new Contact();
		$current_user = get_user_by( 'email', $user_info['email'] );

		$contact->set_email( $user_info['email'] );
		$contact->set_phone( $user_info['phone'] );
		$contact->set_first_name( $user_info['first_name'] );
		$contact->set_last_name( $user_info['last_name'] );
		$contact->set_city( $user_info['city'] );
		$contact->set_state( $user_info['state'] );
		$contact->set_postal_code( $user_info['zipcode'] );
		$contact->set_country( $user_info['country'] );
		$contact->set_address( $user_info['address1'] . ' ' . $user_info['address2'] );

		$contact->add_custom_property( 'membership_levels', $this->get_pmpro_level_names( $current_user->ID ) );
		$contact->add_tag( self::CUSTOM_PREFIX );

		return $contact;
	}

	/**
	 * Update Omnisend contact on profile form change.
	 *
	 * @param array $mapped_fields
	 * @param string|null $phone_number
	 *
	 * @return Contact object
	 */
	public function update_profile_contact( array $mapped_fields, string $phone_number = null ): Contact {
		$contact = new Contact();
		$options = get_option( 'omnisend_pmp_options' );

		$contact->set_email( $mapped_fields['user_email'] );
		if ( isset( $phone_number ) ) {
			$contact->set_phone( $phone_number );
		}
		$contact->set_first_name( $mapped_fields['first_name'] ?? '' );
		$contact->set_last_name( $mapped_fields['last_name'] ?? '' );

		if ( isset( $options['setting_field'] ) ) {
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
	 * Update Omnisend contact custom property membership_level
	 *
	 * @param string $user_email
	 * @param string $membership_level
	 *
	 * @return Contact object
	 */
	public function update_membership_level( string $user_email, string $membership_level ): Contact {
		$contact = new Contact();

		$contact->set_email( $user_email );
		if ( '' != $membership_level ) {
			$contact->add_custom_property( 'membership_levels', $membership_level );
		}

		return $contact;
	}

	/**
	 * Get PMP membership level names
	 *
	 * @param int $user_id
	 *
	 * @return string
	 */
	private function get_pmpro_level_names( int $user_id ): string {
		$new_levels = pmpro_getMembershipLevelsForUser( $user_id );
		$tags       = '';

		foreach ( $new_levels as $new_level ) {
			if ( ! property_exists( $new_level, 'name' ) ) {
				continue;
			}

			$tags .= $new_level->name . ', ';
		}

		return $tags;
	}
}
