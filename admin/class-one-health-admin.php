<?php

/**
 * Funcionalidades do painel administrativo.
 *
 * @since      1.0.0
 * @package    One_Health
 * @subpackage One_Health/admin
 */
class One_Health_Admin {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// 1. CARREGAMENTO DOS MODELS
		// Mantendo sua estrutura que já funciona
		$model_path = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/models/';

		require_once $model_path . 'class-gh-unidade.php';
		require_once $model_path . 'class-gh-especialidade.php';
		require_once $model_path . 'class-gh-medico.php';
		
		// Verificações de segurança
		if ( file_exists( $model_path . 'class-gh-servico.php' ) ) {
			require_once $model_path . 'class-gh-servico.php';
		}
		if ( file_exists( $model_path . 'class-gh-convenio.php' ) ) {
			require_once $model_path . 'class-gh-convenio.php';
		}
		if ( file_exists( $model_path . 'class-gh-agenda.php' ) ) {
            require_once $model_path . 'class-gh-agenda.php';
        }

		// 2. HOOKS DE FORMULÁRIO (POST)
		
		// Unidades
		add_action( 'admin_post_gh_save_unidade', array( $this, 'handle_save_unidade' ) );
		add_action( 'admin_post_gh_delete_unidade', array( $this, 'handle_delete_unidade' ) );

		// Especialidades
		add_action( 'admin_post_gh_save_especialidade', array( $this, 'handle_save_especialidade' ) );
		add_action( 'admin_post_gh_delete_especialidade', array( $this, 'handle_delete_especialidade' ) );

		// Médicos
		add_action( 'admin_post_gh_save_medico', array( $this, 'handle_save_medico' ) );
		add_action( 'admin_post_gh_delete_medico', array( $this, 'handle_delete_medico' ) );

		// Serviços
		add_action( 'admin_post_gh_save_servico', array( $this, 'handle_save_servico' ) );
		add_action( 'admin_post_gh_delete_servico', array( $this, 'handle_delete_servico' ) );

		// Convênios e Planos
		add_action( 'admin_post_gh_save_convenio', array( $this, 'handle_save_convenio' ) );
		add_action( 'admin_post_gh_delete_convenio', array( $this, 'handle_delete_convenio' ) );
		add_action( 'admin_post_gh_save_plano', array( $this, 'handle_save_plano' ) );
		add_action( 'admin_post_gh_delete_plano', array( $this, 'handle_delete_plano' ) );

		// Agenda (Hooks já existentes no seu arquivo)
		add_action( 'admin_post_gh_save_schedule', array( $this, 'handle_save_schedule' ) );
		add_action( 'admin_post_gh_delete_schedule', array( $this, 'handle_delete_schedule' ) );
		add_action( 'admin_post_gh_generate_slots', array( $this, 'handle_generate_slots' ) );

