jQuery(document).ready(function($) {
    
    var bookingData = {
        unidade_id: null,
        unidade_nome: '',
        especialidade_id: null,
        especialidade_nome: '',
        medico_id: null,
        medico_nome: 'Qualquer profissional',
        data_sql: null,
        hora: '',
        slot_id: null
    };

    if ($('#gh-booking-wizard').length) {
        loadUnidades();
    }

    // --- NAVEGAÇÃO ---
    window.gh_next_step = function(current) {
        $('.bw-step-content').removeClass('active');
        $('#step-' + (current + 1)).addClass('active');
        $('.bw-step').removeClass('active');
        $('.bw-step[data-step="' + (current + 1) + '"]').addClass('active');
    };

    window.gh_prev_step = function(current) {
        $('.bw-step-content').removeClass('active');
        $('#step-' + (current - 1)).addClass('active');
        $('.bw-step').removeClass('active');
        $('.bw-step[data-step="' + (current - 1) + '"]').addClass('active');
    };

    // --- CARREGAMENTO DE DADOS ---
    function loadUnidades() {
        $.post(gh_vars.ajax_url, { action: 'gh_get_unidades' }, function(res) {
            if(res.success) {
                var html = '';
                $.each(res.data, function(i, item) {
                    html += '<div tabindex="0" class="bw-card-option" onclick="selectUnidade('+item.id+', \''+item.nome+'\')">';
                    html += '<span class="dashicons dashicons-location" style="font-size:28px; width:28px; height:28px; color:var(--bw-color-accent);"></span>';
                    html += '<h4>'+item.nome+'</h4>';
                    html += '</div>';
                });
                $('#gh-unidades-list').html(html);
            }
        });
    }

    window.selectUnidade = function(id, nome) {
        bookingData.unidade_id = id;
        bookingData.unidade_nome = nome;
        loadEspecialidades();
        gh_next_step(1);
    };

    function loadEspecialidades() {
        $('#gh-especialidades-list').html('<p style="color:var(--bw-color-text-secondary);">Carregando...</p>');
        $.post(gh_vars.ajax_url, { action: 'gh_get_especialidades' }, function(res) {
            if(res.success) {
                var html = '';
                $.each(res.data, function(i, item) {
                    html += '<div tabindex="0" class="bw-card-option" onclick="selectEspecialidade('+item.id+', \''+item.nome+'\')">';
                    html += '<span class="dashicons dashicons-heart" style="font-size:28px; width:28px; height:28px; color:var(--bw-color-accent);"></span>';
                    html += '<h4>'+item.nome+'</h4>';
                    html += '</div>';
                });
                $('#gh-especialidades-list').html(html);
            }
        });
    }

    window.selectEspecialidade = function(id, nome) {
        bookingData.especialidade_id = id;
        bookingData.especialidade_nome = nome;
        loadMedicos();
        gh_next_step(2);
    };

    function loadMedicos() {
        $('#gh-medicos-list').html('<p style="color:var(--bw-color-text-secondary);">Carregando...</p>');
        $.post(gh_vars.ajax_url, { 
            action: 'gh_get_medicos',
            unidade_id: bookingData.unidade_id,
            especialidade_id: bookingData.especialidade_id
        }, function(res) {
            if(res.success) {
                var html = '';
                if(res.data.length === 0) {
                    html = '<p style="color:var(--bw-color-text-secondary);">Nenhum profissional encontrado nesta unidade.</p>';
                    $('#gh-skip-medico').hide();
                } else {
                    $('#gh-skip-medico').show();
                    $.each(res.data, function(i, item) {
                        html += '<div tabindex="0" class="bw-card-option" onclick="selectMedico('+item.id+', \''+item.nome+'\')">';
                        if(item.foto_url) {
                            html += '<img src="'+item.foto_url+'" style="width:64px;height:64px;border-radius:50%;object-fit:cover;margin-bottom:8px; border:2px solid var(--bw-color-accent);">';
                        } else {
                            html += '<span class="dashicons dashicons-businessman" style="font-size:40px; width:40px; height:40px; color:var(--bw-color-accent);"></span>';
                        }
                        html += '<h4>'+item.nome+'</h4>';
                        html += '<span style="font-size:12px; color:var(--bw-color-text-secondary)">CRM: '+item.crm+'</span>';
                        html += '</div>';
                    });
                }
                $('#gh-medicos-list').html(html);
            }
        });
    }

    window.selectMedico = function(id, nome) {
        bookingData.medico_id = id;
        bookingData.medico_nome = nome;
        initCalendar();
        gh_next_step(3);
    };

    window.skipMedico = function() {
        bookingData.medico_id = null;
        bookingData.medico_nome = 'Qualquer Profissional';
        initCalendar();
        gh_next_step(3);
    };

    function initCalendar() {
        var today = $('#gh-date-picker').val();
        loadSlots(today);
        $('#gh-date-picker').off('change').on('change', function() {
            loadSlots($(this).val());
        });
    }

    function loadSlots(date) {
        $('#gh-slots-list').html('<p style="color:var(--bw-color-text-secondary);">Buscando horários...</p>');
        bookingData.data_sql = date;

        $.post(gh_vars.ajax_url, { 
            action: 'gh_get_slots',
            unidade_id: bookingData.unidade_id,
            especialidade_id: bookingData.especialidade_id,
            medico_id: bookingData.medico_id,
            data: date
        }, function(res) {
            if(res.success) {
                var html = '';
                if(res.data.length === 0) {
                    html = '<p style="grid-column: 1/-1; text-align:center; color:var(--bw-color-text-secondary);">Nenhum horário disponível nesta data.</p>';
                } else {
                    $.each(res.data, function(i, item) {
                        html += '<div tabindex="0" class="bw-card-option" style="padding:12px; flex-direction:column; justify-content:center;" onclick="selectSlot('+item.id+', \''+item.hora_formatada+'\', \''+item.medico_nome+'\')">';
                        html += '<strong style="font-size:1.2rem;">'+item.hora_formatada+'</strong>';
                        if(!bookingData.medico_id) {
                            html += '<div style="font-size:0.75rem; color:var(--bw-color-text-secondary); margin-top:4px;">'+item.medico_nome+'</div>';
                        }
                        html += '</div>';
                    });
                }
                $('#gh-slots-list').html(html);
            }
        });
    }

    window.selectSlot = function(id, hora, medico_nome_slot) {
        bookingData.slot_id = id;
        bookingData.hora = hora;
        if(!bookingData.medico_id) bookingData.medico_nome = medico_nome_slot;
        renderSummary();
        gh_next_step(4);
    };

    function renderSummary() {
        $('#sum-unidade').text(bookingData.unidade_nome);
        $('#sum-especialidade').text(bookingData.especialidade_nome);
        $('#sum-medico').text(bookingData.medico_nome);
        var parts = bookingData.data_sql.split('-');
        $('#sum-data').text(parts[2] + '/' + parts[1] + '/' + parts[0] + ' às ' + bookingData.hora);
    }

    // --- SUBMISSÃO FINAL ---
    $('#gh-booking-form').on('submit', function(e) {
        e.preventDefault();
        
        var nome = $('#gh_paciente_nome').val();
        var tel = $('#gh_paciente_tel').val();

        if(!nome || !tel) {
            alert('Preencha nome e telefone.');
            return;
        }

        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Processando...');

        $.post(gh_vars.ajax_url, {
            action: 'gh_save_booking',
            nonce: gh_vars.nonce,
            slot_id: bookingData.slot_id,
            nome: nome,
            telefone: tel
        }, function(res) {
            if(res.success) {
                alert('Agendamento realizado com sucesso!');
                location.reload(); 
            } else {
                alert('Erro: ' + res.data);
                btn.prop('disabled', false).text('Tentar Novamente');
            }
        });
    });

});