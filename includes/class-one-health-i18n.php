<?php

/**
 * Define a funcionalidade de internacionalização.
 *
 * @since      1.0.0
 * @package    One_Health
 * @subpackage One_Health/includes
 */
class One_Health_i18n {

	/**
	 * Carrega o text domain para tradução.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'one-health',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

}