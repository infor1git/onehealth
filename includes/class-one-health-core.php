<?php

/**
 * A classe principal que carrega dependências e define hooks.
 *
 * @since      1.0.0
 * @package    One_Health
 * @subpackage One_Health/includes
 */
class One_Health_Core {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'one-health';
		$this->version     = '1.0.0';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		$includes_dir = plugin_dir_path( __FILE__ );

		require_once $includes_dir . 'class-one-health-loader.php';
		require_once $includes_dir . 'class-one-health-i18n.php';
		require_once $includes_dir . '../admin/class-one-health-admin.php';
		require_once $includes_dir . '../public/class-one-health-public.php';

		$this->loader = new One_Health_Loader();
	}

	private function define_admin_hooks() {
		$plugin_admin = new One_Health_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
	}

	private function define_public_hooks() {
		$plugin_public = new One_Health_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		
		add_shortcode( 'one_health_agendamento', array( $plugin_public, 'render_shortcode' ) );

		// AJAX Hooks - Unidades
		$this->loader->add_action( 'wp_ajax_gh_get_unidades', $plugin_public, 'ajax_get_unidades' );
		$this->loader->add_action( 'wp_ajax_nopriv_gh_get_unidades', $plugin_public, 'ajax_get_unidades' );

		// AJAX Hooks - Especialidades
		$this->loader->add_action( 'wp_ajax_gh_get_especialidades', $plugin_public, 'ajax_get_especialidades' );
		$this->loader->add_action( 'wp_ajax_nopriv_gh_get_especialidades', $plugin_public, 'ajax_get_especialidades' );

		// [NOVO] AJAX Hooks - Médicos
		$this->loader->add_action( 'wp_ajax_gh_get_medicos', $plugin_public, 'ajax_get_medicos' );
		$this->loader->add_action( 'wp_ajax_nopriv_gh_get_medicos', $plugin_public, 'ajax_get_medicos' );

		// [NOVO] AJAX Hooks - Slots (Horários)
		$this->loader->add_action( 'wp_ajax_gh_get_slots', $plugin_public, 'ajax_get_slots' );
		$this->loader->add_action( 'wp_ajax_nopriv_gh_get_slots', $plugin_public, 'ajax_get_slots' );

		// [NOVO] Salvar Agendamento
		$this->loader->add_action( 'wp_ajax_gh_save_booking', $plugin_public, 'ajax_save_booking' );
		$this->loader->add_action( 'wp_ajax_nopriv_gh_save_booking', $plugin_public, 'ajax_save_booking' );
	}

	public function run() {
		$this->loader->run();
	}

	public function get_plugin_name() {
		return $this->plugin_name;
	}

	public function get_version() {
		return $this->version;
	}
}