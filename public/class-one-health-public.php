<?php

class One_Health_Public
{

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_filter('body_class', array($this, 'add_fullscreen_body_class'));
        add_action('after_setup_theme', array($this, 'hide_admin_bar_for_subscribers'));

        add_action('wp_ajax_gh_get_unidades', array($this, 'ajax_get_unidades'));
        add_action('wp_ajax_gh_get_servicos', array($this, 'ajax_get_servicos'));
        add_action('wp_ajax_gh_get_especialidades', array($this, 'ajax_get_especialidades'));
        add_action('wp_ajax_gh_get_convenios', array($this, 'ajax_get_convenios'));
        add_action('wp_ajax_gh_get_planos', array($this, 'ajax_get_planos'));
        add_action('wp_ajax_nopriv_gh_get_planos', array($this, 'ajax_get_planos'));
        add_action('wp_ajax_gh_get_medicos', array($this, 'ajax_get_medicos'));
        add_action('wp_ajax_gh_get_available_dates', array($this, 'ajax_get_available_dates'));
        add_action('wp_ajax_gh_get_slots', array($this, 'ajax_get_slots'));
        add_action('wp_ajax_gh_save_booking', array($this, 'ajax_save_booking'));

        // Auth Hooks (Acesso público)
        add_action('wp_ajax_gh_login_user', array($this, 'ajax_login_user'));
        add_action('wp_ajax_nopriv_gh_login_user', array($this, 'ajax_login_user'));
        add_action('wp_ajax_gh_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_nopriv_gh_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_gh_logout_user', array($this, 'ajax_logout_user'));
        add_action('wp_ajax_nopriv_gh_logout_user', array($this, 'ajax_logout_user'));
        add_filter('the_content', array($this, 'auto_inject_shortcodes'));
    }

    public function hide_admin_bar_for_subscribers()
    {
        if (!current_user_can('edit_posts')) {
            show_admin_bar(false);
        }
    }

    public function add_fullscreen_body_class($classes)
    {
        $current_id = 0;

        // Garante que pega o ID correto da página atual, independentemente do tema
        if (is_page()) {
            $current_id = get_queried_object_id();
        }

        // Pega as opções salvas no Painel > Design
        $p_agd    = (int) get_option('gh_page_agendamento');
        $p_meus   = (int) get_option('gh_page_agendamentos');
        $p_perfil = (int) get_option('gh_page_perfil');

        // Flag de verificação agressiva
        $is_target_page = (
            ($p_agd > 0 && $current_id === $p_agd) ||
            ($p_meus > 0 && $current_id === $p_meus) ||
            ($p_perfil > 0 && $current_id === $p_perfil)
        );

        global $post;
        $has_shortcode = false;
        if (is_a($post, 'WP_Post')) {
            if (
                has_shortcode($post->post_content, 'one_health_agendamento') ||
                has_shortcode($post->post_content, 'one_health_meus_agendamentos')
            ) {
                $has_shortcode = true;
            }
        }

        // Se for a página configurada OU tiver o shortcode
        if ($is_target_page || $has_shortcode) {
            if (is_array($classes)) {
                $classes[] = 'bw-booking-fullscreen';
            } else {
                // Prevenção caso algum tema passe a classe como string
                $classes .= ' bw-booking-fullscreen';
            }
        }

        return $classes;
    }

    // -------------------------------------------------------------
    // INJEÇÃO AUTOMÁTICA DE SHORTCODES
    // -------------------------------------------------------------
    public function auto_inject_shortcodes($content)
    {
        // Assegura que estamos na consulta principal e dentro do loop
        if (is_main_query() && in_the_loop() && is_page()) {
            $current_id = get_the_ID();
            $p_agd      = (int) get_option('gh_page_agendamento');
            $p_meus     = (int) get_option('gh_page_agendamentos');

            // Limpa o conteúdo do tema e força a renderização APENAS do nosso sistema
            if ($p_agd > 0 && $current_id === $p_agd) {
                return '[one_health_agendamento]';
            }
            if ($p_meus > 0 && $current_id === $p_meus) {
                return '[one_health_meus_agendamentos]';
            }
        }
        return $content;
    }

    public function enqueue_scripts()
    {
        $ts_key = get_option('gh_turnstile_sitekey', '');
        if ($ts_key) {
            wp_enqueue_script('turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true);
        }

        wp_enqueue_style('dashicons');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/one-health-public.css', array(), $this->version, 'all');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/one-health-public.js', array('jquery'), $this->version, false);

        global $wpdb;
        $user_convenio_id = 0;
        $user_plano_id = 0;
        $user_convenio_nome = '';
        $user_plano_nome = '';
        $is_logged = is_user_logged_in();

        if ($is_logged) {
            $user_id = get_current_user_id();
            $u_data = $wpdb->get_row($wpdb->prepare("SELECT convenio_id, plano_id FROM {$wpdb->prefix}gh_usuarios WHERE user_id = %d", $user_id));
            if ($u_data && $u_data->convenio_id) {
                $user_convenio_id = $u_data->convenio_id;
                $user_plano_id = $u_data->plano_id;
                $user_convenio_nome = $wpdb->get_var($wpdb->prepare("SELECT nome FROM {$wpdb->prefix}gh_convenios WHERE id=%d", $user_convenio_id));
                if ($user_plano_id) $user_plano_nome = $wpdb->get_var($wpdb->prepare("SELECT nome FROM {$wpdb->prefix}gh_planos WHERE id=%d", $user_plano_id));
            }
        }

        $page_agendamentos = get_option('gh_page_agendamentos', '');

        wp_localize_script($this->plugin_name, 'gh_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gh_public_nonce'),
            'is_logged_in' => $is_logged,
            'u_convenio_id' => $user_convenio_id,
            'u_convenio_nome' => $user_convenio_nome,
            'u_plano_id' => $user_plano_id,
            'u_plano_nome' => $user_plano_nome,
            'url_agendamentos' => $page_agendamentos ? get_permalink($page_agendamentos) : home_url()
        ));
    }

    public function render_shortcode($atts)
    {
        $tema_ativo = get_option('gh_theme', 'bw-theme-branco');
        $cor_destaque = get_option('gh_accent_color', '');

        ob_start();
        if (!empty($cor_destaque)) {
            echo '<style> #gh-booking-wizard { --bw-color-accent: ' . esc_attr($cor_destaque) . ' !important; } </style>';
        }

        echo '<div class="bw-wizard-wrapper ' . esc_attr($tema_ativo) . '" id="gh-booking-wizard">';

        if (! is_user_logged_in()) {
            $this->render_auth_screen();
        } else {
            $this->render_booking_wizard();
        }

        echo '</div>';
        return ob_get_clean();
    }

    private function render_auth_screen()
    {
        global $wpdb;
        $logo_url = get_option('gh_wizard_logo', '');
        $ts_sitekey = get_option('gh_turnstile_sitekey', '');
        $ts_theme = get_option('gh_turnstile_theme', 'auto');
        $convenios = $wpdb->get_results("SELECT id, nome FROM {$wpdb->prefix}gh_convenios WHERE is_active = 1 ORDER BY nome ASC");
?>
        <div class="bw-wizard-content-inner">
            <div class="bw-wizard-header" style="justify-content: center; text-align: center; border:none; box-shadow:none; background:transparent;">
                <?php if ($logo_url): ?><img src="<?php echo esc_url($logo_url); ?>" alt="Logomarca" class="bw-wizard-logo" style="margin:auto; display:block;"><?php else: ?><h2 class="bw-wizard-title" style="margin:0;">Agendamento Online</h2><?php endif; ?>
            </div>

            <div style="text-align:center; margin-bottom: 30px;">
                <h3 style="font-size: 1.4rem; color:var(--bw-color-text-primary);">Acesse sua conta para agendar</h3>
                <p style="color:var(--bw-color-text-secondary);">Para sua segurança e praticidade, precisamos te identificar.</p>
            </div>

            <div class="bw-auth-tabs" style="justify-content: center;">
                <button type="button" class="bw-auth-tab active" data-target="gh_login_form">Já tenho conta</button>
                <button type="button" class="bw-auth-tab" data-target="gh_register_form">Criar Conta</button>
            </div>

            <form id="gh_login_form" class="bw-auth-form gh-ajax-login-form active" style="max-width: 450px; margin: auto;">
                <div class="bw-input-group"><label>E-mail:</label><input type="email" name="email" class="bw-input" required></div>
                <div class="bw-input-group"><label>Senha:</label><input type="password" name="pass" class="bw-input" required></div>
                <?php if ($ts_sitekey): ?><div class="cf-turnstile-container">
                        <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($ts_sitekey); ?>" data-theme="<?php echo esc_attr($ts_theme); ?>"></div>
                    </div><?php endif; ?>
                <button type="submit" class="bw-btn-primary" style="margin-top:15px;">Entrar</button>
            </form>

            <form id="gh_register_form" class="bw-auth-form gh-ajax-register-form" style="display:none;">
                <div class="bw-grid-2-cols">
                    <div class="bw-input-group"><label>Nome <span style="color:red">*</span></label><input type="text" name="nome" class="bw-input" required></div>
                    <div class="bw-input-group"><label>Sobrenome <span style="color:red">*</span></label><input type="text" name="sobrenome" class="bw-input" required></div>
                    <div class="bw-input-group"><label>CPF <span style="color:red">*</span></label><input type="tel" name="cpf" class="bw-input" required placeholder="000.000.000-00"></div>
                    <div class="bw-input-group"><label>Nascimento <span style="color:red">*</span></label><input type="tel" name="nasc" class="bw-input" required placeholder="DD/MM/AAAA"></div>
                    <div class="bw-input-group"><label>E-mail <span style="color:red">*</span></label><input type="email" name="email" class="bw-input" required></div>
                    <div class="bw-input-group"><label>Celular/WhatsApp <span style="color:red">*</span></label><input type="tel" name="tel" class="bw-input" required placeholder="(00) 00000-0000"></div>

                    <div class="bw-input-group"><label>Crie uma Senha <span style="color:red">*</span></label><input type="password" name="pass" class="bw-input" required></div>
                    <div class="bw-input-group"><label>Confirme a Senha <span style="color:red">*</span></label><input type="password" name="pass_confirm" class="bw-input" required></div>
                    <div style="grid-column: 1 / -1; font-size: 0.85rem; margin-top:-5px; color:var(--bw-color-text-secondary);">A senha deve conter no mínimo: 1 letra maiúscula, 1 número e 1 caractere especial.</div>
                </div>

                <h4 style="margin-top:25px; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom:10px;">Endereço</h4>
                <div class="bw-grid-2-cols">
                    <div class="bw-input-group"><label>CEP <span style="color:red">*</span></label><input type="tel" name="cep" class="bw-input viacep-input" required placeholder="00000-000"></div>
                    <div class="bw-input-group"><label>Logradouro</label><input type="text" name="rua" class="bw-input" readonly style="background:rgba(0,0,0,0.05);"></div>
                    <div class="bw-input-group"><label>Número <span style="color:red">*</span></label><input type="text" name="num" class="bw-input" required></div>
                    <div class="bw-input-group"><label>Complemento</label><input type="text" name="comp" class="bw-input"></div>
                    <div class="bw-input-group"><label>Bairro</label><input type="text" name="bairro" class="bw-input" readonly style="background:rgba(0,0,0,0.05);"></div>
                    <div class="bw-input-group"><label>Cidade</label><input type="text" name="cidade" class="bw-input" readonly style="background:rgba(0,0,0,0.05);"></div>
                </div>
                <input type="hidden" name="uf">

                <h4 style="margin-top:25px; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom:10px;">Dados do Convênio (Opcional)</h4>
                <div class="bw-grid-2-cols">
                    <div class="bw-input-group">
                        <label>Convênio</label>
                        <select name="convenio_id" class="bw-input dyn-convenio">
                            <option value="">Particular / Nenhum</option>
                            <?php foreach ($convenios as $c) echo "<option value='{$c->id}'>{$c->nome}</option>"; ?>
                        </select>
                    </div>
                    <div class="bw-input-group dyn-plano-group" style="display:none;">
                        <label>Plano</label><select name="plano_id" class="bw-input dyn-plano">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div class="bw-input-group"><label>Nº da Carteirinha</label><input type="text" name="cart" class="bw-input"></div>
                    <div class="bw-input-group"><label>Validade da Carteirinha</label><input type="tel" name="val_cart" class="bw-input"></div>
                </div>

                <?php if ($ts_sitekey): ?><div class="cf-turnstile-container">
                        <div class="cf-turnstile" data-sitekey="<?php echo esc_attr($ts_sitekey); ?>" data-theme="<?php echo esc_attr($ts_theme); ?>"></div>
                    </div><?php endif; ?>
                <button type="submit" class="bw-btn-primary" style="margin-top:15px; width:100%;">Cadastrar e Entrar</button>
            </form>
        </div>
    <?php
    }

    private function render_booking_wizard()
    {
        $logo_url = get_option('gh_wizard_logo', '');
        $ts_sitekey = get_option('gh_turnstile_sitekey', '');
        $ts_theme = get_option('gh_turnstile_theme', 'auto');

        $page_perfil = get_option('gh_page_perfil', '');
        $url_perfil = $page_perfil ? get_permalink($page_perfil) : '#';
        $page_agendamentos = get_option('gh_page_agendamentos', '');
        $url_agendamentos = $page_agendamentos ? get_permalink($page_agendamentos) : '#';
        $current_user = wp_get_current_user();
    ?>
        <div class="bw-wizard-content-inner">
            <div class="bw-wizard-header">
                <div class="bw-logo-container">
                    <?php if ($logo_url): ?><img src="<?php echo esc_url($logo_url); ?>" alt="Logomarca" class="bw-wizard-logo"><?php else: ?><h2 class="bw-wizard-title" style="margin:0;">Agendamento</h2><?php endif; ?>
                </div>
                <div class="bw-user-header-menu">
                    <div class="bw-logged-in-ui">
                        <div class="bw-user-avatar"><span class="dashicons dashicons-admin-users"></span></div>
                        <div class="bw-user-details">
                            <span class="bw-user-name">Olá, <span><?php echo esc_html($current_user->display_name); ?></span></span>
                            <div class="bw-user-links">
                                <a href="<?php echo esc_url($url_agendamentos); ?>"><span class="dashicons dashicons-calendar-alt"></span> Meu Agendamentos</a>
                                <a href="<?php echo esc_url($url_perfil); ?>"><span class="dashicons dashicons-id-alt"></span> Perfil</a>
                                <a href="#" class="bw-ajax-logout"><span class="dashicons dashicons-external"></span> Sair</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bw-progress-bar-container">
                <div class="bw-progress-bar">
                    <div class="bw-step active" data-step="1"><span class="bw-step-num">1</span> Unidade</div>
                    <div class="bw-step" data-step="2"><span class="bw-step-num">2</span> Serviço</div>
                    <div class="bw-step" data-step="3"><span class="bw-step-num">3</span> Especialidade</div>
                    <div class="bw-step" data-step="4"><span class="bw-step-num">4</span> Convênio</div>
                    <div class="bw-step" data-step="5"><span class="bw-step-num">5</span> Profissional</div>
                    <div class="bw-step" data-step="6"><span class="bw-step-num">6</span> Horário</div>
                    <div class="bw-step" data-step="7"><span class="bw-step-num">7</span> Confirmação</div>
                </div>
            </div>

            <div class="bw-wizard-content">
                <div class="bw-step-content active" id="step-1">
                    <h3>Onde você deseja ser atendido?</h3>
                    <div id="gh-unidades-list" class="bw-grid-options">
                        <p style="opacity:0.7;">Carregando...</p>
                    </div>
                </div>
                <div class="bw-step-content" id="step-2">
                    <h3>Qual serviço deseja agendar?</h3>
                    <div id="gh-servicos-list" class="bw-grid-options"></div>
                </div>
                <div class="bw-step-content" id="step-3">
                    <h3 id="bw-step-3-title">Qual a especialidade?</h3>
                    <div id="gh-especialidades-list" class="bw-grid-options"></div>
                </div>
                <div class="bw-step-content" id="step-4">
                    <h3>Como deseja ser atendido?</h3>
                    <div id="gh-saved-convenio-area" class="bw-highlight-box" style="display:none;">
                        <h4 style="margin-top:0;">Identificamos seu convênio cadastrado:</h4>
                        <p id="gh-saved-convenio-name" style="font-size:1.1rem; font-weight:bold;"></p>
                        <div class="bw-flex-btns">
                            <button type="button" class="bw-btn-primary" onclick="useSavedConvenio()">Usar este</button>
                            <button type="button" class="bw-btn-secondary" onclick="showAllConvenios()">Alterar</button>
                        </div>
                    </div>
                    <div id="gh-convenios-list-container">
                        <div id="gh-convenios-list" class="bw-grid-options"></div>
                    </div>
                </div>
                <div class="bw-step-content" id="step-5">
                    <h3>Escolha o Profissional</h3>
                    <div id="gh-medicos-list" class="bw-grid-options"></div>
                    <div style="margin-top:20px; text-align:center;">
                        <button type="button" id="gh-skip-medico" class="bw-btn-secondary" onclick="skipMedico()"><span class="dashicons dashicons-groups"></span> Mostrar qualquer profissional</button>
                    </div>
                </div>
                <div class="bw-step-content" id="step-6">
                    <h3>Data e Horário</h3>
                    <div class="bw-calendar-layout">
                        <div class="bw-calendar-col">
                            <div class="bw-cal-header"><button type="button" class="bw-cal-nav" id="bw-cal-prev"><span class="dashicons dashicons-arrow-left-alt2"></span></button><strong id="bw-cal-month-name">Mês</strong><button type="button" class="bw-cal-nav" id="bw-cal-next"><span class="dashicons dashicons-arrow-right-alt2"></span></button></div>
                            <div class="bw-cal-days-header"><span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span></div>
                            <div id="bw-cal-grid" class="bw-cal-grid"></div>
                        </div>
                        <div class="bw-slots-col">
                            <div id="gh-slots-list" class="bw-slots-grid">
                                <p style="opacity:0.7;">Selecione um dia no calendário.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bw-step-content" id="step-7">
                    <h3>Confirmação do Agendamento</h3>
                    <div class="bw-summary">
                        <p><strong>Paciente:</strong> <?php echo esc_html($current_user->display_name); ?></p>
                        <hr style="border:0; border-top:1px solid rgba(0,0,0,0.1); margin:10px 0;">
                        <p><strong>Unidade:</strong> <span id="sum-unidade"></span></p>
                        <p><strong>Serviço:</strong> <span id="sum-servico"></span> (<span id="sum-especialidade"></span>)</p>
                        <p><strong>Convênio:</strong> <span id="sum-convenio"></span></p>
                        <p><strong>Profissional:</strong> <span id="sum-medico"></span></p>
                        <p><strong>Data:</strong> <span id="sum-data"></span></p>
                    </div>
                    <form id="gh-final-confirm-form">
                        <button type="submit" class="bw-btn-primary" style="width:100%;"><span class="dashicons dashicons-calendar-alt"></span> Finalizar Agendamento</button>
                    </form>
                </div>

                <div class="bw-navigation-footer" style="margin-top: 30px; border-top: 1px solid var(--bw-color-card-border); padding-top: 15px;">
                    <button type="button" class="bw-btn-back" onclick="gh_prev_step()"><span class="dashicons dashicons-arrow-left-alt2"></span> Voltar</button>
                </div>
            </div>
        </div>

        <div id="bw-modal-preparo" class="bw-modal-overlay" style="display: none;">
            <div class="bw-modal-content">
                <h3 class="bw-modal-title"><span class="dashicons dashicons-clipboard"></span> Instruções Importantes</h3>
                <div id="bw-modal-preparo-text"></div>
                <div class="bw-alert-box"><span class="dashicons dashicons-info"></span> Estas instruções serão enviadas para o seu e-mail.</div>
                <div class="bw-flex-btns" style="justify-content: flex-end; margin-top:20px;"><button type="button" class="bw-btn-secondary bw-close-modal">Cancelar</button><button type="button" class="bw-btn-primary" id="bw-btn-continuar-modal">Ciente, Continuar</button></div>
            </div>
        </div>
        <div id="bw-modal-planos" class="bw-modal-overlay" style="display: none;">
            <div class="bw-modal-content" style="max-width: 450px !important;">
                <h3 class="bw-modal-title"><span class="dashicons dashicons-clipboard"></span> Selecione o Plano</h3>
                <div id="bw-modal-planos-list" style="display:flex; flex-direction:column; gap:8px; max-height:45vh; overflow-y:auto; padding-right:10px; margin-top:15px;"></div>
                <div style="margin-top:20px; text-align:right; border-top:1px solid rgba(0,0,0,0.1); padding-top:15px;"><button type="button" class="bw-btn-secondary bw-close-modal">Cancelar</button></div>
            </div>
        </div>
        <div id="bw-modal-upload" class="bw-modal-overlay" style="display: none;">
            <div class="bw-modal-content" style="max-width: 500px !important;">
                <h3 class="bw-modal-title"><span class="dashicons dashicons-media-document"></span> Anexar Pedido Médico</h3>
                <p class="description">Este convênio exige o envio da guia médica (Você pode selecionar vários arquivos).</p>
                <input type="file" id="gh_guia_file" accept=".pdf,.jpg,.jpeg,.png" multiple style="margin:20px 0; display:block; width:100%;">
                <div class="bw-flex-btns" style="justify-content: flex-end;"><button type="button" class="bw-btn-secondary bw-close-modal">Cancelar</button><button type="button" class="bw-btn-primary" id="bw-btn-enviar-upload">Enviar e Concluir</button></div>
            </div>
        </div>
    <?php
    }

    private function verify_turnstile($token)
    {
        $secret = get_option('gh_turnstile_secret', '');
        if (empty($secret)) return true;
        $verify = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array('body' => array('secret' => $secret, 'response' => $token)));
        if (is_wp_error($verify)) return false;
        $body = json_decode(wp_remote_retrieve_body($verify));
        return $body->success;
    }

    public function ajax_logout_user()
    {
        wp_logout();
        wp_send_json_success();
    }

    public function ajax_login_user()
    {
        if (!$this->verify_turnstile($_POST['ts_token'])) wp_send_json_error('Falha de segurança (Anti-Spam).');
        $user = wp_signon(array('user_login' => sanitize_text_field($_POST['email']), 'user_password' => $_POST['pass'], 'remember' => true), false);
        if (is_wp_error($user)) wp_send_json_error('Credenciais inválidas.');
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        wp_send_json_success();
    }

    public function ajax_register_user()
    {
        if (!$this->verify_turnstile($_POST['ts_token'])) wp_send_json_error('Falha de segurança.');
        $email = sanitize_email($_POST['email']);
        if (email_exists($email)) wp_send_json_error('Este e-mail já está cadastrado.');
        $user_id = wp_create_user($email, $_POST['pass'], $email);
        if (is_wp_error($user_id)) wp_send_json_error('Erro ao criar usuário.');
        $display_name = sanitize_text_field($_POST['nome']) . ' ' . sanitize_text_field($_POST['sobrenome']);
        wp_update_user(array('ID' => $user_id, 'first_name' => sanitize_text_field($_POST['nome']), 'last_name' => sanitize_text_field($_POST['sobrenome']), 'display_name' => $display_name));

        global $wpdb;
        $conv_id = !empty($_POST['convenio_id']) ? intval($_POST['convenio_id']) : null;
        $plano_id = !empty($_POST['plano_id']) ? intval($_POST['plano_id']) : null;

        $nasc = sanitize_text_field($_POST['nasc']);
        if (strpos($nasc, '/') !== false) {
            $parts = explode('/', $nasc);
            if (count($parts) == 3) {
                $nasc = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            }
        }

        $wpdb->insert($wpdb->prefix . 'gh_usuarios', array('user_id' => $user_id, 'cpf' => sanitize_text_field($_POST['cpf']), 'data_nascimento' => $nasc, 'cep' => sanitize_text_field($_POST['cep']), 'logradouro' => sanitize_text_field($_POST['rua']), 'numero' => sanitize_text_field($_POST['num']), 'complemento' => sanitize_text_field($_POST['comp']), 'bairro' => sanitize_text_field($_POST['bairro']), 'cidade' => sanitize_text_field($_POST['cidade']), 'estado' => sanitize_text_field($_POST['uf']), 'convenio_id' => $conv_id, 'plano_id' => $plano_id, 'carteirinha' => sanitize_text_field($_POST['cart']), 'validade_carteirinha' => !empty($_POST['v_cart']) ? sanitize_text_field($_POST['v_cart']) : null));
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_send_json_success();
    }

    public function ajax_get_unidades()
    {
        $path = plugin_dir_path(__FILE__) . '../includes/models/class-gh-unidade.php';
        if (file_exists($path)) {
            require_once $path;
            $model = new GH_Unidade();
            wp_send_json_success($model->get_all());
        }
        wp_send_json_error();
    }
    public function ajax_get_servicos()
    {
        global $wpdb;
        $unidade_id = intval($_POST['unidade_id']);
        $limite_tempo = date('Y-m-d H:i:s', strtotime('+4 hours', current_time('timestamp')));
        $sql = "SELECT DISTINCT s.id, s.nome, s.icone, s.preparo_html, s.tipo FROM {$wpdb->prefix}gh_servicos s INNER JOIN {$wpdb->prefix}gh_servico_especialidade se ON s.id = se.servico_id INNER JOIN {$wpdb->prefix}gh_slots sl ON se.especialidade_id = sl.especialidade_id LEFT JOIN {$wpdb->prefix}gh_agendamentos a ON sl.id = a.slot_id AND a.status = 'A' WHERE s.is_active = 1 AND sl.unidade_id = %d AND sl.status = 'disponivel' AND a.id IS NULL AND sl.data_hora > %s AND (sl.tipos_servico = '' OR sl.tipos_servico IS NULL OR FIND_IN_SET(s.tipo, sl.tipos_servico)) AND ( NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms WHERE ms.medico_id = sl.medico_id AND ms.especialidade_id = sl.especialidade_id) OR EXISTS (SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms WHERE ms.medico_id = sl.medico_id AND ms.especialidade_id = sl.especialidade_id AND ms.servico_id = s.id) ) ORDER BY s.nome ASC";
        wp_send_json_success($wpdb->get_results($wpdb->prepare($sql, $unidade_id, $limite_tempo)));
    }
    public function ajax_get_especialidades()
    {
        global $wpdb;
        $unidade_id = intval($_POST['unidade_id']);
        $servico_id = intval($_POST['servico_id']);
        $limite_tempo = date('Y-m-d H:i:s', strtotime('+4 hours', current_time('timestamp')));
        $tipo_servico = $wpdb->get_var($wpdb->prepare("SELECT tipo FROM {$wpdb->prefix}gh_servicos WHERE id = %d", $servico_id));
        $sql = "SELECT DISTINCT e.id, e.nome, e.icone FROM {$wpdb->prefix}gh_especialidades e INNER JOIN {$wpdb->prefix}gh_servico_especialidade se ON e.id = se.especialidade_id INNER JOIN {$wpdb->prefix}gh_slots sl ON e.id = sl.especialidade_id LEFT JOIN {$wpdb->prefix}gh_agendamentos a ON sl.id = a.slot_id AND a.status = 'A' WHERE e.is_active = 1 AND se.servico_id = %d AND sl.unidade_id = %d AND sl.status = 'disponivel' AND a.id IS NULL AND sl.data_hora > %s AND (sl.tipos_servico = '' OR sl.tipos_servico IS NULL OR FIND_IN_SET(%s, sl.tipos_servico)) AND ( NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms WHERE ms.medico_id = sl.medico_id AND ms.especialidade_id = e.id) OR EXISTS (SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms WHERE ms.medico_id = sl.medico_id AND ms.especialidade_id = e.id AND ms.servico_id = %d) ) ORDER BY e.nome ASC";
        wp_send_json_success($wpdb->get_results($wpdb->prepare($sql, $servico_id, $unidade_id, $limite_tempo, $tipo_servico, $servico_id)));
    }
    public function ajax_get_convenios()
    {
        global $wpdb;
        wp_send_json_success($wpdb->get_results("SELECT id, nome, logo_url, exige_guia FROM {$wpdb->prefix}gh_convenios WHERE is_active = 1 ORDER BY nome ASC"));
    }
    public function ajax_get_planos()
    {
        global $wpdb;
        wp_send_json_success($wpdb->get_results($wpdb->prepare("SELECT id, nome FROM {$wpdb->prefix}gh_planos WHERE convenio_id = %d AND is_active = 1 ORDER BY nome ASC", intval($_POST['convenio_id']))));
    }
    public function ajax_get_medicos()
    {
        global $wpdb;
        $unidade_id = intval($_POST['unidade_id']);
        $especialidade_id = intval($_POST['especialidade_id']);
        $servico_id = isset($_POST['servico_id']) ? intval($_POST['servico_id']) : 0;
        $sql = "SELECT m.id, m.nome, m.foto_url, m.crm FROM {$wpdb->prefix}gh_medicos m INNER JOIN {$wpdb->prefix}gh_medico_unidade mu ON m.id = mu.medico_id INNER JOIN {$wpdb->prefix}gh_medico_especialidade me ON m.id = me.medico_id WHERE m.is_active = 1 AND mu.unidade_id = %d AND me.especialidade_id = %d";
        $args = array($unidade_id, $especialidade_id);
        if ($servico_id > 0) {
            $sql .= " AND ( (NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms WHERE ms.medico_id = m.id AND ms.especialidade_id = %d)) OR (EXISTS (SELECT 1 FROM {$wpdb->prefix}gh_medico_servico ms WHERE ms.medico_id = m.id AND ms.especialidade_id = %d AND ms.servico_id = %d)) )";
            array_push($args, $especialidade_id, $especialidade_id, $servico_id);
        }
        wp_send_json_success($wpdb->get_results($wpdb->prepare($sql, $args)));
    }
    public function ajax_get_available_dates()
    {
        global $wpdb;
        $unidade_id = intval($_POST['unidade_id']);
        $especialidade_id = intval($_POST['especialidade_id']);
        $servico_id = intval($_POST['servico_id']);
        $medico_id = (isset($_POST['medico_id']) && intval($_POST['medico_id']) > 0) ? intval($_POST['medico_id']) : 0;
        $mes_ano = sanitize_text_field($_POST['mes_ano']);
        $limite_tempo = date('Y-m-d H:i:s', strtotime('+4 hours', current_time('timestamp')));
        $tipo_servico = $wpdb->get_var($wpdb->prepare("SELECT tipo FROM {$wpdb->prefix}gh_servicos WHERE id = %d", $servico_id));
        $sql = "SELECT DISTINCT DATE(s.data_hora) as data_disp FROM {$wpdb->prefix}gh_slots s LEFT JOIN {$wpdb->prefix}gh_agendamentos a ON s.id = a.slot_id AND a.status = 'A' WHERE s.status = 'disponivel' AND a.id IS NULL AND s.unidade_id = %d AND s.especialidade_id = %d AND s.data_hora > %s AND s.data_hora LIKE %s";
        $args = array($unidade_id, $especialidade_id, $limite_tempo, $mes_ano . '%');
        if ($medico_id > 0) {
            $sql .= " AND s.medico_id = %d";
            $args[] = $medico_id;
        }
        if ($tipo_servico) {
            $sql .= " AND (s.tipos_servico = '' OR s.tipos_servico IS NULL OR FIND_IN_SET(%s, s.tipos_servico))";
            $args[] = $tipo_servico;
        }
        wp_send_json_success($wpdb->get_col($wpdb->prepare($sql, $args)));
    }
    public function ajax_get_slots()
    {
        global $wpdb;
        $unidade_id = intval($_POST['unidade_id']);
        $especialidade_id = intval($_POST['especialidade_id']);
        $servico_id = intval($_POST['servico_id']);
        $data_date = sanitize_text_field($_POST['data']);
        $medico_id = (isset($_POST['medico_id']) && intval($_POST['medico_id']) > 0) ? intval($_POST['medico_id']) : 0;
        $limite_tempo = date('Y-m-d H:i:s', strtotime('+4 hours', current_time('timestamp')));
        $tipo_servico = $wpdb->get_var($wpdb->prepare("SELECT tipo FROM {$wpdb->prefix}gh_servicos WHERE id = %d", $servico_id));
        $sql = "SELECT s.id, s.data_hora, m.nome as medico_nome FROM {$wpdb->prefix}gh_slots s INNER JOIN {$wpdb->prefix}gh_medicos m ON s.medico_id = m.id LEFT JOIN {$wpdb->prefix}gh_agendamentos a ON s.id = a.slot_id AND a.status = 'A' WHERE s.status = 'disponivel' AND a.id IS NULL AND s.unidade_id = %d AND s.especialidade_id = %d AND DATE(s.data_hora) = %s AND s.data_hora > %s";
        $args = array($unidade_id, $especialidade_id, $data_date, $limite_tempo);
        if ($medico_id > 0) {
            $sql .= " AND s.medico_id = %d";
            $args[] = $medico_id;
        }
        if ($tipo_servico) {
            $sql .= " AND (s.tipos_servico = '' OR s.tipos_servico IS NULL OR FIND_IN_SET(%s, s.tipos_servico))";
            $args[] = $tipo_servico;
        }
        $sql .= " ORDER BY s.data_hora ASC";
        $results = $wpdb->get_results($wpdb->prepare($sql, $args));
        foreach ($results as $row) {
            $row->hora_formatada = date('H:i', strtotime($row->data_hora));
        }
        wp_send_json_success($results);
    }

    public function ajax_save_booking() {
		check_ajax_referer( 'gh_public_nonce', 'nonce' ); global $wpdb;
        
        $user_id = get_current_user_id();
        if(!$user_id) wp_send_json_error('Erro: Você não está logado no sistema.');

		$slot_id = intval($_POST['slot_id']); 
        $servico_id = intval($_POST['servico_id']); 
        $convenio_id = intval($_POST['convenio_id']); 
        $plano_id = !empty($_POST['plano_id']) ? intval($_POST['plano_id']) : null; 

        // 1. Busca o slot PRIMEIRO para garantir que ele existe e obter a Especialidade
		$slot = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}gh_slots WHERE id = %d AND status = 'disponivel'", $slot_id) );
		if ( ! $slot ) wp_send_json_error( 'Este horário acabou de ser reservado por outra pessoa.' );
        if ( $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}gh_agendamentos WHERE slot_id = %d AND status = 'A'", $slot_id) ) ) { wp_send_json_error( 'Horário ocupado.' ); }

        $especialidade_id = $slot->especialidade_id;
        
        // 2. BLOQUEIO DE AGENDAMENTO MÚLTIPLO
        $hoje = current_time('Y-m-d H:i:s');
        $tem_duplicado = $wpdb->get_var($wpdb->prepare("
            SELECT id FROM {$wpdb->prefix}gh_agendamentos 
            WHERE paciente_user_id = %d 
              AND servico_id = %d 
              AND especialidade_id = %d 
              AND status = 'A' 
              AND data_hora > %s 
            LIMIT 1
        ", $user_id, $servico_id, $especialidade_id, $hoje));
        
        if($tem_duplicado) {
            wp_send_json_error('Você já possui um agendamento futuro em aberto para esta especialidade. Para reagendar, primeiro cancele o agendamento atual em "Meus Agendamentos".');
        }

        // 3. Processamento de Uploads de Anexos (Múltiplos arquivos)
        $anexo_urls = array();
        if (!empty($_FILES['guia_file']['name'][0])) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            $files = $_FILES['guia_file'];
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = array(
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    );
                    $movefile = wp_handle_upload( $file, array( 'test_form' => false ) );
                    if ( $movefile && !isset( $movefile['error'] ) ) { 
                        $anexo_urls[] = $movefile['url']; 
                    } else { 
                        wp_send_json_error('Erro no upload de um dos arquivos: ' . $movefile['error']); 
                    }
                }
            }
        }

        $anexo_url = !empty($anexo_urls) ? implode(',', $anexo_urls) : null;

        $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gh_slots WHERE id = %d AND status = 'disponivel'", $slot_id));
        if (! $slot) wp_send_json_error('Este horário acabou de ser reservado por outra pessoa.');
        if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gh_agendamentos WHERE slot_id = %d AND status = 'A'", $slot_id))) {
            wp_send_json_error('Horário ocupado.');
        }

        $inserted = $wpdb->insert($wpdb->prefix . 'gh_agendamentos', array('slot_id' => $slot_id, 'paciente_user_id' => $user_id, 'medico_id' => $slot->medico_id, 'unidade_id' => $slot->unidade_id, 'especialidade_id' => $slot->especialidade_id, 'servico_id' => $servico_id, 'convenio_id' => $convenio_id, 'plano_id' => $plano_id, 'data_hora' => $slot->data_hora, 'status' => 'A', 'anexo_guia_url' => $anexo_url, 'observacoes' => "Agendamento Site"));
        if (! $inserted) wp_send_json_error('Erro ao salvar agendamento.');

        $wpdb->update( $wpdb->prefix . 'gh_slots', array( 'status' => 'ocupado' ), array( 'id' => $slot_id ) );

        if(isset($_POST['update_profile_convenio']) && $_POST['update_profile_convenio'] == '1') {
            // Se convenio_id for 0 (Particular), salvamos como null no cadastro do usuário
            $db_convenio_id = ($convenio_id > 0) ? $convenio_id : null; 
            $db_plano_id = ($plano_id > 0) ? $plano_id : null;
            
            $wpdb->update(
                $wpdb->prefix . 'gh_usuarios', 
                array('convenio_id' => $db_convenio_id, 'plano_id' => $db_plano_id), 
                array('user_id' => $user_id)
            );
        }
        
		wp_send_json_success();
    }

    // -------------------------------------------------------------
    // SHORTCODE: MEUS AGENDAMENTOS
    // -------------------------------------------------------------
    public function render_meus_agendamentos_shortcode($atts)
    {
        if (! is_user_logged_in()) {
            return '<div class="bw-wizard-wrapper bw-theme-branco"><div class="bw-wizard-content-inner"><div style="text-align:center;"><h3>Acesso Restrito</h3><p>Você precisa estar logado para ver seus agendamentos.</p></div></div></div>';
        }

        $tema_ativo = get_option('gh_theme', 'bw-theme-branco');
        $cor_destaque = get_option('gh_accent_color', '');
        $logo_url = get_option('gh_wizard_logo', '');
        $page_agd = get_option('gh_page_agendamento', '');
        $url_agd = $page_agd ? get_permalink($page_agd) : '#';
        $page_perfil = get_option('gh_page_perfil', '');
        $url_perfil = $page_perfil ? get_permalink($page_perfil) : '#';
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        global $wpdb;

        $hoje = current_time('Y-m-d H:i:s');

        // Agendamentos Futuros (Abertos) - Agora trazendo a Especialidade
        $sql_futuros = $wpdb->prepare("
			SELECT a.*, u.nome as unidade_nome, m.nome as medico_nome, s.nome as servico_nome, e.nome as especialidade_nome
			FROM {$wpdb->prefix}gh_agendamentos a
			LEFT JOIN {$wpdb->prefix}gh_unidades u ON a.unidade_id = u.id
			LEFT JOIN {$wpdb->prefix}gh_medicos m ON a.medico_id = m.id
			LEFT JOIN {$wpdb->prefix}gh_servicos s ON a.servico_id = s.id
            LEFT JOIN {$wpdb->prefix}gh_especialidades e ON a.especialidade_id = e.id
			WHERE a.paciente_user_id = %d AND a.status = 'A' AND a.data_hora >= %s
			ORDER BY a.data_hora ASC
		", $user_id, $hoje);
        $futuros = $wpdb->get_results($sql_futuros);

        // Agendamentos Passados (Histórico)
        $sql_passados = $wpdb->prepare("
			SELECT a.*, u.nome as unidade_nome, m.nome as medico_nome, s.nome as servico_nome, e.nome as especialidade_nome
			FROM {$wpdb->prefix}gh_agendamentos a
			LEFT JOIN {$wpdb->prefix}gh_unidades u ON a.unidade_id = u.id
			LEFT JOIN {$wpdb->prefix}gh_medicos m ON a.medico_id = m.id
			LEFT JOIN {$wpdb->prefix}gh_servicos s ON a.servico_id = s.id
            LEFT JOIN {$wpdb->prefix}gh_especialidades e ON a.especialidade_id = e.id
			WHERE a.paciente_user_id = %d AND a.status NOT IN ('C', 'B') AND a.data_hora < %s
			ORDER BY a.data_hora DESC
		", $user_id, $hoje);
        $passados = $wpdb->get_results($sql_passados);

        ob_start();
        if (!empty($cor_destaque)) {
            echo '<style> #gh-meus-agendamentos { --bw-color-accent: ' . esc_attr($cor_destaque) . ' !important; } </style>';
        }
    ?>
        <div class="bw-wizard-wrapper <?php echo esc_attr($tema_ativo); ?>" id="gh-meus-agendamentos">
            <div class="bw-wizard-content-inner">

                <div class="bw-wizard-header">
                    <div class="bw-logo-container">
                        <?php if ($logo_url): ?><img src="<?php echo esc_url($logo_url); ?>" alt="Logomarca" class="bw-wizard-logo"><?php else: ?><h2 class="bw-wizard-title" style="margin:0;">Agendamento</h2><?php endif; ?>
                    </div>
                    <div class="bw-user-header-menu">
                        <div class="bw-logged-in-ui">
                            <div class="bw-user-avatar"><span class="dashicons dashicons-admin-users"></span></div>
                            <div class="bw-user-details">
                                <span class="bw-user-name">Olá, <span><?php echo esc_html($current_user->display_name); ?></span></span>
                                <div class="bw-user-links">
                                    <a href="<?php echo esc_url($url_agd); ?>"><span class="dashicons dashicons-plus-alt"></span> Novo Agendamento</a>
                                    <a href="<?php echo esc_url($url_perfil); ?>"><span class="dashicons dashicons-id-alt"></span> Perfil</a>
                                    <a href="#" class="bw-ajax-logout"><span class="dashicons dashicons-external"></span> Sair</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <h2 style="margin-bottom: 25px; font-weight: 600; font-size: 1.8rem;"><span class="dashicons dashicons-calendar-alt" style="font-size: 1.8rem; width: 1.8rem; height: 1.8rem; color: var(--bw-color-accent);"></span> Meus Agendamentos</h2>

                <div class="gh-agendamentos-futuros">
                    <?php if ($futuros): ?>
                        <div class="bw-grid-options" style="grid-template-columns: 1fr; gap: 20px;">
                            <?php foreach($futuros as $ag): 
								$data_formatada = date('d/m/Y', strtotime($ag->data_hora));
								$hora_formatada = date('H:i', strtotime($ag->data_hora));
                                $nome_servico_completo = esc_html($ag->servico_nome) . ($ag->especialidade_nome ? ' (' . esc_html($ag->especialidade_nome) . ')' : '');
                                
                                // CÁLCULO DAS 72 HORAS
                                $agora_ts = current_time('timestamp');
                                $agendamento_ts = strtotime($ag->data_hora);
                                $diff_horas = ($agendamento_ts - $agora_ts) / 3600;
                                $mostrar_btn_confirmar = ($diff_horas <= 72 && $diff_horas > 0);
							?>
								<div class="bw-card-option" style="text-align: left; flex-direction: row; justify-content: space-between; align-items: center; padding: 2rem; border-left: 5px solid var(--bw-color-accent);" id="agendamento-<?php echo $ag->id; ?>">
									<div>
										<h4 style="margin-top:0; color: var(--bw-color-accent); font-size: 1.3rem; margin-bottom: 12px;"><?php echo $nome_servico_completo; ?></h4>
										<p style="margin: 6px 0; font-size: 1.05rem;"><span class="dashicons dashicons-clock" style="color:var(--bw-color-text-secondary);"></span> <strong><?php echo $data_formatada; ?> às <?php echo $hora_formatada; ?></strong></p>
										<p style="margin: 6px 0; font-size: 1.05rem;"><span class="dashicons dashicons-businessman" style="color:var(--bw-color-text-secondary);"></span> <strong>Profissional:</strong> <?php echo esc_html($ag->medico_nome); ?></p>
										<p style="margin: 6px 0; font-size: 1.05rem;"><span class="dashicons dashicons-location" style="color:var(--bw-color-text-secondary);"></span> <strong>Unidade:</strong> <?php echo esc_html($ag->unidade_nome); ?></p>
										<?php if($ag->presenca_confirmada): ?>
											<span style="display:inline-flex; align-items:center; gap:5px; margin-top:15px; padding:6px 12px; background: rgba(16, 185, 129, 0.15); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 6px; font-size:0.95rem; font-weight:bold;"><span class="dashicons dashicons-yes-alt"></span> Presença Confirmada</span>
										<?php endif; ?>
									</div>
									<div style="display: flex; flex-direction: column; gap: 12px; min-width: 220px;">
										<?php if(!$ag->presenca_confirmada): ?>
                                            <?php if($mostrar_btn_confirmar): ?>
											    <button type="button" class="bw-btn-primary gh-btn-confirmar" data-id="<?php echo $ag->id; ?>"><span class="dashicons dashicons-yes"></span> Confirmar Presença</button>
                                            <?php else: ?>
                                                <button type="button" class="bw-btn-primary" disabled style="opacity:0.4; cursor:not-allowed;" title="A confirmação estará liberada faltando 72 horas para a data agendada."><span class="dashicons dashicons-clock"></span> Confirmar Presença (Em Breve)</button>
                                            <?php endif; ?>
										<?php endif; ?>
										<button type="button" class="bw-btn-secondary gh-btn-cancelar" data-id="<?php echo $ag->id; ?>" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3);"><span class="dashicons dashicons-no"></span> Cancelar Agendamento</button>
									</div>
								</div>
							<?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--bw-color-text-secondary); padding: 30px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px solid var(--bw-color-card-border); text-align:center; font-size: 1.1rem;">Você não possui agendamentos futuros em aberto.</p>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 40px; border-top: 1px solid var(--bw-color-card-border); padding-top: 30px; display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; align-items: center;">
                    <a href="<?php echo esc_url($url_agd); ?>" class="bw-btn-primary" style="width: auto; padding: 10px 22px; border-radius: 20px; font-size: 0.95rem; text-transform: none; letter-spacing: normal; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin:0;"><span class="dashicons dashicons-plus-alt"></span> Novo Agendamento</a>
                    <button type="button" class="bw-btn-back" id="gh-toggle-passados" style="margin:0;"><span class="dashicons dashicons-visibility"></span> Mostrar Agendamentos Anteriores</button>
                </div>

                <div class="gh-agendamentos-passados" style="display: none; margin-top: 30px;">
                    <h3 style="margin-bottom: 20px; font-size: 1.4rem;">Histórico de Atendimentos</h3>
                    <?php if ($passados): ?>
                        <div class="bw-grid-options" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); opacity: 0.85;">
                            <?php foreach ($passados as $ag):
                                $data_formatada = date('d/m/Y', strtotime($ag->data_hora));
                                $hora_formatada = date('H:i', strtotime($ag->data_hora)); // <- NOVO
                                $nome_servico_completo = esc_html($ag->servico_nome) . ($ag->especialidade_nome ? ' (' . esc_html($ag->especialidade_nome) . ')' : '');
                            ?>
                                <div class="bw-card-option" style="text-align: left; padding: 1.5rem; display: block; align-items:flex-start;">
                                    <h4 style="margin-top:0; color: var(--bw-color-text-primary); font-size: 1.1rem; border-bottom:1px solid rgba(0,0,0,0.05); padding-bottom:10px;"><?php echo $nome_servico_completo; ?></h4>
                                    <p style="margin: 8px 0 4px 0; font-size: 0.95rem;"><strong>Data:</strong> <?php echo $data_formatada; ?> às <?php echo $hora_formatada; ?></p>
                                    <p style="margin: 4px 0; font-size: 0.95rem;"><strong>Médico:</strong> <?php echo esc_html($ag->medico_nome); ?></p>
                                    <p style="margin: 4px 0; font-size: 0.95rem;"><strong>Unidade:</strong> <?php echo esc_html($ag->unidade_nome); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--bw-color-text-secondary); text-align:center;">Nenhum histórico encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    public function ajax_confirmar_presenca()
    {
        check_ajax_referer('gh_public_nonce', 'nonce');
        if (! is_user_logged_in()) wp_send_json_error('Acesso negado.');

        global $wpdb;
        $agendamento_id = intval($_POST['agendamento_id']);
        $user_id = get_current_user_id();

        $atualizado = $wpdb->update(
            $wpdb->prefix . 'gh_agendamentos',
            array('presenca_confirmada' => 1),
            array('id' => $agendamento_id, 'paciente_user_id' => $user_id),
            array('%d'),
            array('%d', '%d')
        );

        if ($atualizado !== false) {
            wp_send_json_success('Sua presença foi confirmada com sucesso!');
        } else {
            wp_send_json_error('Ocorreu um erro ao confirmar a presença.');
        }
    }

    public function ajax_cancelar_agendamento()
    {
        check_ajax_referer('gh_public_nonce', 'nonce');
        if (! is_user_logged_in()) wp_send_json_error('Acesso negado.');

        global $wpdb;
        $agendamento_id = intval($_POST['agendamento_id']);
        $user_id = get_current_user_id();

        // Resgata o agendamento para pegar o slot_id
        $ag = $wpdb->get_row($wpdb->prepare("SELECT slot_id FROM {$wpdb->prefix}gh_agendamentos WHERE id = %d AND paciente_user_id = %d AND status = 'A'", $agendamento_id, $user_id));

        if (!$ag) {
            wp_send_json_error('Agendamento não encontrado ou já processado.');
        }

        // 1. Altera o status do agendamento para C (Cancelado)
        $wpdb->update(
            $wpdb->prefix . 'gh_agendamentos',
            array('status' => 'C'),
            array('id' => $agendamento_id),
            array('%s'),
            array('%d')
        );

        // 2. Libera o slot na agenda (volta para disponível)
        if ($ag->slot_id) {
            $wpdb->update(
                $wpdb->prefix . 'gh_slots',
                array('status' => 'disponivel'),
                array('id' => $ag->slot_id),
                array('%s'),
                array('%d')
            );
        }

        wp_send_json_success('Agendamento cancelado com sucesso.');
    }
}
