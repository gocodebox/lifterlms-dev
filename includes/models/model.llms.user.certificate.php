<?php
/**
 * LLMS_User_Certificate model class
 *
 * @package LifterLMS/Models/Classes
 *
 * @since 3.8.0
 * @version [version]
 */

defined( 'ABSPATH' ) || exit;

/**
 * Certificates earned by a student.
 *
 * @since 3.8.0
 * @since [version] Utilize `LLMS_Abstract_User_Engagement` abstract.
 *
 * @property string  $allow_sharing Whether or not public certificate sharing is enabled for the certificate.
 *                                  Either "yes" or "no".
 * @property string  $background    The CSS background color for the certificate.
 * @property int     $author        WP_User ID of the user who the certificate belongs to.
 * @property string  $content       The merged certificate content.
 * @property int     $engagement    WP_Post ID of the `llms_engagement` post used to trigger the certificate.
 *                                  An empty value or `0` indicates the certificate was awarded manually or
 *                                  before the engagement value was stored.
 * @property float   $height        The certificate's height.
 * @property float[] $margins       The certificate's margins.
 * @property string  $orientation   The certificate's orientation.
 * @property int     $parent        WP_Post ID of the template `llms_certificate` post.
 * @property int     $related       WP_Post ID of the related post.
 * @property int     $sequential_id The sequential certificate ID.
 * @property string  $size          The certificate's registered size ID.
 * @property string  $title         Certificate title.
 * @property string  $unit          The certificate's registered unit ID.
 * @property float   $width         The certificate's width.
 */
class LLMS_User_Certificate extends LLMS_Abstract_User_Engagement {

	/**
	 * Database (WP) post type name
	 *
	 * @var string
	 */
	protected $db_post_type = 'llms_my_certificate';

	/**
	 * Post type model name
	 *
	 * @var string
	 */
	protected $model_post_type = 'certificate';

	/**
	 * Object properties
	 *
	 * @var array
	 */
	protected $properties = array(
		'allow_sharing' => 'yesno',
		'background'    => 'string',
		'engagement'    => 'absint',
		'height'        => 'float',
		'margins'       => 'array',
		'orientation'   => 'string',
		'related'       => 'absint',
		'sequential_id' => 'absint',
		'size'          => 'string',
		'unit'          => 'string',
		'width'         => 'float',
	);

	/**
	 * Array of default property values.
	 *
	 * In the form of key => default value.
	 *
	 * @var array
	 */
	protected $property_defaults = array(
		'background'    => '#ffffff',
		'orientation'   => 'landscape',
		'margins'       => array( 5, 5, 5, 5 ),
		'sequential_id' => 1,
	);

	/**
	 * Constructor.
	 *
	 * Overrides parent method to setup default properties that depend on other property values.
	 *
	 * @since [version]
	 *
	 * @param string|int|LLMS_Post_Model|WP_Post $model Existing post or model object or ID
	 * @param array                              $args  Args to create the post, only applies when $model is 'new'.
	 * @return void
	 */
	public function __construct( $model, $args = array() ) {

		$this->set_property_defaults();

		parent::__construct( $model, $args );

	}

	/**
	 * Called immediately after creating / inserting a new post into the database
	 *
	 * This stub can be overwritten by child classes.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	protected function after_create() {

		$this->set( 'sequential_id', llms_get_certificate_sequential_id( $this->get( 'parent' ), true ) );
		$this->set( 'content', $this->merge_content() );

	}

	/**
	 * Can user manage and make some actions on the certificate
	 *
	 * @since 4.5.0
	 * @since [version] Prevent logged out users from managing certificates not assigned to a user.
	 *
	 * @param int|null $user_id Optional. WP User ID (will use get_current_user_id() if none supplied). Default `null`.
	 * @return bool
	 */
	public function can_user_manage( $user_id = null ) {

		$user_id = $user_id ? $user_id : get_current_user_id();
		$result  = ( $user_id && ( $user_id === $this->get_user_id() || llms_can_user_bypass_restrictions( $user_id ) ) );

		/**
		 * Filter whether or not a user can manage a given certificate.
		 *
		 * @since 4.5.0
		 *
		 * @param boolean               $result      Whether or not the user can manage certificate.
		 * @param int                   $user_id     WP_User ID of the user viewing the certificate.
		 * @param LLMS_User_Certificate $certificate Certificate class instance.
		 */
		return apply_filters( 'llms_certificate_can_user_manage', $result, $user_id, $this );

	}

