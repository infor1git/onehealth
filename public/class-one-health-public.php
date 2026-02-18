<?php

class One_Health_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
        
        // Adiciona classe no body se o shortcode estiver na página (para Fullscreen UI)
        add_filter( 'body_class', array( $this, 'add_fullscreen_body_class' ) );
	}

    /**
     * Aplica classe de fullscreen e tema no body se o shortcode estiver presente
     */
    public function add_fullscreen_body_class( $classes ) {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'one_health_agendamento' ) ) {
            $classes[] = 'bw-booking-fullscreen';
        }
        return $classes;
    }

	public function enqueue_scripts() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/one-health-public.css', array(), $this->version, 'all' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/one-health-public.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'gh_vars', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'gh_public_nonce' )
		));
	}

	public function render_shortcode( $atts ) {
        // Pega o tema salvo no banco, ou Branco como padrão
        $tema_ativo = get_option('gh_theme', 'bw-theme-branco');
        
		ob_start();
		?>
		<div class="bw-wizard-wrapper <?php echo esc_attr($tema_ativo); ?>" id="gh-booking-wizard">
            <div class="bw-wizard-content-inner">
			
                <div class="bw-progress-bar">
                    <div class="bw-step active" data-step="1">Unidade</div>
                    <div class="bw-step" data-step="2">Especialidade</div>
                    <div class="bw-step" data-step="3">Profissional</div>
                    <div class="bw-step" data-step="4">Horário</div>
                    <div class="bw-step" data-step="5">Identificação</div>
                </div>

                <div class="bw-wizard-content">
                    
                    <div class="bw-step-content active" id="step-1">
                        <h3>Selecione a Unidade</h3>
                        <div id="gh-unidades-list" class="bw-grid-options"><p style="color:var(--bw-color-text-secondary);">Carregando...</p></div>
                    </div>

                    <div class="bw-step-content" id="step-2">
                        <h3>Qual a especialidade?</h3>
                        <button type="button" class="bw-btn-back" onclick="gh_prev_step(1)">
                            <span class="dashicons dashicons-arrow-left-alt2"></span> Voltar
                        </button>
                        <div id="gh-especialidades-list" class="bw-grid-options"></div>
                    </div>

                    <div class="bw-step-content" id="step-3">
                        <h3>Escolha o Profissional</h3>
                        <button type="button" class="bw-btn-back" onclick="gh_prev_step(2)">
                            <span class="dashicons dashicons-arrow-left-alt2"></span> Voltar
                        </button>
                        <div id="gh-medicos-list" class="bw-grid-options"></div>
                        <div style="margin-top:20px; text-align:center;">
                            <button type="button" id="gh-skip-medico" class="bw-btn-secondary" onclick="skipMedico()">
                                <span class="dashicons dashicons-groups"></span> Qualquer profissional (Ver horários)
                            </button>
                        </div>
                    </div>

                    <div class="bw-step-content" id="step-4">
                        <h3>Escolha o Horário</h3>
                        <button type="button" class="bw-btn-back" onclick="gh_prev_step(3)">
                            <span class="dashicons dashicons-arrow-left-alt2"></span> Voltar
                        </button>
                        <div style="margin-bottom:1.5rem;" class="bw-input-group">
                            <label>Data de preferência:</label>
                            <input type="date" id="gh-date-picker" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div id="gh-slots-list" class="bw-slots-grid">
                            <p style="color:var(--bw-color-text-secondary);">Selecione uma data para ver os horários.</p>
                        </div>
                    </div>

                    <div class="bw-step-content" id="step-5">
                        <h3>Confirme seus Dados</h3>
                        <button type="button" class="bw-btn-back" onclick="gh_prev_step(4)">
                            <span class="dashicons dashicons-arrow-left-alt2"></span> Voltar
                        </button>
                        
                        <div class="bw-summary">
                            <p>Unidade: <span id="sum-unidade"></span></p>
                            <p>Especialidade: <span id="sum-especialidade"></span></p>
                            <p>Profissional: <span id="sum-medico"></span></p>
                            <p>Data e Hora: <span id="sum-data"></span></p>
                        </div>

                        <form id="gh-booking-form">
                            <div class="bw-input-group" style="margin-bottom:1rem;">
                                <label>Nome Completo:</label>
                                <input type="text" id="gh_paciente_nome" class="bw-input" required placeholder="Digite seu nome">
                            </div>
                            
                            <div class="bw-input-group" style="margin-bottom:1.5rem;">
                                <label>Telefone / WhatsApp:</label>
                                <input type="text" id="gh_paciente_tel" class="bw-input" required placeholder="(00) 00000-0000">
                            </div>
                            
                            <button type="submit" class="bw-btn-primary">
                                <span class="dashicons dashicons-calendar-alt"></span> Confirmar Agendamento
                            </button>
                        </form>
                    </div>
                </div>

            </div> </div>
		<?php
		return ob_get_clean();
	}

	// --- AJAX HANDLERS ---
    // (O RESTANTE DA CLASSE PHP CONTINUA INTACTO E EXATAMENTE IGUAL)
	public function ajax_get_unidades() {
		$path = plugin_dir_path( __FILE__ ) . '../includes/models/class-gh-unidade.php';
		if(file_exists($path)) {
			require_once $path;
			$model = new GH_Unidade();
			wp_send_json_success( $model->get_all() );
		}
		wp_send_json_error();
	}

	public function ajax_get_especialidades() {
		$path = plugin_dir_path( __FILE__ ) . '../includes/models/class-gh-especialidade.php';
		if(file_exists($path)) {
			require_once $path;
			$model = new GH_Especialidade();
			wp_send_json_success( $model->get_all() );
		}
		wp_send_json_error();
	}

	public function ajax_get_medicos() {
		global $wpdb;
		$unidade_id = intval($_POST['unidade_id']);
		$especialidade_id = intval($_POST['especialidade_id']);

		$sql = "SELECT m.id, m.nome, m.foto_url, m.crm 
				FROM {$wpdb->prefix}gh_medicos m
				INNER JOIN {$wpdb->prefix}gh_medico_unidade mu ON m.id = mu.medico_id
				INNER JOIN {$wpdb->prefix}gh_medico_especialidade me ON m.id = me.medico_id
				WHERE m.is_active = 1 
				AND mu.unidade_id = %d 
				AND me.especialidade_id = %d";
		
		$results = $wpdb->get_results( $wpdb->prepare($sql, $unidade_id, $especialidade_id) );
		
		wp_send_json_success( $results );
	}

	public function ajax_get_slots() {
		global $wpdb;
		
		$unidade_id = intval($_POST['unidade_id']);
		$especialidade_id = intval($_POST['especialidade_id']);
		$data_date = sanitize_text_field($_POST['data']); 
		$medico_id = !empty($_POST['medico_id']) ? intval($_POST['medico_id']) : null;

		$sql = "SELECT s.id, s.data_hora, m.nome as medico_nome
				FROM {$wpdb->prefix}gh_slots s
				INNER JOIN {$wpdb->prefix}gh_medicos m ON s.medico_id = m.id
				WHERE s.status = 'disponivel'
				AND s.unidade_id = %d
				AND s.especialidade_id = %d
				AND DATE(s.data_hora) = %s";
		
		$args = array($unidade_id, $especialidade_id, $data_date);

		if($medico_id) {
			$sql .= " AND s.medico_id = %d";
			$args[] = $medico_id;
		}

		$sql .= " ORDER BY s.data_hora ASC";

		$results = $wpdb->get_results( $wpdb->prepare($sql, $args) );
		
		foreach($results as $row) {
			$row->hora_formatada = date('H:i', strtotime($row->data_hora));
		}

		wp_send_json_success( $results );
	}

	public function ajax_save_booking() {
		check_ajax_referer( 'gh_public_nonce', 'nonce' );

		global $wpdb;
		$slot_id = intval($_POST['slot_id']);
		$nome    = sanitize_text_field($_POST['nome']);
		$tel     = sanitize_text_field($_POST['telefone']);

		$slot = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}gh_slots WHERE id = %d AND status = 'disponivel'", $slot_id) );

		if ( ! $slot ) {
			wp_send_json_error( 'Este horário acabou de ser reservado por outra pessoa. Por favor, escolha outro.' );
		}
		
		$table_agendamentos = $wpdb->prefix . 'gh_agendamentos';
		
		$data = array(
			'slot_id'          => $slot_id,
			'paciente_user_id' => 0, 
			'medico_id'        => $slot->medico_id,
			'unidade_id'       => $slot->unidade_id,
			'especialidade_id' => $slot->especialidade_id,
			'servico_id'       => 0, 
			'data_hora'        => $slot->data_hora,
			'status'           => 'agendado',
			'observacoes'      => "Paciente: $nome - Tel: $tel" 
		);

		$inserted = $wpdb->insert( $table_agendamentos, $data );

		if ( ! $inserted ) {
			wp_send_json_error( 'Erro ao salvar agendamento.' );
		}

		$wpdb->update( 
			$wpdb->prefix . 'gh_slots', 
			array( 'status' => 'ocupado', 'agendamento_id' => $wpdb->insert_id ), 
			array( 'id' => $slot_id ) 
		);

		wp_send_json_success();
	}
}