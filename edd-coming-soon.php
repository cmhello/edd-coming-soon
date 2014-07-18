<?php
/*
Plugin Name: EDD Coming Soon
Plugin URI: http://sumobi.com/shop/edd-coming-soon/
Description: Allows "custom status" downloads (not available for purchase) in Easy Digital Downloads
Version: 1.2
Author: Andrew Munro, Sumobi
Author URI: http://sumobi.com/
Contributors: sc0ttkclark
License: GPL-2.0+
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

// Plugin constants
if ( !defined( 'EDD_COMING_SOON' ) )
	define( 'EDD_COMING_SOON', '1.2' );

if ( !defined( 'EDD_COMING_SOON_URL' ) )
	define( 'EDD_COMING_SOON_URL', plugin_dir_url( __FILE__ ) );

if ( !defined( 'EDD_COMING_SOON_DIR' ) )
	define( 'EDD_COMING_SOON_DIR', plugin_dir_path( __FILE__ ) );

/* Enable the voting feature */
if ( !defined( 'EDD_COMING_SOON_VOTE_ENABLE' ) )
	define( 'EDD_COMING_SOON_VOTE_ENABLE', apply_filters( 'edd_cs_vote_enable', true ) );

/* Enable the voting feature when in the shortcode */
if ( !defined( 'EDD_COMING_SOON_VOTE_SHORTCODE' ) )
	define( 'EDD_COMING_SOON_VOTE_SHORTCODE', apply_filters( 'edd_cs_vote_shortcode_enable', false ) );


/**
 * Internationalization
 *
 * @since 1.0
 */
