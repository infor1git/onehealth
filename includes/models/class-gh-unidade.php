<?php

/**
 * Model responsável por gerenciar os dados da tabela wp_gh_unidades
 */
class GH_Unidade {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gh_unidades';
    }

    /**
     * Busca todas as unidades ativas
     */
    public function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE is_active = 1 ORDER BY nome ASC" );
    }

    /**
     * Busca uma unidade pelo ID
     */
    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
    }

    /**
     * Salva (Insere ou Atualiza) uma unidade
     */
    public function save( $data ) {
        global $wpdb;

        $fields = array(
            'nome'        => sanitize_text_field( $data['nome'] ),
            'cep'         => sanitize_text_field( $data['cep'] ),
            'logradouro'  => sanitize_text_field( $data['logradouro'] ),
            'numero'      => sanitize_text_field( $data['numero'] ),
            'complemento' => sanitize_text_field( $data['complemento'] ),
            'bairro'      => sanitize_text_field( $data['bairro'] ),
            'cidade'      => sanitize_text_field( $data['cidade'] ),
            'estado'      => sanitize_text_field( $data['estado'] ),
            'mapa_url'    => esc_url_raw( $data['mapa_url'] ),
            'is_active'   => isset( $data['is_active'] ) ? 1 : 0
        );

        $format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' );

        // Se tiver ID, atualiza. Se não, insere.
        if ( ! empty( $data['id'] ) ) {
            return $wpdb->update( 
                $this->table_name, 
                $fields, 
                array( 'id' => intval( $data['id'] ) ), 
                $format, 
                array( '%d' ) 
            );
        } else {
            // Força ativo na criação se não especificado
            if ( ! isset( $data['is_active'] ) ) $fields['is_active'] = 1;
            
            return $wpdb->insert( $this->table_name, $fields, $format );
        }
    }

    /**
     * "Exclui" uma unidade (Soft delete ou validação de dependência)
     */
    public function delete( $id ) {
        global $wpdb;
        
        // Validação de segurança: Verificar se existem médicos ou agendamentos nesta unidade
        $medicos_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}gh_medico_unidade WHERE unidade_id = %d", $id ) );
        
        if ( $medicos_count > 0 ) {
            return new WP_Error( 'dependency_error', 'Não é possível excluir esta unidade pois existem médicos vinculados a ela. Desative-a em vez disso.' );
        }

        // Se livre, exclui fisicamente (ou pode mudar is_active para 0 se preferir histórico eterno)
        return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }
}