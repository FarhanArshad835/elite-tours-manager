<?php
/**
 * Funnel Contact Form
 * - AJAX handler for the per-experience funnel contact form.
 * - Validates input, emails the site contact address, logs lead to et_funnel_leads option.
 * - Honeypot field "website" silently rejects bots.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_etm_funnel_submit',        'etm_funnel_submit' );
add_action( 'wp_ajax_nopriv_etm_funnel_submit', 'etm_funnel_submit' );

function etm_funnel_submit(): void {
    check_ajax_referer( 'etm_funnel', '_wpnonce' );

    // Honeypot — bots fill the "website" field; humans never see it
    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_success( 'Thank you' ); // pretend success, drop silently
    }

    $name    = sanitize_text_field( wp_unslash( $_POST['name']    ?? '' ) );
    $email   = sanitize_email(     wp_unslash( $_POST['email']   ?? '' ) );
    $phone   = sanitize_text_field( wp_unslash( $_POST['phone']   ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
    $exp     = sanitize_text_field( wp_unslash( $_POST['experience'] ?? '' ) );
    $exp_url = esc_url_raw(         wp_unslash( $_POST['experience_url'] ?? '' ) );

    if ( $name === '' || $email === '' || $message === '' ) {
        wp_send_json_error( 'Please fill in name, email, and message.', 400 );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'That email address looks invalid.', 400 );
    }

    // Recipient = site_settings contact_email, fall back to admin_email
    $site = get_option( 'et_site_settings', [] );
    $to   = ! empty( $site['contact_email'] ) ? $site['contact_email'] : get_option( 'admin_email' );

    $subject = sprintf( '[Elite Tours] Funnel lead — %s', $exp ?: 'General' );
    $body    = "New enquiry from the website funnel.\n\n";
    $body   .= "Experience: " . ( $exp ?: 'General contact' ) . "\n";
    if ( $exp_url ) $body .= "Page: $exp_url\n";
    $body   .= "\nName:    $name\n";
    $body   .= "Email:   $email\n";
    if ( $phone ) $body .= "Phone:   $phone\n";
    $body   .= "\nMessage:\n$message\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];
    wp_mail( $to, $subject, $body, $headers );

    // Log lead to options for admin review (capped to last 200)
    $leads   = get_option( 'et_funnel_leads', [] );
    if ( ! is_array( $leads ) ) $leads = [];
    $leads[] = [
        'time'       => current_time( 'mysql' ),
        'experience' => $exp,
        'page_url'   => $exp_url,
        'name'       => $name,
        'email'      => $email,
        'phone'      => $phone,
        'message'    => $message,
    ];
    if ( count( $leads ) > 200 ) {
        $leads = array_slice( $leads, -200 );
    }
    update_option( 'et_funnel_leads', $leads );

    wp_send_json_success( 'Thanks — we\'ll be in touch shortly.' );
}
