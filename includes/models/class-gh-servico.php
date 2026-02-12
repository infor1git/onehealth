<?php

class GH_Servico {

    private $table_name;
    private $table_rel;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gh_servicos';
        $this->table_rel  = $wpdb->prefix . 'gh_servico_especialidade';
    }

    public function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE is_active = 1 ORDER BY nome ASC" );
    }

    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
    }

    public function get_especialidades_ids( $servico_id ) {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare( "SELECT especialidade_id FROM {$this->table_rel} WHERE servico_id = %d", $servico_id ) );
    }

    public function save( $data ) {
        global $wpdb;

        $fields = array(
            'nome'         => sanitize_text_field( $data['nome'] ),
            'tipo'         => sanitize_text_field( $data['tipo'] ),
            'valor'        => floatval( str_replace(',', '.', $data['valor'] ) ), // Trata R$
            'preparo_html' => wp_kses_post( $data['preparo_html'] ), // Permite HTML seguro
            'is_active'    => isset( $data['is_active'] ) ? 1 : 0
        );

        $format = array( '%s', '%s', '%f', '%s', '%d' );
        $servico_id = 0;

        if ( ! empty( $data['id'] ) ) {
            $servico_id = intval( $data['id'] );
            $wpdb->update( $this->table_name, $fields, array( 'id' => $servico_id ), $format, array( '%d' ) );
        } else {
            if ( ! isset( $data['is_active'] ) ) $fields['is_active'] = 1;
            $wpdb->insert( $this->table_name, $fields, $format );
            $servico_id = $wpdb->insert_id;
        }

        // Atualiza Especialidades
        $wpdb->delete( $this->table_rel, array( 'servico_id' => $servico_id ), array( '%d' ) );
        
        if ( ! empty( $data['especialidades'] ) && is_array( $data['especialidades'] ) ) {
            foreach ( $data['especialidades'] as $esp_id ) {
                $wpdb->insert( 
                    $this->table_rel, 
                    array( 'servico_id' => $servico_id, 'especialidade_id' => intval( $esp_id ) ),
                    array( '%d', '%d' )
                );
            }
        }

        return $servico_id;
    }

    public function delete( $id ) {
        global $wpdb;
        // Validação futura: verificar agendamentos
        return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }
}