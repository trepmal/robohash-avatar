<?php
/**
 * Plugin Name: RoboHash Avatar
 * Plugin URI: http://trepmal.com/plugins/robohash-avatar/
 * Description: RoboHash characters as default avatars
 * Version: 0.5
 * Author: Kailey Lampert
 * Author URI: http://kaileylampert.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package trepmal/robohash-avatar
 */

$robohash_avatar = new RoboHash_Avatar( );

/**
 * RoboHash_Avatar
 *
 * Primarily a namespace.
 */
class RoboHash_Avatar {

	/**
	 * Hook in
	 */
	function __construct() {
		add_filter( 'avatar_defaults' ,      array( $this, 'avatar_defaults' ) );
		add_filter( 'get_avatar',            array( $this, 'get_avatar' ), 11, 6 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'load-options.php',      array( $this, 'update' ) );
	}

	/**
	 * Add RoboHash option to avatar list
	 *
	 * @param array $avatar_defaults All avatar options.
	 * @return array Avatar options.
	 */
	function avatar_defaults( $avatar_defaults ) {

		$options = get_option( 'robohash_options', array( 'bot' => 'set1', 'bg' => 'bg1' ) );

		$bots  = 'RoboHash (Generated) ';
		$bots .= '<label for="robohash_bot">Body</label> <select id="robohash_bot" name="robohash_bot">';
		$bots .= '<option value="set1"' . selected( $options['bot'], 'set1', false ) . '>Robots</option>';
		$bots .= '<option value="set2"' . selected( $options['bot'], 'set2', false ) . '>Monsters</option>';
		$bots .= '<option value="set3"' . selected( $options['bot'], 'set3', false ) . '>Robot Heads</option>';
		$bots .= '<option value="any" ' . selected( $options['bot'], 'any',  false ) . '>Any</option>';
		$bots .= '</select> ';

		$bgs  = '<label for="robohash_bg">Background</label> <select id="robohash_bg" name="robohash_bg">';
		$bgs .= '<option value=""    ' . selected( $options['bg'], '',    false ) . '>None</option>';
		$bgs .= '<option value="bg1" ' . selected( $options['bg'], 'bg1', false ) . '>Scene</option>';
		$bgs .= '<option value="bg2" ' . selected( $options['bg'], 'bg2', false ) . '>Abstract</option>';
		$bgs .= '<option value="any" ' . selected( $options['bg'], 'any', false ) . '>Any</option>';
		$bgs .= '</select>';

		$hidden = '<input type="hidden" id="spinner" value="' . admin_url( 'images/wpspin_light-2x.gif' ) . '" />';

		// Current avatar, based on saved options.
		$avatar_url = str_replace(
			array(
				'set1',
				'bg1',
			),
			array(
				$options['bot'],
				$options['bg'],
			),
			'https://robohash.org/set_set1/bgset_bg1/emailhash.png'
		);

		$avatar_defaults[ $avatar_url ] = $bots . $bgs . $hidden;

		return $avatar_defaults;
	}

	/**
	 * Filter avatar
	 *
	 * @param string $avatar      &lt;img&gt; tag for the user's avatar.
	 * @param mixed  $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
	 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param int    $size        Square avatar width and height in pixels to retrieve.
	 * @param string $default     URL for the default image or a default type.
	 * @param string $alt         Alternative text to use in the avatar image tag. Default empty.
	 * @param array  $args        Arguments passed to get_avatar_data(), after processing.
	 * @return string HTML
	 */
	function get_avatar( $avatar, $id_or_email, $size, $default, $alt, $args ) {

		// Determine email address.
		if ( is_numeric( $id_or_email ) ) {
			$email = get_userdata( $id_or_email )->user_email;
		} elseif ( is_object( $id_or_email ) ) {
			$email = $id_or_email->comment_author_email;
		} else {
			$email = $id_or_email;
		}

		// Since we're hooking directly into get_avatar,
		// we need to make sure another avatar hasn't been selected.
		if ( strpos( $default, 'https://robohash.org/' ) !== false ) {
			$email = empty( $email ) ? 'nobody' : md5( $email );

			// In rare cases were there is no email associated with the comment (like Mr WordPress)
			// we have to work around a bit to insert the custom avatar.
			$direct = get_option( 'avatar_default' );
			$new_av_url = str_replace( 'emailhash', $email, $direct );
			// 'www' version for WP2.9 and older
			if (
				0 === strpos( $default, 'http://0.gravatar.com/avatar/' ) ||
				0 === strpos( $default, 'http://www.gravatar.com/avatar/' )
			) {
				$avatar = str_replace( $default, $new_av_url . "&size={$size}x{$size}", $avatar );
			}

			// Otherwise, just swap the placeholder with the hash.
			$avatar = str_replace( 'emailhash', $email, $avatar );

			// This is ugly, but has to be done.
			// Make sure we pass the correct size params to the generated avatar.
			$avatar = str_replace( '%3F', "%3Fsize={$size}x{$size}%26", $avatar );

		}

		return $avatar;
	}

	/**
	 * Enqueue js for live avatar upates
	 *
	 * @param string $hook Page hook.
	 */
	function admin_enqueue_scripts( $hook ) {
		// We use this js for the live preview when toggling avatar options.
		if ( 'options-discussion.php' != $hook ) {
			return;
		}
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'robohash', plugins_url( 'robohash.js', __FILE__ ), array( 'jquery' ) );
	}

	/**
	 * Save options
	 */
	function update() {
		wp_verify_nonce( 'discussion-options' );

		if ( isset( $_POST['robohash_bot'] ) && isset( $_POST['robohash_bg'] ) ) {
			$options = array(
				'bot' => sanitize_text_field( wp_unslash( $_POST['robohash_bot'] ) ),
				'bg'  => sanitize_text_field( wp_unslash( $_POST['robohash_bg'] ) ),
			);
			update_option( 'robohash_options', $options );
		}
	}

}
