<?php
class RICF_Form_Handler {
    private $version;
    private $table_name;
    public function __construct( $version = '1.1.0' ) {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ri_cf_entries';
        $this->version = $version;

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_ri_cf_submit', array( $this, 'handle_ajax_submit' ) );
        add_action( 'wp_ajax_nopriv_ri_cf_submit', array( $this, 'handle_ajax_submit' ) );
    }

    public function enqueue_assets() {
        //wp_enqueue_script( 'ricf-form', RICF_URL . 'assets/js/form.js', array('jquery'), $this->version, true );
        wp_enqueue_style( 'ricf-style', RICF_URL . 'assets/css/ri-cf.css',array(), $this->version );
        wp_enqueue_script( 'ri-cf-script', RICF_URL . 'assets/js/ri-cf.js', array('jquery'), $this->version, true );
        
        wp_localize_script( 'ri-cf-script', 'ri_cf_obj', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ri-cf-nonce' ),
        ) );



        // Optionally include SweetAlert2 from CDN for nicer modals (you may replace with custom modal)
        wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true );
    }

    /**
         * Handle AJAX submission
         */
        public function handle_ajax_submit() {
            // Only accept POST
            if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
                wp_send_json_error( array( 'message' => 'Invalid request method' ), 405 );
            }

            // Check nonce
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
            if ( ! wp_verify_nonce( $nonce, 'ri-cf-nonce' ) ) {
                wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
            }

            // Sanitize and validate fields
            $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
            $email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
            $phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
            $message = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';

            $errors = array();

            if ( empty( $name ) ) {
                $errors[] = 'Name is required.';
            }

            if ( empty( $email ) || ! is_email( $email ) ) {
                $errors[] = 'A valid email is required.';
            }

            if ( empty( $message ) ) {
                $errors[] = 'Message is required.';
            }

            if ( ! empty( $errors ) ) {
                wp_send_json_error( array( 'message' => implode( ' ', $errors ) ) );
            }

            // Insert into DB
            global $wpdb;
            $table_name = $wpdb->prefix . 'ri_cf_entries';
            
            error_log( 'TABLE NAME USED: ' . $table_name );

            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'name'       => $name,
                    'email'      => $email,
                    'phone'      => $phone,
                    'message'    => $message,
                    'created_at' => current_time( 'mysql' ),
                ),
                array( '%s', '%s', '%s', '%s', '%s' )
            );

            if ( $inserted ) {
                wp_send_json_success( array( 'message' => 'Thanks! Your message was submitted.' ) );
            } else {
                wp_send_json_error( array( 'message' => 'Database error, please try again.' ) );
            }
        }
}
