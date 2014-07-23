<?php

class Secure_Attachments_Manager_Admin {

    private $version;

    private $options;

    function __construct($version)
    {
        $this->version = $version;
    }

    public function register_scripts() {
        wp_register_script( 'secure-attachments', plugins_url( 'js/secure-attachments-admin.js', __FILE__ ) );
        wp_register_script( 'secure-attachments-jquery-form', plugins_url( 'js/jquery.form.min.js', __FILE__ ) );
    }

    public function register_styles() {
        wp_register_style( 'secure-attachments-admin-css', plugins_url( 'css/secure-attachments-admin.css', __FILE__  ) );
    }

    public function enqueue_styles() {
        if( is_admin() ) {
            wp_enqueue_style( 'secure-attachments-admin-css', false, array(), $this->version );
        }
    }

    public function enqueue_scripts($hook) {
        if( is_admin() && ( 'post.php' == $hook || 'post-new.php' == $hook ) ){
            wp_enqueue_script( 'secure-attachments-jquery-form' );
            wp_enqueue_script( 'secure-attachments' );
        }

        wp_localize_script( 'secure-attachments-jquery-form', 'ajaxUploadDocument', array(
            'url' => '/wp-admin/admin-ajax.php',
            'getDocUrl' => '/wp-content/plugins/secure-attachments/download-attachment.php',
            'loading_text' => __( 'loading file ... ', 'secure-attachments' ),
            'loading_text' => __( 'loading file completed', 'secure-atttachments' ),
        ) );

        return;
    }

    public function add_meta_box_post() {

        add_meta_box(
            'secure-attachments-div',
            __( 'Secure Attachments', 'secure-attachments' ),
            array( $this, 'render_meta_box' ),
            'post',
            'side',
            'high'
        );

    }

    public function add_meta_box_page() {

        add_meta_box(
            'secure-attachments-div',
            __( 'Secure Attachments', 'secure-attachments' ),
            array( $this, 'render_meta_box' ),
            'page',
            'side',
            'high'
        );

    }

    public function render_meta_box($post, $args = array() ) {
        $attached_documents = get_post_meta($post->ID,'secure-attachments-list', true);
        $hide_attached_documents = get_post_meta($post->ID,'secure-attachments-hide-file', true);
        require_once plugin_dir_path( __FILE__ ) . 'partials/secure-attachments-metabox.php';
    }


    public function secure_attachments_ajax_upload_document()
    {

        $res = array(
            'status' => 0,
            'msg'    => __('There was an error trying to upload the file. Please try again!','secure-attachments'),
        );

        if( ! isset( $_FILES['secure-attachments-file'] ) ) {
            $res['msg'] = __( 'No file selected', 'secure-attachments' );
            echo json_encode( $res );
            exit;
        }

        if ( ! check_ajax_referer( 'secure_attachments_add', '_wpnonce_secure_attachments_add', true ) ) {
            echo json_encode( $res );
            exit;
        }

        if ( ! current_user_can( 'edit_post', $_POST['post_ID'] ) ) {
            echo json_encode( $res );
            exit;
        }

        $sa = new Secure_Attachments( $_POST['post_ID'] );
        $uploadedFiles = $sa->getUploadedFileAsArrayOfFile($_FILES['secure-attachments-file']);

        if ( empty( $uploadedFiles ) ) {
            echo json_encode( $res );
            exit;
        }

        $firstUploadedFile = $uploadedFiles[0];
        $file_params = array(
            'file-title'       => sanitize_text_field( $_POST['secure-attachments-file-title'] ),
            'file-description' => sanitize_text_field( $_POST['secure-attachments-file-description'] ),
            'file-order'       => (int) $_POST['secure-attachments-file-order'],
        );

        if ( ! $sa->validateUploadedFile( $firstUploadedFile, $file_params['file-title'] ) ) {
            $errors = $sa->getValidationErrors();
            if( ! empty( $errors ) ) {
                $res['msg'] = reset( $errors );
            }
            echo json_encode( $res );
            exit;
        }

        $res['title'] = $file_params['file-title'];
        $res['name']  = sanitize_file_name( $firstUploadedFile['name'] );
        $res['type']  = $firstUploadedFile['type'];
        $res['size']  = round( ( $firstUploadedFile['size'] / 1024), 2 ) . " KB";
        $res['post']  = $_POST['post_ID'];
        $res['blog_id'] = get_current_blog_id();

        if( $sa->saveUploadedFile( $firstUploadedFile, $file_params ) ) {
            $res['status'] = 1;
            $res['msg'] = __( 'The file has been uploaded successfully!','secure-attachments' );
        }

        echo json_encode( $res );

        exit;

    }

