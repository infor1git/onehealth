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
    var designLogoUploader;
    $('#gh_upload_design_logo_button').on('click', function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) return;
        if (designLogoUploader) { designLogoUploader.open(); return; }

        designLogoUploader = wp.media({
            title: 'Escolher Logo do Wizard',
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

});