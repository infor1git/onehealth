<?php

class GH_Agenda {

    private $table_schedules;
    private $table_slots;

    public function __construct() {
        global $wpdb;
        $this->table_schedules = $wpdb->prefix . 'gh_schedules';
        $this->table_slots     = $wpdb->prefix . 'gh_slots';
    }

    public function get_schedules_by_medico( $medico_id ) {
        global $wpdb;
        
        $sql = "SELECT s.*, 
                       u.nome as unidade_nome, 
                       e.nome as especialidade_nome 
                FROM {$this->table_schedules} s
                LEFT JOIN {$wpdb->prefix}gh_unidades u ON s.unidade_id = u.id
                LEFT JOIN {$wpdb->prefix}gh_especialidades e ON s.especialidade_id = e.id
                WHERE s.medico_id = %d 
                ORDER BY s.dia_semana ASC, s.hora_inicio ASC";
        
        return $wpdb->get_results( $wpdb->prepare( $sql, $medico_id ) );
    }

    public function save_schedule( $data ) {
        global $wpdb;

        $medico_id        = intval( $data['medico_id'] );
        $unidade_id       = intval( $data['unidade_id'] );
        $especialidade_id = intval( $data['especialidade_id'] );
        $dia_semana       = intval( $data['dia_semana'] );
        $hora_inicio      = sanitize_text_field( $data['hora_inicio'] );
        $hora_fim         = sanitize_text_field( $data['hora_fim'] );
        
        // Novos campos: Duração e Tipos
        $duracao_slot     = !empty($data['duracao_slot']) ? intval($data['duracao_slot']) : 30;
        $tipos_servico    = !empty($data['tipos_servico']) && is_array($data['tipos_servico']) ? implode(',', array_map('sanitize_text_field', $data['tipos_servico'])) : 'consulta,exame,procedimento';
        
        // Intervalos
        $int1_ini = !empty($data['intervalo_1_inicio']) ? sanitize_text_field( $data['intervalo_1_inicio'] ) : null;
        $int1_fim = !empty($data['intervalo_1_fim']) ? sanitize_text_field( $data['intervalo_1_fim'] ) : null;

        if ( strtotime($hora_inicio) >= strtotime($hora_fim) ) {
            return new WP_Error( 'invalid_time', 'Erro: O horário final deve ser depois do inicial.' );
        }

        $fields = array(
            'medico_id'          => $medico_id,
            'unidade_id'         => $unidade_id,
            'especialidade_id'   => $especialidade_id,
            'dia_semana'         => $dia_semana,
            'hora_inicio'        => $hora_inicio,
            'hora_fim'           => $hora_fim,
            'intervalo_1_inicio' => $int1_ini,
            'intervalo_1_fim'    => $int1_fim,
            'duracao_slot'       => $duracao_slot,
            'tipos_servico'      => $tipos_servico,
            'is_active'          => 1
        );

        $format = array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d' );

        $inserted = $wpdb->insert( $this->table_schedules, $fields, $format );

        if ( false === $inserted ) {
            return new WP_Error( 'db_error', 'Erro ao salvar no banco de dados.' );
        }

        return $wpdb->insert_id;
    }

    public function delete_schedule( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_schedules, array( 'id' => $id ), array( '%d' ) );
    }

    /**
     * Gera os slots respeitando a duração configurada no turno e tipos de serviço
     */
    public function generate_slots( $start_date, $end_date, $medico_id = null ) {
        global $wpdb;

        $query = "SELECT * FROM {$this->table_schedules} WHERE is_active = 1";
        if ( $medico_id ) {
            $query .= $wpdb->prepare( " AND medico_id = %d", $medico_id );
        }
        $schedules = $wpdb->get_results( $query );

        if ( empty( $schedules ) ) return 0;

        $count = 0;
        $current = strtotime( $start_date );
        $end     = strtotime( $end_date );

        while ( $current <= $end ) {
            $dia_semana_hoje = date( 'w', $current );
            $data_hoje       = date( 'Y-m-d', $current );

            foreach ( $schedules as $rule ) {
                if ( intval($rule->dia_semana) !== intval($dia_semana_hoje) ) continue;

                // Usa a duração definida no turno ou 30 min padrão
                $duracao  = intval($rule->duracao_slot) > 0 ? intval($rule->duracao_slot) : 30;
                $inicio   = strtotime( "$data_hoje " . $rule->hora_inicio );
                $fim      = strtotime( "$data_hoje " . $rule->hora_fim );
                $tipos    = isset($rule->tipos_servico) ? $rule->tipos_servico : 'consulta,exame,procedimento';
                
                $pausa_ini = $rule->intervalo_1_inicio ? strtotime( "$data_hoje " . $rule->intervalo_1_inicio ) : 0;
                $pausa_fim = $rule->intervalo_1_fim ? strtotime( "$data_hoje " . $rule->intervalo_1_fim ) : 0;

                while ( $inicio < $fim ) {
                    if ( $pausa_ini && $pausa_fim && $inicio >= $pausa_ini && $inicio < $pausa_fim ) {
                        $inicio = strtotime( "+{$duracao} minutes", $inicio );
                        continue;
                    }

                    $data_hora_slot = date( 'Y-m-d H:i:s', $inicio );
                    
                    $existe = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$this->table_slots} WHERE medico_id = %d AND data_hora = %s LIMIT 1",
                        $rule->medico_id, $data_hora_slot
                    ));

                    if ( ! $existe ) {
                        $wpdb->insert( $this->table_slots, array(
                            'medico_id'        => $rule->medico_id,
                            'unidade_id'       => $rule->unidade_id,
                            'especialidade_id' => $rule->especialidade_id,
                            'data_hora'        => $data_hora_slot,
                            'duracao_minutos'  => $duracao,
                            'tipos_servico'    => $tipos, // Grava os tipos permitidos no slot
                            'status'           => 'disponivel'
                        ));
                        $count++;
                    }

                    $inicio = strtotime( "+{$duracao} minutes", $inicio );
                }
            }
            $current = strtotime( '+1 day', $current );
        }

        return $count;
    }

    public function clear_slots( $start, $end, $medico_id = null ) {
        global $wpdb;
        $sql = "DELETE FROM {$this->table_slots} WHERE status = 'disponivel' AND data_hora BETWEEN %s AND %s";
        $args = array( "$start 00:00:00", "$end 23:59:59" );
        if ( $medico_id ) {
            $sql .= " AND medico_id = %d";
            $args[] = $medico_id;
        }
        return $wpdb->query( $wpdb->prepare( $sql, $args ) );
    }
}