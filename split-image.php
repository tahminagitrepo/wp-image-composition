<?php
/**
 * Plugin Name:     Split Image
 * Plugin URI:      
 * Description:     Combine multiple into one
 * Author:          Tahmina chowdhury
 * Author URI:      
 * Text Domain:     split-image
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Split_Image
 */

/**
 * Class split image
 *
 * Create Image Split Compositions in WordPress
 *
 * @package DMG
 */
class Split_Image {

	/**
	 * Menu slug
	 *
	 * @var string
	 */
	const MENU_SLUG = 'dmg-image-split';

	/**
	 * Initialize
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'process_form' ] );
		add_action( 'admin_menu', [ __CLASS__, 'add_submenu_page' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_dmg_upload_split_image' , array(__CLASS__, "upload_split_image"));
	}

	/**
	 * Add submenu page
	 */
	public static function add_submenu_page() {
		add_submenu_page( 
			'upload.php',
			'Image Split',
			'Image Split',
    		'upload_files',
    		self::MENU_SLUG,
    		[ __CLASS__, 'page_callback' ]
    	);
	}

	/**
	 * The page callback
	 */
	public static function page_callback() {
		?>
		<div class="wrap">
			<h2>Image Composition</h2>
			<div id="root"></div>
		</div>
		<?php
	}

	/**
	 * Add assets
	 */
	public static function enqueue_scripts() {

		// Ensure we're on the correct page
		global $pagenow;
		if ( $pagenow !== 'upload.php' || empty( $_GET['page'] ) || $_GET['page'] !== self::MENU_SLUG ) {
			return;
		}

		// Enqueue standard media scripts
		wp_enqueue_media();

		$assets_json = file_get_contents(__DIR__ . "/assets/build/asset-manifest.json");
		$assets = json_decode($assets_json, true);

		if(isset($assets["files"]["main.js"])) {
			wp_enqueue_script(
				self::MENU_SLUG,
				plugin_dir_url(__FILE__) . "assets/build" .$assets["files"]["main.js"],
				[],
				null, 
				true
			);

			wp_localize_script(self::MENU_SLUG, 'image_proxy', plugin_dir_url(__FILE__) . "image.php?path=");
		}

		if(isset($assets["files"]["main.css"])) {
			wp_enqueue_style(
				self::MENU_SLUG,
				plugin_dir_url(__FILE__) .  "assets/build" . $assets["files"]["main.css"]
			);
		}
		// Enqueue custom script and styles
		
	}

	/**
	 * Process form
	 */
	public static function upload_split_image() {
		// Make sure nonce is set
		// if ( ! wp_verify_nonce( $_POST['metro-image-composition'], 'metro-image-composition' ) ) {
		// 	return;
		// }

		$entityBody = file_get_contents('php://input');


		$postData = json_decode($entityBody, true);

		// Make sure data is set
		if ( empty( $postData['imageData'] )) {
			echo json_encode([
				"message" => "Unable to save image. No Composition data",
				"status" => "error"
			]);
			die();
		}


		// Extract image data
		$data  = base64_decode( preg_replace('#^data:image/\w+;base64,#i', '', $postData['imageData']) );
		$image = imagecreatefromstring( $data );

		// Require files that may not have been included
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Sanitize image title if passed
		$image_title = ( ! empty( $postData['title'] ) ) ? sanitize_text_field( $postData['title'] ) : '';

		// Determine file names
		$filename = ( ! empty( $image_title ) ) ? sanitize_title( $image_title ) : 'comp-' . time();
		$filename .= '.png';
		$tempnam  = wp_tempnam( $filename );

		imagepng( $image, $tempnam );

		// Save temp file
		$files = [
			'name'     => $filename,
			'tmp_name' => $tempnam,
		];

		// Pass image title if set
		$post_data = [];
		if ( ! empty( $image_title ) ) {
			$post_data['post_title'] = $image_title;
		}

		// Upload the new image
		$uploaded_id = media_handle_sideload(
			$files,
			0,
			null,
			$post_data
		);

		// Redirect to upload.php
		if ( $uploaded_id > 0 ) {

			// Add post meta for each source image ID
			// foreach ( $image_ids as $image_id ) {
			// 	add_post_meta( $uploaded_id, 'metro_image_comp_source_id', $image_id );
			// }

			echo json_encode([
				"message" => array(
					"info"=>"{$filename} has beed added.",
					"admin_url" => "/wp-admin/upload.php?item=" . $uploaded_id,
					"attachement_url" => wp_get_attachment_url($uploaded_id)
				),

				"status" => "success"
			]);
			die();
			
			
		}

		echo json_encode([
			"message" => "Unable add image",
			"status" => "error"
		]);
		die();
	}
}

Split_Image::init();
