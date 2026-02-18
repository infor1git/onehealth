<?php

class GH_Medico {

    private $table_name;
    private $table_unidade;
    private $table_especialidade;
    private $table_servico; // [NOVO]

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'gh_medicos';
        $this->table_unidade = $wpdb->prefix . 'gh_medico_unidade';
        $this->table_especialidade = $wpdb->prefix . 'gh_medico_especialidade';
        $this->table_servico = $wpdb->prefix . 'gh_medico_servico';
    }

    public function get_all() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_name} WHERE is_active = 1 ORDER BY nome ASC" );
    }

    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ) );
    }

    public function get_unidades_ids( $medico_id ) {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare( "SELECT unidade_id FROM {$this->table_unidade} WHERE medico_id = %d", $medico_id ) );
    }

    public function get_especialidades_ids( $medico_id ) {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare( "SELECT especialidade_id FROM {$this->table_especialidade} WHERE medico_id = %d", $medico_id ) );
    }

    // [NOVO] Retorna IDs de serviços explicitamente vinculados
    public function get_servicos_ids( $medico_id ) {
        global $wpdb;
        return $wpdb->get_col( $wpdb->prepare( "SELECT servico_id FROM {$this->table_servico} WHERE medico_id = %d", $medico_id ) );
    }

    public function save( $data ) {
        global $wpdb;

        $fields = array(
            'nome'      => sanitize_text_field( $data['nome'] ),
            'crm'       => sanitize_text_field( $data['crm'] ),
            'email'     => sanitize_email( $data['email'] ),
            'telefone'  => sanitize_text_field( $data['telefone'] ),
            'cpf'       => sanitize_text_field( $data['cpf'] ),
            'foto_url'  => esc_url_raw( $data['foto_url'] ),
            'is_active' => isset( $data['is_active'] ) ? 1 : 0
        );

        $format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%d' );
        $medico_id = 0;

        if ( ! empty( $data['id'] ) ) {
            $medico_id = intval( $data['id'] );
            $wpdb->update( $this->table_name, $fields, array( 'id' => $medico_id ), $format, array( '%d' ) );
        } else {
            if ( ! isset( $data['is_active'] ) ) $fields['is_active'] = 1;
            $wpdb->insert( $this->table_name, $fields, $format );
            $medico_id = $wpdb->insert_id;
        }

        if ( ! $medico_id ) return false;

        // 1. Unidades
        $wpdb->delete( $this->table_unidade, array( 'medico_id' => $medico_id ), array( '%d' ) );
        if ( ! empty( $data['unidades'] ) && is_array( $data['unidades'] ) ) {
            foreach ( $data['unidades'] as $uid ) {
                $wpdb->insert( $this->table_unidade, array( 'medico_id' => $medico_id, 'unidade_id' => intval($uid) ), array( '%d', '%d' ) );
            }
        }

        // 2. Especialidades
        $wpdb->delete( $this->table_especialidade, array( 'medico_id' => $medico_id ), array( '%d' ) );
        if ( ! empty( $data['especialidades'] ) && is_array( $data['especialidades'] ) ) {
            foreach ( $data['especialidades'] as $eid ) {
                $wpdb->insert( $this->table_especialidade, array( 'medico_id' => $medico_id, 'especialidade_id' => intval($eid) ), array( '%d', '%d' ) );
            }
        }

        // 3. Serviços (Nova Lógica)
        // Regra: "Se nenhum serviço selecionado, atende todos. Se algum selecionado, atende só aqueles."
        // Implementação no Banco: Salvamos apenas o que foi marcado.
        // Se a lista vier vazia, apagamos tudo (o que na lógica de leitura significará "todos", se validarmos assim).
        // Mas para garantir a lógica "Marcar Todos por Padrão", o admin deve enviar os IDs.
        
        $wpdb->delete( $this->table_servico, array( 'medico_id' => $medico_id ), array( '%d' ) );
        
        if ( ! empty( $data['servicos_permitidos'] ) && is_array( $data['servicos_permitidos'] ) ) {
            foreach ( $data['servicos_permitidos'] as $sid ) {
                $wpdb->insert( $this->table_servico, array( 'medico_id' => $medico_id, 'servico_id' => intval($sid) ), array( '%d', '%d' ) );
            }
        }

        return $medico_id;
    }

    public function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) );
    }
}