	/**
	 * Can user view the certificate
	 *
	 * @since 4.5.0
	 *
	 * @param int|null $user_id Optional. WP User ID (will use get_current_user_id() if none supplied). Default `null`.
	 * @return bool
	 */
	public function can_user_view( $user_id = null ) {

		$user_id = $user_id ? $user_id : get_current_user_id();
		$result  = $this->can_user_manage( $user_id ) || $this->is_sharing_enabled();

		/**
		 * Filter whether or not a user can view a user's certificate.
		 *
		 * @since 4.5.0
		 *
		 * @param boolean               $result      Whether or not the user can view the certificate.
		 * @param int                   $user_id     WP_User ID of the user viewing the certificate.
		 * @param LLMS_User_Certificate $certificate Certificate class instance.
		 */
		return apply_filters( 'llms_certificate_can_user_view', $result, $user_id, $this );

	}

	/**
	 * Retrieves the certificate background color value.
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	public function get_background() {
		return $this->get( 'background' );
	}

	/**
	 * Retrieve information about the certificate background image.
	 *
	 * This is a legacy function used for certificates using template version 1.
	 *
	 * @since [version]
	 *
	 * @return array {
	 *     Returns an associative array of information about the background image.
	 *
	 *     @type string $src        The image source url.
	 *     @type int    $width      The image display width, in pixels.
	 *     @type int    $height     The image display height, in pixels.
	 *     @type bool   $is_default Whether or not the default image was returned.
	 * }
	 */
	public function get_background_image() {

		$id     = $this->get( 'id' );
		$img_id = get_post_thumbnail_id( $id );
		$size   = $size = llms_parse_bool( get_option( 'lifterlms_certificate_legacy_image_size', 'yes' ) ) ? 'full' : 'lifterlms_certificate_background';

		if ( ! $img_id ) {

			// Get the source.
			$src = llms()->certificates()->get_default_image( $id );

			// Denote it's the default image in the return.
			$is_default = true;

			/**
			 * Filters the display height of the default certificate background image.
			 *
			 * @since 2.2.0
			 *
			 * @param int $height         Display height of the image, in pixels.
			 * @param int $certificate_id WP_Post ID of the earned certificate.
			 */
			$height = apply_filters( 'lifterlms_certificate_background_image_placeholder_height', 616, $id );

			/**
			 * Filters the display width of the default certificate background image.
			 *
			 * @since 2.2.0
			 *
			 * @param int $width          Display width of the image, in pixels.
			 * @param int $certificate_id WP_Post ID of the earned certificate.
			 */
			$width = apply_filters( 'lifterlms_certificate_background_image_placeholder_width', 800, $id );

		} else {

			list( $src, $width, $height ) = wp_get_attachment_image_src( $img_id, $size );

			// Denote it's not the default image in the return.
			$is_default = false;

			/**
			 * Filters the image source of the certificate background image.
			 *
			 * @since 2.2.0
			 *
			 * @param string $src            The image source url.
			 * @param int    $certificate_id WP_Post ID of the earned certificate.
			 */
			$src = apply_filters( 'lifterlms_certificate_background_image_src', $src, $id );

			/**
			 * Filters the display height of the certificate background image.
			 *
			 * @since 2.2.0
			 *
			 * @param int $height         Display height of the image, in pixels.
			 * @param int $certificate_id WP_Post ID of the earned certificate.
			 */
			$height = apply_filters( 'lifterlms_certificate_background_image_height', $height, $id );

			/**
			 * Filters the display width of the certificate background image.
			 *
			 * @since 2.2.0
			 *
			 * @param int $width          Display width of the image, in pixels.
			 * @param int $certificate_id WP_Post ID of the earned certificate.
			 */
			$width = apply_filters( 'lifterlms_certificate_background_image_width', $width, $id );

		}

		return compact( 'src', 'width', 'height', 'is_default' );

	}

