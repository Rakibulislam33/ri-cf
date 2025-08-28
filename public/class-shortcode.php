<?php
class RICF_Shortcode {
    public function __construct() {
        add_shortcode( 'ri_contact_form', array( $this, 'render_contact_form' ) );
    }

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
}
