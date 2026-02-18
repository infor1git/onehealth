<?php

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    One_Health
 * @subpackage One_Health/includes
 */
class One_Health_Activator {

	public static function activate() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();
		$tables = array();

		// [TABELAS EXISTENTES MANTIDAS]
		$tables['gh_unidades'] = "CREATE TABLE {$wpdb->prefix}gh_unidades (
			id bigint(20) NOT NULL AUTO_INCREMENT, nome varchar(255) NOT NULL, cep varchar(10), logradouro varchar(255), numero varchar(20), complemento varchar(100), bairro varchar(100), cidade varchar(100), estado varchar(2), mapa_url text, is_active tinyint(1) DEFAULT 1, created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id)
		) $charset_collate;";

		$tables['gh_especialidades'] = "CREATE TABLE {$wpdb->prefix}gh_especialidades (
			id bigint(20) NOT NULL AUTO_INCREMENT, nome varchar(255) NOT NULL, cbo varchar(20), icone varchar(50) DEFAULT 'dashicons-heart', is_active tinyint(1) DEFAULT 1, created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id)
		) $charset_collate;";

		$tables['gh_convenios'] = "CREATE TABLE {$wpdb->prefix}gh_convenios (
			id bigint(20) NOT NULL AUTO_INCREMENT, nome varchar(255) NOT NULL, logo_url text, exige_guia tinyint(1) DEFAULT 0, is_active tinyint(1) DEFAULT 1, created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id)
		) $charset_collate;";

		$tables['gh_planos'] = "CREATE TABLE {$wpdb->prefix}gh_planos (
			id bigint(20) NOT NULL AUTO_INCREMENT, convenio_id bigint(20) NOT NULL, nome varchar(255) NOT NULL, is_active tinyint(1) DEFAULT 1, created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id), KEY convenio_id (convenio_id)
		) $charset_collate;";

		$tables['gh_servicos'] = "CREATE TABLE {$wpdb->prefix}gh_servicos (
			id bigint(20) NOT NULL AUTO_INCREMENT, nome varchar(255) NOT NULL, tipo varchar(50) NOT NULL, icone varchar(50) DEFAULT 'dashicons-clipboard', valor decimal(10,2) DEFAULT 0.00, preparo_html longtext, is_active tinyint(1) DEFAULT 1, created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id)
		) $charset_collate;";

		$tables['gh_servico_especialidade'] = "CREATE TABLE {$wpdb->prefix}gh_servico_especialidade (
			servico_id bigint(20) NOT NULL, especialidade_id bigint(20) NOT NULL, PRIMARY KEY (servico_id, especialidade_id)
		) $charset_collate;";

		$tables['gh_medicos'] = "CREATE TABLE {$wpdb->prefix}gh_medicos (
			id bigint(20) NOT NULL AUTO_INCREMENT, nome varchar(255) NOT NULL, crm varchar(50) NOT NULL, email varchar(100), telefone varchar(50), cpf varchar(20), foto_url text, is_active tinyint(1) DEFAULT 1, created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id)
		) $charset_collate;";

		$tables['gh_medico_unidade'] = "CREATE TABLE {$wpdb->prefix}gh_medico_unidade (
			medico_id bigint(20) NOT NULL, unidade_id bigint(20) NOT NULL, PRIMARY KEY (medico_id, unidade_id)
		) $charset_collate;";

		$tables['gh_medico_especialidade'] = "CREATE TABLE {$wpdb->prefix}gh_medico_especialidade (
			medico_id bigint(20) NOT NULL, especialidade_id bigint(20) NOT NULL, PRIMARY KEY (medico_id, especialidade_id)
		) $charset_collate;";

		// [NOVA TABELA] Relacionamento Médico x Serviço (para restrições)
		$tables['gh_medico_servico'] = "CREATE TABLE {$wpdb->prefix}gh_medico_servico (
			medico_id bigint(20) NOT NULL, servico_id bigint(20) NOT NULL, PRIMARY KEY (medico_id, servico_id)
		) $charset_collate;";

		$tables['gh_schedules'] = "CREATE TABLE {$wpdb->prefix}gh_schedules (
			id bigint(20) NOT NULL AUTO_INCREMENT, medico_id bigint(20) NOT NULL, unidade_id bigint(20) NOT NULL, especialidade_id bigint(20) NOT NULL, dia_semana tinyint(1) NOT NULL, hora_inicio time NOT NULL, hora_fim time NOT NULL, intervalo_1_inicio time, intervalo_1_fim time, intervalo_2_inicio time, intervalo_2_fim time, intervalo_3_inicio time, intervalo_3_fim time, duracao_slot int DEFAULT 30, tipos_servico varchar(255) DEFAULT 'consulta,exame,procedimento', is_active tinyint(1) DEFAULT 1, PRIMARY KEY  (id), KEY medico_id (medico_id)
		) $charset_collate;";

		$tables['gh_slots'] = "CREATE TABLE {$wpdb->prefix}gh_slots (
			id bigint(20) NOT NULL AUTO_INCREMENT, medico_id bigint(20) NOT NULL, unidade_id bigint(20) NOT NULL, especialidade_id bigint(20) NOT NULL, data_hora datetime NOT NULL, duracao_minutos int DEFAULT 30, tipos_servico varchar(255) DEFAULT 'consulta,exame,procedimento', status varchar(20) DEFAULT 'disponivel', created_at datetime DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY  (id), KEY idx_data_medico (data_hora, medico_id)
		) $charset_collate;";

		$tables['gh_agendamentos'] = "CREATE TABLE {$wpdb->prefix}gh_agendamentos (
			id bigint(20) NOT NULL AUTO_INCREMENT, slot_id bigint(20) NULL, paciente_user_id bigint(20) unsigned NOT NULL, medico_id bigint(20) NOT NULL, unidade_id bigint(20) NOT NULL, especialidade_id bigint(20) NOT NULL, servico_id bigint(20) NOT NULL, convenio_id bigint(20) NULL, plano_id bigint(20) NULL, data_hora datetime NOT NULL, status varchar(20) DEFAULT 'A', anexo_guia_url text, observacoes text, created_by bigint(20) unsigned, created_at datetime DEFAULT CURRENT_TIMESTAMP, updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY  (id), KEY slot_id (slot_id), KEY paciente_user_id (paciente_user_id)
		) $charset_collate;";

		foreach ( $tables as $sql ) {
			dbDelta( $sql );
		}

		self::add_foreign_keys();
		update_option( 'one_health_db_version', '1.2.0' );
	}

	private static function add_foreign_keys() {
		global $wpdb;
		$fk_queries = array();
		$fk_queries[] = "ALTER TABLE {$wpdb->prefix}gh_planos ADD CONSTRAINT fk_planos_convenios FOREIGN KEY (convenio_id) REFERENCES {$wpdb->prefix}gh_convenios(id) ON DELETE CASCADE;";
		$fk_queries[] = "ALTER TABLE {$wpdb->prefix}gh_schedules ADD CONSTRAINT fk_sched_medico FOREIGN KEY (medico_id) REFERENCES {$wpdb->prefix}gh_medicos(id) ON DELETE CASCADE;";
		$fk_queries[] = "ALTER TABLE {$wpdb->prefix}gh_agendamentos ADD CONSTRAINT fk_agend_medico FOREIGN KEY (medico_id) REFERENCES {$wpdb->prefix}gh_medicos(id) ON DELETE RESTRICT;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach($fk_queries as $query) {
			$wpdb->query($query);
		}
	}
}