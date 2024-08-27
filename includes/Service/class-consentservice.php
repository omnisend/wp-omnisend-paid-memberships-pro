<?php
/**
 * Omnisend Consent service
 *
 * @package OmnisendPaidMerbershipsProPlugin
 */

declare(strict_types=1);

namespace Omnisend\PaidMembershipsProAddon\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ConsentService
 *
 * @package Omnisend\PaidMembershipsProAddon\Service
 */
class ConsentService {

	public function __construct() {
		$options = get_option( 'omnisend_pmp_options' );
		if ( isset( $options['setting_field'] ) ) {
			add_action( 'pmpro_checkout_after_billing_fields', array( $this, 'omnisend_consent_billing_fields' ), 10, 1 );
			add_action( 'pmpro_checkout_after_user_fields', array( $this, 'omnisend_consent_checkout_fields' ), 10, 1 );
			add_action( 'pmpro_show_user_profile', array( $this, 'omnisend_consent_profile_edit_fields' ), 10, 1 );
			add_action( 'pmpro_after_change_membership_level', array( $this, 'omnisend_update_membership_lvl' ), 10, 3 );
		}

		add_action( 'pmpro_after_checkout', array( $this, 'omnisend_save_checkout_fields' ), 10, 1 );
		add_action( 'pmpro_member_profile_edit_form_tag', array( $this, 'omnisend_save_profile_fields' ) );
	}

	public function omnisend_consent_billing_fields(): void {
		$omnisend_api  = new OmnisendApiService();
		$contract_data = $omnisend_api->get_omnisend_contact_consent();

		$sms_consent = '';
		if ( $contract_data['sms'] == 'subscribed' ) {
			$sms_consent = 'checked';
		}

		echo '<div class="pmpro_card_content" style="padding-top: 0; padding-bottom: 0;">';

		$current_user = wp_get_current_user();
		if ( 0 != $current_user->ID ) {
			$email_consent = '';
			if ( $contract_data['email'] == 'subscribed' ) {
				$email_consent = 'checked';
			}

			echo '<div class="pmpro_checkout-field pmpro_checkout-field-consent-email">
				<label for="bconsentEmail">' . esc_html( __( 'Subscribe me to your mailing lists', 'omnisend-paid-memberships-pro' ) ) . '</label>
				<input id="bconsentEmail" name="bconsentEmail" ' . esc_html( $email_consent ) . ' type="checkbox" class="input" value="1">
			</div>';

		}

		echo '<div class="pmpro_checkout-field pmpro_checkout-field-consent-phone">
			<label for="bconsentPhone">' . esc_html( __( 'Subscribe me to your SMS lists', 'omnisend-paid-memberships-pro' ) ) . '</label>
			<input id="bconsentPhone" name="bconsentPhone" ' . esc_html( $sms_consent ) . ' type="checkbox" class="input" value="1">
		</div>';
		echo '</div>';
	}

	public function omnisend_consent_checkout_fields(): void {
		$current_user = wp_get_current_user();
		if ( 0 == $current_user->ID ) {
			echo '<div class="pmpro_checkout-field pmpro_checkout-field-consent-email">
				<label for="bconsentEmail">' . esc_html( __( 'Subscribe me to your mailing lists', 'omnisend-paid-memberships-pro' ) ) . '</label>
				<input id="bconsentEmail" name="bconsentEmail" type="checkbox" class="input" value="1">
			</div>';
		}
	}

	public function omnisend_consent_profile_edit_fields(): void {
		$omnisend_api  = new OmnisendApiService();
		$contract_data = $omnisend_api->get_omnisend_contact_consent();

		$email_consent = '';
		if ( $contract_data['email'] == 'subscribed' ) {
			$email_consent = 'checked';
		}

		echo '<div class="pmpro_member_profile_edit-field pmpro_member_profile_edit-field-consent-email">
			<label for="bconsentEmail">' . esc_html( __( 'Subscribe me to your mailing lists', 'omnisend-paid-memberships-pro' ) ) . '</label>
			<input id="bconsentEmail" name="bconsentEmail" ' . esc_html( $email_consent ) . ' type="checkbox" class="input" value="1">
		</div>';

		$sms_consent = '';
		if ( $contract_data['sms'] == 'subscribed' ) {
			$sms_consent = 'checked';
		}

		echo '<div class="pmpro_member_profile_edit-field pmpro_member_profile_edit-field-consent-phone">
				<label for="bconsentPhone">' . esc_html( __( 'Subscribe me to your SMS lists', 'omnisend-paid-memberships-pro' ) ) . '</label>
				<input id="bconsentPhone" name="bconsentPhone" ' . esc_html( $sms_consent ) . ' type="checkbox" class="input" value="1">
		</div>';
	}

	public function omnisend_save_profile_fields(): void {
		$current_user = wp_get_current_user();
		if ( isset( $_POST['update_user_nonce'] ) && check_admin_referer( 'update-user_' . $current_user->ID, 'update_user_nonce' ) ) {
			if ( isset( $_POST['action'] ) && 'update-profile' === $_POST['action'] && isset( $_REQUEST['user_email'] ) ) {
				$omnisend_api = new OmnisendApiService();
				$omnisend_api->create_omnisend_profile_contact( $_REQUEST );
			}
		}
	}

	public function omnisend_save_checkout_fields(): void {
		if ( isset( $_POST['pmpro_checkout_nonce'] ) && check_admin_referer( 'pmpro_checkout_nonce', 'pmpro_checkout_nonce' ) ) {
			if ( isset( $_REQUEST['bconsentEmail'] ) || isset( $_REQUEST['bconsentPhone'] ) || ! isset( $options['setting_field'] ) ) {
				$omnisend_api = new OmnisendApiService();
				$omnisend_api->create_omnisend_contact( $_REQUEST );
			}
		}
	}

	/**
	 * Function to be called after a membership level change
	 *
	 * @param int $level_id The ID of the new membership level.
	 * @param int $user_id The ID of the user.
	 */
	public function omnisend_update_membership_lvl( int $level_id, int $user_id ): void {
		$user       = get_userdata( $user_id );
		$user_email = $user->user_email;
		$new_level  = pmpro_getLevel( $level_id );

		if ( isset( $new_level->name ) ) {
			$omnisend_api = new OmnisendApiService();
			$omnisend_api->update_membership_level( $user_email, $new_level->name );
		}
	}
}
