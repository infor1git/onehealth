<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
$model = new GH_Especialidade();
$base_url = admin_url( 'admin.php?page=one-health-especialidades' );

if ( isset( $_GET['message'] ) ) {
    if ( $_GET['message'] == 'created' ) echo '<div class="notice notice-success is-dismissible"><p>Especialidade criada!</p></div>';
    if ( $_GET['message'] == 'updated' ) echo '<div class="notice notice-success is-dismissible"><p>Especialidade atualizada!</p></div>';
    if ( $_GET['error'] ) echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $_GET['error'] ) . '</p></div>';
}
?>

<div class="wrap">
    <?php if ( $action == 'list' ) : ?>
        <h1 class="wp-heading-inline">Especialidades</h1>
        <a href="<?php echo $base_url . '&action=new'; ?>" class="page-title-action">Adicionar Nova</a>
        <hr class="wp-header-end">

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr><th style="width:50px;">Ícone</th><th>Nome</th><th>CBO</th><th>Status</th><th style="width: 150px;">Ações</th></tr>
            </thead>
            <tbody>
                <?php $items = $model->get_all();
                if ( $items ) : foreach ( $items as $item ) : ?>
                    <tr>
                        <td style="text-align:center;"><span class="dashicons <?php echo !empty($item->icone) ? esc_attr($item->icone) : 'dashicons-heart'; ?>"></span></td>
                        <td><strong><?php echo esc_html( $item->nome ); ?></strong></td>
                        <td><?php echo esc_html( $item->cbo ); ?></td>
                        <td><?php echo $item->is_active ? '<span style="color:green;">Ativo</span>' : '<span style="color:red;">Inativo</span>'; ?></td>
                        <td>
                            <a href="<?php echo $base_url . '&action=edit&id=' . $item->id; ?>" class="button button-small">Editar</a>
                            <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=gh_delete_especialidade&id=' . $item->id ), 'delete_especialidade_' . $item->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Tem certeza?');">Excluir</a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="5">Nenhuma especialidade cadastrada.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ( $action == 'new' || $action == 'edit' ) : 
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $dados = $id ? $model->get( $id ) : null;
    ?>
        <h1 class="wp-heading-inline"><?php echo $id ? 'Editar Especialidade' : 'Nova Especialidade'; ?></h1>
        <a href="<?php echo $base_url; ?>" class="page-title-action">Voltar</a>
        <hr class="wp-header-end">

        <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
            <input type="hidden" name="action" value="gh_save_especialidade">
            <?php if ($id) : ?><input type="hidden" name="id" value="<?php echo $id; ?>"><?php endif; ?>
            <?php wp_nonce_field( 'gh_save_especialidade_nonce', 'gh_security' ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><label for="gh_nome">Nome <span class="required">*</span></label></th>
                        <td><input name="nome" type="text" id="gh_nome" value="<?php echo $dados ? esc_attr($dados->nome) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Ícone</label></th>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <input type="hidden" name="icone" id="gh_icone_input" value="<?php echo $dados && !empty($dados->icone) ? esc_attr($dados->icone) : 'dashicons-heart'; ?>">
                                <div id="gh_icone_preview" style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid #ccc;border-radius:6px;">
                                    <span class="dashicons <?php echo $dados && !empty($dados->icone) ? esc_attr($dados->icone) : 'dashicons-heart'; ?>" style="font-size:24px;width:24px;height:24px;"></span>
                                </div>
                                <button type="button" class="button" id="gh_open_icon_picker">Escolher Ícone</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gh_cbo">CBO</label></th>
                        <td><input name="cbo" type="text" id="gh_cbo" value="<?php echo $dados ? esc_attr($dados->cbo) : ''; ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th scope="row">Status</th>
                        <td><label><input name="is_active" type="checkbox" value="1" <?php checked( $dados ? $dados->is_active : 1, 1 ); ?>> Ativo</label></td>
                    </tr>
                </tbody>
            </table>
            <p class="submit"><input type="submit" class="button button-primary" value="Salvar"></p>
        </form>
    <?php endif; ?>
</div>