	/**
	 * Retrieves the value for either the width or height.
	 *
	 * @since [version]
	 *
	 * @param string  $dimension Dimension key, either "width" or "height".
	 * @param boolean $with_unit Whether or not to include the unit in the return.
	 * @return string|float If `$with_unit` is `true`, returns a string with the unit, otherwise returns the dimension as a float.
	 */
	private function get_dimension( $dimension, $with_unit = false ) {

		$ret = 0;
		if ( 'CUSTOM' === $this->get_size() ) {
			$ret = $this->get( $dimension );
		} else {
			$size_info = $this->get_registered_size_data();
			$ret       = $size_info[ $dimension];
		}

		return $with_unit ? sprintf( '%1$s%2$s', $ret, $this->get_unit() ) : $ret;

	}

	/**
	 * Retrieve dimensions adjusted for orientation.
	 *
	 * The width and height are always stored as if the certificate were to be displayed in portrait
	 * mode. This method will return the dimensions as necessary to use in styling rules.
	 *
	 * When the certificate is displaying in landscape the width and height are transposed
	 * automatically by this method.
	 *
	 * @since [version]
	 *
	 * @return {
	 *     Array of dimensions.
	 *
	 *     @type string $width  The display width (with units).
	 *     @type string $height The display height (with units).
	 * }
	 */
	public function get_dimensions_for_display() {

		$orientation = $this->get_orientation();
		$width       = $this->get_width( true );
		$height      = $this->get_height( true );

		return array(
			'width'  => 'portrait' === $orientation ? $width : $height,
			'height' => 'portrait' === $orientation ? $height : $width,
		);

	}

	/**
	 * Retrieve the height dimension.
	 *
	 * @since [version]
	 *
	 * @param boolean $with_unit Whether or not to include the unit in the return.
	 * @return string|float If `$with_unit` is `true`, returns a string with the unit, otherwise returns the height as a float.
	 */
	public function get_height( $with_unit = false ) {
		return $this->get_dimension( 'height', $with_unit );
	}

	/**
	 * Retrieves the certificate's margins.
	 *
	 * @since [version]
	 *
	 * @param boolean $with_units Whether or not to include the percent sign unit in the return.
	 * @return float[] Array of floats representing the margins. The margins are listed as they would be
	 *                 when defining the margins of an element in CSS: `array( $left, $top, $right, $bottom )`.
	 */
	public function get_margins( $with_units = false ) {

		$margins = $this->get( 'margins' );

		if ( $with_units ) {
			$margins = array_map( function( $margin ) {
				return $margin . '%';
			}, $margins );
		}

		return $margins;
	}

	/**
	 * Retrieve merge codes and data.
	 *
	 * @since [version]
	 *
	 * @return array Array mapping merge codes to the merge data.
	 */
	protected function get_merge_data() {

		$template_id   = $this->get( 'parent' );
		$user_id       = $this->get_user_id();
		$related_id    = $this->get( 'related' );
		$engagement_id = $this->get( 'engagement' );

		$user = get_userdata( $user_id );

		$codes = array(
			// Site.
			'{site_title}'     => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			'{site_url}'       => get_permalink( llms_get_page_id( 'myaccount' ) ),
			// User.
			'{user_login}'     => $user ? $user->user_login : '',
			'{first_name}'     => $user ? $user->first_name : '',
			'{last_name}'      => $user ? $user->last_name : '',
			'{email_address}'  => $user ? $user->user_email : '',
			'{student_id}'     => $user ? $user_id : '',
			// Certificate.
			'{current_date}'   => wp_date( get_option( 'date_format' ) ),
			'{certificate_id}' => $this->get( 'id' ),
			'{sequential_id}'  => $this->get_sequential_id(),
		);

		$codes = LLMS_Engagement_Handler::do_deprecated_filter(
			$codes,
			array( $template_id, $user_id, $related_id ),
			'certificate',
			'llms_certificate_merge_codes',
			'llms_certificate_merge_data'
		);

		return apply_filters( 'llms_certificate_merge_data', $codes, $user_id, $template_id, $related_id );

	}

