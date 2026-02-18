jQuery(document).ready(function($) {
    
    /* ----------------------------------------------------------
     * 1. Lógica do CEP (Mantida)
     * ---------------------------------------------------------- */
    $('#gh_cep').on('blur', function() {
        var cep = $(this).val().replace(/\D/g, '');
        if (cep !== "") {
            var validacep = /^[0-9]{8}$/;
            if(validacep.test(cep)) {
                $("#gh_logradouro").val("Buscando...");
                $.getJSON("https://viacep.com.br/ws/"+ cep +"/json/?callback=?", function(dados) {
                    if (!("erro" in dados)) {
                        $("#gh_logradouro").val(dados.logradouro);
                        $("#gh_bairro").val(dados.bairro);
                        $("#gh_cidade").val(dados.localidade);
                        $("#gh_estado").val(dados.uf);
                        $("#gh_numero").focus();
                    } else {
                        alert("CEP não encontrado.");
                        $("#gh_logradouro").val("");
                    }
                });
            } else {
                alert("Formato de CEP inválido.");
            }
        }
    });

    /* ----------------------------------------------------------
     * 2. Upload de Imagem (MÉDICO)
     * ---------------------------------------------------------- */
    var medicoUploader;
    $('#gh_upload_image_button').on('click', function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) return;
        if (medicoUploader) { medicoUploader.open(); return; }

        medicoUploader = wp.media({
            title: 'Escolher Foto do Médico',
            button: { text: 'Usar esta foto' },
            multiple: false
        });

        medicoUploader.on('select', function() {
            var attachment = medicoUploader.state().get('selection').first().toJSON();
            $('#gh_foto_url').val(attachment.url);
            $('#gh_image_preview').attr('src', attachment.url);
            $('#gh_image_preview_container').show();
        });
        medicoUploader.open();
    });

    $('#gh_remove_image_button').on('click', function(e){
        e.preventDefault();
        $('#gh_foto_url').val('');
        $('#gh_image_preview').attr('src', '');
        $('#gh_image_preview_container').hide();
    });

    /* ----------------------------------------------------------
     * 3. Upload de Imagem (CONVÊNIO - LOGO) - NOVO
     * ---------------------------------------------------------- */
    var logoUploader;
    $('#gh_upload_logo_button').on('click', function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) return;
        if (logoUploader) { logoUploader.open(); return; }

        logoUploader = wp.media({
            title: 'Escolher Logomarca do Convênio',
            button: { text: 'Usar esta logo' },
            multiple: false
        });

        logoUploader.on('select', function() {
            var attachment = logoUploader.state().get('selection').first().toJSON();
            $('#gh_logo_url').val(attachment.url);
            $('#gh_logo_preview').attr('src', attachment.url);
            $('#gh_logo_preview_container').show();
        });
        logoUploader.open();
    });

    $('#gh_remove_logo_button').on('click', function(e){
        e.preventDefault();
        $('#gh_logo_url').val('');
        $('#gh_logo_preview').attr('src', '');
        $('#gh_logo_preview_container').hide();
    });

    /* ----------------------------------------------------------
     * 4. Marcar / Desmarcar Todas as Especialidades - NOVO
     * ---------------------------------------------------------- */
    $('#gh_select_all_esp').on('click', function(e) {
        e.preventDefault();
        $('.gh-esp-checkbox').prop('checked', true);
    });

    $('#gh_deselect_all_esp').on('click', function(e) {
        e.preventDefault();
        $('.gh-esp-checkbox').prop('checked', false);
    });

    /* ----------------------------------------------------------
     * 5. Upload de Logo do Wizard (Aba Design) - NOVO
     * ---------------------------------------------------------- */
    // --- UPLOAD DE LOGO & IMAGEM ---
    var designLogoUploader;
    
    // Upload Logo do Wizard
    $('#gh_upload_design_logo_button').on('click', function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) return;
        if (designLogoUploader) { designLogoUploader.open(); return; }

        designLogoUploader = wp.media({
            title: 'Escolher Logo',
            button: { text: 'Usar esta logo' },
            multiple: false
        });

        designLogoUploader.on('select', function() {
            var attachment = designLogoUploader.state().get('selection').first().toJSON();
            $('#gh_design_logo_url').val(attachment.url);
            $('#gh_design_logo_preview').attr('src', attachment.url);
            $('#gh_design_logo_preview_container').show();
        });
        designLogoUploader.open();
    });

    $('#gh_remove_design_logo_button').on('click', function(e){
        e.preventDefault();
        $('#gh_design_logo_url').val('');
        $('#gh_design_logo_preview').attr('src', '');
        $('#gh_design_logo_preview_container').hide();
    });

    // Upload Foto do Médico
    var medicoImageUploader;
    $('#gh_upload_image_button').on('click', function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) return;
        if (medicoImageUploader) { medicoImageUploader.open(); return; }

        medicoImageUploader = wp.media({
            title: 'Escolher Foto do Médico',
            button: { text: 'Usar esta foto' },
            multiple: false
        });

        medicoImageUploader.on('select', function() {
            var attachment = medicoImageUploader.state().get('selection').first().toJSON();
            $('#gh_foto_url').val(attachment.url);
            $('#gh_image_preview').attr('src', attachment.url);
            $('#gh_image_preview_container').show();
        });
        medicoImageUploader.open();
    });

    $('#gh_remove_image_button').on('click', function(e){
        e.preventDefault();
        $('#gh_foto_url').val('');
        $('#gh_image_preview').attr('src', '');
        $('#gh_image_preview_container').hide();
    });


    // --- SELETOR DE ÍCONES (CORRIGIDO E ROBUSTO) ---
    // Usamos 'body' delegate para garantir que funcione mesmo em elementos dinâmicos
    $('body').on('click', '#gh_open_icon_picker', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Previne conflitos de evento

        // Lista de ícones curada para saúde/admin
        var icons = [
            'dashicons-heart', 'dashicons-clipboard', 'dashicons-plus-alt', 'dashicons-visibility', 
            'dashicons-businessman', 'dashicons-groups', 'dashicons-calendar-alt', 'dashicons-clock',
            'dashicons-location-alt', 'dashicons-shield', 'dashicons-sos', 'dashicons-admin-generic',
            'dashicons-awards', 'dashicons-chart-pie', 'dashicons-store', 'dashicons-buddicons-activity',
            'dashicons-car', 'dashicons-bell', 'dashicons-camera', 'dashicons-format-image',
            'dashicons-pressthis', 'dashicons-info', 'dashicons-nametag', 'dashicons-universal-access',
            'dashicons-id', 'dashicons-edit', 'dashicons-trash', 'dashicons-star-filled'
        ];

        // Remove modal anterior se existir
        $('#gh_icon_modal').remove();

        var modalHtml = '<div id="gh_icon_modal" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:999999;display:flex;align-items:center;justify-content:center;">';
        modalHtml += '<div style="background:#fff;padding:20px;border-radius:8px;width:400px;max-width:95%;box-shadow:0 10px 25px rgba(0,0,0,0.5);">';
        modalHtml += '<h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Selecione um Ícone</h3>';
        modalHtml += '<div style="display:flex;flex-wrap:wrap;gap:10px;max-height:300px;overflow-y:auto;padding:10px 0;">';
        
        icons.forEach(function(icon) {
            modalHtml += '<div class="gh-icon-option" data-icon="'+icon+'" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;border:1px solid #ddd;border-radius:4px;cursor:pointer;font-size:20px;background:#f9f9f9;">';
            modalHtml += '<span class="dashicons '+icon+'"></span>';
            modalHtml += '</div>';
        });

        modalHtml += '</div>';
        modalHtml += '<div style="text-align:right; border-top:1px solid #eee; padding-top:10px;"><button type="button" class="button" id="gh_close_icon_modal">Cancelar</button></div>';
        modalHtml += '</div></div>';

        $('body').append(modalHtml);
    });

    $('body').on('click', '.gh-icon-option', function() {
        var icon = $(this).data('icon');
        $('#gh_icone_input').val(icon);
        // Atualiza a visualização no admin (remove classes anteriores e adiciona a nova)
        var previewSpan = $('#gh_icone_preview span');
        previewSpan.attr('class', 'dashicons ' + icon); 
        $('#gh_icon_modal').remove();
    });

    $('body').on('click', '#gh_close_icon_modal', function(e) {
        e.preventDefault();
        $('#gh_icon_modal').remove();
    });


    // --- HELPERS DE CHECKBOX (Marcar/Desmarcar Todos) ---
    // Usado na aba de Serviços do Médico e no cadastro de Serviços
    $('.gh-select-all-btn').on('click', function(e) {
        e.preventDefault();
        var targetGroup = $(this).data('group'); // Ex: 'esp-12'
        $('.gh-check-' + targetGroup).prop('checked', true);
    });

    $('.gh-deselect-all-btn').on('click', function(e) {
        e.preventDefault();
        var targetGroup = $(this).data('group');
        $('.gh-check-' + targetGroup).prop('checked', false);
    });
});