<?php

class Secure_Attachment {

    private $file_name;
    private $file_title;
    private $file_size;
    private $file_type;
    private $order;
    private $description;

    function __construct( $file_data = array() ) {
        $this->file_id = '';
        $this->file_name = '';
        $this->file_title = '';
        $this->file_size = 0;
        $this->file_type = '';
        $this->order = 0;
        $this->description = '';

        if( isset( $file_data['file-name'] ) ) {
            $this->file_name = $file_data['file-name'];
        }
        if( isset( $file_data['file-title'] ) ) {
            $this->file_name = $file_data['file-title'];
        }
        if( isset( $file_data['file-size'] ) ) {
            $this->file_name = $file_data['file-size'];
        }
        if( isset( $file_data['file-type'] ) ) {
            $this->file_name = $file_data['file-type'];
        }
        if( isset( $file_data['order'] ) ) {
            $this->file_name = $file_data['order'];
        }
        if( isset( $file_data['description'] ) ) {
            $this->file_name = $file_data['description'];
        }
    }



}