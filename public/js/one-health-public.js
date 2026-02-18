jQuery(document).ready(function($) {
    if ($('body').hasClass('bw-booking-fullscreen') && $('#gh-booking-wizard').length) { $('#gh-booking-wizard').appendTo('body'); }

    var bookingData = { unidade_id: 0, unidade_nome: '', servico_id: 0, servico_nome: '', especialidade_id: 0, especialidade_nome: '', convenio_id: 0, convenio_nome: '', plano_id: null, plano_nome: '', medico_id: 0, medico_nome: 'Qualquer profissional', data_sql: '', hora: '', slot_id: 0, exige_guia: 0 };
    var stepHistory = [1];
    var servicosMap = {}; 

    // --- SALVAMENTO E RESTAURAÇÃO DE SESSÃO ---
    function saveBookingState() {
        sessionStorage.setItem('gh_booking_state', JSON.stringify({ data: bookingData, history: stepHistory, servMap: servicosMap }));
    }

    function clearBookingState() {
        sessionStorage.removeItem('gh_booking_state');
    }

    var savedState = sessionStorage.getItem('gh_booking_state');
    if (savedState) {
        var state = JSON.parse(savedState);
        bookingData = state.data;
        stepHistory = state.history;
        servicosMap = state.servMap || {};

        var currentStep = stepHistory[stepHistory.length - 1];
        gh_goto_step(currentStep, true); 
    } else {
        if ($('#gh-booking-wizard').length) { loadUnidades(); }
    }

    // Intercepta botão Sair para salvar o estado antes do reload
    $('#bw-logout-link').on('click', function(e) {
        saveBookingState();
        // O link href fará o redirecionamento nativo do WP
    });

    // --- FUNÇÃO DE BUSCA E FILTRO ---
    function enableSearch(containerId, inputPlaceholder) {
        $('#' + containerId).parent().find('.bw-search-input-container').remove();
        var searchHtml = '<div class="bw-search-input-container" style="position:relative; margin-bottom:15px; width: 100%;"><input type="text" class="bw-input bw-search-input" placeholder="'+inputPlaceholder+'" style="padding-left:45px;"><span class="dashicons dashicons-search" style="position:absolute; left:15px; top:18px; color:var(--bw-color-text-secondary);"></span></div>';
        $('#' + containerId).before(searchHtml);
        $('#' + containerId).parent().find('input.bw-search-input').on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $('#' + containerId + ' .bw-card-option').each(function() { $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1); });
        });
    }

    // --- NAVEGAÇÃO SEGURA (Com Re-fetch Automático se vazio) ---
    window.gh_goto_step = function(targetStep, isRestoring) {
        $('.bw-step-content').removeClass('active').hide(); $('#step-' + targetStep).addClass('active').fadeIn(300);
        $('.bw-step').removeClass('active'); $('.bw-step').each(function() { if($(this).data('step') <= targetStep) { $(this).addClass('active'); } });

        // Se está restaurando (ou voltando) e a div estiver vazia, chama a função correspondente
        if (targetStep >= 3 && bookingData.servico_nome) { $('#bw-step-3-title').text('Qual a especialidade para o Serviço: ' + bookingData.servico_nome + '?'); }
        
        if (targetStep == 1 && $('#gh-unidades-list .bw-card-option').length === 0) loadUnidades();
        if (targetStep == 2 && $('#gh-servicos-list .bw-card-option').length === 0) loadServicos();
        if (targetStep == 3 && $('#gh-especialidades-list .bw-card-option').length === 0) loadEspecialidades();
        if (targetStep == 4 && $('#gh-convenios-list .bw-card-option').length === 0 && $('#gh-saved-convenio-area').is(':hidden')) loadConvenios();
        if (targetStep == 5 && $('#gh-medicos-list .bw-card-option').length === 0) loadMedicos();
        if (targetStep == 6 && $('#bw-cal-grid .bw-cal-day').length === 0) initCalendar();
        
        if(targetStep == 7) {
            renderSummary();
            if(!gh_vars.is_logged_in && bookingData.convenio_id > 0) {
                $('#r_convenio').val(bookingData.convenio_id).trigger('change', [bookingData.plano_id]); 
            }
        }

        if(!isRestoring) saveBookingState();
    }

    window.gh_next_step = function(nextStep) { if(stepHistory[stepHistory.length - 1] !== nextStep) { stepHistory.push(nextStep); } gh_goto_step(nextStep, false); };
    window.gh_prev_step = function() { if(stepHistory.length > 1) { stepHistory.pop(); gh_goto_step(stepHistory[stepHistory.length - 1], false); } };
    $('.bw-step').on('click', function() { var c = $(this).data('step'); if(stepHistory.indexOf(c) !== -1) { stepHistory = stepHistory.slice(0, stepHistory.indexOf(c) + 1); gh_goto_step(c, false); } });

    // PASSO 1 > 2
    function loadUnidades() { $.post(gh_vars.ajax_url, { action: 'gh_get_unidades' }, function(res) { if(res.success) { var html = ''; $.each(res.data, function(i, item) { html += '<div tabindex="0" class="bw-card-option" onclick="selectUnidade('+item.id+', \''+item.nome+'\')"><span class="dashicons dashicons-location" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>'+item.nome+'</h4></div>'; }); $('#gh-unidades-list').html(html); enableSearch('gh-unidades-list', 'Buscar unidade...'); } }); }
    window.selectUnidade = function(id, nome) { bookingData.unidade_id = id; bookingData.unidade_nome = nome; loadServicos(); gh_next_step(2); };

    // PASSO 2 > 3
    function loadServicos() {
        $('#gh-servicos-list').html('<p style="opacity:0.7;">Buscando serviços...</p>'); servicosMap = {}; 
        $.post(gh_vars.ajax_url, { action: 'gh_get_servicos', unidade_id: bookingData.unidade_id }, function(res) {
            if(res.success) {
                var html = ''; if(res.data.length === 0) { html = '<p style="opacity:0.7;">Nenhum serviço disponível.</p>'; } else { $.each(res.data, function(i, item) { servicosMap[item.id] = item.preparo_html; html += '<div tabindex="0" class="bw-card-option" onclick="selectServico('+item.id+', \''+item.nome+'\')"><span class="dashicons '+(item.icone ? item.icone : 'dashicons-clipboard')+'" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>'+item.nome+'</h4></div>'; }); }
                $('#gh-servicos-list').html(html); enableSearch('gh-servicos-list', 'Buscar serviço...');
            }
        });
    }
    window.selectServico = function(id, nome) {
        bookingData.servico_id = id; bookingData.servico_nome = nome;
        $('#bw-step-3-title').text('Qual a especialidade para o Serviço: ' + nome + '?');
        if(servicosMap[id] && servicosMap[id].trim() !== '') { $('#bw-modal-preparo-text').html(servicosMap[id]); $('#bw-modal-preparo').css('display', 'flex').hide().fadeIn(250); } else { loadEspecialidades(); }
    };
    $('#bw-btn-fechar-modal').on('click', function() { $('#bw-modal-preparo').fadeOut(200); });
    $('#bw-btn-continuar-modal').on('click', function() { $('#bw-modal-preparo').fadeOut(200); loadEspecialidades(); });

    // PASSO 3 > 4
    function loadEspecialidades() {
        $('#gh-especialidades-list').html('<p style="opacity:0.7;">Buscando especialidades...</p>');
        $.post(gh_vars.ajax_url, { action: 'gh_get_especialidades', unidade_id: bookingData.unidade_id, servico_id: bookingData.servico_id }, function(res) {
            if(res.success) {
                if(res.data.length === 1) { bookingData.especialidade_id = res.data[0].id; bookingData.especialidade_nome = res.data[0].nome; loadConvenios(); gh_next_step(4); } 
                else if(res.data.length > 1) { var html = ''; $.each(res.data, function(i, item) { html += '<div tabindex="0" class="bw-card-option" onclick="selectEspecialidade('+item.id+', \''+item.nome+'\')"><span class="dashicons '+(item.icone ? item.icone : 'dashicons-heart')+'" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>'+item.nome+'</h4></div>'; }); $('#gh-especialidades-list').html(html); enableSearch('gh-especialidades-list', 'Buscar especialidade...'); gh_next_step(3); } 
                else { $('#gh-especialidades-list').html('<p style="opacity:0.7;">Sem agenda disponível.</p>'); gh_next_step(3); }
            }
        });
    }
    window.selectEspecialidade = function(id, nome) { bookingData.especialidade_id = id; bookingData.especialidade_nome = nome; loadConvenios(); gh_next_step(4); };

    // PASSO 4
    function loadConvenios() {
        if(gh_vars.is_logged_in && gh_vars.u_convenio_id > 0) {
            var nomeText = gh_vars.u_convenio_nome; if(gh_vars.u_plano_nome) nomeText += ' (Plano: ' + gh_vars.u_plano_nome + ')';
            $('#gh-saved-convenio-name').text(nomeText); $('#gh-saved-convenio-area').show(); $('#gh-convenios-list-container').hide();
        } else { showAllConvenios(); }
    }
    window.useSavedConvenio = function() {
        $.post(gh_vars.ajax_url, { action: 'gh_get_convenios' }, function(res) {
            var ex = 0; if(res.success) { $.each(res.data, function(i, item) { if(item.id == gh_vars.u_convenio_id) ex = item.exige_guia; }); }
            bookingData.convenio_id = gh_vars.u_convenio_id; bookingData.convenio_nome = gh_vars.u_convenio_nome; bookingData.plano_id = gh_vars.u_plano_id; bookingData.plano_nome = gh_vars.u_plano_nome; bookingData.exige_guia = ex; loadMedicos(); gh_next_step(5);
        });
    };
    window.showAllConvenios = function() {
        $('#gh-saved-convenio-area').hide(); $('#gh-convenios-list-container').show();
        if(gh_vars.is_logged_in) { $('#gh-save-convenio-checkbox-area').show(); }
        $('#gh-convenios-list').html('<p style="opacity:0.7;">Buscando convênios...</p>');
        $.post(gh_vars.ajax_url, { action: 'gh_get_convenios' }, function(res) {
            if(res.success) {
                var html = '<div tabindex="0" class="bw-card-option" onclick="selectConvenio(0, \'Particular\', 0)"><span class="dashicons dashicons-money-alt" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span><h4>Particular</h4></div>';
                $.each(res.data, function(i, item) { html += '<div tabindex="0" class="bw-card-option" onclick="selectConvenio('+item.id+', \''+item.nome+'\', '+item.exige_guia+')">'; if(item.logo_url) { html += '<img src="'+item.logo_url+'" style="max-height:40px; margin-bottom:10px; border-radius:4px;">'; } else { html += '<span class="dashicons dashicons-shield" style="font-size:32px; height:32px; color:var(--bw-color-accent);"></span>'; } html += '<h4>'+item.nome+'</h4></div>'; });
                $('#gh-convenios-list').html(html); enableSearch('gh-convenios-list', 'Qual o seu convênio?');
            }
        });
    };
    window.selectConvenio = function(id, nome, exige) { 
        bookingData.convenio_id = id; bookingData.convenio_nome = nome; bookingData.exige_guia = exige; bookingData.plano_id = null; bookingData.plano_nome = '';
        if(id == 0) { loadMedicos(); gh_next_step(5); } else {
            $.post(gh_vars.ajax_url, { action: 'gh_get_planos', convenio_id: id }, function(res) {
                if(res.success && res.data.length > 0) {
                    var html = ''; $.each(res.data, function(i, item) { html += '<div class="bw-plano-option" onclick="selectPlano('+item.id+', \''+item.nome+'\')">'+item.nome+'</div>'; });
                    $('#bw-modal-planos-list').html(html); $('#bw-modal-planos').css('display', 'flex').hide().fadeIn(250);
                } else { loadMedicos(); gh_next_step(5); }
            });
        }
    };
    window.selectPlano = function(id, nome) { bookingData.plano_id = id; bookingData.plano_nome = nome; $('#bw-modal-planos').fadeOut(200); loadMedicos(); gh_next_step(5); };
    $('#bw-btn-fechar-modal-planos').on('click', function() { $('#bw-modal-planos').fadeOut(200); });

    // PASSO 5, 6
    function loadMedicos() {
        $('#gh-medicos-list').html('<p style="opacity:0.7;">Buscando corpo clínico...</p>');
        $.post(gh_vars.ajax_url, { action: 'gh_get_medicos', unidade_id: bookingData.unidade_id, especialidade_id: bookingData.especialidade_id, servico_id: bookingData.servico_id }, function(res) {
            if(res.success) {
                var html = ''; if(res.data.length === 0) { html = '<p style="opacity:0.7;">Nenhum profissional disponível.</p>'; $('#gh-skip-medico').hide(); } 
                else { $('#gh-skip-medico').show(); $.each(res.data, function(i, item) { html += '<div tabindex="0" class="bw-card-option" onclick="selectMedico('+item.id+', \''+item.nome+'\')">'; if(item.foto_url) { html += '<img src="'+item.foto_url+'" style="width:70px;height:70px;border-radius:50%;object-fit:cover;margin-bottom:12px; border:3px solid var(--bw-color-accent);">'; } else { html += '<span class="dashicons dashicons-businessman" style="font-size:50px; width:50px; height:50px; color:var(--bw-color-accent);"></span>'; } html += '<h4>'+item.nome+'</h4><span style="font-size:12px; opacity: 0.8;">CRM: '+item.crm+'</span></div>'; }); }
                $('#gh-medicos-list').html(html); enableSearch('gh-medicos-list', 'Pesquisar pelo nome...');
            }
        });
    }
    window.selectMedico = function(id, nome) { bookingData.medico_id = id; bookingData.medico_nome = nome; initCalendar(); gh_next_step(6); };
    window.skipMedico = function() { bookingData.medico_id = 0; bookingData.medico_nome = 'Qualquer Profissional'; initCalendar(); gh_next_step(6); };

    var calDate = new Date(); function initCalendar() { calDate = new Date(); renderCalendarMonth(); }
    $('#bw-cal-prev').on('click', function() { calDate.setMonth(calDate.getMonth() - 1); renderCalendarMonth(); });
    $('#bw-cal-next').on('click', function() { calDate.setMonth(calDate.getMonth() + 1); renderCalendarMonth(); });
    function renderCalendarMonth() {
        var month = calDate.getMonth(); var year = calDate.getFullYear(); var mesAno = year + '-' + String(month + 1).padStart(2, '0');
        var monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        $('#bw-cal-month-name').text(monthNames[month] + " " + year); buildCalendarGrid(month, year, []); $('#gh-slots-list').html('<p style="opacity:0.7; padding: 20px;">Selecione um dia no calendário ao lado.</p>');
        $.post(gh_vars.ajax_url, { action: 'gh_get_available_dates', unidade_id: bookingData.unidade_id, especialidade_id: bookingData.especialidade_id, servico_id: bookingData.servico_id, medico_id: bookingData.medico_id, mes_ano: mesAno }, function(res) { if(res.success && res.data) { buildCalendarGrid(month, year, res.data); } });
    }
    function buildCalendarGrid(month, year, availMap) {
        var grid = $('#bw-cal-grid'); grid.empty(); var firstDay = new Date(year, month, 1).getDay(); var daysInMonth = new Date(year, month + 1, 0).getDate();
        for (let i = 0; i < firstDay; i++) { grid.append('<div class="bw-cal-empty"></div>'); }
        for (let d = 1; d <= daysInMonth; d++) {
            var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0'); var isAvailable = availMap.includes(dateStr);
            var divDay = $('<div class="bw-cal-day">' + d + '</div>'); divDay.attr('data-date', dateStr);
            if(isAvailable) { divDay.addClass('available'); divDay.on('click', function() { $('.bw-cal-day').removeClass('selected'); $(this).addClass('selected'); loadSlots($(this).attr('data-date')); }); } else { divDay.addClass('disabled'); } grid.append(divDay);
        }
    }
    function loadSlots(dateStr) {
        $('#gh-slots-list').html('<p style="padding: 20px;">Buscando horários...</p>'); bookingData.data_sql = dateStr;
        $.post(gh_vars.ajax_url, { action: 'gh_get_slots', unidade_id: bookingData.unidade_id, especialidade_id: bookingData.especialidade_id, servico_id: bookingData.servico_id, medico_id: bookingData.medico_id, data: dateStr }, function(res) {
            if(res.success) { var html = ''; if(res.data.length === 0) { html = '<p style="grid-column: 1/-1; padding:20px; opacity:0.7;">Acabaram os horários para este dia.</p>'; } else { $.each(res.data, function(i, item) { html += '<div tabindex="0" class="bw-card-option bw-slot-card" onclick="selectSlot('+item.id+', \''+item.hora_formatada+'\', \''+item.medico_nome+'\')"><strong style="font-size:1.4rem;">'+item.hora_formatada+'</strong>'; if(bookingData.medico_id == 0) { html += '<div style="font-size:0.75rem; margin-top:4px; opacity:0.8;">'+item.medico_nome+'</div>'; } html += '</div>'; }); } $('#gh-slots-list').html(html); }
        });
    }

    // PASSO 7 (Autenticação e Confirmação com Upload)
    window.selectSlot = function(id, hora, medico_nome_slot) { bookingData.slot_id = id; bookingData.hora = hora; if(bookingData.medico_id == 0) bookingData.medico_nome = medico_nome_slot; renderSummary(); gh_next_step(7); };

    function renderSummary() { 
        $('#sum-unidade').text(bookingData.unidade_nome); $('#sum-servico').text(bookingData.servico_nome); $('#sum-especialidade').text(bookingData.especialidade_nome); 
        var textConvenio = bookingData.convenio_nome; if(bookingData.plano_id) { textConvenio += ' (Plano: ' + bookingData.plano_nome + ')'; } $('#sum-convenio').text(textConvenio); $('#sum-medico').text(bookingData.medico_nome); var parts = bookingData.data_sql.split('-'); $('#sum-data').text(parts[2] + '/' + parts[1] + '/' + parts[0] + ' às ' + bookingData.hora); 
    }

    $('.bw-auth-tab').on('click', function(){ $('.bw-auth-tab').removeClass('active'); $(this).addClass('active'); $('.bw-auth-form').hide(); $('#' + $(this).data('target')).fadeIn(); });

    $('#r_cep').on('blur', function() {
        var cep = $(this).val().replace(/\D/g, '');
        if(cep.length == 8) {
            $.getJSON('https://viacep.com.br/ws/'+cep+'/json/', function(data) {
                if(!data.erro) { $('#r_rua').val(data.logradouro); $('#r_bairro').val(data.bairro); $('#r_cidade').val(data.localidade); $('#r_uf').val(data.uf); $('#r_num').focus(); }
            });
        }
    });

    $('#r_convenio').on('change', function(e, prefillPlanoId) {
        var cid = $(this).val();
        if(cid > 0) {
            $.post(gh_vars.ajax_url, { action: 'gh_get_planos', convenio_id: cid }, function(res) {
                if(res.success && res.data.length > 0) {
                    var opts = '<option value="">Selecione o plano...</option>'; $.each(res.data, function(i, p) { opts += '<option value="'+p.id+'">'+p.nome+'</option>'; });
                    $('#r_plano').html(opts).parent().show(); if(prefillPlanoId) $('#r_plano').val(prefillPlanoId);
                } else { $('#r_plano').html('<option value="">Sem planos</option>').parent().hide(); }
            });
        } else { $('#r_plano').html('<option value="">Selecione um convênio primeiro</option>').parent().hide(); }
    });

    // LOGIN
    $('#gh-login-form').on('submit', function(e){
        e.preventDefault(); var btn = $(this).find('button[type="submit"]'); btn.prop('disabled', true).text('Aguarde...');
        var token = $(this).find('[name="cf-turnstile-response"]').val();
        $.post(gh_vars.ajax_url, { action: 'gh_login_user', nonce: gh_vars.nonce, email: $('#l_email').val(), pass: $('#l_pass').val(), ts_token: token }, function(res){
            if(res.success) { saveBookingState(); location.reload(); } 
            else { alert(res.data); btn.prop('disabled', false).text('Entrar e Continuar'); if(typeof turnstile !== 'undefined') turnstile.reset(); }
        });
    });

    // REGISTER
    $('#gh-register-form').on('submit', function(e){
        e.preventDefault(); var btn = $(this).find('button[type="submit"]'); btn.prop('disabled', true).text('Registrando...');
        var token = $(this).find('[name="cf-turnstile-response"]').val();
        var data = { action: 'gh_register_user', nonce: gh_vars.nonce, ts_token: token, nome: $('#r_nome').val(), sobrenome: $('#r_sobrenome').val(), cpf: $('#r_cpf').val(), nasc: $('#r_nasc').val(), email: $('#r_email').val(), tel: $('#r_tel').val(), pass: $('#r_pass').val(), cep: $('#r_cep').val(), rua: $('#r_rua').val(), num: $('#r_num').val(), comp: $('#r_comp').val(), bairro: $('#r_bairro').val(), cidade: $('#r_cidade').val(), uf: $('#r_uf').val(), convenio_id: $('#r_convenio').val(), plano_id: $('#r_plano').val(), cart: $('#r_cart').val(), v_cart: $('#r_val_cart').val() };
        $.post(gh_vars.ajax_url, data, function(res){
            if(res.success) { saveBookingState(); location.reload(); } 
            else { alert(res.data); btn.prop('disabled', false).text('Cadastrar e Continuar'); if(typeof turnstile !== 'undefined') turnstile.reset(); }
        });
    });

    // CONFIRM FINAL
    $('#gh-final-confirm-form').on('submit', function(e){
        e.preventDefault(); 
        if(bookingData.exige_guia == 1) { $('#bw-modal-upload').css('display', 'flex').hide().fadeIn(250); } else { submitFinalBooking(); }
    });

    $('#bw-btn-fechar-upload').on('click', function() { $('#bw-modal-upload').fadeOut(200); });
    $('#bw-btn-enviar-upload').on('click', function() {
        if(!$('#gh_guia_file')[0].files[0]) { alert("Por favor, selecione o arquivo do pedido médico."); return; }
        $('#bw-modal-upload').fadeOut(200); submitFinalBooking();
    });

    function submitFinalBooking() {
        var form = $('#gh-final-confirm-form'); var btn = form.find('button[type="submit"]'); 
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Processando...');
        
        var token = form.find('[name="cf-turnstile-response"]').val();
        var updateProfile = $('#gh_save_new_convenio').is(':checked') ? 1 : 0;

        var formData = new FormData();
        formData.append('action', 'gh_save_booking'); formData.append('nonce', gh_vars.nonce); formData.append('ts_token', token);
        formData.append('slot_id', bookingData.slot_id); formData.append('servico_id', bookingData.servico_id); formData.append('convenio_id', bookingData.convenio_id);
        if(bookingData.plano_id) formData.append('plano_id', bookingData.plano_id);
        formData.append('update_profile_convenio', updateProfile);

        if(bookingData.exige_guia == 1 && $('#gh_guia_file')[0].files.length > 0) { formData.append('guia_file', $('#gh_guia_file')[0].files[0]); }

        $.ajax({
            url: gh_vars.ajax_url, type: 'POST', data: formData, processData: false, contentType: false,
            success: function(res) {
                if(res.success) { clearBookingState(); alert('Agendamento realizado com sucesso!'); location.reload(); } 
                else { alert('Atenção: ' + res.data); btn.prop('disabled', false).html('<span class="dashicons dashicons-calendar-alt"></span> Finalizar Agendamento'); if(typeof turnstile !== 'undefined') turnstile.reset(); }
            },
            error: function(xhr) { alert('Erro de conexão ou segurança ('+xhr.status+'). A página será recarregada.'); saveBookingState(); location.reload(); }
        });
    }
});