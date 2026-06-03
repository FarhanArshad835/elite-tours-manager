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

    // Honeypot, bots fill the "website" field; humans never see it
    if ( ! empty( $_POST['website'] ) ) {
        wp_send_json_success( 'Thank you' ); // pretend success, drop silently
    }

    // Name: prefer `name`, fall back to first_name + last_name (contact page)
    $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
    if ( $name === '' ) {
        $first = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
        $last  = sanitize_text_field( wp_unslash( $_POST['last_name']  ?? '' ) );
        $name  = trim( $first . ' ' . $last );
    }

    $email   = sanitize_email(     wp_unslash( $_POST['email']   ?? '' ) );
    $phone   = sanitize_text_field( wp_unslash( $_POST['phone']   ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
    $exp     = sanitize_text_field( wp_unslash( $_POST['experience'] ?? '' ) );
    $exp_url = esc_url_raw(         wp_unslash( $_POST['experience_url'] ?? '' ) );

    // Contact-page extras, all optional. Funnel form doesn't send these.
    $location     = sanitize_text_field( wp_unslash( $_POST['location']     ?? $_POST['travelling_from'] ?? '' ) );
    $travellers   = sanitize_text_field( wp_unslash( $_POST['travellers']   ?? '' ) );
    $dates        = sanitize_text_field( wp_unslash( $_POST['dates']        ?? '' ) );
    $journey_type = sanitize_text_field( wp_unslash( $_POST['journey_type'] ?? '' ) );
    $interests_in = $_POST['interests'] ?? [];
    $interests    = [];
    if ( is_array( $interests_in ) ) {
        foreach ( $interests_in as $i ) {
            $clean = sanitize_text_field( wp_unslash( $i ) );
            if ( $clean !== '' ) $interests[] = $clean;
        }
    }

    if ( $name === '' || $email === '' || $message === '' ) {
        wp_send_json_error( 'Please fill in name, email, and message.', 400 );
    }
    if ( ! is_email( $email ) ) {
        wp_send_json_error( 'That email address looks invalid.', 400 );
    }

    // Recipient = site_settings contact_email, fall back to admin_email
    $site = get_option( 'et_site_settings', [] );
    $to   = ! empty( $site['contact_email'] ) ? $site['contact_email'] : get_option( 'admin_email' );

    $subject = sprintf( '[Elite Tours] Enquiry, %s', $exp ?: 'General' );
    $body    = "New enquiry from the website.\n\n";
    $body   .= "Experience: " . ( $exp ?: 'General contact' ) . "\n";
    if ( $exp_url ) $body .= "Page:       $exp_url\n";
    $body   .= "\nName:       $name\n";
    $body   .= "Email:      $email\n";
    if ( $phone )        $body .= "Phone:      $phone\n";
    if ( $location )     $body .= "From:       $location\n";
    if ( $travellers )   $body .= "Travellers: $travellers\n";
    if ( $dates )        $body .= "Dates:      $dates\n";
    if ( $journey_type ) $body .= "Journey:    $journey_type\n";
    if ( $interests )    $body .= "Interests:  " . implode( ', ', $interests ) . "\n";
    $body   .= "\nMessage:\n$message\n";

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
    ];
    wp_mail( $to, $subject, $body, $headers );

    // Log lead to options for admin review (capped to last 200)
    $leads   = get_option( 'et_funnel_leads', [] );
    if ( ! is_array( $leads ) ) $leads = [];
    $lead    = [
        'time'       => current_time( 'mysql' ),
        'experience' => $exp,
        'page_url'   => $exp_url,
        'name'       => $name,
        'email'      => $email,
        'phone'      => $phone,
        'message'    => $message,
    ];
    if ( $location )     $lead['location']     = $location;
    if ( $travellers )   $lead['travellers']   = $travellers;
    if ( $dates )        $lead['dates']        = $dates;
    if ( $journey_type ) $lead['journey_type'] = $journey_type;
    if ( $interests )    $lead['interests']    = $interests;
    $leads[] = $lead;
    if ( count( $leads ) > 200 ) {
        $leads = array_slice( $leads, -200 );
    }
    update_option( 'et_funnel_leads', $leads );

    wp_send_json_success( 'Thanks, we\'ll be in touch shortly.' );
}
