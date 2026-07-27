<?php









if ( ! defined( 'ABSPATH' ) ) {
	return;  
}

global $WCFM;

do_action( 'woocommerce_email_header', $email_heading, $email );

$reply_mail_body = '<br/>' . __( 'Hi', 'wc-frontend-manager' ) .
										',<br/><br/>' .
										sprintf( __( 'You have a recent enquiry for %s.', 'wc-frontend-manager' ), '{enquiry_for}' ) .
										'<br/><br/><strong><i>' .
										'"{enquiry}"' .
										'</i></strong><br/><br/>' .
										'{additional_info}' .
										sprintf( __( 'To respond to this Enquiry, please %1$sClick Here%2$s', 'wc-frontend-manager' ), '<a href="{enquiry_url}">', '</a>' ) .
										'<br /><br/>' . __( 'Thank You', 'wc-frontend-manager' ) .
										'<br /><br/>';

echo $reply_mail_body;

do_action( 'woocommerce_email_footer', $email );
