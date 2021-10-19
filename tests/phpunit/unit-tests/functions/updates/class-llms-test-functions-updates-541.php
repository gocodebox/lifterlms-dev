<?php
/**
* Test updates functions when updating to 5.4.1
 *
 * @package LifterLMS/Tests/Functions/Updates
 *
 * @group functions
 * @group updates
 * @group updates_541
 *
 * @since [version]
 */
class LLMS_Test_Functions_Updates_541 extends LLMS_UnitTestCase {

	/**
	 * Setup before class.
	 *
	 * Include update functions file.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once LLMS_PLUGIN_DIR . 'includes/functions/updates/llms-functions-updates-541.php';
	}

	/**
	 * Test llms_update_541_buddypress_profile_endpoints_bc() method.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_llms_update_541_buddypress_profile_endpoints_bc() {

		$this->assertEquals(
			array(),
			get_option( 'llms_integration_buddypress_profile_endpoints', array() )
		);

		// Run the update.
		llms_update_541_buddypress_profile_endpoints_bc();

		$this->assertEquals(
			array(),
			get_option( 'llms_integration_buddypress_profile_endpoints', array() )
		);

		// Enable the integration.
		update_option(  'llms_integration_buddypress_enabled', 'yes' );

		// Run the update.
		llms_update_541_buddypress_profile_endpoints_bc();

		$this->assertEquals(
			array(
				'view-courses',
				'view-memberships',
				'view-achievements',
				'view-certificates',
			),
			get_option( 'llms_integration_buddypress_profile_endpoints', array() )
		);

		// Turn it off.
		update_option(  'llms_integration_buddypress_enabled', 'no' );

	}

	/**
	 * Test llms_update_541_update_db_version()
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	public function test_update_541_update_db_version() {

		$orig = get_option( 'lifterlms_db_version' );

		// Remove existing db version.
		delete_option( 'lifterlms_db_version' );

		llms_update_541_update_db_version();

		$this->assertEquals( '5.4.1', get_option( 'lifterlms_db_version' ) );

		update_option( 'lifterlms_db_version', $orig );

	}

}
