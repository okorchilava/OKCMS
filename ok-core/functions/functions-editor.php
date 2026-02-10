<?php
/*
 * OK Text Editor Component
 * მთავარი გამშვები ფაილი
 */

if (!defined('OK_LOADED')) exit;

function ok_render_editor($name, $content = '', $id = 'ok_main_editor') {
    // მიუთითე სწორი გზა საქაღალდემდე
    // მაგალითად, თუ ეს ფაილი არის 'ok-core'-ში, ხოლო ნაწილები 'ok-core/functions/editor'-ში:
    $editor_parts_path = __DIR__ . '/editor';

    // 1. სტილები
    if (file_exists($editor_parts_path . '/css.php')) {
        require $editor_parts_path . '/css.php';
    } else {
        echo "Error: Editor CSS not found in $editor_parts_path";
    }
    ?>

    <div class="ok-editor-wrapper">
        <?php 
        if (file_exists($editor_parts_path . '/toolbar.php')) {
            require $editor_parts_path . '/toolbar.php';
        } 
        ?>
        
        <div id="<?php echo $id; ?>" class="ok-editor-content" contenteditable="true">
            <?php echo $content; ?>
        </div>
        
        <textarea name="<?php echo $name; ?>" id="hidden_<?php echo $id; ?>" style="display:none;"><?php echo htmlspecialchars($content); ?></textarea>
    </div>

    <?php 
    if (file_exists($editor_parts_path . '/scripts.php')) {
        require $editor_parts_path . '/scripts.php';
    } 
    ?>
    <?php
}