function edd_coming_soon_textdomain() {

	load_plugin_textdomain( 'edd-coming-soon', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

}
add_action( 'init', 'edd_coming_soon_textdomain' );


/**
 * Check if it's a Custom Status download
 *
 * @param int $download_id 	Download Post ID
 *
 * @return boolean 			Whether Custom Status is active
 *
 * @since 1.0
 */
function edd_coming_soon_is_active( $download_id = 0 ) {
	global $post;

	if ( empty( $download_id ) && is_object( $post ) && isset( $post->ID ) )
		$download_id = $post->ID;

	if ( !empty( $download_id ) )
		return (boolean) get_post_meta( $download_id, 'edd_coming_soon', true );

	return false;
}


/**
 * Render the Custom Status checkbox
 *
 * @param int 	$post_id Post ID
 *
 * @since 1.0
 */
function edd_coming_soon_render_option( $post_id ) {

	$coming_soon      = (boolean) get_post_meta( $post_id, 'edd_coming_soon', true );
	$coming_soon_text = get_post_meta( $post_id, 'edd_coming_soon_text', true );
	$count            = intval( get_post_meta( $post_id, '_edd_coming_soon_votes', true ) );

	// Default
	$default_text = apply_filters( 'edd_cs_coming_soon_text', __( 'Coming Soon', 'edd-coming-soon' ) );
?>
	<p>
		<label for="edd_coming_soon">
			<input type="checkbox" name="edd_coming_soon" id="edd_coming_soon" value="1" <?php checked( true, $coming_soon ); ?> />
			<?php _e( 'Enable Coming Soon / Custom Status download', 'edd-coming-soon' ); ?>
		</label>
	</p>

	<div id="edd_coming_soon_container"<?php echo $coming_soon ? '' : ' style="display:none;"'; ?>>
		<p>
			<label for="edd_coming_soon_text">
				<input type="text" name="edd_coming_soon_text" id="edd_coming_soon_text" size="45" style="width:110px;" value="<?php echo esc_attr( $coming_soon_text ); ?>" />
				<?php echo sprintf( __( 'Custom Status text (default: <em>%s</em>)', 'edd-coming-soon' ), $default_text ); ?>
			</label>
		</p>

		<?php if( true === EDD_COMING_SOON_VOTE_ENABLE ): ?>
			<h3><?php _e( 'Customer\'s Opinion', 'edd-coming-soon' ); ?></h3>
			<p><?php printf( __( '%s people want this %s.', 'edd-coming-soon' ), "<code>$count</code>", edd_get_label_singular() ); ?></p>
		<?php endif; ?>
	</div>
<?php
}
add_action( 'edd_meta_box_fields', 'edd_coming_soon_render_option', 10 );


/**
 * Hook into EDD save filter and add the download image fields
 *
 * @param array $fields 	Array of fields to save for EDD
 *
 * @return array 			Array of fields to save for EDD
 *
 * @since 1.0
 */
function edd_coming_soon_metabox_fields_save( $fields ) {

	$fields[] = 'edd_coming_soon';
	$fields[] = 'edd_coming_soon_text';

	return $fields;

}
add_filter( 'edd_metabox_fields_save', 'edd_coming_soon_metabox_fields_save' );


/**
 * Append custom status text to normal prices and price ranges within the admin price column
 *
 * @return string	The text to display
 *
 * @since 1.2
 */
function edd_coming_soon_admin_price_column( $price, $download_id ) {

	$price .= '<br />' . edd_coming_soon_get_custom_status_text();

	return $price;

}
add_filter( 'edd_download_price', 'edd_coming_soon_admin_price_column', 20, 2 );
add_filter( 'edd_price_range', 'edd_coming_soon_admin_price_column', 20, 2 );


/**
 * Get the custom status text
 *
 * @return string	The custom status text or default 'Coming Soon' text
 *
 * @since 1.2
 */
function edd_coming_soon_get_custom_status_text() {

	if ( ! edd_coming_soon_is_active( get_the_ID() ) )
		return;

	$custom_text = get_post_meta( get_the_ID(), 'edd_coming_soon_text', true );
	$custom_text = !empty ( $custom_text ) ? $custom_text : apply_filters( 'edd_cs_coming_soon_text', __( 'Coming Soon', 'edd-coming-soon' ) );

	// either the custom status or default 'Coming Soon' text

	// admin colum text
	if ( is_admin() )
		return apply_filters( 'edd_coming_soon_display_admin_text', '<strong>' . $custom_text . '</strong>' );
	else
	// front-end text.
		return apply_filters( 'edd_coming_soon_display_text', '<p><strong>' . $custom_text . '</strong></p>' );
}


/**
 * Display the coming soon text. Hooks onto bottom of shortcode.
 * Hook this function to wherever you want it to display
 *
 * @since 1.2
 */
function edd_coming_soon_display_text() {

	echo edd_coming_soon_get_custom_status_text();

}
add_action( 'edd_download_after', 'edd_coming_soon_display_text' );


/**
 * Append coming soon text after main content on single download pages
 *
 * @return $content The main post content
 * @since 1.2
*/
function edd_coming_soon_single_download( $content ) {

	if ( is_singular( 'download' ) && is_main_query() ) {
		return $content . edd_coming_soon_get_custom_status_text();
	}

	return $content;

}
add_filter( 'the_content', 'edd_coming_soon_single_download' );



/**
 * Remove the purchase form if it's not a Custom Status download
 * Purchase form includes the buy button and any options if it's variable priced
 *
 * @param string  $purchase_form Form HTML
 * @param array   $args          Arguments for display
 *
 * @return string Form HTML
 *
 * @since 1.0
 */
function edd_coming_soon_purchase_download_form( $purchase_form, $args ) {

	global $post;

	if ( edd_coming_soon_is_active( $args[ 'download_id' ] ) ) {

		if( true === EDD_COMING_SOON_VOTE_ENABLE ) {

			/* Display the voting form on single page */
			if( is_single( $post ) && 'download' == $post->post_type ) {

				return edd_coming_soon_get_vote_form();

			} else {

				/* Only display the form in the download shortcode if enabled */
				if( true === EDD_COMING_SOON_VOTE_SHORTCODE ) {
					return edd_coming_soon_get_vote_form();
				} else {
					return '';
				}
			}

		} else {
			return '';
		}

	}

	return $purchase_form;
}
add_filter( 'edd_purchase_download_form', 'edd_coming_soon_purchase_download_form', 10, 2 );


/**
 * Prevent download from being added to cart (free or priced) with ?edd_action=add_to_cart&download_id=XXX
 *
 * @param int	$download_id Download Post ID
 *
 * @since 1.0
 */
function edd_coming_soon_pre_add_to_cart( $download_id ) {

	if ( edd_coming_soon_is_active( $download_id ) ) {
		$add_text = apply_filters( 'edd_coming_soon_pre_add_to_cart', __( 'This download cannot be purchased', 'edd-coming-soon' ), $download_id );

		wp_die( $add_text, '', array( 'back_link' => true ) );
	}

}
add_action( 'edd_pre_add_to_cart', 'edd_coming_soon_pre_add_to_cart' );


/**
 * Scripts
 *
 * @since 1.0
 */
function edd_coming_soon_admin_scripts( $hook ) {

	global $post;

	if ( is_object( $post ) && $post->post_type != 'download' ) {
		return;
	}

	wp_enqueue_script( 'edd-cp-admin-scripts', EDD_COMING_SOON_URL . 'js/edd-coming-soon-admin.js', array( 'jquery' ), EDD_COMING_SOON );

}
add_action( 'admin_enqueue_scripts', 'edd_coming_soon_admin_scripts' );

add_action( 'init', 'edd_coming_soon_increment_votes' );
/**
 * Increment the votes count.
 *
 * Adds one more vote for the current "coming soon" product.
 *
 * @since   1.3.0
 * @return  Status of the update
 */
function edd_coming_soon_increment_votes() {

	if ( !isset( $_POST['edd_cs_pid'] ) || !isset( $_POST['edd_cs_nonce'] ) || !wp_verify_nonce( $_POST['edd_cs_nonce'], 'vote' ) )
		return false;

	$product_id = isset( $_POST['edd_cs_pid'] ) ? intval( $_POST['edd_cs_pid'] ) : false;

	if ( false === $product_id )
		return false;

	/* Get current votes count */
	$current = $new = intval( get_post_meta( $product_id, '_edd_coming_soon_votes', true ) );

	/* Increment the count */
	++$new;

	/* Update post meta */
	$update = update_post_meta( $product_id, '_edd_coming_soon_votes', $new, $current );

	/* Set a cookie to prevent multiple votes */
	if( false !== $update )
		setcookie( "edd_cs_vote_$product_id", '1', time() + 60*60*30, '/' );

	$redirect = get_permalink( $product_id );

	/* Read-only redirect (to avoid resubmissions on page refresh) */
	wp_redirect( $redirect );
	exit;

}

/**
 * Get the voting form.
 *
 * The form will record a new vote for the current product.
 *
 * @since  1.3.0
 * @return string Form markup
 */
function edd_coming_soon_get_vote_form() {

	global $post;

	$voted       = isset( $_COOKIE['edd_cs_vote_' . $post->ID] ) ? true : false;
	$description = apply_filters( 'edd_cs_vote_description', sprintf( __( 'Tell the developer you want this %s and we will notify him/her of your interest.', 'edd-coming-soon' ), edd_get_label_singular( true ) ) );
	$submission  = apply_filters( 'edd_cs_vote_submission', sprintf( __( 'I want this %s', 'edd-coming-soon' ), edd_get_label_singular( true ) ) );
	?>

	<?php if( true === $voted ): ?>

		<p><?php printf( __( 'We heard you! Your interest for this %s was duly noted.', 'edd-coming-soon' ), edd_get_label_singular( true ) ); ?></p>

	<?php else: ?>

		<form role="form" method="post" action="<?php echo get_permalink( $post->ID ); ?>" class="edd-coming-soon-vote-form">
			<p class="edd-cs-vote-description"><?php echo $description; ?></p>
			<input type="hidden" name="edd_cs_pid" value="<?php echo $post->ID; ?>">
			<?php wp_nonce_field( 'vote', 'edd_cs_nonce', false, true ); ?>
			<button type="submit" class="edd-coming-soon-vote-btn" name="edd_cs_vote"><span class="dashicons dashicons-heart"></span> <?php echo $submission; ?></button>
		</form>

	<?php endif; ?>

<?php }

/**
 * Votes dashboard widget.
 *
 * Displays the total number of votes for each
 * "coming soon" product.
 *
 * @since  1.3.0 
 * @return void
 */
function edd_coming_soon_votes_widget() {
	
	$args = array(
		'post_type'              => 'download',
		'post_status'            => 'any',
		'meta_key'               => '_edd_coming_soon_votes',
		'orderby'                => 'meta_value_num',
		'order'                  => 'DESC',
		'no_found_rows'          => false,
		'cache_results'          => false,
		'update_post_term_cache' => false,
		'update_post_meta_cache' => false,
		'meta_query'             => array(
			array(
				'key'     => 'edd_coming_soon',
				'value'   => '1',
				'type'    => 'CHAR',
				'compare' => '='
			)
		)	
	);
	
	$query = new WP_Query( $args );

	if( !empty( $query->posts ) ) {

		$alternate = ''; ?>

		<table class="widefat">
			<thead>
				<tr>
					<th width="80%"><?php echo edd_get_label_singular(); ?></th>
					<th width="20%"><?php _e( 'Votes', 'edd-coming-soon' ); ?></th>
				</tr>
			</thead>

			<?php foreach( $query->posts as $post ):

				$votes     = intval( get_post_meta( $post->ID, '_edd_coming_soon_votes', true ) );
				$alternate = ( '' == $alternate ) ? 'class="alternate"' : '';
				?>

				<tr <?php echo $alternate; ?>>
					<td><?php echo $post->post_title; ?></td>
					<td style="text-align:center;"><?php echo $votes; ?></td>
				</td>

			<?php endforeach; ?>

		</table>

		<p><small><?php printf( __( '%s with no votes won\'t appear in the above list.', 'edd-coming-soon' ), edd_get_label_plural() ); ?></small></p>

	<?php } else {

		printf( __( 'Either there are no &laquo;Coming Soon&raquo; %s in the shop at the moment, or none of them got voted for.', 'edd-coming-soon' ), edd_get_label_plural( true ) );

	}

}

add_action( 'wp_dashboard_setup', 'edd_coming_soon_votes_add_widget' );

/**
 * Add a dashboard widget for votes.
 *
 * @since  1.3.0
 */
function edd_coming_soon_votes_add_widget() {

	if( true === EDD_COMING_SOON_VOTE_ENABLE )
		wp_add_dashboard_widget( 'edd_coming_soon_votes_widget', sprintf( __( 'Most Wanted Coming Soon %s', 'edd-coming-soon' ), edd_get_label_plural() ), 'edd_coming_soon_votes_widget' );
}

add_action( 'wp_footer', 'edd_coming_soon_voting_progress' );

/**
 * Add voting progress.
 *
 * This replaces the vote button label during
 * the form submission in order to clearly show
 * the visitor that his vote is being taken into account.
 *
 * @since  1.3.0
 * @return void
 */
function edd_coming_soon_voting_progress() {

	if ( wp_script_is( 'jquery', 'done' ) ):

		$voting = apply_filters( 'edd_cs_voting_text', __( 'Voting...', 'edd-coming-soon' ) ); ?>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('.edd-coming-soon-vote-btn').on('click', function() {
					$(this).text('<?php echo $voting; ?>');
				});
			});
		</script>

	<?php endif;
}