	/**
	 * Retrieves the certificate's orientation value.
	 *
	 * @since [version]
	 *
	 * @see llms_get_certificate_orientations()
	 *
	 * @return string
	 */
	public function get_orientation() {
		return $this->get( 'orientation' );
	}

	/**
	 * Retrieves the registered size data array for the certificate's size.
	 *
	 * This method should not be used without first verifying that the certificate's
	 * size is not set to CUSTOM as this is not a valid size and the sitewide default
	 * will be returned.
	 *
	 * @since [version]
	 *
	 * @see llms_get_certificate_sizes()
	 *
	 * @return array
	 */
	private function get_registered_size_data() {

		$size = $this->get_size();
		$sizes = llms_get_certificate_sizes();
		if ( ! $size || empty( $sizes[ $size ] ) ) {
			$size = get_option( 'llms_certificate_default_size', 'LETTER' );
		}

		return $sizes[ $size ] ?? array_values( $sizes )[0];

	}

	/**
	 * Retrieve the formatted sequential id for the certificate.
	 *
	 * The sequential ID is stored as an integer and formatted for display according the filterable
	 * settings found in this method.
	 *
	 * By default, the sequential ID will appear as a 6 character number, left-side padded with zeros.
	 *
	 * Examples:
	 *   + 1      = 000001
	 *   + 20     = 000020
	 *   + 12345  = 012345
	 *   + 999999 = 999999
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	public function get_sequential_id() {

		/**
		 * Filter certificate sequential id formatting settings.
		 *
		 * These settings are passed as arguments to `str_pad()`.
		 *
		 * @since [version]
		 *
		 * @link https://www.php.net/manual/en/function.str-pad.php
		 *
		 * @param array {
		 *    Array of formatting settings.
		 *
		 *    @type int    $length    Number of characters for the ID.
		 *    @type string $character Padding character.
		 *    @type int    $type      String padding type. Expects a valid `pad_type` PHP constant: STR_PAD_RIGHT, STR_PAD_LEFT, or STR_PAD_BOTH.
		 * }
		 * @param LLMS_User_Certificate $certificate Instance of the certificate object.
		 */
		$formatting = apply_filters(
			'llms_certificate_sequential_id_format',
			array(
				'length'    => 6,
				'character' => '0',
				'type'      => STR_PAD_LEFT,
			),
			$this
		);

		$raw_id = $this->get( 'sequential_id' );

		$id = str_pad(
			(string) $raw_id,
			$formatting['length'],
			$formatting['character'],
			$formatting['type']
		);

