<?php
class RICF_Loader {
    public function init() {
        require_once RICF_PATH . 'includes/class-form-handler.php';
        require_once RICF_PATH . 'includes/class-admin-page.php';
        require_once RICF_PATH . 'public/class-shortcode.php';
    
        new RICF_Form_Handler();
        new RICF_Admin_Page();
        new RICF_Shortcode();
    }
}
