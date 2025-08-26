<?php
/**
 * Plugin Name: RI Contact Form
 * Plugin URI:  https://example.com/
 * Description: Simple OOP contact form plugin with DB table, AJAX submission, validation and admin entries list.
 * Version:     1.0.0
 * Author:      Rakibul Islam
 * Author URI:  https://rakibulislam33.github.io/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: ri-cf
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'RI_CF_Plugin' ) ) {

    class RI_CF_Plugin {

        /**
         * Table name will be $wpdb->prefix . 'ri_cf_entries'
         */
        private $table_name;
        private static $instance = null;
        private $version = '1.0.0';

        public static function init() {
            if ( self::$instance === null ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            global $wpdb;
            $this->table_name = $wpdb->prefix . 'ri_cf_entries';

            // Activation hook
            register_activation_hook( __FILE__, array( $this, 'activate' ) );

            // Shortcode
            add_shortcode( 'ri_contact_form', array( $this, 'render_contact_form' ) );

            // Enqueue scripts/styles
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

            // AJAX handlers (both for guests and logged in)
            add_action( 'wp_ajax_ri_cf_submit', array( $this, 'handle_ajax_submit' ) );
            add_action( 'wp_ajax_nopriv_ri_cf_submit', array( $this, 'handle_ajax_submit' ) );

            // Admin menu
            add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );

            // Admin assets
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );

            // Maybe localization in future
            load_plugin_textdomain( 'ri-cf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        }

        /**
         * Activation: create DB table
         */
        public function activate() {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();

            $table = $this->table_name;

            $sql = "CREATE TABLE IF NOT EXISTS {$table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                email varchar(191) NOT NULL,
                phone varchar(50) DEFAULT NULL,
                message text NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY email (email)
            ) {$charset_collate};";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );

            // store plugin version
            add_option( 'ri_cf_version', $this->version );
        }

        /**
         * Enqueue front-end scripts + styles
         */
        public function enqueue_assets() {
            // Only enqueue if shortcode present? For simplicity enqueue always; you can optimize later.
            wp_enqueue_style( 'ri-cf-style', plugin_dir_url( __FILE__ ) . 'assets/css/ri-cf.css', array(), $this->version );

            wp_enqueue_script( 'ri-cf-script', plugin_dir_url( __FILE__ ) . 'assets/js/ri-cf.js', array( 'jquery' ), $this->version, true );

            // Localize AJAX url & nonce to script
            wp_localize_script( 'ri-cf-script', 'ri_cf_obj', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ri-cf-nonce' ),
            ) );

            // Optionally include SweetAlert2 from CDN for nicer modals (you may replace with custom modal)
            wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11', true );
        }

        /**
         * Render the contact form (shortcode)
         */
        public function render_contact_form( $atts = array() ) {
            $atts = shortcode_atts( array(
                'title' => 'Contact Us'
            ), $atts, 'ri_contact_form' );

            ob_start();
            ?>
            <div class="ri-cf-wrap">
                <h3><?php echo esc_html( $atts['title'] ); ?></h3>
                <form id="ri-cf-form" method="post" action="">
                    <?php wp_nonce_field( 'ri_cf_frontend', 'ri_cf_nonce_field' ); ?>
                    <div class="ri-cf-row">
                        <label for="ri_name">Name <span class="required">*</span></label>
                        <input type="text" id="ri_name" name="name" required>
                    </div>

                    <div class="ri-cf-row">
                        <label for="ri_email">Email <span class="required">*</span></label>
                        <input type="email" id="ri_email" name="email" required>
                    </div>

                    <div class="ri-cf-row">
                        <label for="ri_phone">Phone</label>
                        <input type="text" id="ri_phone" name="phone">
                    </div>

                    <div class="ri-cf-row">
                        <label for="ri_message">Message <span class="required">*</span></label>
                        <textarea id="ri_message" name="message" rows="5" required></textarea>
                    </div>

                    <input type="hidden" name="action" value="ri_cf_submit">
                    <button type="submit" id="ri-cf-submit">Send Message</button>
                </form>
            </div>
            <?php
            return ob_get_clean();
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
            $table = $this->table_name;

            $inserted = $wpdb->insert(
                $table,
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

        /**
         * Register admin menu page
         */
        public function register_admin_menu() {
            add_menu_page(
                'RI Contact Form',
                'RI Contact Form',
                'manage_options',
                'ri-cf-entries',
                array( $this, 'admin_entries_page' ),
                'dashicons-email',
                26
            );
        }

        /**
         * Enqueue admin assets (for admin page only)
         */
        public function admin_enqueue_assets( $hook ) {
            if ( $hook !== 'toplevel_page_ri-cf-entries' ) {
                return;
            }
            wp_enqueue_style( 'ri-cf-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin-ri-cf.css', array(), $this->version );
            wp_enqueue_script( 'ri-cf-admin-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin-ri-cf.js', array( 'jquery' ), $this->version, true );

            wp_localize_script( 'ri-cf-admin-script', 'ri_cf_admin_obj', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'ri-cf-admin-nonce' ),
            ));
        }

        /**
         * Admin page: list entries
         */
        public function admin_entries_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to view this page.', 'ri-cf' ) );
            }

            global $wpdb;
            $table = $this->table_name;

            // Handle delete action (simple, CSRF protected)
            if ( isset( $_GET['ri_delete'] ) && is_numeric( $_GET['ri_delete'] ) ) {
                $del_nonce = isset( $_GET['_ri_delete_nonce'] ) ? sanitize_text_field( $_GET['_ri_delete_nonce'] ) : '';
                if ( wp_verify_nonce( $del_nonce, 'ri-delete-entry' ) ) {
                    $del_id = intval( $_GET['ri_delete'] );
                    $wpdb->delete( $table, array( 'id' => $del_id ), array( '%d' ) );
                    echo '<div class="updated notice"><p>Entry deleted.</p></div>';
                } else {
                    echo '<div class="error notice"><p>Invalid nonce for delete.</p></div>';
                }
            }

            $entries = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100" ); // limit for safety

            ?>
            <div class="wrap">
                <h1>RI Contact Form — Entries</h1>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="18%">Name</th>
                            <th width="20%">Email</th>
                            <th width="12%">Phone</th>
                            <th>Message</th>
                            <th width="12%">Submitted</th>
                            <th width="7%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $entries ) : ?>
                            <?php foreach ( $entries as $entry ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $entry->id ); ?></td>
                                    <td><?php echo esc_html( $entry->name ); ?></td>
                                    <td><?php echo esc_html( $entry->email ); ?></td>
                                    <td><?php echo esc_html( $entry->phone ); ?></td>
                                    <td><?php echo esc_html( wp_trim_words( $entry->message, 30, '...' ) ); ?></td>
                                    <td><?php echo esc_html( $entry->created_at ); ?></td>
                                    <td>
                                        <a class="button" href="<?php echo esc_url( add_query_arg( array( 'ri_delete' => $entry->id, '_ri_delete_nonce' => wp_create_nonce( 'ri-delete-entry' ) ) ) ); ?>" onclick="return confirm('Delete this entry?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="7">No entries yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
    }

    // Initialize the plugin.
    RI_CF_Plugin::init();
}
