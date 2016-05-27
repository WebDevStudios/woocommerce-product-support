<?php
require_once( 'WOO_PROD_SUPPORT-Base-Tests.php' );

class WOO_PROD_SUPPORT_template_tags extends WOO_PROD_SUPPORT_Base_Tests {

	/**
	 * Set up
	 */
	public function setUp() {
		parent::setUp();
	}

	/*
	 * Teardown.
	 */
	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * Tests for our inc files being present and available.
	 */
	public function test_WOO_PROD_SUPPORT_loader_exist() {
		$this->assertFileExists( WOO_PROD_SUPPORT_DIRECTORY_PATH . '/bbp-content-restriction.php' );
		$this->assertFileExists( WOO_PROD_SUPPORT_DIRECTORY_PATH . '/woocommerce-product-support.php' );
		$this->assertFileExists( WOO_PROD_SUPPORT_DIRECTORY_PATH . '/readme.txt' );
	}
}
