<?php

class GH_Especialidade {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gh_especialidades';
    }

    public function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE is_active = 1 ORDER BY nome ASC" );
    }

    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
    }

    public function save( $data ) {
        global $wpdb;

        $fields = array(
            'nome'      => sanitize_text_field( $data['nome'] ),
            'cbo'       => sanitize_text_field( $data['cbo'] ),
            'icone'     => !empty($data['icone']) ? sanitize_text_field( $data['icone'] ) : 'dashicons-heart',
            'is_active' => isset( $data['is_active'] ) ? 1 : 0
        );

        $format = array( '%s', '%s', '%s', '%d' );

        if ( ! empty( $data['id'] ) ) {
            return $wpdb->update( $this->table_name, $fields, array( 'id' => intval( $data['id'] ) ), $format, array( '%d' ) );
        } else {
            if ( ! isset( $data['is_active'] ) ) $fields['is_active'] = 1;
            return $wpdb->insert( $this->table_name, $fields, $format );
        }
    }

    public function delete( $id ) {
        global $wpdb;
        $medicos_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}gh_medico_especialidade WHERE especialidade_id = %d", $id ) );
        
        if ( $medicos_count > 0 ) {
            return new WP_Error( 'dependency_error', 'Impossível excluir: Existem médicos com esta especialidade.' );
        }

        return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }
}