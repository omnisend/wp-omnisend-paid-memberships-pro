<?php
/**
 * Omnisend initial sync
 *
 * @package OmnisendPaidMerbershipsProPlugin
 */

declare(strict_types=1);

namespace Omnisend\PaidMembershipsProAddon\Cron;

use Omnisend\PaidMembershipsProAddon\Service\OmnisendApiService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OmnisendInitialSync
 */
class OmnisendInitialSync {
	private const SCHEDULE_HOOK_CODE = 'omnisend_pmp_initial_sync';
	private const SCHEDULE_CODE      = 'omni_send_core_every_minute';

	/**
	 * Schedule or unschedule sync event
	 *
	 * @param bool $plugin_active
	 */
	public function __construct( bool $plugin_active ) {
		if ( ! $plugin_active ) {
			wp_clear_scheduled_hook( self::SCHEDULE_HOOK_CODE );

			return;
		}

		add_action( self::SCHEDULE_HOOK_CODE, array( $this, 'execute' ) );

		if ( ! wp_next_scheduled( self::SCHEDULE_HOOK_CODE ) ) {
			wp_schedule_event( time(), self::SCHEDULE_CODE, self::SCHEDULE_HOOK_CODE );
		}
	}

	/**
	 * Executes contact sync
	 *
	 * @return void
	 */
	public static function execute(): void {
		if ( get_option( 'pmp_initial_sync_made' ) !== false ) {
			return;
		}

		$options = get_option( 'omnisend_pmp_options' );

		if ( ! isset( $options['sync_field'] ) || $options['sync_field'] !== '1' ) {
			return;
		}

		add_option( 'pmp_initial_sync_made', '2' );

		$omnisend_api_service = new OmnisendApiService();
		$omnisend_api_service->create_users_as_omnisend_contacts();

		update_option( 'pmp_initial_sync_made', '1' );
	}
}