		// Design:
		add_action( 'admin_post_gh_save_design', array( $this, 'handle_save_design' ) );
	}

	/**
	* Scripts e Estilos
	*/
	public function enqueue_scripts() {
		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/one-health-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function enqueue_styles() {
        // [ALTERADO] Carrega o CSS moderno do Admin UI
        wp_enqueue_style( $this->plugin_name . '-admin', plugin_dir_url( __FILE__ ) . 'css/one-health-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Criação do Menu
	 */
	public function add_plugin_admin_menu() {
		add_menu_page( 'One Health', 'OneHealth', 'manage_options', 'one-health', array( $this, 'display_dashboard_page' ), 'dashicons-heart', 6 );

		add_submenu_page( 'one-health', 'Dashboard', 'Dashboard', 'manage_options', 'one-health', array( $this, 'display_dashboard_page' ) );
		add_submenu_page( 'one-health', 'Unidades', 'Unidades', 'manage_options', 'one-health-unidades', array( $this, 'display_unidades_page' ) );
		add_submenu_page( 'one-health', 'Especialidades', 'Especialidades', 'manage_options', 'one-health-especialidades', array( $this, 'display_especialidades_page' ) );
		add_submenu_page( 'one-health', 'Médicos', 'Médicos', 'manage_options', 'one-health-medicos', array( $this, 'display_medicos_page' ) );
		add_submenu_page( 'one-health', 'Serviços', 'Serviços', 'manage_options', 'one-health-servicos', array( $this, 'display_servicos_page' ) );
		add_submenu_page( 'one-health', 'Convênios', 'Convênios', 'manage_options', 'one-health-convenios', array( $this, 'display_convenios_page' ) );
		add_submenu_page( 'one-health', 'Gerar Agenda', 'Gerar Agenda', 'manage_options', 'one-health-gerar-agenda', array( $this, 'display_gerar_agenda_page' ) );
		add_submenu_page( 'one-health', 'Design', 'Design', 'manage_options', 'one-health-design', array( $this, 'display_design_page' ) );
	}

	/**
	 * VIEWS (Renderização das Telas)
	 */
	public function display_dashboard_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/html-dashboard.php';
	}
	public function display_unidades_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/html-unidades.php';
	}
	public function display_especialidades_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/html-especialidades.php';
	}
    public function display_servicos_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/html-servicos.php';
	}
	public function display_convenios_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/html-convenios.php';
	}

    // [ALTERADO] Injeta a classe GH_Agenda para a view usar na aba de turnos
	public function display_medicos_page() {
        if(class_exists('GH_Agenda')) { 
            $agenda_model = new GH_Agenda(); 
        }
		require_once plugin_dir_path( __FILE__ ) . 'views/html-medicos.php';
	}
	
    // [ALTERADO] Aponta para o arquivo real em vez de echo "Em breve"
	public function display_gerar_agenda_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/html-gerar-agenda.php';
	}

	public function display_design_page() {
		require_once plugin_dir_path( __FILE__ ) . 'views/html-design.php';
	}

	public function handle_save_design() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'gh_save_design_nonce', 'gh_security' );
		
		if ( isset( $_POST['gh_theme'] ) ) {
			update_option( 'gh_theme', sanitize_text_field( $_POST['gh_theme'] ) );
		}
		
		wp_redirect( admin_url( 'admin.php?page=one-health-design&message=saved' ) );
		exit;
	}

	/**
	 * HANDLERS (Lógica de Salvamento)
	 */

	// 1. Unidade
	public function handle_save_unidade() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'gh_save_unidade_nonce', 'gh_security' );
		
		$model = new GH_Unidade();
		$res = $model->save( $_POST );
		
		$msg = ! empty( $_POST['id'] ) ? 'updated' : 'created';
		wp_redirect( admin_url( 'admin.php?page=one-health-unidades&message=' . $msg ) );
		exit;
	}
	public function handle_delete_unidade() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'delete_unidade_' . intval($_GET['id']) );
		
		$model = new GH_Unidade();
		$res = $model->delete( intval($_GET['id']) );
		
		if ( is_wp_error( $res ) ) wp_die( $res->get_error_message() );
		wp_redirect( admin_url( 'admin.php?page=one-health-unidades&message=deleted' ) );
		exit;
	}

	// 2. Especialidade
	public function handle_save_especialidade() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'gh_save_especialidade_nonce', 'gh_security' );
		
		$model = new GH_Especialidade();
		$model->save( $_POST );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-especialidades&message=saved' ) );
		exit;
	}
	public function handle_delete_especialidade() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'delete_especialidade_' . intval($_GET['id']) );
		
		$model = new GH_Especialidade();
		$res = $model->delete( intval($_GET['id']) );
		
		if ( is_wp_error( $res ) ) wp_die( $res->get_error_message() );
		wp_redirect( admin_url( 'admin.php?page=one-health-especialidades&message=deleted' ) );
		exit;
	}

	// 3. Médico
	public function handle_save_medico() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'gh_save_medico_nonce', 'gh_security' );
		
		$model = new GH_Medico();
		$model->save( $_POST );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-medicos&message=saved' ) );
		exit;
	}
	public function handle_delete_medico() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'delete_medico_' . intval($_GET['id']) );
		
		$model = new GH_Medico();
		$model->delete( intval($_GET['id']) );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-medicos&message=deleted' ) );
		exit;
	}

	// 4. Serviço
	public function handle_save_servico() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'gh_save_servico_nonce', 'gh_security' );
		
		$model = new GH_Servico();
		$model->save( $_POST );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-servicos&message=saved' ) );
		exit;
	}
	public function handle_delete_servico() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'delete_servico_' . intval($_GET['id']) );
		
		$model = new GH_Servico();
		$model->delete( intval($_GET['id']) );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-servicos&message=deleted' ) );
		exit;
	}

	// 5. Convênio
	public function handle_save_convenio() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'gh_save_convenio_nonce', 'gh_security' );
		
		$model = new GH_Convenio();
		$id = $model->save( $_POST );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-convenios&action=edit&id=' . $id . '&message=saved' ) );
		exit;
	}
	public function handle_delete_convenio() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'delete_convenio_' . intval($_GET['id']) );
		
		$model = new GH_Convenio();
		$model->delete( intval($_GET['id']) );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-convenios&message=deleted' ) );
		exit;
	}

	// 6. Planos
	public function handle_save_plano() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'gh_save_plano_nonce', 'gh_plano_security' );
		
		$model = new GH_Convenio();
		$model->save_plano( $_POST );
		
		$convenio_id = intval( $_POST['convenio_id'] );
		wp_redirect( admin_url( 'admin.php?page=one-health-convenios&action=edit&id=' . $convenio_id . '&message=plano_saved' ) );
		exit;
	}
	public function handle_delete_plano() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permissão negada.' );
		check_admin_referer( 'delete_plano_' . intval($_GET['id']) );
		
		$id = intval( $_GET['id'] );
		$convenio_id = intval( $_GET['convenio_id'] );
		
		$model = new GH_Convenio();
		$model->delete_plano( $id );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-convenios&action=edit&id=' . $convenio_id . '&message=plano_deleted' ) );
		exit;
	}

    // 7. Agendas (Mantidos como no seu arquivo)

	public function handle_save_schedule() {
		if(!current_user_can('manage_options')) wp_die('Acesso negado');
		check_admin_referer('gh_save_schedule_nonce', 'gh_schedule_security');

		$model = new GH_Agenda();
		$res = $model->save_schedule($_POST);

		if ( is_wp_error( $res ) ) {
			wp_die( $res->get_error_message() );
		}

		wp_redirect( admin_url( 'admin.php?page=one-health-medicos&action=edit&id=' . intval($_POST['medico_id']) . '&tab=turnos&message=schedule_created' ) );
		exit;
	}

	public function handle_delete_schedule() {
		if(!current_user_can('manage_options')) wp_die('Acesso negado');
		check_admin_referer('delete_schedule_' . intval($_GET['id']));

		$model = new GH_Agenda();
		$model->delete_schedule( intval($_GET['id']) );

		wp_redirect( admin_url( 'admin.php?page=one-health-medicos&action=edit&id=' . intval($_GET['medico_id']) . '&tab=turnos&message=schedule_deleted' ) );
		exit;
	}
	
	public function handle_generate_slots() {
		if(!current_user_can('manage_options')) wp_die('Acesso negado');
		check_admin_referer('gh_generate_slots_nonce', 'gh_security');
		
		$model = new GH_Agenda();
		
		// Limpar se solicitado
		if ( isset($_POST['clear_existing']) ) {
			$model->clear_slots( $_POST['start_date'], $_POST['end_date'], !empty($_POST['medico_id']) ? $_POST['medico_id'] : null );
		}

		$count = $model->generate_slots( $_POST['start_date'], $_POST['end_date'], !empty($_POST['medico_id']) ? $_POST['medico_id'] : null );
		
		wp_redirect( admin_url( 'admin.php?page=one-health-gerar-agenda&message=generated&count=' . $count ) );
		exit;
	}

}