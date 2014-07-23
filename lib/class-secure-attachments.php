<?php

class Secure_Attachments {

    private $post_id;

    /**
     * path for the directory where the file uploaded will be saved
     *
     * @access private
     * @var string $upload_dir the current path where uploaded file we be saved
     */
    private $upload_dir;

    /**
     * max file size in Kilobyte allowed for each file. this parameter is valid at applicaion level.
     * server parameters should be configured accordingly
     *
     * @access private
     * @var integer $max_file_size the max file size allowed in KiloByte
     */
    private $max_file_size;

    /**
     * file extensions enabled to be stored on the server (e.g. doc, pdf, ...)
     *
     * @access private
     * @var array $valid_extensions the current file extensions enabled to be stored on the server
     */
    private $valid_extensions;

    /**
     * file type enabled to be stored on the server (e.g. image/jpg)
     *
     * @access private
     * @var array $valid_mime_type the current file type enabled to be stored on the server
     */
    private $valid_mime_type;

    private $validationErrors;

    private $errors;


    /**
     * the list of secure attachments (usually associated to a post or page)
     *
     * @access private
     * @var array $attachments
     */
    private $attachments;


    /**
     * retrieve options saved in the DB for the plugin and load secure attachments list for a
     * specific post (if requested)
     *
     * @param null $post_id
     */
    function __construct( $post_id = null ) {
        $this->post_id = $post_id;
        $this->upload_dir = '';
        $this->max_file_size = 0;
        $this->valid_extensions = array();
        $this->valid_mime_type = array();
        $this->validationErrors = array();
        $this->attachments = array();

        $options = get_option( 'secure-attachments-options', array() );

        if( isset( $options['upload-dir'] ) ) {
            $this->upload_dir = $options['upload-dir'];
        }
        if( isset( $options['max-file-size'] ) ) {
            $this->max_file_size = $options['max-file-size'];
        }
        if( isset( $options['file-extension'] ) ) {
            $this->valid_extensions = explode( ',', $options['file-extension'] );
        }
        if( isset( $options['file-type'] ) ) {
            $this->valid_mime_type = explode( ',', $options['file-type'] );
        }

        $this->updateAttachments();
    }


    /**
     * @return int
     */
    public function getMaxFileSize()
    {
        return $this->max_file_size;
    }

    /**
     * @return null
     */
    public function getPostId()
    {
        return $this->post_id;
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        if( empty ( $this->post_id ) ) {
            return false;
        }else{
            return $this->upload_dir . "/" . get_current_blog_id() . "/" . $this->post_id . "/";
        }
    }

    /**
     * @return array
     */
    public function getValidExtensions()
    {
        return $this->valid_extensions;
    }

    /**
     * @return array
     */
    public function getValidMimeType()
    {
        return $this->valid_mime_type;
    }

    public function isValidFileExtension( $extension ) {
        $extension = strtolower( trim( $extension ) );
        if( in_array( $extension, $this->valid_extensions) ) {
            return true;
        }
        return false;
    }

    public function isValidFileType( $file_type ) {
        $file_type = strtolower( trim( $file_type ) );
        if( in_array( $file_type, $this->valid_mime_type) ) {
            return true;
        }
        return false;
    }

    public function isValidFileSize( $file_size ) {
        if( ! is_int( (int) $file_size ) ) {
            return false;
        }
        if( $file_size <= ( $this->max_file_size * 1024 * 1024) ) {
            return true;
        }
        return false;
    }

    public function isFileTitleAlreadyUsed($file_title) {
        $in_use = false;
        foreach( $this->attachments as $attachment ) {
            if( $attachment['file-title'] == $file_title ) {
                $in_use = true;
                break;
            }
        }
        return $in_use;
    }

    public function isFileNameAlreadyUsed( $file_name ) {
        $in_use = false;
        foreach( $this->attachments as $attachment ) {
            if( $attachment['file-name'] == $file_name ) {
                $in_use = true;
                break;
            }
        }
        return $in_use;
    }