		/**
		 * Filters the formatted certificate sequential ID string.
		 *
		 * @since [version]
		 *
		 * @param string                $id          The formatted sequential ID.
		 * @param int                   $raw_id      The raw ID before formatting was applied.
		 * @param array                 $formatting  Array of formatting settings, see `llms_certificate_sequential_id_format`.
		 * @param LLMS_User_Certificate $certificate Instance of the certificate object.
		 */
		return apply_filters( 'llms_certificate_sequential_id', $id, $raw_id, $formatting, $this );

	}

	/**
	 * Retrieves the ID of the certificate's size.
	 *
	 * @since [version]
	 *
	 * @see llms_get_certificate_sizes()
	 *
	 * @return string
	 */
	public function get_size() {
		return $this->get( 'size' );
	}

	/**
	 * Retrieves the certificate's template version.
	 *
	 * Since LifterLMS 6.0.0, certificates are created using the block editor.
	 *
	 * Certificates created in the classic editor will use template version 1 while any certificates
	 * created in the block editor use template version 2. Therefore a certificate that has content
	 * and no blocks will use template version 1 and any empty certificates or those containing blocks
	 * will use template version 2.
	 *
	 * @since [version]
	 *
	 * @return integer
	 */
	public function get_template_version() {

		$version = empty( $this->get( 'content', true ) ) || has_blocks( $this->get( 'id' ) ) ? 2 : 1;

		/**
		 * Filters a certificate's template version.
		 *
		 * @since [version]
		 *
		 * @param int $version The template version.
		 */
		return apply_filters( 'llms_certificate_template_version', $version, $this );

	}

	/**
	 * Retrieves the ID of the certificate's unit.
	 *
	 * @since [version]
	 *
	 * @see llms_get_certificate_units()
	 *
	 * @return string
	 */
	public function get_unit() {

		if ( 'CUSTOM' === $this->get_size() ) {
			return $this->get( 'unit' );
		}

		$size_info = $this->get_registered_size_data();
		return $size_info['unit'];

	}

	/**
	 * Retrieve the width dimension.
	 *
	 * @since [version]
	 *
	 * @param boolean $with_unit Whether or not to include the unit in the return.
	 * @return string|float If `$with_unit` is `true`, returns a string with the unit, otherwise returns the width as a float.
	 */
	public function get_width( $with_unit = false ) {
		return $this->get_dimension( 'width', $with_unit );
	}

	/**
	 * Is sharing enabled
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public function is_sharing_enabled() {

		/**
		 * Filter whether or not sharing is enabled for a certificate.
		 *
		 * @since 4.5.0
		 *
		 * @param boolean               $enabled     Whether or not sharing is enabled.
		 * @param LLMS_User_Certificate $certificate Certificate class instance.
		 */
		return apply_filters( 'llms_certificate_is_sharing_enabled', llms_parse_bool( $this->get( 'allow_sharing' ) ), $this );

	}

	/**
	 * Merges the post content based on content from the template.
	 *
	 * @since [version]
	 *
	 * @return string
	 */
	public function merge_content() {

		// Merge.
		$merge   = $this->get_merge_data();
		$content = str_replace( array_keys( $merge ), array_values( $merge ), $this->get( 'content', true ) );

		// Do shortcodes.
		LLMS_Shortcodes::init(); // In certain circumstances shortcodes won't be registered yet.
		add_filter( 'llms_user_info_shortcode_user_id', array( $this, 'get_user_id' ) );
		$content = do_shortcode( $content );
		remove_filter( 'llms_user_info_shortcode_user_id', array( $this, 'get_user_id' ) );

		// Preserve legacy functionality which wraps the post content in the HTML specified in the template file.
		$use_template = apply_filters_deprecated(
			'llms_certificate_use_legacy_template',
			array( false, $this ),
			'[version]',
			'', // There is no direct replacement.
			__( 'Loading custom HTML from the certificate template is deprecated. All HTML should be added to the certificate directly via the editor or applied via post content filters.', 'lifterlms' )
		);
		if ( $use_template ) {
			ob_start();
			llms_get_template(
				'certificates/template.php',
				array(
					'email_message' => $content,
					'title'         => $this->get( 'title' ),
					'image'         => $this->get( 'certificate_image' ),
				)
			);
			$content = ob_get_clean();
		}

		return $content;

	}

	/**
	 * Configure non-static property defaults.
	 *
	 * @since [version]
	 *
	 * @return void
	 */
	private function set_property_defaults() {

		// Default size is configured via a site option.
		$default_size = get_option( 'llms_certificate_default_size', 'LETTER' );
		$this->property_defaults['size']    = ! $default_size ? 'LETTER' : $default_size;

	}

	/**
	 * Update the certificate by regenerating it's content and title from the template.
	 *
	 * @since [version]
	 *
	 * @return WP_Error|boolean Returns a `WP_Error` if an error is encountered checking the template post, otherwise returns `true`.
	 */
	public function sync() {

		$template_id = $this->get( 'parent' );
		$check       = LLMS_Engagement_Handler::check_post( $template_id, 'llms_certificate' );
		if ( is_wp_error( $check ) ) {
			return $check;
		}

		$template = get_post( $template_id );

		$this->set( 'title', $template->post_title );
		$this->set( 'content', $template->post_content );

		// Save the fully merged content.
		$this->set( 'content', $this->merge_content() );

		return true;

	}

}
