<?php

/**
 * Disparado durante a desativação do plugin.
 *
 * @since      1.0.0
 * @package    One_Health
 * @subpackage One_Health/includes
 */
class One_Health_Deactivator {

	/**
	 * Execução curta na desativação.
	 * Nota: Não apagamos as tabelas na desativação para não perder dados acidentalmente.
	 * A remoção de dados deve ser feita apenas em um script de "uninstall" se desejado.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Limpar regras de rewrite se necessário, ou parar crons.
		flush_rewrite_rules();
	}

}