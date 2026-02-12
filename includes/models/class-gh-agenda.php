<?php

class GH_Agenda {

    private $table_schedules;
    private $table_slots;

    public function __construct() {
        global $wpdb;
        $this->table_schedules = $wpdb->prefix . 'gh_schedules';
        $this->table_slots     = $wpdb->prefix . 'gh_slots';
    }

    /**
     * Retorna os turnos configurados para um médico
     */
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

    /**
     * Salva um novo turno de trabalho
     */
    public function save_schedule( $data ) {
        global $wpdb;

        // Sanitização rigorosa
        $medico_id        = intval( $data['medico_id'] );
        $unidade_id       = intval( $data['unidade_id'] );
        $especialidade_id = intval( $data['especialidade_id'] );
        $dia_semana       = intval( $data['dia_semana'] );
        $hora_inicio      = sanitize_text_field( $data['hora_inicio'] );
        $hora_fim         = sanitize_text_field( $data['hora_fim'] );
        
        // Intervalos opcionais
        $int1_ini = !empty($data['intervalo_1_inicio']) ? sanitize_text_field( $data['intervalo_1_inicio'] ) : null;
        $int1_fim = !empty($data['intervalo_1_fim']) ? sanitize_text_field( $data['intervalo_1_fim'] ) : null;

        // Validação Lógica
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
            'is_active'          => 1
        );

        $format = array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d' );

        $inserted = $wpdb->insert( $this->table_schedules, $fields, $format );

        if ( false === $inserted ) {
            return new WP_Error( 'db_error', 'Erro ao salvar no banco de dados: ' . $wpdb->last_error );
        }

        return $wpdb->insert_id;
    }

    /**
     * Remove um turno
     */
    public function delete_schedule( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_schedules, array( 'id' => $id ), array( '%d' ) );
    }

    /**
     * Gera os slots (horários) baseados nos turnos
     */
    public function generate_slots( $start_date, $end_date, $medico_id = null ) {
        global $wpdb;

        // 1. Buscar regras (schedules)
        $query = "SELECT * FROM {$this->table_schedules} WHERE is_active = 1";
        if ( $medico_id ) {
            $query .= $wpdb->prepare( " AND medico_id = %d", $medico_id );
        }
        $schedules = $wpdb->get_results( $query );

        if ( empty( $schedules ) ) return 0;

        $count = 0;
        $current = strtotime( $start_date );
        $end     = strtotime( $end_date );

        // 2. Iterar dias
        while ( $current <= $end ) {
            $dia_semana_hoje = date( 'w', $current ); // 0 (Dom) - 6 (Sab)
            $data_hoje       = date( 'Y-m-d', $current );

            foreach ( $schedules as $rule ) {
                // Se a regra não for para hoje, pule
                if ( intval($rule->dia_semana) !== intval($dia_semana_hoje) ) continue;

                $duracao  = 30; // Minutos (Pode virar config depois)
                $inicio   = strtotime( "$data_hoje " . $rule->hora_inicio );
                $fim      = strtotime( "$data_hoje " . $rule->hora_fim );
                
                // Intervalo/Almoço
                $pausa_ini = $rule->intervalo_1_inicio ? strtotime( "$data_hoje " . $rule->intervalo_1_inicio ) : 0;
                $pausa_fim = $rule->intervalo_1_fim ? strtotime( "$data_hoje " . $rule->intervalo_1_fim ) : 0;

                // Loop de horários dentro do turno
                while ( $inicio < $fim ) {
                    // Pula se estiver no intervalo
                    if ( $pausa_ini && $pausa_fim && $inicio >= $pausa_ini && $inicio < $pausa_fim ) {
                        $inicio = strtotime( "+{$duracao} minutes", $inicio );
                        continue;
                    }

                    // Verifica duplicação antes de inserir
                    $data_hora_slot = date( 'Y-m-d H:i:s', $inicio );
                    
                    // Otimização: Check simples
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
                            'status'           => 'disponivel'
                        ));
                        $count++;
                    }

                    // Próximo slot
                    $inicio = strtotime( "+{$duracao} minutes", $inicio );
                }
            }
            // Próximo dia
            $current = strtotime( '+1 day', $current );
        }

        return $count;
    }

    /**
     * Limpa slots para regeneração
     */
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