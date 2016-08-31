<?php
/**
 * Plugin Name: Letter Avatars
 * Version: 0.1
 * Author: scossar
 */

namespace Testeleven\LetterAvatars;

$letter_avatars = new \Testeleven\LetterAvatars\LetterAvatars();

class LetterAvatars {

	public function __construct() {
		add_filter( 'get_avatar', array( $this, 'get_avatar_letter_or_gravatar' ), 10, 6 );
		add_action( 'user_register', array( $this, 'generate_default_avatar' ) );
		add_action( 'wp_login', array( $this, 'check_for_avatar' ), 10, 2 );
	}

	public function check_for_avatar( $username, $user ) {
		if ( ! file_exists( $this->get_user_avatar( $user->ID ) ) ) {
			$this->generate_default_avatar( $user->ID );
		}
	}

	public function generate_default_avatar( $user_id ) {
		$base_avatar_url = $this->get_raw_avatar_url( $user_id );
		$user_name       = $this->get_user_data( $user_id )['user_name'];
		$base_image      = imagecreatefrompng( $base_avatar_url );
		imagefilter( $base_image, IMG_FILTER_COLORIZE, rand( 100, 255 ), rand( 100, 255 ), rand( 100, 255 ) );
		imagepng( $base_image, plugin_dir_path( __FILE__ ) . "assets/user-avatars/{$user_name}.png" );
		imagedestroy( $base_image );
	}

	protected function get_user_data( $user_id ) {
		$user_data = [];
		$raw_data  = get_userdata( $user_id );
		if ( $raw_data ) {
			$user_data['id']        = $raw_data->ID;
			$user_data['email']     = $raw_data->user_email;
			$user_data['user_name'] = $raw_data->user_login;
		}

		return $user_data;
	}

	protected function get_username_first_letter( $user_id ) {
		$user_name = $this->get_user_data( $user_id )['user_name'];

		return substr( $user_name, 0, 1 );
	}

	protected function get_avatar_filename( $user_id ) {
		$letter = $this->get_username_first_letter( $user_id );

		return $letter . '.png';
	}

	protected function get_user_email_hash( $id ) {
			$user_data = $this->get_user_data( $id );

			return md5( strtolower( trim( $user_data['email'] ) ) );
	}

	protected function get_gravatar_url( $id ) {
		$email_hash   = $this->get_user_email_hash( $id );
		$gravatar_url = "https://www.gravatar.com/avatar/{$email_hash}";

		return $gravatar_url;
	}

	protected function get_raw_avatar_url( $user_id ) {
		$filename = $this->get_avatar_filename( $user_id );

		return plugins_url( "assets/{$filename}", __FILE__ );
	}

	protected function get_user_avatar( $id ) {
		$user_name = $this->get_user_data( $id )['user_name'];
		$filename  = $user_name . '.png';

		return plugins_url( "assets/user-avatars/{$filename}", __FILE__ );
	}

	protected function generate_image_url( $image_name ) {
		return plugins_url( "assets/{$image_name}", __FILE__ );
	}

	protected function get_id( $id_or_email ) {
		if ( is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			$id = $user->ID;
		} elseif ( is_a( $id_or_email, '\WP_User' ) ) {
			$id = $id_or_email->ID;
		} else {
			$id = $id_or_email;
		}

		return $id;
	}

	function get_avatar_letter_or_gravatar( $avatar, $id_or_email, $size = 96, $default = '', $alt = '', $args = null ) {
		$defaults = array(
			// get_avatar_data() args.
			'size'          => 96,
			'height'        => null,
			'width'         => null,
			'default'       => get_option( 'avatar_default', 'mystery' ),
			'force_default' => false,
			'rating'        => get_option( 'avatar_rating' ),
			'scheme'        => null,
			'alt'           => '',
			'class'         => null,
			'force_display' => false,
			'extra_attr'    => '',
		);

		$id = $this->get_id( $id_or_email );

		if ( empty( $args ) ) {
			$args = array();
		}

		$args['size']    = (int) $size;
		$args['default'] = $default;
//		$args['default'] = $this->get_raw_avatar_url( $id_or_email );
		$arg['alt'] = $alt;

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['height'] ) ) {
			$args['height'] = $args['size'];
		}
		if ( empty( $args['width'] ) ) {
			$args['width'] = $args['size'];
		}

		if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			$id_or_email = get_comment( $id_or_email );
		}

		/**
		 * Filters whether to retrieve the avatar URL early.
		 *
		 * Passing a non-null value will effectively short-circuit get_avatar(), passing
		 * the value through the {@see 'get_avatar'} filter and returning early.
		 *
		 * @since 4.2.0
		 *
		 * @param string $avatar HTML for the user's avatar. Default null.
		 * @param mixed $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
		 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
		 * @param array $args Arguments passed to get_avatar_url(), after processing.
		 */
		$avatar = apply_filters( 'pre_get_avatar', null, $id_or_email, $args );

		if ( ! is_null( $avatar ) ) {
			/** This filter is documented in wp-includes/pluggable.php */
//			return apply_filters( 'get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args );
			return $avatar;
		}

		if ( ! $args['force_display'] && ! get_option( 'show_avatars' ) ) {
			return false;
		}

//		$url2x = get_avatar_url( $id_or_email, array_merge( $args, array( 'size' => $args['size'] * 2 ) ) );
		$args['force_default'] = true;

		$args = get_avatar_data( $id, $args );

//		$url = $args['url'];
		$url = $this->get_user_avatar( $id );

		if ( ! $url || is_wp_error( $url ) ) {
			return false;
		}

		$class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );

		if ( ! $args['found_avatar'] || $args['force_default'] ) {
			$class[] = 'avatar-default';
		}

		if ( $args['class'] ) {
			if ( is_array( $args['class'] ) ) {
				$class = array_merge( $class, $args['class'] );
			} else {
				$class[] = $args['class'];
			}
		}

		$avatar = sprintf(
			"<img alt='%s' src='%s' class='%s' height='%d' width='%d' %s/>",
			esc_attr( $args['alt'] ),
			esc_url( $url ),
			esc_attr( join( ' ', $class ) ),
			(int) $args['height'],
			(int) $args['width'],
			$args['extra_attr']
		);

		/**
		 * Filters the avatar to retrieve.
		 *
		 * @since 2.5.0
		 * @since 4.2.0 The `$args` parameter was added.
		 *
		 * @param string $avatar &lt;img&gt; tag for the user's avatar.
		 * @param mixed $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
		 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
		 * @param int $size Square avatar width and height in pixels to retrieve.
		 * @param string $alt Alternative text to use in the avatar image tag.
		 *                                       Default empty.
		 * @param array $args Arguments passed to get_avatar_data(), after processing.
		 */
//		return apply_filters( 'get_avatar', $avatar, $id_or_email, $args['size'], $args['default'], $args['alt'], $args );
		return $avatar;
	}
}