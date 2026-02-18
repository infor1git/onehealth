<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['message'] ) && $_GET['message'] == 'saved' ) { echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>'; }

$tema_atual = get_option('gh_theme', 'bw-theme-branco');
$logo_atual = get_option('gh_wizard_logo', '');
$cor_destaque = get_option('gh_accent_color', '');
$ts_sitekey = get_option('gh_turnstile_sitekey', '');
$ts_secret = get_option('gh_turnstile_secret', '');
$ts_theme = get_option('gh_turnstile_theme', 'auto');
$page_perfil = get_option('gh_page_perfil', '');
$page_agendamentos = get_option('gh_page_agendamentos', '');
?>
<div class="wrap">
    <h1 class="wp-heading-inline">Configurações do Wizard</h1>
    <hr class="wp-header-end">
    <div class="postbox" style="max-width: 800px; padding: 20px; margin-top: 20px;">
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_save_design">
            <?php wp_nonce_field( 'gh_save_design_nonce', 'gh_security' ); ?>
            <table class="form-table">
                <tbody>
                    <tr><td colspan="2"><h2 style="margin:0; padding-bottom:5px; border-bottom:1px solid #eee;">Aparência e Design</h2></td></tr>
                    <tr>
                        <th scope="row">Logomarca do Wizard</th>
                        <td>
                            <div id="gh_design_logo_preview_container" style="<?php echo $logo_atual ? '' : 'display:none;'; ?> margin-bottom: 10px;">
                                <img id="gh_design_logo_preview" src="<?php echo esc_url($logo_atual); ?>" style="max-width:200px; max-height:80px; background:#e5e7eb; padding:10px; border-radius:8px;">
                            </div>
                            <input type="hidden" name="gh_wizard_logo" id="gh_design_logo_url" value="<?php echo esc_attr($logo_atual); ?>">
                            <div style="display:flex; gap: 5px;"><button type="button" class="button" id="gh_upload_design_logo_button">Selecionar Logo</button><button type="button" class="button link-delete" id="gh_remove_design_logo_button">Remover</button></div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cor de Destaque</th>
                        <td>
                            <div style="display:flex; align-items: center; gap: 15px;">
                                <input type="color" name="gh_accent_color" id="gh_accent_color" value="<?php echo esc_attr($cor_destaque ? $cor_destaque : '#0ea5e9'); ?>">
                                <label><input type="checkbox" name="gh_use_default_color" value="1" <?php checked($cor_destaque, ''); ?>> Usar cor padrão do Tema</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tema Base (Background)</th>
                        <td>
                            <fieldset style="display: flex; flex-direction: column; gap: 12px; background: #f9fafb; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <label><input type="radio" name="gh_theme" value="bw-theme-branco" <?php checked($tema_atual, 'bw-theme-branco'); ?>> <b>Branco</b></label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-dark" <?php checked($tema_atual, 'bw-theme-dark'); ?>> <b>Dark</b></label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-blue-ocean" <?php checked($tema_atual, 'bw-theme-blue-ocean'); ?>> <b>Blue Ocean</b></label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-aurora" <?php checked($tema_atual, 'bw-theme-aurora'); ?>> <b>Aurora</b></label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-green-forest" <?php checked($tema_atual, 'bw-theme-green-forest'); ?>> <b>Green Forest</b></label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr><td colspan="2"><h2 style="margin-top:20px; padding-bottom:5px; border-bottom:1px solid #eee;">Páginas do Usuário</h2></td></tr>
                    <tr>
                        <th scope="row">Página de Perfil</th>
                        <td>
                            <?php wp_dropdown_pages(array('name' => 'gh_page_perfil', 'show_option_none' => '&mdash; Selecione &mdash;', 'selected' => $page_perfil, 'class' => 'regular-text')); ?>
                            <p class="description">Página onde o usuário atualizará seus dados cadastrais.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Página Meus Agendamentos</th>
                        <td>
                            <?php wp_dropdown_pages(array('name' => 'gh_page_agendamentos', 'show_option_none' => '&mdash; Selecione &mdash;', 'selected' => $page_agendamentos, 'class' => 'regular-text')); ?>
                            <p class="description">Página que exibirá o histórico de agendamentos do usuário.</p>
                        </td>
                    </tr>
                    
                    <tr><td colspan="2"><h2 style="margin-top:20px; padding-bottom:5px; border-bottom:1px solid #eee;">Segurança (Cloudflare Turnstile)</h2></td></tr>
                    <tr><th scope="row">Site Key</th><td><input type="text" name="gh_turnstile_sitekey" value="<?php echo esc_attr($ts_sitekey); ?>" class="regular-text"></td></tr>
                    <tr><th scope="row">Secret Key</th><td><input type="password" name="gh_turnstile_secret" value="<?php echo esc_attr($ts_secret); ?>" class="regular-text"></td></tr>
                    <tr>
                        <th scope="row">Tema do Captcha</th>
                        <td>
                            <select name="gh_turnstile_theme" class="regular-text">
                                <option value="auto" <?php selected($ts_theme, 'auto'); ?>>Automático (Baseado no Sistema)</option>
                                <option value="light" <?php selected($ts_theme, 'light'); ?>>Claro</option>
                                <option value="dark" <?php selected($ts_theme, 'dark'); ?>>Escuro</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Salvar Configurações"></p>
        </form>
    </div>
</div>