    public function secure_attachments_ajax_remove_document()
    {
        $res = array(
            'status' => 0,
            'msg'    => __('There was an error trying to remove the file. Please try again!', 'acd-attach-document'),
			'name' => $_POST['file_name'],
			'span_file_id' => $_POST['span_file_id'],
        );

        if ( ! check_ajax_referer( 'secure_attachments_remove', '_wpnonce_secure_attachments_remove', true ) ) {
            echo json_encode( $res );
            exit;
        }

        if ( ! current_user_can( 'edit_post', $_POST['post_ID'] ) ) {
            echo json_encode( $res );
            exit;
        }

        $sa = new Secure_Attachments( $_POST['post_ID'] );

        if( $sa->removeAttachment( $_POST['file_name'] ) ) {
            $res['status'] = 1;
            $res['msg'] = __( 'The file has been deleted successfully!','secure-attachments' );
        }

        echo json_encode($res);
        exit;

    }

/**
     * start section to manage plugin options
     */
    public function add_plugin_options_page() {
        add_options_page(
            'Settings Admin',
            __('Secure Attachments Settings', 'secure-attachments'),
            'manage_options',
            'secure-attachments-options-admin',
            array( $this, 'create_admin_options_page' )
        );
    }

    public function create_admin_options_page()
    {
        $this->options = get_option( 'secure-attachments-options' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'Secure Attachments Options', 'secure-attachments' )?></h2>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'secure-attachments-options' );
                do_settings_sections( 'secure-attachments-options' );
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    public function options_page_init()
    {
        register_setting(
            'secure-attachments-options', // Option group
            'secure-attachments-options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'secure-attachments-options', // ID
            'Secure Attachments Options', // Title
            array( $this, 'print_section_info' ), // Callback
            'secure-attachments-options' // Page
        );

        add_settings_field(
            'upload-dir', // ID
            'Upload Directory', // Title
            array( $this, 'upload_dir_callback' ), // Callback
            'secure-attachments-options', // Page
            'secure-attachments-options' // Section
        );

        add_settings_field(
            'max-file-size',
            'Max File Size',
            array( $this, 'max_file_size_callback' ),
            'secure-attachments-options',
            'secure-attachments-options'
        );

        add_settings_field(
            'file-extension',
            'File Extension',
            array( $this, 'file_extension_callback' ),
            'secure-attachments-options',
            'secure-attachments-options'
        );

        add_settings_field(
            'file-type',
            'File Type',
            array( $this, 'file_type_callback' ),
            'secure-attachments-options',
            'secure-attachments-options'
        );
    }

    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['max-file-size'] ) )
            $new_input['max-file-size'] = absint( $input['max-file-size'] );

        if( isset( $input['upload-dir'] ) )
            $new_input['upload-dir'] = sanitize_text_field( $input['upload-dir'] );

        if( isset( $input['file-type'] ) )
            $new_input['file-type'] = sanitize_text_field( $input['file-type'] );

        if( isset( $input['file-extension'] ) )
            $new_input['file-extension'] = sanitize_text_field( $input['file-extension'] );

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter your settings below:';
    }

    public function upload_dir_callback()
    {
        $value = isset( $this->options['upload-dir'] ) ? esc_attr( $this->options['upload-dir']) : '';
        $description = '<p class="description">' . __('absolute path to save file on server (e.g. /home/www/my-website/my-directory)', 'secure-attachments') . '</p>';
        printf(
            '<input type="text" id="upload-dir" name="secure-attachments-options[upload-dir]" value="%s" class="large-text ltr" />%s',
            $value,
            $description
        );
    }

    public function max_file_size_callback()
    {
        $value = isset( $this->options['max-file-size'] ) ? esc_attr( $this->options['max-file-size']) : '';
        $description = '<p class="description">' . __( 'MAX file size in MB (e.g. 2 = 2Mb)', 'secure-attachments' ) . '</p>';
        printf(
            '<input type="text" id="max-file-size" name="secure-attachments-options[max-file-size]" value="%s" class="little-text ltr" />%s',
            $value,
            $description
        );
    }

    public function file_extension_callback()
    {
        $value = isset( $this->options['file-extension'] ) ? esc_attr( $this->options['file-extension']) : '';
        $description = '<p class="description">' . __('valid file extension separated by comma (e.g. pdf,mpg,jpg)', 'secure-attachments') . '</p>';
        printf(
            '<textarea id="file-extension" name="secure-attachments-options[file-extension]" class="large-text code" >%s</textarea>%s',
            $value,
            $description
        );
    }

    public function file_type_callback()
    {
        $value = isset( $this->options['file-type'] ) ? esc_attr( $this->options['file-type']) : '';
        $description = '<p class="description">' . __('valid file type separated by comma (e.g. image/gif,image/jpeg,image/jpg)', 'secure-attachments') . '</p>';
        printf(
            '<textarea id="file-type" name="secure-attachments-options[file-type]" class="large-text code" >%s</textarea>%s',
            $value,
            $description
        );
    }

}