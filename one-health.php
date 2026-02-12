<?php
/**
 * Plugin Name:       One Health
 * Description:       Sistema SAAS de agendamento de consultas, exames e procedimentos para clínicas e hospitais.
 * Version:           1.0.0
 * Author:            One Health Tech
 * Text Domain:       one-health
 * Domain Path:       /languages
 */

// Se este arquivo for chamado diretamente, abortar.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Definição de Constantes do Plugin
 */
define( 'ONE_HEALTH_VERSION', '1.0.0' );
define( 'ONE_HEALTH_DB_VERSION', '1.0.0' );
define( 'ONE_HEALTH_PATH', plugin_dir_path( __FILE__ ) );
define( 'ONE_HEALTH_URL', plugin_dir_url( __FILE__ ) );

/**
 * Código que roda na ativação do plugin.
 * Usado para criar tabelas de banco de dados.
 */
function activate_one_health() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-one-health-activator.php';
	One_Health_Activator::activate();
}

/**
 * Código que roda na desativação do plugin.
 */
function deactivate_one_health() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-one-health-deactivator.php';
	One_Health_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_one_health' );
register_deactivation_hook( __FILE__, 'deactivate_one_health' );

/**
 * A classe core principal do plugin que orquestra tudo.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-one-health-core.php';

/**
 * Inicia a execução do plugin.
 */
function run_one_health() {
	$plugin = new One_Health_Core();
	$plugin->run();
}
run_one_health();