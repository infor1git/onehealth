<?php

class One_Health_Public {

    // [CONSTRUTOR MANTIDO IGUAL]
	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
        add_filter( 'body_class', array( $this, 'add_fullscreen_body_class' ) );
		add_action( 'wp_ajax_gh_get_available_dates', array( $this, 'ajax_get_available_dates' ) );
		add_action( 'wp_ajax_nopriv_gh_get_available_dates', array( $this, 'ajax_get_available_dates' ) );
		add_action( 'wp_ajax_gh_get_servicos', array( $this, 'ajax_get_servicos' ) );
		add_action( 'wp_ajax_nopriv_gh_get_servicos', array( $this, 'ajax_get_servicos' ) );
		add_action( 'wp_ajax_gh_get_convenios', array( $this, 'ajax_get_convenios' ) );
		add_action( 'wp_ajax_nopriv_gh_get_convenios', array( $this, 'ajax_get_convenios' ) );
		add_action( 'wp_ajax_gh_get_especialidades', array( $this, 'ajax_get_especialidades' ) );
		add_action( 'wp_ajax_nopriv_gh_get_especialidades', array( $this, 'ajax_get_especialidades' ) );
        // [MODIFICAÇÃO] Ajax de Médicos precisa filtrar por serviço
        add_action( 'wp_ajax_gh_get_medicos', array( $this, 'ajax_get_medicos' ) );
		add_action( 'wp_ajax_nopriv_gh_get_medicos', array( $this, 'ajax_get_medicos' ) );
        // [MODIFICAÇÃO] Save Booking mantido
        add_action( 'wp_ajax_gh_save_booking', array( $this, 'ajax_save_booking' ) );
		add_action( 'wp_ajax_nopriv_gh_save_booking', array( $this, 'ajax_save_booking' ) );
	}

    // [MÉTODOS AUXILIARES E RENDERIZADOR - MANTIDOS IGUAIS]
    public function add_fullscreen_body_class( $classes ) { global $post; if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'one_health_agendamento' ) ) { $classes[] = 'bw-booking-fullscreen'; } return $classes; }
	public function enqueue_scripts() { wp_enqueue_style( 'dashicons' ); wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/one-health-public.css', array(), $this->version, 'all' ); wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/one-health-public.js', array( 'jquery' ), $this->version, false ); wp_localize_script( $this->plugin_name, 'gh_vars', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'gh_public_nonce' ) )); }
	public function render_shortcode( $atts ) {
        $tema_ativo = get_option('gh_theme', 'bw-theme-branco'); $logo_url = get_option('gh_wizard_logo', ''); $cor_destaque = get_option('gh_accent_color', '');
		ob_start();
        if(!empty($cor_destaque)){ echo '<style> #gh-booking-wizard { --bw-color-accent: ' . esc_attr($cor_destaque) . ' !important; } </style>'; }
        // [HTML COMPLETO MANTIDO DO ARQUIVO ANTERIOR]
		?>
		<div class="bw-wizard-wrapper <?php echo esc_attr($tema_ativo); ?>" id="gh-booking-wizard">
            <div class="bw-wizard-content-inner">
				<div class="bw-wizard-header"><?php if($logo_url): ?><img src="<?php echo esc_url($logo_url); ?>" alt="Logomarca" class="bw-wizard-logo"><?php else: ?><h2 class="bw-wizard-title">Agendamento Online</h2><?php endif; ?></div>
                <div class="bw-progress-bar"><div class="bw-step active" data-step="1">1. Unidade</div><div class="bw-step" data-step="2">2. Especialidade</div><div class="bw-step" data-step="3">3. Serviço</div><div class="bw-step" data-step="4">4. Convênio</div><div class="bw-step" data-step="5">5. Profissional</div><div class="bw-step" data-step="6">6. Horário</div><div class="bw-step" data-step="7">7. Confirmação</div></div>
                <div class="bw-wizard-content">
                    <div class="bw-step-content active" id="step-1"><h3>Selecione a Unidade</h3><div id="gh-unidades-list" class="bw-grid-options"><p style="opacity:0.7;">Carregando...</p></div></div>
                    <div class="bw-step-content" id="step-2"><h3>Qual a especialidade?</h3><div id="gh-especialidades-list" class="bw-grid-options"></div></div>
                    <div class="bw-step-content" id="step-3"><h3>Qual serviço deseja agendar?</h3><div id="gh-servicos-list" class="bw-grid-options"></div></div>
                    <div class="bw-step-content" id="step-4"><h3>Escolha o seu Convênio</h3><div id="gh-convenios-list" class="bw-grid-options"></div></div>
                    <div class="bw-step-content" id="step-5"><h3>Escolha o Profissional</h3><div id="gh-medicos-list" class="bw-grid-options"></div><div style="margin-top:20px; text-align:center;"><button type="button" id="gh-skip-medico" class="bw-btn-secondary" onclick="skipMedico()"><span class="dashicons dashicons-groups"></span> Mostrar qualquer profissional</button></div></div>
                    <div class="bw-step-content" id="step-6"><h3>Escolha a Data e Horário</h3><div class="bw-calendar-layout"><div class="bw-calendar-col"><div class="bw-cal-header"><button type="button" class="bw-cal-nav" id="bw-cal-prev"><span class="dashicons dashicons-arrow-left-alt2"></span></button><strong id="bw-cal-month-name">Mês</strong><button type="button" class="bw-cal-nav" id="bw-cal-next"><span class="dashicons dashicons-arrow-right-alt2"></span></button></div><div class="bw-cal-days-header"><span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span></div><div id="bw-cal-grid" class="bw-cal-grid"></div></div><div class="bw-slots-col"><div id="gh-slots-list" class="bw-slots-grid"><p style="opacity:0.7;">Selecione uma data para ver os horários.</p></div></div></div></div>
                    <div class="bw-step-content" id="step-7"><h3>Confirme seus Dados</h3><div class="bw-summary"><p>Unidade: <span id="sum-unidade"></span></p><p>Especialidade: <span id="sum-especialidade"></span></p><p>Serviço: <span id="sum-servico"></span></p><p>Convênio: <span id="sum-convenio"></span></p><p>Profissional: <span id="sum-medico"></span></p><p>Data e Hora: <span id="sum-data"></span></p></div><form id="gh-booking-form"><div class="bw-input-group" style="margin-bottom:1rem;"><label>Nome Completo:</label><input type="text" id="gh_paciente_nome" class="bw-input" required placeholder="Digite seu nome"></div><div class="bw-input-group" style="margin-bottom:1.5rem;"><label>Telefone / WhatsApp:</label><input type="text" id="gh_paciente_tel" class="bw-input" required placeholder="(00) 00000-0000"></div><button type="submit" class="bw-btn-primary"><span class="dashicons dashicons-calendar-alt"></span> Confirmar Agendamento</button></form></div>
					<div class="bw-navigation-footer" style="margin-top: 30px; border-top: 1px solid var(--bw-color-card-border); padding-top: 15px;"><button type="button" class="bw-btn-back" onclick="gh_prev_step()"><span class="dashicons dashicons-arrow-left-alt2"></span> Voltar Passo Anterior</button></div>
                </div>
            </div> 
            <div id="bw-modal-preparo" class="bw-modal-overlay" style="display: none;"><div class="bw-modal-content"><h3 class="bw-modal-title"><span class="dashicons dashicons-clipboard" style="font-size:30px; width:30px; height:30px;"></span> Instruções Importantes</h3><div id="bw-modal-preparo-text"></div><div style="font-size: 0.9rem; margin: 25px 0; padding: 15px; background: rgba(0,0,0,0.05); border-left: 4px solid var(--bw-color-accent); border-radius: 6px;"><span class="dashicons dashicons-info" style="vertical-align: text-top; color:var(--bw-color-accent);"></span><strong>Aviso:</strong> Estas instruções também serão enviadas para o seu e-mail após a confirmação.</div><div style="display: flex; gap: 15px; justify-content: flex-end;"><button type="button" class="bw-btn-secondary" id="bw-btn-fechar-modal" style="margin:0;">Cancelar</button><button type="button" class="bw-btn-primary" id="bw-btn-continuar-modal" style="width:auto; margin:0;">Estou Ciente e Continuar</button></div></div></div>
		</div>
		<?php return ob_get_clean();
	}

    // === AJAX METHODS ===

	// 1. MÉDICOS (Com Filtro de Serviço)
	public function ajax_get_medicos() {
		global $wpdb;
		$unidade_id = intval($_POST['unidade_id']);
		$especialidade_id = intval($_POST['especialidade_id']);
        $servico_id = isset($_POST['servico_id']) ? intval($_POST['servico_id']) : 0;

        // SQL Base
		$sql = "SELECT m.id, m.nome, m.foto_url, m.crm FROM {$wpdb->prefix}gh_medicos m
				INNER JOIN {$wpdb->prefix}gh_medico_unidade mu ON m.id = mu.medico_id
				INNER JOIN {$wpdb->prefix}gh_medico_especialidade me ON m.id = me.medico_id
				WHERE m.is_active = 1 AND mu.unidade_id = %d AND me.especialidade_id = %d";
        
        $args = array($unidade_id, $especialidade_id);

        // Lógica de Serviço: O médico pode realizar este serviço?
        // REGRA: Se o médico não tem NENHUM serviço dessa especialidade cadastrado em gh_medico_servico, ele faz todos.
        // Se ele tem ALGUM cadastrado, ele só faz se o atual estiver na lista.
        if ( $servico_id > 0 ) {
            $sql .= " AND (
                (NOT EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms 
                    INNER JOIN {$wpdb->prefix}gh_servico_especialidade se_check ON ms.servico_id = se_check.servico_id
                    WHERE ms.medico_id = m.id AND se_check.especialidade_id = %d
                ))
                OR
                (EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms
                    WHERE ms.medico_id = m.id AND ms.servico_id = %d
                ))
            )";
            $args[] = $especialidade_id;
            $args[] = $servico_id;
        }

		$results = $wpdb->get_results( $wpdb->prepare($sql, $args) );
		wp_send_json_success( $results );
	}

    // [DEMAIS MÉTODOS MANTIDOS COM A LÓGICA DE 4 HORAS]
	public function ajax_get_especialidades() {
		global $wpdb;
        $limite_tempo = date('Y-m-d H:i:s', strtotime('+4 hours', current_time('timestamp')));
		$sql = "SELECT DISTINCT e.id, e.nome, e.icone FROM {$wpdb->prefix}gh_especialidades e
                INNER JOIN {$wpdb->prefix}gh_slots s ON e.id = s.especialidade_id
                LEFT JOIN {$wpdb->prefix}gh_agendamentos a ON s.id = a.slot_id AND a.status = 'A'
                WHERE e.is_active = 1 AND s.status = 'disponivel' AND a.id IS NULL AND s.data_hora > %s ORDER BY e.nome ASC";
        $results = $wpdb->get_results( $wpdb->prepare($sql, $limite_tempo) );
		wp_send_json_success( $results );
	}
    public function ajax_get_servicos() {
        global $wpdb;
        $especialidade_id = intval($_POST['especialidade_id']);
        $sql = "SELECT s.id, s.nome, s.valor, s.preparo_html, s.icone FROM {$wpdb->prefix}gh_servicos s
                INNER JOIN {$wpdb->prefix}gh_servico_especialidade se ON s.id = se.servico_id
                WHERE s.is_active = 1 AND se.especialidade_id = %d ORDER BY s.nome ASC";
        $results = $wpdb->get_results( $wpdb->prepare($sql, $especialidade_id) );
        wp_send_json_success( $results );
    }
	public function ajax_get_available_dates() {
		global $wpdb;
		$unidade_id = intval($_POST['unidade_id']); $especialidade_id = intval($_POST['especialidade_id']); $servico_id = intval($_POST['servico_id']); $medico_id = (isset($_POST['medico_id']) && intval($_POST['medico_id']) > 0) ? intval($_POST['medico_id']) : 0; $mes_ano = sanitize_text_field($_POST['mes_ano']); 
        $limite_tempo = date('Y-m-d H:i:s', strtotime('+4 hours', current_time('timestamp')));
        $tipo_servico = $wpdb->get_var($wpdb->prepare("SELECT tipo FROM {$wpdb->prefix}gh_servicos WHERE id = %d", $servico_id));
		$sql = "SELECT DISTINCT DATE(s.data_hora) as data_disp FROM {$wpdb->prefix}gh_slots s LEFT JOIN {$wpdb->prefix}gh_agendamentos a ON s.id = a.slot_id AND a.status = 'A' WHERE s.status = 'disponivel' AND a.id IS NULL AND s.unidade_id = %d AND s.especialidade_id = %d AND s.data_hora > %s AND s.data_hora LIKE %s";
		$args = array($unidade_id, $especialidade_id, $limite_tempo, $mes_ano . '%');
		if($medico_id > 0) { $sql .= " AND s.medico_id = %d"; $args[] = $medico_id; }
        if($tipo_servico) { $sql .= " AND (s.tipos_servico = '' OR s.tipos_servico IS NULL OR FIND_IN_SET(%s, s.tipos_servico))"; $args[] = $tipo_servico; }
		$results = $wpdb->get_col( $wpdb->prepare($sql, $args) );
		wp_send_json_success( $results );
	}
	public function ajax_get_slots() {
		global $wpdb;
		$unidade_id = intval($_POST['unidade_id']); $especialidade_id = intval($_POST['especialidade_id']); $servico_id = intval($_POST['servico_id']); $data_date = sanitize_text_field($_POST['data']); $medico_id = (isset($_POST['medico_id']) && intval($_POST['medico_id']) > 0) ? intval($_POST['medico_id']) : 0;
        $limite_tempo = date('Y-m-d H:i:s', strtotime('+4 hours', current_time('timestamp')));
        $tipo_servico = $wpdb->get_var($wpdb->prepare("SELECT tipo FROM {$wpdb->prefix}gh_servicos WHERE id = %d", $servico_id));
		$sql = "SELECT s.id, s.data_hora, m.nome as medico_nome FROM {$wpdb->prefix}gh_slots s INNER JOIN {$wpdb->prefix}gh_medicos m ON s.medico_id = m.id LEFT JOIN {$wpdb->prefix}gh_agendamentos a ON s.id = a.slot_id AND a.status = 'A' WHERE s.status = 'disponivel' AND a.id IS NULL AND s.unidade_id = %d AND s.especialidade_id = %d AND DATE(s.data_hora) = %s AND s.data_hora > %s";
		$args = array($unidade_id, $especialidade_id, $data_date, $limite_tempo);
		if($medico_id > 0) { $sql .= " AND s.medico_id = %d"; $args[] = $medico_id; }
        if($tipo_servico) { $sql .= " AND (s.tipos_servico = '' OR s.tipos_servico IS NULL OR FIND_IN_SET(%s, s.tipos_servico))"; $args[] = $tipo_servico; }
		$sql .= " ORDER BY s.data_hora ASC";
		$results = $wpdb->get_results( $wpdb->prepare($sql, $args) );
		foreach($results as $row) { $row->hora_formatada = date('H:i', strtotime($row->data_hora)); }
		wp_send_json_success( $results );
	}
    public function ajax_get_unidades() { $path = plugin_dir_path( __FILE__ ) . '../includes/models/class-gh-unidade.php'; if(file_exists($path)) { require_once $path; $model = new GH_Unidade(); wp_send_json_success( $model->get_all() ); } wp_send_json_error(); }
    public function ajax_get_convenios() { global $wpdb; $sql = "SELECT id, nome, logo_url FROM {$wpdb->prefix}gh_convenios WHERE is_active = 1 ORDER BY nome ASC"; $results = $wpdb->get_results( $sql ); wp_send_json_success( $results ); }
    public function ajax_save_booking() { check_ajax_referer( 'gh_public_nonce', 'nonce' ); global $wpdb; $slot_id = intval($_POST['slot_id']); $servico_id = intval($_POST['servico_id']); $convenio_id = intval($_POST['convenio_id']); $nome = sanitize_text_field($_POST['nome']); $tel = sanitize_text_field($_POST['telefone']); $slot = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}gh_slots WHERE id = %d AND status = 'disponivel'", $slot_id) ); if ( ! $slot ) wp_send_json_error( 'Este horário acabou de ser reservado.' ); $table_agendamentos = $wpdb->prefix . 'gh_agendamentos'; $ja_agendado = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$table_agendamentos} WHERE slot_id = %d AND status = 'A'", $slot_id) ); if ( $ja_agendado ) { wp_send_json_error( 'Desculpe! Outro paciente reservou este horário.' ); } $data = array( 'slot_id' => $slot_id, 'paciente_user_id' => 0, 'medico_id' => $slot->medico_id, 'unidade_id' => $slot->unidade_id, 'especialidade_id' => $slot->especialidade_id, 'servico_id' => $servico_id, 'convenio_id' => $convenio_id, 'data_hora' => $slot->data_hora, 'status' => 'A', 'observacoes' => "Paciente: $nome - Tel: $tel" ); $inserted = $wpdb->insert( $table_agendamentos, $data ); if ( ! $inserted ) wp_send_json_error( 'Erro ao salvar.' ); $wpdb->update( $wpdb->prefix . 'gh_slots', array( 'status' => 'ocupado' ), array( 'id' => $slot_id ) ); wp_send_json_success(); }
}