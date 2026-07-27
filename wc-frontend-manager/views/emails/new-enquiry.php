<?php









if ( ! defined( 'ABSPATH' ) ) {
	return;  
}

global $WCFM;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( 'Hi,', 'wc-frontend-manager' ); ?></p>

<?php do_action( 'wcfm_enquiry_email_before', $enquiry_id ); ?>

<p><?php printf( esc_html__( 'You have a recent inquiry for %s.', 'wc-frontend-manager' ), $enquiry_for ); ?></p>

<?php do_action( 'wcfm_enquiry_email_before_enquiry', $enquiry_id ); ?>

<blockquote><strong><i><?php echo wpautop( wptexturize( make_clickable( $enquiry ) ) );  ?></i></strong></blockquote>

<?php do_action( 'wcfm_enquiry_email_after_enquiry', $enquiry_id ); ?>

<?php
if ( $additional_info ) {
	do_action( 'wcfm_enquiry_email_before_additonal_info', $enquiry_id );
	echo '<p>' . wp_kses_post( wpautop( wptexturize( $additional_info ) ) ) . '<br/></p>';
	do_action( 'wcfm_enquiry_email_after_additonal_info', $enquiry_id );
}
?>

<p><?php printf( esc_html__( 'To respond this Inquiry, please %1$sClick Here%2$s.', 'wc-frontend-manager' ), '<a href="' . $enquiry_url . '">', '</a>' ); ?></p>

<?php do_action( 'wcfm_enquiry_email_after', $enquiry_id ); ?>

<p><?php esc_html_e( 'Thank You', 'wc-frontend-manager' ); ?></p>
										 

<?php do_action( 'woocommerce_email_footer', $email ); ?>
