<?php wp_nonce_field('secure_attachments_add', '_wpnonce_secure_attachments_add'); ?>
<?php wp_nonce_field('secure_attachments_remove', '_wpnonce_secure_attachments_remove'); ?>

<h4><?php _e( 'Upload new Secure Attachment', 'secure-attachments' ); ?></h4>
<input type="file" id="secure-attachments-file" name="secure-attachments-file[]" /><br>

<div id="secure-attachments-file-details" class="hidden">
<label for="secure-attachments-file"><?php _e( 'Title', 'secure-attachments' )?></label>
<input type="text" id="secure-attachments-file-title" name="secure-attachments-file-title" value="" /><br>
<label for="secure-attachments-file-description"><?php _e( 'Description', 'secure-attachments' )?></label>
<textarea id="secure-attachments-file-description" name="secure-attachments-file-description"></textarea><br>
<label for="secure-attachments-file-order"><?php _e( 'Order', 'secure-attachments' )?></label>
<input type="text" id="secure-attachments-file-order" name="secure-attachments-file-order" value="" /><br>
<br>
<input id="cancel-secure-attachment-button" type="button" value="<?php _e('Cancel','secure-attachments'); ?>" class="button upload-btn">
<input id="upload-secure-attachment-button" type="button" value="<?php _e('Upload File to Server','secure-attachments'); ?>" class="button upload-btn">
<div class="clear"></div>
</div>

<div class="secure-attachments-upload-progress">
    <div class="secure-attachments-upload-percent"></div >
    <div class="secure-attachments-upload-bar"></div >
</div>

<div id="secure-attachments-upload-status"></div>

<div class="secure-attachments-list">
    <h4><?php _e( 'Secure Attachments uploaded', 'secure-attachments' ); ?></h4>
    <?php if(is_array($attached_documents)):
        $i = 0;?>
        <span id="no-secure-attachments" class="hidden"><?php _e( 'No documents uploaded up to now', 'secure-attachments' ); ?></span>
        <?php foreach ($attached_documents as $doc) :
            $i++;?>
            <span class="secure-attachments-item" id="file-<?php echo $i;?>">
                <a class="ntdelbutton" href="<?php echo $doc['file-name']; ?>" data-action="remove-attached-document" data-value="<?php echo $i; ?>"></a>
                <a href="<?php echo plugins_url() . '/secure-attachments/download-attachment.php?blog_ID=' . get_current_blog_id() . '&post_ID=' . $post->ID . '&file_name=' .$doc['file-name']; ?>" ><?php echo $doc['file-title']; ?> (<?php echo round( ( $doc['file-size'] / 1024), 2 ) . " KB"; ?>)</a>
            </span>
        <?php endforeach;?>
    <?php else: ?>
        <span id="no-secure-attachments"><?php _e( 'No documents uploaded up to now', 'secure-attachments' ); ?></span>
    <?php endif; ?>
</div>

<h4><?php _e( 'Options', 'secure-attachments' ); ?></h4>
<input type="checkbox" id="secure-attachments-hide-file" name="secure-attachments-hide-file" value="1" <?php if( $hide_attached_documents ) echo "checked"; ?>/><label for="secure-attachments-hide-file"> <?php _e('hide attachments', 'anie-theme'); ?></label>
