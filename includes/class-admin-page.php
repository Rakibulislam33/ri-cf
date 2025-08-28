<?php
class RICF_Admin_Page {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
    }

    public function register_menu() {
        add_menu_page( 'RI Contact Form', 'RI Contact Form', 'manage_options', 'ri-cf', array( $this, 'render_page' ) );
    }

    public function render_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'ri_cf_entries';
        $results = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );
        
        echo '<div class="wrap"><h2>Form Submissions</h2><table class="widefat"><thead><tr><th>Name</th><th>Email</th><th>Message</th><th>Date</th></tr></thead><tbody>';
        foreach ( $results as $row ) {
            echo "<tr><td>{$row->name}</td><td>{$row->email}</td><td>{$row->message}</td><td>{$row->created_at}</td></tr>";
        }
        echo '</tbody></table></div>';
    }
}
