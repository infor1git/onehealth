<?php

class One_Health_Public {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
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
		ob_start();
		?>
		<div id="gh-booking-wizard">
			
			<div class="gh-progress-bar">
				<div class="gh-step active" data-step="1">1. Unidade</div>
				<div class="gh-step" data-step="2">2. Especialidade</div>
				<div class="gh-step" data-step="3">3. Profissional</div>
				<div class="gh-step" data-step="4">4. Horário</div>
				<div class="gh-step" data-step="5">5. Identificação</div>
			</div>

			<div class="gh-wizard-content">
				
				<div class="gh-step-content active" id="step-1">
					<h3>Selecione a Unidade</h3>
					<div id="gh-unidades-list" class="gh-grid-options"><p>Carregando...</p></div>
				</div>

				<div class="gh-step-content" id="step-2">
					<h3>Qual a especialidade?</h3>
					<button type="button" class="gh-btn-back" onclick="gh_prev_step(1)">Voltar</button>
					<div id="gh-especialidades-list" class="gh-grid-options"></div>
				</div>

				<div class="gh-step-content" id="step-3">
					<h3>Escolha o Profissional</h3>
					<button type="button" class="gh-btn-back" onclick="gh_prev_step(2)">Voltar</button>
					<div id="gh-medicos-list" class="gh-grid-options"></div>
					<div style="margin-top:20px; text-align:center;">
						<button type="button" class="gh-btn-secondary" onclick="skipMedico()">Qualquer profissional (Ver horários)</button>
					</div>
				</div>

				<div class="gh-step-content" id="step-4">
					<h3>Escolha o Horário</h3>
					<button type="button" class="gh-btn-back" onclick="gh_prev_step(3)">Voltar</button>
					<div class="gh-calendar-container">
						<label>Data:</label>
						<input type="date" id="gh-date-picker" value="<?php echo date('Y-m-d'); ?>">
					</div>
					<div id="gh-slots-list" class="gh-slots-grid">
						<p>Selecione uma data para ver os horários.</p>
					</div>
				</div>

				<div class="gh-step-content" id="step-5">
					<h3>Confirme seus Dados</h3>
					<button type="button" class="gh-btn-back" onclick="gh_prev_step(4)">Voltar</button>
					
					<div class="gh-summary" style="background:#f9f9f9; padding:15px; border-radius:5px; margin-bottom:20px;">
						<p><strong>Unidade:</strong> <span id="sum-unidade"></span></p>
						<p><strong>Especialidade:</strong> <span id="sum-especialidade"></span></p>
						<p><strong>Profissional:</strong> <span id="sum-medico"></span></p>
						<p><strong>Data/Hora:</strong> <span id="sum-data"></span></p>
					</div>

					<form id="gh-booking-form">
						<p><label>Nome Completo:</label><br>
						<input type="text" id="gh_paciente_nome" style="width:100%; padding:8px;" required></p>
						
						<p><label>Telefone / WhatsApp:</label><br>
						<input type="text" id="gh_paciente_tel" style="width:100%; padding:8px;" required></p>
						
						<button type="submit" class="gh-btn-primary" style="width:100%; margin-top:10px;">Confirmar Agendamento</button>
					</form>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// --- AJAX HANDLERS ---

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

		// Query complexa: Traz médicos que atendem na Unidade X E na Especialidade Y
		// Fazendo INNER JOIN com as tabelas de relacionamento
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
		$data_date = sanitize_text_field($_POST['data']); // YYYY-MM-DD
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
		
		// Formatar hora para frontend
		foreach($results as $row) {
			$row->hora_formatada = date('H:i', strtotime($row->data_hora));
		}

		wp_send_json_success( $results );
	}

	/**
	 * Salva o agendamento e atualiza o status do slot
	 */
	public function ajax_save_booking() {
		check_ajax_referer( 'gh_public_nonce', 'nonce' );

		global $wpdb;
		$slot_id = intval($_POST['slot_id']);
		$nome    = sanitize_text_field($_POST['nome']);
		$tel     = sanitize_text_field($_POST['telefone']);

		// 1. Verificar se slot ainda está livre
		$slot = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}gh_slots WHERE id = %d AND status = 'disponivel'", $slot_id) );

		if ( ! $slot ) {
			wp_send_json_error( 'Este horário acabou de ser reservado por outra pessoa. Por favor, escolha outro.' );
		}

		// 2. Criar usuário ou pegar existente (Simplificado: salvamos apenas texto por enquanto)
		// Numa versão futura, criaríamos um WP_User aqui.
		
		// 3. Criar Agendamento
		$table_agendamentos = $wpdb->prefix . 'gh_agendamentos';
		
		$data = array(
			'slot_id'          => $slot_id,
			'paciente_user_id' => 0, // 0 = Visitante (sem login)
			'medico_id'        => $slot->medico_id,
			'unidade_id'       => $slot->unidade_id,
			'especialidade_id' => $slot->especialidade_id,
			'servico_id'       => 0, // Pendente implementar escolha de serviço
			'data_hora'        => $slot->data_hora,
			'status'           => 'agendado',
			'observacoes'      => "Paciente: $nome - Tel: $tel" // Guardamos os dados aqui provisoriamente
		);

		$inserted = $wpdb->insert( $table_agendamentos, $data );

		if ( ! $inserted ) {
			wp_send_json_error( 'Erro ao salvar agendamento.' );
		}

		// 4. Atualizar Slot para 'ocupado'
		$wpdb->update( 
			$wpdb->prefix . 'gh_slots', 
			array( 'status' => 'ocupado', 'agendamento_id' => $wpdb->insert_id ), 
			array( 'id' => $slot_id ) 
		);

		wp_send_json_success();
	}
}