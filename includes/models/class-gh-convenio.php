<?php

class GH_Convenio {

    private $table_name;
    private $table_planos;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gh_convenios';
        $this->table_planos = $wpdb->prefix . 'gh_planos';
    }

    // --- MÉTODOS DE CONVÊNIO ---

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
            'nome'        => sanitize_text_field( $data['nome'] ),
            'logo_url'    => esc_url_raw( $data['logo_url'] ),
            'exige_guia'  => isset( $data['exige_guia'] ) ? 1 : 0,
            'is_active'   => isset( $data['is_active'] ) ? 1 : 0
        );

        $format = array( '%s', '%s', '%d', '%d' );

        if ( ! empty( $data['id'] ) ) {
            $wpdb->update( $this->table_name, $fields, array( 'id' => intval( $data['id'] ) ), $format, array( '%d' ) );
            return intval( $data['id'] );
        } else {
            if ( ! isset( $data['is_active'] ) ) $fields['is_active'] = 1;
            $wpdb->insert( $this->table_name, $fields, $format );
            return $wpdb->insert_id;
        }
    }

    public function delete( $id ) {
        global $wpdb;
        // Ao deletar convênio, deleta planos (Cascade está no DB, mas garantimos via código se precisar)
        $wpdb->delete( $this->table_planos, array( 'convenio_id' => $id ), array( '%d' ) );
        return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }

    // --- MÉTODOS DE PLANOS ---

    public function get_planos( $convenio_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->table_planos} WHERE convenio_id = %d AND is_active = 1", $convenio_id ) );
    }

    public function save_plano( $data ) {
        global $wpdb;
        
        $fields = array(
            'convenio_id' => intval( $data['convenio_id'] ),
            'nome'        => sanitize_text_field( $data['nome_plano'] ),
            'is_active'   => 1
        );

        return $wpdb->insert( $this->table_planos, $fields, array( '%d', '%s', '%d' ) );
    }

    public function delete_plano( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_planos, array( 'id' => $id ), array( '%d' ) );
    }
}