<?php
// Evita acesso direto
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['message'] ) && $_GET['message'] == 'saved' ) {
    echo '<div class="notice notice-success is-dismissible"><p>Design salvo com sucesso!</p></div>';
}

$tema_atual = get_option('gh_theme', 'bw-theme-branco');
$logo_atual = get_option('gh_wizard_logo', '');
$cor_destaque = get_option('gh_accent_color', '');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Aparência e Design (Wizard)</h1>
    <hr class="wp-header-end">

    <div class="postbox" style="max-width: 800px; padding: 20px; margin-top: 20px;">
        <h2>Configurações Visuais do Agendamento</h2>
        
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_save_design">
            <?php wp_nonce_field( 'gh_save_design_nonce', 'gh_security' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">Logomarca do Wizard</th>
                        <td>
                            <div id="gh_design_logo_preview_container" style="<?php echo $logo_atual ? '' : 'display:none;'; ?> margin-bottom: 10px;">
                                <img id="gh_design_logo_preview" src="<?php echo esc_url($logo_atual); ?>" style="max-width:200px; max-height:80px; background:#e5e7eb; padding:10px; border-radius:8px;">
                            </div>
                            <input type="hidden" name="gh_wizard_logo" id="gh_design_logo_url" value="<?php echo esc_attr($logo_atual); ?>">
                            <div style="display:flex; gap: 5px;">
                                <button type="button" class="button" id="gh_upload_design_logo_button">Selecionar Logo</button>
                                <button type="button" class="button link-delete" id="gh_remove_design_logo_button">Remover</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Cor de Destaque (Botões e Seleções)</th>
                        <td>
                            <div style="display:flex; align-items: center; gap: 15px;">
                                <input type="color" name="gh_accent_color" id="gh_accent_color" value="<?php echo esc_attr($cor_destaque ? $cor_destaque : '#0ea5e9'); ?>">
                                <label>
                                    <input type="checkbox" name="gh_use_default_color" value="1" <?php checked($cor_destaque, ''); ?>> 
                                    Usar cor padrão do Tema escolhido
                                </label>
                            </div>
                            <p class="description">Altera a cor dos botões, ícones e itens selecionados.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tema Base (Background)</th>
                        <td>
                            <fieldset style="display: flex; flex-direction: column; gap: 12px; background: #f9fafb; padding: 15px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <label><input type="radio" name="gh_theme" value="bw-theme-branco" <?php checked($tema_atual, 'bw-theme-branco'); ?>> <b>Branco</b> (Light Theme)</label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-dark" <?php checked($tema_atual, 'bw-theme-dark'); ?>> <b>Dark</b> (Escuro Moderno)</label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-blue-ocean" <?php checked($tema_atual, 'bw-theme-blue-ocean'); ?>> <b>Blue Ocean</b> (Tons de Azul Profundo)</label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-aurora" <?php checked($tema_atual, 'bw-theme-aurora'); ?>> <b>Aurora</b> (Azul com Roxo/Neon)</label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-green-forest" <?php checked($tema_atual, 'bw-theme-green-forest'); ?>> <b>Green Forest</b> (Verde Esmeralda/Teal)</label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-lilac" <?php checked($tema_atual, 'bw-theme-lilac'); ?>> <b>Lilac</b> (Lilás e Tons Pastel Soft)</label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-yellow-sunset" <?php checked($tema_atual, 'bw-theme-yellow-sunset'); ?>> <b>Yellow Sunset</b> (Amarelo e Laranja Soft)</label>
                                <label><input type="radio" name="gh_theme" value="bw-theme-red" <?php checked($tema_atual, 'bw-theme-red'); ?>> <b>Red</b> (Vermelho Carmesim)</label>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Salvar Configurações">
            </p>
        </form>
    </div>
</div>