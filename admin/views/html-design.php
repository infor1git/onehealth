<?php
// Evita acesso direto
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['message'] ) && $_GET['message'] == 'saved' ) {
    echo '<div class="notice notice-success is-dismissible"><p>Tema visual salvo com sucesso!</p></div>';
}

$tema_atual = get_option('gh_theme', 'bw-theme-branco');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Aparência e Design (Wizard)</h1>
    <hr class="wp-header-end">

    <div class="postbox" style="max-width: 800px; padding: 20px; margin-top: 20px;">
        <h2>Selecione o Tema Visual do Agendamento</h2>
        <p class="description">Escolha o esquema de cores que será aplicado na tela de agendamento (Glassmorphism).</p>
        
        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_save_design">
            <?php wp_nonce_field( 'gh_save_design_nonce', 'gh_security' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">Tema Ativo</th>
                        <td>
                            <fieldset style="display: flex; flex-direction: column; gap: 12px;">
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
                <input type="submit" name="submit" id="submit" class="button button-primary button-large" value="Salvar Design">
            </p>
        </form>
    </div>
</div>