    public function validateUploadedFile( $file, $file_title = '' ) {
        $file_extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
        $this->validationErrors = array();
        if( empty ( $file_title ) ) {
            $this->validationErrors['file-title'] = __( 'Non è stato indicato un titolo per il nuovo allegato', 'secure-attachments' );
                                                                                                                        }
        if( $this->isFileTitleAlreadyUsed( $file_title ) ) {
            $this->validationErrors['file-title'] = sprintf( __( 'Esiste già un file con il titolo %s', 'secure-attachments' ), $file_title );
        }
        if( $this->isFileNameAlreadyUsed( $file['name'] ) ) {
            $this->validationErrors['file-name'] = sprintf( __( 'Esiste già un file %s', 'secure-attachments' ), $file['name'] );
        }
        if( ! $this->isValidFileExtension( $file_extension ) ) {
            $this->validationErrors['file-extension'] = sprintf( __( 'L\'estensione del file %s non è un\'estensione valida', 'secure-attachments' ), $file['name'] );
        }
        if( ! $this->isValidFileSize( $file['size'] ) ) {
            $this->validationErrors['file-size'] = sprintf(  __( 'Il file ha superato le dimensioni massime consentite di %s', 'secure-attachments' ), $this->max_file_size );
        }
        if( ! $this->isValidFileType( $file['type'] ) ) {
            $this->validationErrors['file-type'] = sprintf(  __( 'La tipologia del file caricato (%s) non è una tipologia valida', 'secure-attachments' ), $file['type'] );
        }
        if( $file['error'] ) {
            $this->validationErrors['upload-error'] = sprintf(  __( 'Si è verifcato un errore durante il caricamento del file %s', 'secure-attachments' ), $file['name'] );
        }
        if( 0 == count($this->validationErrors) ) {
            return true;
        }
        return false;
    }

    public function saveUploadedFile( $file, $params = array() ) {
        /*
         * $param = array(
         *  'file-name'
         *  'file-description'
         *  'file-order'
         * )
         */
        $this->errors = array();

        if( is_null( $this->post_id ) ) {
            $this->errors['post-id'] = __( 'Non è stato indicato un post valido a cui associare l\'allegato', 'secure-attachments' );
            return false;
        }
        if( ! get_post_status( $this->post_id ) ) {
            $this->errors['post-id'] = sprintf( __( 'il post con ID %s non è un post valido', 'secure-attachments' ), $this->post_id );
            return false;
        }
        if( wp_check_post_lock( $this->post_id ) ) {
            $this->errors['post-id'] = sprintf( __( 'il post con ID %s è in uso da parte di un altro utente', 'secure-attachments' ), $this->post_id );
            return false;
        }
        if( ! isset($params['file-title'] ) ) {
            $this->errors['file-title'] = __( 'Non è stato indicato un titolo per il nuovo allegato', 'secure-attachments' );
            return false;
        }

        $this->updateAttachments();

        $upload_directory = $this->getUploadDir();
        $sanitized_filename = sanitize_file_name( $file['name'] );
        $destination = $upload_directory . $sanitized_filename;
        if( file_exists($destination)) {
            $this->errors['file-name'] = sprintf( __( 'il file %s esiste già sul server. caricare il file con nome diverso o cancellare il file esistente', 'secure-attachments' ), $sanitized_filename );
            return false;
        }
        if( ! file_exists( $upload_directory )) {
            mkdir( $upload_directory, 0777, true );
        }
        if( ! $this->move_uploaded_file( $file['tmp_name'], $destination ) ) {
            return false;
        }
        if( ! $this->saveMetaInformationUploadedFile( $file, $params) ) {
            unlink( $destination );
            return false;
        }

        return true;

    }

    public function  getValidationErrors() {
        return $this->validationErrors;
    }

    public function  getErrors() {
        return $this->errors;
    }

    public function getAttachments() {
        return $this->attachments;
    }

    public function getUploadedFileAsArrayOfFile( $files ) {
        $newFilesArray =  array();
        foreach( $files as $key => $values ) {
            foreach( $values as $index => $value ) {
                $newFilesArray[$index][$key] = $value;
            }
        }
        return $newFilesArray;
    }

