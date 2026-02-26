jQuery(document).ready(function($) {
    // =========================================================================
    // PROTEÇÃO FULLSCREEN GERAL (Para qualquer tela do sistema)
    // =========================================================================
    if ($('.bw-wizard-wrapper').length) {
        // 1. Força a classe no body via JS (Caso o hook do PHP do tema falhe)
        $('body').addClass('bw-booking-fullscreen');
        
        // 2. Arranca a tela de dentro do tema e joga na raiz do site
        $('.bw-wizard-wrapper').appendTo('body'); 
    }

    var bookingData = { unidade_id: 0, unidade_nome: '', servico_id: 0, servico_nome: '', especialidade_id: 0, especialidade_nome: '', convenio_id: 0, convenio_nome: '', plano_id: null, plano_nome: '', medico_id: 0, medico_nome: 'Qualquer profissional', data_sql: '', hora: '', slot_id: 0, exige_guia: 0, update_profile_convenio: 0 };
    var stepHistory = [1];
    var servicosMap = {}; 

    // --- MÁSCARAS DE INPUTS EM TEMPO REAL ---
    $(document).on('input', 'input[name="cpf"]', function(){
        var v = $(this).val().replace(/\D/g, ''); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d)/, '$1.$2'); v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2'); $(this).val(v);
    });
    $(document).on('input', 'input[name="tel"]', function(){
        var v = $(this).val().replace(/\D/g, ''); v = v.replace(/^(\d{2})(\d)/g, '($1) $2'); v = v.replace(/(\d)(\d{4})$/, '$1-$2'); $(this).val(v);
    });
    $(document).on('input', 'input[name="cep"]', function(){
        var v = $(this).val().replace(/\D/g, ''); v = v.replace(/^(\d{5})(\d)/, '$1-$2'); $(this).val(v);
    });
    $(document).on('input', 'input[name="nasc"]', function(){
        var v = $(this).val().replace(/\D/g, '');
        if (v.length > 8) v = v.substring(0, 8);
        v = v.replace(/(\d{2})(\d)/, '$1/$2'); v = v.replace(/(\d{2})(\d)/, '$1/$2');
        $(this).val(v);
    });

    // --- SESSÃO DO USUÁRIO ---
    function saveBookingState() { sessionStorage.setItem('gh_booking_state', JSON.stringify({ data: bookingData, history: stepHistory, servMap: servicosMap })); }
    function clearBookingState() { sessionStorage.removeItem('gh_booking_state'); }

    try {
        var savedState = sessionStorage.getItem('gh_booking_state');
        if (savedState && gh_vars.is_logged_in) { 
            var state = JSON.parse(savedState);
            bookingData = state.data; stepHistory = state.history || [1]; servicosMap = state.servMap || {};
            if ($('#gh-unidades-list .bw-card-option').length === 0) loadUnidades(); 
            gh_goto_step(stepHistory[stepHistory.length - 1], true); 
        } else {
            if ($('#gh-booking-wizard').length && gh_vars.is_logged_in) { loadUnidades(); }
        }
    } catch(e) { clearBookingState(); if ($('#gh-booking-wizard').length && gh_vars.is_logged_in) { loadUnidades(); } }

    function enableSearch(containerId, inputPlaceholder) {
        $('#' + containerId).parent().find('.bw-search-input-container').remove();
        var searchHtml = '<div class="bw-search-input-container" style="position:relative; margin-bottom:15px; width: 100%;"><input type="text" class="bw-input bw-search-input" placeholder="'+inputPlaceholder+'" style="padding-left:45px;"><span class="dashicons dashicons-search" style="position:absolute; left:15px; top:18px; color:var(--bw-color-text-secondary);"></span></div>';
        $('#' + containerId).before(searchHtml);
        $('#' + containerId).parent().find('input.bw-search-input').on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $('#' + containerId + ' .bw-card-option').each(function() { $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1); });
        });
    }

    // --- NAVEGAÇÃO SEGURA (Com Auto-Scroll) ---
    window.gh_goto_step = function(targetStep, isRestoring) {
        $('.bw-step-content').removeClass('active').hide(); $('#step-' + targetStep).addClass('active').fadeIn(300);
        $('.bw-step').removeClass('active'); $('.bw-step').each(function() { if($(this).data('step') <= targetStep) { $(this).addClass('active'); } });

        // Auto-Scroll Mobile das Abas
        var $progressBar = $('.bw-progress-bar');
        var $activeStep = $('.bw-step[data-step="' + targetStep + '"]');
        if ($activeStep.length && $progressBar.length) {
            var scrollPos = $activeStep.position().left + $progressBar.scrollLeft() - ($progressBar.width() / 2) + ($activeStep.outerWidth() / 2);
            $progressBar.animate({ scrollLeft: scrollPos }, 300);
        }

        if (targetStep >= 3 && bookingData.servico_nome) { $('#bw-step-3-title').text('Qual a especialidade para o Serviço: ' + bookingData.servico_nome + '?'); }
        
        if (targetStep == 1 && $('#gh-unidades-list .bw-card-option').length === 0) loadUnidades();
        if (targetStep == 2 && $('#gh-servicos-list .bw-card-option').length === 0) loadServicos();
        if (targetStep == 3 && $('#gh-especialidades-list .bw-card-option').length === 0) loadEspecialidades();
        if (targetStep == 4 && $('#gh-convenios-list .bw-card-option').length === 0 && $('#gh-saved-convenio-area').is(':hidden')) loadConvenios();
        if (targetStep == 5 && $('#gh-medicos-list .bw-card-option').length === 0) loadMedicos();
        if (targetStep == 6 && $('#bw-cal-grid .bw-cal-day').length === 0) initCalendar();
        
        if(targetStep == 7) { 
            renderSummary(); 
            // Inicializa ou re-inicializa os dropdowns customizados
            applyCustomSelects(); 
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
        bookingData.servico_id = id; bookingData.servico_nome = nome; $('#bw-step-3-title').text('Qual a especialidade para o Serviço: ' + nome + '?');
        if(servicosMap[id] && servicosMap[id].trim() !== '') { $('#bw-modal-preparo-text').html(servicosMap[id]); $('#bw-modal-preparo').css('display', 'flex').hide().fadeIn(250); } else { loadEspecialidades(); }
    };
    $('.bw-close-modal').on('click', function() { $(this).closest('.bw-modal-overlay').fadeOut(200); });
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
        if(gh_vars.u_convenio_id > 0) {
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
        $('#gh-saved-convenio-area').hide(); $('#gh-convenios-list-container').show(); $('#gh-save-convenio-checkbox-area').show();
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
        if(id == 0) { 
            promptConvenioUpdate(function() { loadMedicos(); gh_next_step(5); }); 
        } else {
            $.post(gh_vars.ajax_url, { action: 'gh_get_planos', convenio_id: id }, function(res) {
                if(res.success && res.data.length > 0) {
                    var html = ''; $.each(res.data, function(i, item) { html += '<div class="bw-plano-option" onclick="selectPlano('+item.id+', \''+item.nome+'\')">'+item.nome+'</div>'; });
                    $('#bw-modal-planos-list').html(html); $('#bw-modal-planos').css('display', 'flex').hide().fadeIn(250);
                } else { 
                    promptConvenioUpdate(function() { loadMedicos(); gh_next_step(5); }); 
                }
            });
        }
    };

    window.selectPlano = function(id, nome) { 
        bookingData.plano_id = id; bookingData.plano_nome = nome; 
        $('#bw-modal-planos').fadeOut(200); 
        promptConvenioUpdate(function() { loadMedicos(); gh_next_step(5); }); 
    };

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

    var calDate = new Date(); 
    var isAutoAdvancing = false;
    var autoAdvanceAttempts = 0;

    function initCalendar(autoAdvance = false) { 
        calDate = new Date(); 
        isAutoAdvancing = autoAdvance; 
        autoAdvanceAttempts = 0; 
        renderCalendarMonth(); 
    }

    // Chamadas modificadas para ativar o auto-avanço ao chegar na etapa 6
    window.selectMedico = function(id, nome) { bookingData.medico_id = id; bookingData.medico_nome = nome; initCalendar(true); gh_next_step(6); };
    window.skipMedico = function() { bookingData.medico_id = 0; bookingData.medico_nome = 'Qualquer Profissional'; initCalendar(true); gh_next_step(6); };

    $('#bw-cal-prev').on('click', function() { isAutoAdvancing = false; calDate.setMonth(calDate.getMonth() - 1); renderCalendarMonth(); });
    $('#bw-cal-next').on('click', function() { isAutoAdvancing = false; calDate.setMonth(calDate.getMonth() + 1); renderCalendarMonth(); });
    
    function renderCalendarMonth() {
        var month = calDate.getMonth(); var year = calDate.getFullYear(); var mesAno = year + '-' + String(month + 1).padStart(2, '0');
        var monthNames = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
        $('#bw-cal-month-name').text(monthNames[month] + " " + year); buildCalendarGrid(month, year, []); $('#gh-slots-list').html('<p style="opacity:0.7; padding: 20px;">Selecione um dia no calendário.</p>');
        
        $.post(gh_vars.ajax_url, { action: 'gh_get_available_dates', unidade_id: bookingData.unidade_id, especialidade_id: bookingData.especialidade_id, servico_id: bookingData.servico_id, medico_id: bookingData.medico_id, mes_ano: mesAno }, function(res) { 
            if(res.success && res.data) { 
                // Se não houver vaga, e estiver buscando automaticamente (máx 6 meses pra frente)
                if(res.data.length === 0 && isAutoAdvancing && autoAdvanceAttempts < 6) {
                    autoAdvanceAttempts++;
                    calDate.setMonth(calDate.getMonth() + 1);
                    renderCalendarMonth();
                    return; // Para a execução atual
                }
                isAutoAdvancing = false; // Desliga o modo automático após encontrar ou atingir o limite
                buildCalendarGrid(month, year, res.data); 
            } 
        });
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

    // PASSO 7 (Resumo Final)
    window.selectSlot = function(id, hora, medico_nome_slot) { bookingData.slot_id = id; bookingData.hora = hora; if(bookingData.medico_id == 0) bookingData.medico_nome = medico_nome_slot; renderSummary(); gh_next_step(7); };

    function renderSummary() { 
        $('#sum-unidade').text(bookingData.unidade_nome); $('#sum-servico').text(bookingData.servico_nome); $('#sum-especialidade').text(bookingData.especialidade_nome); 
        var textConvenio = bookingData.convenio_nome; if(bookingData.plano_id) { textConvenio += ' (Plano: ' + bookingData.plano_nome + ')'; } $('#sum-convenio').text(textConvenio); $('#sum-medico').text(bookingData.medico_nome); var parts = bookingData.data_sql.split('-'); $('#sum-data').text(parts[2] + '/' + parts[1] + '/' + parts[0] + ' às ' + bookingData.hora); 
    }

    // --- AUTENTICAÇÃO E REGISTRO ---
    $(document).on('click', '.bw-auth-tab', function(){ 
        var parent = $(this).closest('.bw-auth-tabs').parent();
        parent.find('.bw-auth-tab').removeClass('active'); $(this).addClass('active'); 
        parent.find('.bw-auth-form').hide(); $('#' + $(this).data('target')).fadeIn(); 
    });

    $(document).on('blur', '.viacep-input', function() {
        var form = $(this).closest('form'); var cep = $(this).val().replace(/\D/g, '');
        if(cep.length == 8) { $.getJSON('https://viacep.com.br/ws/'+cep+'/json/', function(data) { if(!data.erro) { form.find('[name="rua"]').val(data.logradouro); form.find('[name="bairro"]').val(data.bairro); form.find('[name="cidade"]').val(data.localidade); form.find('[name="uf"]').val(data.uf); form.find('[name="num"]').focus(); } }); }
    });

    // =========================================================================
    // LÓGICA DO SELECT CUSTOMIZADO (Dropdown Premium) E BUSCA DE PLANOS
    // =========================================================================
    function applyCustomSelects() {
        // Encontra select's ainda não processados (especificamente o de convênio e plano no form de registro)
        $('select.dyn-convenio:not(.bw-custom-select-applied), select.dyn-plano:not(.bw-custom-select-applied)').each(function() {
            var $select = $(this);
            $select.addClass('bw-custom-select-applied');
            
            // Cria o Container e as Estruturas
            var $wrapper = $('<div class="bw-select-wrapper"></div>');
            var $trigger = $('<div class="bw-select-trigger"><span class="bw-select-text"></span><span class="dashicons dashicons-arrow-down-alt2"></span></div>');
            var $optionsContainer = $('<div class="bw-select-options"></div>');

            $select.hide().after($wrapper);
            $wrapper.append($trigger).append($optionsContainer);

            // Popula os dados iniciais
            updateCustomSelectDOM($select, $trigger, $optionsContainer);

            // Evento de Clique para Abrir/Fechar
            $trigger.on('click', function(e) {
                e.stopPropagation();
                var isOpen = $(this).hasClass('open');
                
                // Fecha todos os outros antes de abrir este
                $('.bw-select-trigger').removeClass('open');
                $('.bw-select-options').removeClass('show');

                if (!isOpen) {
                    $(this).addClass('open');
                    $optionsContainer.addClass('show');
                }
            });

            // Evento ao Clicar em uma Opção Visual
            $optionsContainer.on('click', '.bw-select-option', function(e) {
                e.stopPropagation();
                var value = $(this).data('value');
                var text = $(this).text();
                
                // Atualiza Texto Visual
                $trigger.find('.bw-select-text').text(text);
                $optionsContainer.find('.bw-select-option').removeClass('selected');
                $(this).addClass('selected');
                
                $trigger.removeClass('open');
                $optionsContainer.removeClass('show');

                // Atualiza Select Original
                if ($select.val() !== value) {
                    $select.val(value).trigger('change'); // Aciona os outros eventos dependentes
                }
            });
        });
    }

    // Função que atualiza o DOM visual baseado nas <option> do select escondido
    function updateCustomSelectDOM($select, $trigger, $optionsContainer) {
        $optionsContainer.empty();
        var selectedText = "";
        
        $select.find('option').each(function() {
            var val = $(this).val();
            var text = $(this).text();
            var isSelected = $(this).is(':selected');

            if (isSelected) {
                selectedText = text;
            }

            var $opt = $('<div class="bw-select-option" data-value="'+val+'">'+text+'</div>');
            if (isSelected) $opt.addClass('selected');
            $optionsContainer.append($opt);
        });

        // Caso a lista de options não tenha dado match (ex: campo dinâmico que esvaziou)
        if (!selectedText && $select.find('option').length > 0) {
            selectedText = $select.find('option:first').text();
            $optionsContainer.find('.bw-select-option:first').addClass('selected');
        }

        $trigger.find('.bw-select-text').text(selectedText);
    }

    // Fecha os custom selects ao clicar fora
    $(document).on('click', function() {
        $('.bw-select-trigger').removeClass('open');
        $('.bw-select-options').removeClass('show');
    });

    // EVENTO DE BUSCA DE PLANOS QUANDO O SELECT DE CONVÊNIO MUDA
    $(document).on('change', 'select.dyn-convenio', function() {
        var form = $(this).closest('form'); 
        var cid = parseInt($(this).val()) || 0; 
        
        var planoSelect = form.find('select.dyn-plano');
        var planoGroup = planoSelect.closest('.bw-input-group');
        
        if (planoGroup.length === 0) planoGroup = form.find('.dyn-plano-group');

        // Captura elementos visuais customizados para atualização
        var planoWrapper = planoSelect.next('.bw-select-wrapper');
        var planoTrigger = planoWrapper.find('.bw-select-trigger');
        var planoOptions = planoWrapper.find('.bw-select-options');

        if (cid > 0) {
            planoGroup.css('display', 'block');
            planoSelect.html('<option value="">Buscando planos...</option>');
            if (planoTrigger.length) updateCustomSelectDOM(planoSelect, planoTrigger, planoOptions);
            
            $.post(gh_vars.ajax_url, { action: 'gh_get_planos', convenio_id: cid }, function(response) {
                var res = response;
                
                if (typeof response === 'string') {
                    try { 
                        var match = response.match(/\{[\s\S]*\}/);
                        if (match) res = JSON.parse(match[0]);
                    } catch(err) {}
                }

                if (res && res.success && res.data && res.data.length > 0) {
                    var opts = '<option value="">Selecione o plano...</option>'; 
                    $.each(res.data, function(i, p) { 
                        opts += '<option value="' + p.id + '">' + p.nome + '</option>'; 
                    });
                    planoSelect.html(opts); 
                    planoGroup.css('display', 'block'); 
                    
                    if (planoTrigger.length) updateCustomSelectDOM(planoSelect, planoTrigger, planoOptions);
                } else { 
                    planoSelect.html('<option value="">Nenhum plano cadastrado</option>'); 
                    planoGroup.css('display', 'none'); 
                    planoSelect.val('');
                    if (planoTrigger.length) updateCustomSelectDOM(planoSelect, planoTrigger, planoOptions);
                }
            }).fail(function() {
                planoSelect.html('<option value="">Erro ao buscar planos</option>'); 
                planoGroup.css('display', 'none');
                planoSelect.val('');
                if (planoTrigger.length) updateCustomSelectDOM(planoSelect, planoTrigger, planoOptions);
            });
        } else { 
            planoSelect.html('<option value="">Selecione um convênio primeiro</option>'); 
            planoGroup.css('display', 'none'); 
            planoSelect.val('');
            if (planoTrigger.length) updateCustomSelectDOM(planoSelect, planoTrigger, planoOptions);
        }
    });

    // =========================================================================

    function resetTurnstile(form) { if(typeof turnstile !== 'undefined') { var tsDiv = form.find('.cf-turnstile'); if(tsDiv.length > 0 && tsDiv.attr('id')) { turnstile.reset(tsDiv.attr('id')); } else { turnstile.reset(); } } }

    $(document).on('submit', '.gh-ajax-login-form', function(e){
        e.preventDefault(); var form = $(this); var btn = form.find('button[type="submit"]'); btn.prop('disabled', true).text('Aguarde...');
        var token = form.find('[name="cf-turnstile-response"]').val();
        $.post(gh_vars.ajax_url, { action: 'gh_login_user', email: form.find('[name="email"]').val(), pass: form.find('[name="pass"]').val(), ts_token: token }, function(res){
            if(res.success) { saveBookingState(); location.reload(); } else { ghAlert(res.data, 'error'); btn.prop('disabled', false).text('Entrar'); resetTurnstile(form); }
        });
    });

    $(document).on('submit', '.gh-ajax-register-form', function(e){
        e.preventDefault(); var form = $(this); var btn = form.find('button[type="submit"]');
        
        // VALIDAÇÃO DE SENHA FORTE NO CLIENTE
        var pass = form.find('[name="pass"]').val();
        var passConf = form.find('[name="pass_confirm"]').val();
        if (pass !== passConf) { ghAlert('As senhas não coincidem.', 'error'); return; }
        if (!/(?=.*[A-Z])/.test(pass) || !/(?=.*\d)/.test(pass) || !/(?=.*[^A-Za-z0-9])/.test(pass)) {
            ghAlert('A senha deve conter no mínimo 1 letra maiúscula, 1 número e 1 caractere especial.', 'error'); return;
        }

        btn.prop('disabled', true).text('Registrando...');
        var token = form.find('[name="cf-turnstile-response"]').val();
        var data = { action: 'gh_register_user', ts_token: token, nome: form.find('[name="nome"]').val(), sobrenome: form.find('[name="sobrenome"]').val(), cpf: form.find('[name="cpf"]').val(), nasc: form.find('[name="nasc"]').val(), email: form.find('[name="email"]').val(), tel: form.find('[name="tel"]').val(), pass: pass, cep: form.find('[name="cep"]').val(), rua: form.find('[name="rua"]').val(), num: form.find('[name="num"]').val(), comp: form.find('[name="comp"]').val(), bairro: form.find('[name="bairro"]').val(), cidade: form.find('[name="cidade"]').val(), uf: form.find('[name="uf"]').val(), convenio_id: form.find('select.dyn-convenio').val(), plano_id: form.find('select.dyn-plano').val(), cart: form.find('[name="cart"]').val(), v_cart: form.find('[name="val_cart"]').val() };
        
        $.post(gh_vars.ajax_url, data, function(res){
            if(res.success) { saveBookingState(); location.reload(); } else { ghAlert(res.data, 'error'); btn.prop('disabled', false).text('Cadastrar e Entrar'); resetTurnstile(form); }
        });
    });

    $(document).on('click', '.bw-ajax-logout', function(e) {
        e.preventDefault(); clearBookingState(); $(this).html('<span class="dashicons dashicons-update spin"></span> Saindo...');
        $.post(gh_vars.ajax_url, { action: 'gh_logout_user' }, function(res) { location.reload(); }).fail(function() { location.reload(); });
    });

    // FINALIZAÇÃO DE AGENDAMENTO
    $('#gh-final-confirm-form').on('submit', function(e){
        e.preventDefault(); 
        if(bookingData.exige_guia == 1) { $('#bw-modal-upload').css('display', 'flex').hide().fadeIn(250); } else { submitFinalBooking(); }
    });

    $('#bw-btn-enviar-upload').on('click', function() {
        if(!$('#gh_guia_file')[0].files[0]) { ghAlert("Por favor, selecione o arquivo do pedido médico.", 'error'); return; }
        $('#bw-modal-upload').fadeOut(200); submitFinalBooking();
    });

    function submitFinalBooking() {
        var form = $('#gh-final-confirm-form'); var btn = form.find('button[type="submit"]'); 
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Processando...');
        
        var formData = new FormData();
        formData.append('action', 'gh_save_booking'); formData.append('nonce', gh_vars.nonce);
        formData.append('slot_id', bookingData.slot_id); formData.append('servico_id', bookingData.servico_id); formData.append('convenio_id', bookingData.convenio_id);
        if(bookingData.plano_id) formData.append('plano_id', bookingData.plano_id);
        
        if(bookingData.update_profile_convenio === 1) {
            formData.append('update_profile_convenio', '1');
        }
        
        if(bookingData.exige_guia == 1 && $('#gh_guia_file')[0].files.length > 0) { 
            var files = $('#gh_guia_file')[0].files;
            for(var i = 0; i < files.length; i++) {
                formData.append('guia_file[]', files[i]);
            }
        }

        $.ajax({
            url: gh_vars.ajax_url, type: 'POST', data: formData, processData: false, contentType: false,
            success: function(res) {
                if(res.success) { 
                    clearBookingState(); 
                    ghAlert('Seu agendamento foi realizado com sucesso!', 'success', function() {
                        window.location.href = gh_vars.url_agendamentos; // Redireciona para a tela Meus Agendamentos
                    }); 
                } 
                else { 
                    ghAlert(res.data, 'error'); // Exibe a mensagem de bloqueio (ex: serviço duplicado)
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-calendar-alt"></span> Finalizar Agendamento'); 
                }
            },
            error: function() { 
                ghAlert('Falha na requisição. Verifique sua conexão e tente novamente.', 'error'); 
                btn.prop('disabled', false).html('<span class="dashicons dashicons-calendar-alt"></span> Finalizar Agendamento'); 
            }
        });
    }

    // =========================================================================
    // MODAIS CUSTOMIZADOS DE ALERTA E CONFIRMAÇÃO (Substitui os nativos do navegador)
    // =========================================================================
    window.ghAlert = function(message, type, callback) {
        $('#gh-custom-alert').remove();
        var icon = type === 'error' ? 'dashicons-warning' : (type === 'success' ? 'dashicons-yes-alt' : 'dashicons-info');
        var color = type === 'error' ? '#ef4444' : (type === 'success' ? '#10b981' : 'var(--bw-color-accent)');
        var html = '<div id="gh-custom-alert" class="bw-modal-overlay" style="display:flex; z-index: 2147483649;"><div class="bw-modal-content" style="max-width: 400px; text-align:center;"><span class="dashicons '+icon+'" style="font-size: 55px; width:55px; height:55px; color: '+color+'; margin-bottom: 20px;"></span><h3 style="margin-top:0;">Aviso do Sistema</h3><p style="font-size: 1.1rem; color: var(--bw-color-text-secondary); margin-bottom:25px;">'+message+'</p><div><button type="button" class="bw-btn-primary" id="gh-btn-alert-ok" style="width:100%; color: #ffffff !important;">OK, Entendi</button></div></div></div>';
        var target = $('.bw-wizard-wrapper').length ? $('.bw-wizard-wrapper') : $('body');
        target.append(html);
        $('#gh-btn-alert-ok').focus().on('click', function() {
            $('#gh-custom-alert').fadeOut(200, function(){ $(this).remove(); });
            if(typeof callback === 'function') callback();
        });
    };

    window.ghConfirm = function(message, callbackYes) {
        $('#gh-custom-confirm').remove();
        var html = '<div id="gh-custom-confirm" class="bw-modal-overlay" style="display:flex; z-index: 2147483649;"><div class="bw-modal-content" style="max-width: 420px; text-align:center;"><span class="dashicons dashicons-warning" style="font-size: 55px; width:55px; height:55px; color: #f59e0b; margin-bottom: 20px;"></span><h3 style="margin-top:0;">Atenção</h3><p style="font-size: 1.1rem; color: var(--bw-color-text-secondary); margin-bottom: 25px;">'+message+'</p><div class="bw-flex-btns" style="justify-content:center;"><button type="button" class="bw-btn-secondary" id="gh-btn-confirm-no" style="color: var(--bw-color-text-primary);">Não, Voltar</button><button type="button" class="bw-btn-primary" id="gh-btn-confirm-yes" style="background:#ef4444; border-color:#ef4444; color: #ffffff !important;">Sim, Confirmar</button></div></div></div>';
        var target = $('.bw-wizard-wrapper').length ? $('.bw-wizard-wrapper') : $('body');
        target.append(html);
        $('#gh-btn-confirm-no').on('click', function() {
            $('#gh-custom-confirm').fadeOut(200, function(){ $(this).remove(); });
        });
        $('#gh-btn-confirm-yes').on('click', function() {
            $('#gh-custom-confirm').fadeOut(200, function(){ $(this).remove(); });
            if(typeof callbackYes === 'function') callbackYes();
        });
    };

    // MODAL DE CONFIRMAÇÃO DE PRESENÇA
    window.ghConfirmPresence = function(callbackYes) {
        $('#gh-custom-confirm-presence').remove();
        var message = 'Confirmar Presença indica que você comparecerá ao agendamento realizado. Ao confirmar agora, não serão realizadas outras tentativas de confirmação de presença.';
        var html = '<div id="gh-custom-confirm-presence" class="bw-modal-overlay" style="display:flex; z-index: 2147483649;"><div class="bw-modal-content" style="max-width: 480px; text-align:center;"><span class="dashicons dashicons-yes-alt" style="font-size: 55px; width:55px; height:55px; color: #10b981; margin-bottom: 20px;"></span><h3 style="margin-top:0;">Confirmar Presença</h3><p style="font-size: 1.05rem; color: var(--bw-color-text-secondary); margin-bottom: 25px;">'+message+'</p><div class="bw-flex-btns" style="justify-content:center;"><button type="button" class="bw-btn-secondary" id="gh-btn-presence-no" style="color: var(--bw-color-text-primary);">Confirmar Depois</button><button type="button" class="bw-btn-primary" id="gh-btn-presence-yes" style="background:#10b981; border-color:#10b981; color:#ffffff !important;">Sim, Confirmar Presença</button></div></div></div>';
        var target = $('.bw-wizard-wrapper').length ? $('.bw-wizard-wrapper') : $('body');
        target.append(html);
        $('#gh-btn-presence-no').on('click', function() { $('#gh-custom-confirm-presence').fadeOut(200, function(){ $(this).remove(); }); });
        $('#gh-btn-presence-yes').on('click', function() { $('#gh-custom-confirm-presence').fadeOut(200, function(){ $(this).remove(); }); if(typeof callbackYes === 'function') callbackYes(); });
    };

    // MODAL INTELIGENTE DE ATUALIZAÇÃO DE CONVÊNIO/PLANO
    // MODAL INTELIGENTE DE ATUALIZAÇÃO DE CONVÊNIO/PLANO
    window.promptConvenioUpdate = function(callback) {
        var currentCov = parseInt(gh_vars.u_convenio_id) || 0;
        var currentPlan = parseInt(gh_vars.u_plano_id) || 0;
        var newCov = parseInt(bookingData.convenio_id) || 0;
        var newPlan = parseInt(bookingData.plano_id) || 0;
        
        var needsModal = false; var title = ''; var msg = '';
        
        // REGRA CORRIGIDA: Só avança direto se os dados escolhidos forem idênticos aos do perfil.
        if(currentCov === newCov && currentPlan === newPlan) {
            bookingData.update_profile_convenio = 0; callback(); return;
        }

        if(currentCov !== newCov) {
            var nomeCov = newCov === 0 ? 'Particular' : bookingData.convenio_nome;
            needsModal = true; 
            title = 'Atualizar Convênio?'; 
            msg = 'Deseja definir seu atendimento principal como <strong>' + nomeCov + '</strong>? Isto vai atualizar o seu cadastro para agendamentos futuros.';
        } else if (currentCov === newCov && currentPlan !== newPlan) {
            needsModal = true; 
            title = 'Atualizar Plano?'; 
            msg = 'Deseja alterar o plano do seu cadastro para agendamentos futuros?';
        }

        if(!needsModal) { bookingData.update_profile_convenio = 0; callback(); return; }

        $('#gh-custom-convenio-modal').remove();
        var html = '<div id="gh-custom-convenio-modal" class="bw-modal-overlay" style="display:flex; z-index: 2147483649;"><div class="bw-modal-content" style="max-width: 450px; text-align:center;"><span class="dashicons dashicons-id" style="font-size: 55px; width:55px; height:55px; color: var(--bw-color-accent); margin-bottom: 20px;"></span><h3 style="margin-top:0;">'+title+'</h3><p style="font-size: 1.05rem; color: var(--bw-color-text-secondary); margin-bottom: 25px;">'+msg+'</p><div class="bw-flex-btns" style="justify-content:center; flex-direction:column; gap:10px;"><button type="button" class="bw-btn-primary" id="gh-btn-cov-yes" style="color: #ffffff !important; width:100%;">Sim, atualizar meu cadastro</button><button type="button" class="bw-btn-secondary" id="gh-btn-cov-no" style="color: var(--bw-color-text-primary); width:100%;">Não, apenas para este agendamento</button></div></div></div>';
        var target = $('.bw-wizard-wrapper').length ? $('.bw-wizard-wrapper') : $('body'); target.append(html);

        $('#gh-btn-cov-no').on('click', function() { 
            bookingData.update_profile_convenio = 0; 
            $('#gh-custom-convenio-modal').fadeOut(200, function(){ $(this).remove(); callback(); }); 
        });
        
        $('#gh-btn-cov-yes').on('click', function() { 
            bookingData.update_profile_convenio = 1; 
            gh_vars.u_convenio_id = newCov; 
            gh_vars.u_plano_id = newPlan; // Atualiza a variável na tela para não perguntar de novo na mesma sessão
            $('#gh-custom-convenio-modal').fadeOut(200, function(){ $(this).remove(); callback(); }); 
        });
    };

    // =========================================================================
    // LÓGICA DA TELA DE MEUS AGENDAMENTOS
    // =========================================================================
    
    // Toggle para mostrar histórico com troca de texto e ícone
    $(document).on('click', '#gh-toggle-passados', function() {
        var btn = $(this);
        var container = $('.gh-agendamentos-passados');
        
        // Remove a animação do fade original e usa slideToggle com callback sincronizado
        container.slideToggle(250, function() {
            if (container.is(':visible')) {
                btn.html('<span class="dashicons dashicons-hidden"></span> Ocultar Agendamentos Anteriores');
                btn.css({'background': 'var(--bw-color-input-bg)', 'color': 'var(--bw-color-text-primary)'});
            } else {
                btn.html('<span class="dashicons dashicons-visibility"></span> Mostrar Agendamentos Anteriores');
                btn.css({'background': 'var(--bw-color-card-bg)', 'color': 'var(--bw-color-text-secondary)'});
            }
        });
    });

    // Confirmar Presença
    $(document).on('click', '.gh-btn-confirmar', function() {
        var btn = $(this);
        var id = btn.data('id');
        
        ghConfirmPresence(function() {
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Aguarde...');
            $.post(gh_vars.ajax_url, {
                action: 'gh_confirmar_presenca',
                nonce: gh_vars.nonce,
                agendamento_id: id
            }, function(res) {
                if(res.success) {
                    ghAlert(res.data, 'success', function() { location.reload(); });
                } else {
                    ghAlert(res.data, 'error');
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Confirmar Presença');
                }
            });
        });
    });

    // Cancelar Agendamento
    $(document).on('click', '.gh-btn-cancelar', function() {
        var btn = $(this);
        var id = btn.data('id');
        
        ghConfirm('Tem certeza que deseja cancelar este agendamento? Esta ação não pode ser desfeita.', function() {
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Cancelando...');

            $.post(gh_vars.ajax_url, {
                action: 'gh_cancelar_agendamento',
                nonce: gh_vars.nonce,
                agendamento_id: id
            }, function(res) {
                if(res.success) {
                    ghAlert(res.data, 'success', function() { location.reload(); });
                } else {
                    ghAlert(res.data, 'error');
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-no"></span> Cancelar Agendamento');
                }
            });
        });
    });
});