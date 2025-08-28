<?php
class RICF_Deactivator {
    public static function deactivate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ri_cf_entries';
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    }
}