    public function removeAttachment( $filename ) {
        $check = $this->post_id;
        $check2 = get_post_status( $check );

        if( is_null( $this->post_id ) ) {
            $this->errors['post-id'] = __( 'Non è stato indicato un post valido a cui associare l\'allegato', 'secure-attachments' );
            return false;
        }
        if( ! get_post_status( $this->post_id ) ) {
            $this->errors['post-id'] = sprintf( __( 'il post con ID %s non è un post valido', 'secure-attachments' ), $this->post_id );
            return false;
        }
        if( wp_check_post_lock( $this->post_id ) ) {
            $this->errors['post-id'] = sprintf( __( 'il post con ID %s è in uso da parte di un altro utente', 'secure-attachments' ), $this->post_id );
            return false;
        }
        $upload_directory = $this->getUploadDir();
        $destination = $upload_directory . $filename;
        if( ! file_exists( $destination ) ) {
            $this->errors['file-name'] = sprintf( __( 'impossibile cancellare il file %s, il file non esiste sul server', 'secure-attachments' ), $filename );
            return false;
        }
        if( ! unlink( $destination ) ) {
            $this->errors['file-name'] = sprintf( __( 'impossibile cancellare il file %s', 'secure-attachments' ), $filename );
        }
        $this->removeMetaInformationAttachmentFile( $filename );

        return true;

    }

    public function userCanAccessAttachment( $filename, $password = null ) {
        if( current_user_can( 'edit_post', $this->post_id ) ) {
            return true;
        }
        return false;
    }

    protected function move_uploaded_file( $source, $destination ) {
        if( ! move_uploaded_file( $source, $destination ) ) {
            return false;
        }
        return true;
    }

    private function saveMetaInformationUploadedFile( $file, $params = array() ) {
        if( is_null($this->post_id) ) {
            return false;
        }
        if( ! get_post_status ( $this->post_id ) ) {
            return false;
        }
        if( ! isset($params['file-title'] ) ) {
            return false;
        }
        $new_file_metadata = $this->buildMetaInformationFile( $file, $params );
        $this->addNewAttachmentMetaInformationFile($new_file_metadata);
        $this->saveAttachments();

        return true;

    }

    private function removeMetaInformationAttachmentFile( $filename ) {
        if( is_null($this->post_id) ) {
            return false;
        }
        if( ! get_post_status ( $this->post_id ) ) {
            return false;
        }
        $index = 0;
        foreach( $this->attachments as $attachment ) {
            if( $attachment['file-name'] == $filename ) {
                break;
            }
            $index++;
        }
        array_splice( $this->attachments, $index, 1 );
        $this->saveAttachments();

        return true;

    }

    private function buildMetaInformationFile( $file, $params = array() ) {
        $file_metadata = array(
            'file-title' => '',
            'file-name' => $file['name'],
            'file-size' => $file['size'],
            'file-type' => $file['type'],
            'file-description' => '',
            'file-order' => 0,
        );
        if( isset($params['file-title'] ) ) {
            $file_metadata['file-title'] = $params['file-title'];
        }
        if( isset($params['file-description'] ) ) {
            $file_metadata['file-description'] = $params['file-description'];
        }
        if( isset($params['file-order'] ) ) {
            $file_metadata['file-order'] = $params['file-order'];
        }
        return $file_metadata;
    }

    private function addNewAttachmentMetaInformationFile( $new_file_metadata ) {
        if( ! isset( $new_file_metadata['file-order'] )
            || 0 == $new_file_metadata['file-order']
            || 0 == count( $this->attachments ) )  {
            $this->attachments[] = $new_file_metadata;
            return;
        }

        $index = 0;
        foreach( $this->attachments as $attachment ) {
            if( $attachment['file-order'] > $new_file_metadata['file-order']
                || $attachment['file-order'] == 0) {
                break;
            }
            $index++;
        }
        array_splice( $this->attachments, $index, 0, array($new_file_metadata) );
    }

    private function saveAttachments() {
        if( 0 == count( $this->attachments ) ) {
            delete_post_meta( $this->post_id, 'secure-attachments-list' );
            return;
        }
        update_post_meta( $this->post_id, 'secure-attachments-list', $this->attachments );
    }

    private function updateAttachments() {
        $this->attachments = array();
        if( ! empty( $this->post_id ) && is_int( (int) $this->post_id) ) {
            $this->attachments = get_post_meta( $this->post_id, 'secure-attachments-list', true);
        }
        if( empty( $this->attachments) ) {
            $this->attachments = array();
        }
    }

}