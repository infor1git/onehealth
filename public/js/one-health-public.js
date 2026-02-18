jQuery(document).ready(function($) {
    if ($('body').hasClass('bw-booking-fullscreen') && $('#gh-booking-wizard').length) { $('#gh-booking-wizard').appendTo('body'); }

    var bookingData = { unidade_id: 0, unidade_nome: '', especialidade_id: 0, especialidade_nome: '', servico_id: 0, servico_nome: '', convenio_id: 0, convenio_nome: '', medico_id: 0, medico_nome: 'Qualquer profissional', data_sql: '', hora: '', slot_id: 0 };
    var stepHistory = [1];
    var servicosMap = {}; 

    if ($('#gh-booking-wizard').length) { loadUnidades(); }

    // --- FUNÇÃO DE BUSCA UNIVERSAL ---
    function enableSearch(containerId, inputPlaceholder) {
        // Remove input anterior se existir para evitar duplicação
        $('#' + containerId).parent().find('.bw-search-input-container').remove();
        
        var searchHtml = '<div class="bw-search-input-container" style="margin-bottom:15px;">';
        searchHtml += '<input type="text" class="bw-input bw-search-input" placeholder="'+inputPlaceholder+'" style="padding-left:35px;">';
        searchHtml += '<span class="dashicons dashicons-search" style="position:absolute; left:10px; top:12px; color:#999;"></span>';
        searchHtml += '</div>';
        
        $('#' + containerId).before(searchHtml);
        
        var input = $('#' + containerId).parent().find('input.bw-search-input');
        input.parent().css('position', 'relative'); // Para o ícone

        input.on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $('#' + containerId + ' .bw-card-option').each(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(val) > -1);
            });
        });
    }

    // NAVEGAÇÃO
    window.gh_goto_step = function(targetStep) {
        $('.bw-step-content').removeClass('active').hide();
        $('#step-' + targetStep).addClass('active').fadeIn(300);
        $('.bw-step').removeClass('active');
        $('.bw-step').each(function() { if($(this).data('step') <= targetStep) { $(this).addClass('active'); } });
    }
    window.gh_next_step = function(nextStep) { if(stepHistory[stepHistory.length - 1] !== nextStep) { stepHistory.push(nextStep); } gh_goto_step(nextStep); };
    window.gh_prev_step = function() { if(stepHistory.length > 1) { stepHistory.pop(); var prev = stepHistory[stepHistory.length - 1]; gh_goto_step(prev); } };
    $('.bw-step').on('click', function() { var c = $(this).data('step'); if(stepHistory.indexOf(c) !== -1) { stepHistory = stepHistory.slice(0, stepHistory.indexOf(c) + 1); gh_goto_step(c); } });

    // CARREGAMENTOS COM BUSCA
    function loadUnidades() {
        $.post(gh_vars.ajax_url, { action: 'gh_get_unidades' }, function(res) {
            if(res.success) {
                var html = '';
                $.each(res.data, function(i, item) {
                    html += '<div tabindex="0" class="bw-card-option" onclick="selectUnidade('+item.id+', \''+item.nome+'\')"><span class="dashicons dashicons-location" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>'+item.nome+'</h4></div>';
                });
                $('#gh-unidades-list').html(html);
                // Busca em Unidades? Pode ser útil
            }
        });
    }
    window.selectUnidade = function(id, nome) { bookingData.unidade_id = id; bookingData.unidade_nome = nome; loadEspecialidades(); gh_next_step(2); };

    function loadEspecialidades() {
        $('#gh-especialidades-list').html('<p style="opacity:0.7;">Buscando especialidades...</p>');
        $.post(gh_vars.ajax_url, { action: 'gh_get_especialidades' }, function(res) {
            if(res.success) {
                var html = '';
                if(res.data.length === 0) { html = '<p style="opacity:0.7;">Sem agenda disponível.</p>'; } 
                else {
                    $.each(res.data, function(i, item) {
                        var icone = item.icone ? item.icone : 'dashicons-heart';
                        html += '<div tabindex="0" class="bw-card-option" onclick="selectEspecialidade('+item.id+', \''+item.nome+'\')"><span class="dashicons '+icone+'" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>'+item.nome+'</h4></div>';
                    });
                }
                $('#gh-especialidades-list').html(html);
                enableSearch('gh-especialidades-list', 'Buscar especialidade...');
            }
        });
    }
    window.selectEspecialidade = function(id, nome) { bookingData.especialidade_id = id; bookingData.especialidade_nome = nome; loadServicos(); gh_next_step(3); };

    function loadServicos() {
        $('#gh-servicos-list').html('<p style="opacity:0.7;">Buscando serviços...</p>');
        servicosMap = {}; 
        $.post(gh_vars.ajax_url, { action: 'gh_get_servicos', especialidade_id: bookingData.especialidade_id }, function(res) {
            if(res.success) {
                var html = '';
                if(res.data.length === 0) { html = '<p style="opacity:0.7;">Nenhum serviço disponível.</p>'; } 
                else {
                    $.each(res.data, function(i, item) {
                        servicosMap[item.id] = item.preparo_html; 
                        var icone = item.icone ? item.icone : 'dashicons-clipboard';
                        html += '<div tabindex="0" class="bw-card-option" onclick="selectServico('+item.id+', \''+item.nome+'\')"><span class="dashicons '+icone+'" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>'+item.nome+'</h4></div>';
                    });
                }
                $('#gh-servicos-list').html(html);
                enableSearch('gh-servicos-list', 'Buscar serviço...');
            }
        });
    }
    window.selectServico = function(id, nome) {
        bookingData.servico_id = id; bookingData.servico_nome = nome;
        var preparo = servicosMap[id];
        if(preparo && preparo.trim() !== '') { $('#bw-modal-preparo-text').html(preparo); $('#bw-modal-preparo').css('display', 'flex').hide().fadeIn(250); } 
        else { loadConvenios(); gh_next_step(4); }
    };
    $('#bw-btn-fechar-modal').on('click', function() { $('#bw-modal-preparo').fadeOut(200); });
    $('#bw-btn-continuar-modal').on('click', function() { $('#bw-modal-preparo').fadeOut(200); loadConvenios(); gh_next_step(4); });

    function loadConvenios() {
        $('#gh-convenios-list').html('<p style="opacity:0.7;">Buscando convênios...</p>');
        $.post(gh_vars.ajax_url, { action: 'gh_get_convenios' }, function(res) {
            if(res.success) {
                var html = '<div tabindex="0" class="bw-card-option" onclick="selectConvenio(0, \'Particular\')"><span class="dashicons dashicons-money-alt" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>Particular</h4></div>';
                $.each(res.data, function(i, item) {
                    html += '<div tabindex="0" class="bw-card-option" onclick="selectConvenio('+item.id+', \''+item.nome+'\')">';
                    if(item.logo_url) { html += '<img src="'+item.logo_url+'" style="max-height:40px; margin-bottom:10px; border-radius:4px;">'; } 
                    else { html += '<span class="dashicons dashicons-shield" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span>'; }
                    html += '<h4>'+item.nome+'</h4></div>';
                });
                $('#gh-convenios-list').html(html);
                enableSearch('gh-convenios-list', 'Buscar convênio...');
            }
        });
    }
    window.selectConvenio = function(id, nome) { bookingData.convenio_id = id; bookingData.convenio_nome = nome; loadMedicos(); gh_next_step(5); };

    function loadMedicos() {
        $('#gh-medicos-list').html('<p style="opacity:0.7;">Buscando corpo clínico...</p>');
        $.post(gh_vars.ajax_url, { 
            action: 'gh_get_medicos', 
            unidade_id: bookingData.unidade_id, 
            especialidade_id: bookingData.especialidade_id,
            servico_id: bookingData.servico_id // Passa o serviço para filtrar médicos
        }, function(res) {
            if(res.success) {
                var html = '';
                if(res.data.length === 0) { html = '<p style="opacity:0.7;">Nenhum profissional disponível para este serviço.</p>'; $('#gh-skip-medico').hide(); } 
                else {
                    $('#gh-skip-medico').show();
                    $.each(res.data, function(i, item) {
                        html += '<div tabindex="0" class="bw-card-option" onclick="selectMedico('+item.id+', \''+item.nome+'\')">';
                        if(item.foto_url) { html += '<img src="'+item.foto_url+'" style="width:70px;height:70px;border-radius:50%;object-fit:cover;margin-bottom:12px; border:3px solid var(--bw-color-accent);">'; } 
                        else { html += '<span class="dashicons dashicons-businessman" style="font-size:50px; width:50px; height:50px; color:var(--bw-color-accent);"></span>'; }
                        html += '<h4>'+item.nome+'</h4><span style="font-size:12px; opacity: 0.8;">CRM: '+item.crm+'</span></div>';
                    });
                }
                $('#gh-medicos-list').html(html);
            }
        });
    }
    window.selectMedico = function(id, nome) { bookingData.medico_id = id; bookingData.medico_nome = nome; initCalendar(); gh_next_step(6); };
    window.skipMedico = function() { bookingData.medico_id = 0; bookingData.medico_nome = 'Qualquer Profissional'; initCalendar(); gh_next_step(6); };

    // [CALENDÁRIO E FINALIZAÇÃO MANTIDOS IDÊNTICOS - CÓDIGO LONGO SUPRIMIDO PARA ECONOMIA DE ESPAÇO NA RESPOSTA, MAS ESTÁ NO CÓDIGO ORIGINAL]
    // ... (initCalendar, renderCalendarMonth, buildCalendarGrid, loadSlots, selectSlot, renderSummary, Form Submit) ...
    // Apenas certifique-se de que a lógica de calendário receba o servico_id também, como feito na versão anterior.
    
    // REINSERINDO A PARTE DO CALENDÁRIO PARA GARANTIR FUNCIONAMENTO:
    var calDate = new Date(); var availableDatesMap = [];
    function initCalendar() { calDate = new Date(); renderCalendarMonth(); }
    $('#bw-cal-prev').on('click', function() { calDate.setMonth(calDate.getMonth() - 1); renderCalendarMonth(); });
    $('#bw-cal-next').on('click', function() { calDate.setMonth(calDate.getMonth() + 1); renderCalendarMonth(); });
    function renderCalendarMonth() {
        var month = calDate.getMonth(); var year = calDate.getFullYear();
        var mesAno = year + '-' + String(month + 1).padStart(2, '0');
        var monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        $('#bw-cal-month-name').text(monthNames[month] + " " + year);
        buildCalendarGrid(month, year, []);
        $('#gh-slots-list').html('<p style="opacity:0.7; padding: 20px;">Selecione um dia no calendário ao lado.</p>');
        $.post(gh_vars.ajax_url, { 
            action: 'gh_get_available_dates', unidade_id: bookingData.unidade_id, especialidade_id: bookingData.especialidade_id, servico_id: bookingData.servico_id, medico_id: bookingData.medico_id, mes_ano: mesAno
        }, function(res) { if(res.success && res.data) { availableDatesMap = res.data; buildCalendarGrid(month, year, availableDatesMap); } });
    }
    function buildCalendarGrid(month, year, availMap) {
        var grid = $('#bw-cal-grid'); grid.empty();
        var firstDay = new Date(year, month, 1).getDay(); var daysInMonth = new Date(year, month + 1, 0).getDate();
        for (let i = 0; i < firstDay; i++) { grid.append('<div class="bw-cal-empty"></div>'); }
        for (let d = 1; d <= daysInMonth; d++) {
            var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            var isAvailable = availMap.includes(dateStr);
            var divDay = $('<div class="bw-cal-day">' + d + '</div>'); divDay.attr('data-date', dateStr);
            if(isAvailable) { divDay.addClass('available'); divDay.on('click', function() { $('.bw-cal-day').removeClass('selected'); $(this).addClass('selected'); loadSlots($(this).attr('data-date')); }); } 
            else { divDay.addClass('disabled'); } grid.append(divDay);
        }
    }
    function loadSlots(dateStr) {
        $('#gh-slots-list').html('<p style="padding: 20px;">Buscando horários para o dia...</p>'); bookingData.data_sql = dateStr;
        $.post(gh_vars.ajax_url, { action: 'gh_get_slots', unidade_id: bookingData.unidade_id, especialidade_id: bookingData.especialidade_id, servico_id: bookingData.servico_id, medico_id: bookingData.medico_id, data: dateStr }, function(res) {
            if(res.success) {
                var html = '';
                if(res.data.length === 0) { html = '<p style="grid-column: 1/-1; padding:20px; opacity:0.7;">Acabaram os horários para este dia.</p>'; } 
                else { $.each(res.data, function(i, item) { html += '<div tabindex="0" class="bw-card-option bw-slot-card" onclick="selectSlot('+item.id+', \''+item.hora_formatada+'\', \''+item.medico_nome+'\')"><strong style="font-size:1.4rem;">'+item.hora_formatada+'</strong>'; if(bookingData.medico_id == 0) { html += '<div style="font-size:0.75rem; margin-top:4px; opacity:0.8;">'+item.medico_nome+'</div>'; } html += '</div>'; }); }
                $('#gh-slots-list').html(html);
            }
        });
    }
    window.selectSlot = function(id, hora, medico_nome_slot) { bookingData.slot_id = id; bookingData.hora = hora; if(bookingData.medico_id == 0) bookingData.medico_nome = medico_nome_slot; renderSummary(); gh_next_step(7); };
    function renderSummary() { $('#sum-unidade').text(bookingData.unidade_nome); $('#sum-especialidade').text(bookingData.especialidade_nome); $('#sum-servico').text(bookingData.servico_nome); $('#sum-convenio').text(bookingData.convenio_nome); $('#sum-medico').text(bookingData.medico_nome); var parts = bookingData.data_sql.split('-'); $('#sum-data').text(parts[2] + '/' + parts[1] + '/' + parts[0] + ' às ' + bookingData.hora); }
    $('#gh-booking-form').on('submit', function(e) {
        e.preventDefault(); var nome = $('#gh_paciente_nome').val(); var tel = $('#gh_paciente_tel').val();
        if(!nome || !tel) { alert('Preencha nome e telefone.'); return; }
        var btn = $(this).find('button[type="submit"]'); btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Processando...');
        $.post(gh_vars.ajax_url, { action: 'gh_save_booking', nonce: gh_vars.nonce, slot_id: bookingData.slot_id, servico_id: bookingData.servico_id, convenio_id: bookingData.convenio_id, nome: nome, telefone: tel }, function(res) {
            if(res.success) { alert('Agendamento realizado com sucesso!'); location.reload(); } else { alert('Atenção: ' + res.data); btn.prop('disabled', false).html('<span class="dashicons dashicons-calendar-alt"></span> Tentar Novamente'); }
        });
    });
});