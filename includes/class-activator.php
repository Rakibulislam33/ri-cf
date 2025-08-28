<?php
class RICF_Activator {
    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ri_cf_entries';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                email varchar(191) NOT NULL,
                phone varchar(50) DEFAULT NULL,
                message text NOT NULL,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY email (email)
            ) {$charset_collate};";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
