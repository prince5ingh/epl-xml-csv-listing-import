<?php
/**
 * Importer Functions
 *
 * @package     EPL-IMPORTER-ADD-ON
 * @subpackage  Functions/Importer
 * @copyright   Copyright (c) 2019, Merv Barrett
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $epl_ai_meta_fields;

$epl_ai_meta_fields = epl_wpimport_get_meta_fields();

/**
 * Register Importer Fields
 *
 * @since 1.0
 */
function epl_wpimport_register_fields() {
	global $epl_ai_meta_fields, $epl_wpimport;

	// Initialize EPL WP All Import Pro add-on.
	$epl_wpimport = new RapidAddon('Easy Property Listings Custom Fields', 'epl_wpimport_addon');

	if(!empty($epl_ai_meta_fields)) {
		foreach($epl_ai_meta_fields as $epl_meta_box) {
			if(!empty($epl_meta_box['groups'])) {
				foreach($epl_meta_box['groups'] as $group) {
					$epl_wpimport->add_title( $group['label'], $group['label'] );
			                $fields = $group['fields'];
			                $fields = array_filter($fields);
			                if(!empty($fields)) {
						foreach($fields as $field) {

							switch($field['type']) {

								case 'textarea':
									$epl_wpimport->add_field($field['name'], $field['label'], 'textarea');
									break;

								case 'url':
								case 'date':
								case 'sold-date':
								case 'sold-date':
								case 'auction-date':
								case 'decimal':
								case 'number':
								case 'checkbox':
								case 'checkbox_single':
									$epl_wpimport->add_field($field['name'], $field['label'], 'text');
									break;

								case 'select':
								case 'radio':
									$opts = isset($field['opts']) ? $field['opts'] : array();
									if( !empty($opts) ) {
										foreach($opts as $opt_key	=>	&$opt_value) {
											if( is_array($opt_value) ) {
												$opts[$opt_key] = $opt_value['label'];
											}
										}
									}
									$epl_wpimport->add_field($field['name'], $field['label'], 'radio',array());
									break;

								default:
									$type = in_array($field['type'], array('text','hidden',) ) ? 'text' : $field['type'];
									$epl_wpimport->add_field($field['name'], $field['label'], $type);
									break;
							}
						}
			                }
				}
			}
		}

		// Register Import Function
		$epl_wpimport->set_import_function('epl_wpimport_import_function');

		// display a dismiss able notice warning the user to install WP All Import to use the add-on.
		$epl_wpimport->admin_notice( __( "Easy Property Listings Importer plugin recommends you install <a href='http://www.wpallimport.com/'>WP All Import Pro 4.2.6+</a>" , 'epl-wpimport') );

		// the add-on will run for all themes/post types if no arguments are passed to run()
		$epl_wpimport->run(
		        array(
			        "post_types" => epl_get_core_post_types()
		        )
		);
	}
}
add_action('init','epl_wpimport_register_fields');

/**
 * Import Function
 *
 * @since 1.0
 */
function epl_wpimport_import_function( $post_id, $data, $import_options ) {

	global $epl_wpimport,$epl_ai_meta_fields;

	$imported_metas = array();

	$live_import		= function_exists('epl_get_option')  ?  epl_get_option('epl_wpimport_skip_update') : 'off';

	if(!empty($epl_ai_meta_fields)) {

		$epl_wpimport->log( '<b>' . __('EPL IMPORTER' , 'epl-wpimport') . ': ' . __('UPDATING FIELDS' , 'epl-wpimport') . '</b>' );

		foreach($epl_ai_meta_fields as $epl_meta_box) {

			if(!empty($epl_meta_box['groups'])) {
				foreach($epl_meta_box['groups'] as $group) {

				        $fields = $group['fields'];
				        $fields = array_filter($fields);

				        if(!empty($fields)) {
							foreach($fields as $field) {

									if( !$import_options['options']['is_update_epl'] ) {

										if ( $epl_wpimport->can_update_meta($field['name'], $import_options) ) {

											if($field['name'] == 'property_images_mod_date') {

												$old_mod_date = get_post_meta($post_id,'property_images_mod_date',true);
												update_post_meta($post_id,'property_images_mod_date_old', $old_mod_date);

												$epl_wpimport->log( '- ' . __('Field Updated:' , 'epl-wpimport') . '`property_images_mod_date_old`' . ' POST: ' . $post_id . ': - ' . __('Images Modified Date: ' , 'epl-wpimport') . '`' . $old_mod_date . '`' );
	                						}

			                				if( ( isset($field['import']) && $field['import'] == 'preserve' ) || in_array( $field['name'], epl_wpimport_skip_fields() ) ){

			                					$epl_wpimport->log( '- ' . __('Field Skipped:' , 'epl-wpimport') . '`' . $field['name'] . '` value `' . $data[$field['name']] . '`' );

		                						continue;
											}

			                				// Field Import exclude empty fields
			                				if ( !empty( $data[$field['name']] ) ) {

												update_post_meta($post_id, $field['name'], $data[$field['name']]);

												// Log
												$epl_wpimport->log( '- ' . __('Field Updated:' , 'epl-wpimport') . '`' . $field['name'] . '` value `' . $data[$field['name']] . '`' );
											}

											$imported_metas[] = $field['name'];
										}
									}

									if ( pmai_is_epl_update_allowed($field['name'], $import_options['options']) ) {

										if($field['name'] == 'property_images_mod_date') {

											$old_mod_date = get_post_meta($post_id,'property_images_mod_date',true);
											update_post_meta($post_id,'property_images_mod_date_old', $old_mod_date);

											$epl_wpimport->log( '- ' . __('Field Updated:' , 'epl-wpimport') . '`property_images_mod_date_old`' . ' POST: ' . $post_id . ': - ' . __('Images Modified Date: ' , 'epl-wpimport') . '`' . $old_mod_date . '`' );
                						}

		                				if( ( isset($field['import']) && $field['import'] == 'preserve' ) || in_array( $field['name'], epl_wpimport_skip_fields() ) ){

		                					$epl_wpimport->log( '- ' . __('Field Skipped:' , 'epl-wpimport') . '`' . $field['name'] . '` value `' . $data[$field['name']] . '`' );

	                						continue;
										}

		                				// Field Import exclude empty fields
		                				if ( !empty( $data[$field['name']] ) ) {

											update_post_meta($post_id, $field['name'], $data[$field['name']]);

											// Log
											$epl_wpimport->log( '- ' . __('Field Updated:' , 'epl-wpimport') . '`' . $field['name'] . '` value `' . $data[$field['name']] . '`' );
										}

										$imported_metas[] = $field['name'];
									}
							}
				        }
				}
			}
		}

		if( !empty($imported_metas) ) {
			$epl_wpimport->log( '- ' . __('All EPL Fields Updated' , 'epl-wpimport') );
		} else {
			$epl_wpimport->log( '- ' . __('Preserve EPL Fields' , 'epl-wpimport') );
		}
        
	}
}

/**
 * Notification that EPL Importer is Running Logging Output
 *
 * @since 1.0
 */
function epl_wpimport_log( $post_id ) {

	global $epl_wpimport;

	// Importer Title
	$epl_wpimport_label 	= __( 'EPL IMPORTER' , 'epl-wpimport')  . ': ';

	// Live Import Status
	$live_import		= function_exists('epl_get_option')  ?  epl_get_option('epl_wpimport_skip_update') : 'off';

	// Live Import Label
	$live_import_label	= $live_import == 'on'  ?  __('Record Skipping Enabled' , 'epl-wpimport') : __('Record Skipping Disabled' , 'epl-wpimport');

	// Log EPL All Importer Activation Status
	$epl_wpimport->log( $epl_wpimport_label . '<b>' . $live_import_label . '</b>' );

}
add_action('pmxi_before_post_import', 'epl_wpimport_log', 10, 1);

/**
 * Notification that EPL Importer is processing images logging output
 *
 * @since 1.0
 */
function epl_wpimport_log_pmxi_gallery_image( $post_id ) {

	/*
	* Parameters
	* $pid 			– the ID of the post/page/Custom Post Type that was just created.
	* $attid 		– the ID of the attachment
	* $image_filepath 	– the full path to the file: C:\path\to\wordpress\wp-content\uploads\2010\05\filename.png
	*/

	global $epl_wpimport;

	// Importer Title
	$epl_wpimport_label 	= __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ';

	// Live Import Status
	$live_import		= function_exists('epl_get_option')  ?  epl_get_option('epl_wpimport_skip_update') : 'off';

	// Live Import Label
	$live_import_label	= $live_import == 'on'  ?  __( 'Record Skipping Enabled for Images' , 'epl-wpimport' ) : __( 'Record Skipping Disabled for Images' , 'epl-wpimport' );

	// Log EPL All Importer Activation Status
	$epl_wpimport->log( $epl_wpimport_label . '<b>' . $live_import_label . '</b>' );
}
add_action('pmxi_before_post_import', 'epl_wpimport_log_pmxi_gallery_image', 10, 1);

/**
 * Update notification: Skipped
 *
 * @since 1.0
 */
function epl_wpimport_post_skipped_notification($vars) {
	global $epl_wpimport;

	$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Record Skipped' , 'epl-wpimport') );

	return $vars;
}

/**
 * Image loop
 *
 * @since 1.0
 */
function epl_wpimport_img_loop($unique_id,$mod_time,$url,$id) {

	$urls 		= array_unique(array_filter(explode(",",$url)));
	$len 		= count($urls);
	$i 		= 0;
	foreach($urls as $index	=>	$img_src) {
		if($img_src != '') {
			echo $url;
			if ($i == $len - 1) {
				// last
			} else {
				echo "\n";
			}
		}
		$i++;
	}
}

/**
 * Skip image uploading if if images mod date is not newer
 *
 * @since 1.0
 */
function epl_wpimport_is_image_to_update($default,$post_object,$xml_object) {

	$live_import	= function_exists('epl_get_option')  ?  epl_get_option('epl_wpimport_skip_update') : 'off';

	if($live_import == 'off') {
		return $default;
	}
	global $epl_wpimport;

	/** only upload images which are recently modified **/
	if( get_post_meta($post_object['ID'],'property_images_mod_date',true)  != '') {
		$new_mod_date =  strtotime(
				epl_feedsync_format_date(
				get_post_meta($post_object['ID'],'property_images_mod_date',true)
			)
		);

	        $old_mod_date =  strtotime(
			epl_feedsync_format_date(
				get_post_meta($post_object['ID'],'property_images_mod_date_old',true)
			)
	        );

	        $epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Image Updating process started: Old Modified Date: ' , 'epl-wpimport') . $old_mod_date . ' - '. __('New Modified Date:' , 'epl-wpimport') . ' ' . $new_mod_date );

		if($old_mod_date < $new_mod_date ) {
			$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Updated Images, Uploading' , 'epl-wpimport') . '...' );
			return true;
		} else {

				$attachments = get_children( array( 'post_parent' => $post_object['ID'], 'post_type'	=>	'attachment' ) );
				$count = count( $attachments );

				// if attachment count is 0 then maybe all attachments are deleted for this listings due to faulty old mod date
				if( absint($count) == 0) {
					if($old_mod_date > $new_mod_date ) {

						$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Old Modified date greater than new modified date. Attachment Count: ' , 'epl-wpimport') . $count );

					} else {

						$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Old Modified date equals new modified date. Attachment Count: ' , 'epl-wpimport') . $count );

					}

					$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Insert & restore deleted attachments' ) );
					return true;
				}

	        	$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('No new images, skipping image update' , 'epl-wpimport') );
	        	return false;
	        }
        } else {
		$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('New Images, updating' , 'epl-wpimport') );
		return true;
	}
}
add_filter('pmxi_is_images_to_update','epl_wpimport_is_image_to_update',10,3);

/**
 * Skip old image deletion if images mod date is not newer
 *
 * @since 1.0
 */
function epl_wpimport_delete_images($default,$post_object,$xml_object) {

	global $epl_wpimport;
	$live_import		= function_exists('epl_get_option')  ?  epl_get_option('epl_wpimport_skip_update') : 'off';

	if( get_post_meta($post_object["ID"],'property_images_mod_date',true)  != '') {
		$mod_date =  strtotime(
			epl_feedsync_format_date(
				get_post_meta($post_object["ID"],'property_images_mod_date',true)
			)
		);
	}

	// check if image mod time tag is present, use it
	if( isset($xml_object['feedsync_image_modtime']) ) {
		$new_mod_date = $xml_object['feedsync_image_modtime'];
	} else {
		if( function_exists('EPL_MLS') ){
			$new_mod_date = $xml_object['images']['@attributes']['modTime'];
		} else {
			$new_mod_date =
			isset($xml_object['images']['img']) ?
				current($xml_object['images']['img'][0]['modTime']) :
				current($xml_object['objects']['img'][0]['modTime']);
		}
	}


	$new_mod_date = strtotime(epl_feedsync_format_date($new_mod_date));

	$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Image Processing Started: Old Modified Date: ' , 'epl-wpimport') . $mod_date . ' - ' . __('New Modified Date: ' , 'epl-wpimport') . $new_mod_date . ' ' . __('Live Import: ' , 'epl-wpimport') . $live_import );

	if ( $live_import == 'off' ) {
		// if live update is off delete
		$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Live import off, default WP All Import functions' , 'epl-wpimport') );
		return $default;
	} else {
		// possible delete
		if( $mod_date == $new_mod_date )  {
			// DO not delete
			$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Images unchanged, skipping image deletion' , 'epl-wpimport') );
			return false;
		} else {
			$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Images changes detected, deleting images' , 'epl-wpimport') );
			 return true;

		}
	}
	// default filter values in WP All Import is: true
	return true;
}
add_filter('pmxi_delete_images','epl_wpimport_delete_images',10,3);

/**
 * Notification that EPL Importer is Running
 *
 * @since 1.0
 */
function epl_wpimport_notification( $notification = 'skip' , $post_id = false ) {

	global $epl_wpimport;

	// Importer Title
	$epl_wpimport_label 	= __( 'EPL IMPORTER' , 'epl-wpimport' );

	$notification_label 	= __( 'Record Skipped' , 'epl-wpimport');

	$post_title = '';

	if ( $post_id != false ) {
		$post_title = get_the_title( $post_id );
		$post_title = ' `' . $post_title . '`';

	}

	if ( $notification == 'update' ) {
		$notification_label 	= __('Date modified, updating...' , 'epl-wpimport');
	}

	if ( $notification == 'modified'  ) {
		$notification_label	= __('Modified Listing, updating...' , 'epl-wpimport');
	}

	if ( $notification == 'update_field'  ) {
		$notification_label	= __('Updating Field...' , 'epl-wpimport');
	}

	if ( $notification == 'skip_unchanged' ) {
		$notification_label	= __('Listing Modified Time Unchanged, Skipping Record Update.' , 'epl-wpimport');
	}

	if ( $notification == 'updating' ) {
		$notification_label	= __('Updating Fields:' , 'epl-wpimport');
	}

	if ( $notification == 'skip' ) {
		$notification_label	= __('Skipped, previously imported record found for:' , 'epl-wpimport');
	}

	// Output
	$epl_wpimport->log( $epl_wpimport_label . ': ' . $notification_label . ' ' . $post_title );
}

/**
 * Only update post of mod date is newer
 *
 * @since 1.0
 */
function epl_wpimport_is_post_to_update_depricated( $pid , $xml_node) {

	global $epl_wpimport;
	//add_action('pmxi_before_post_import', 'epl_wpimport_post_saved_notification', 10, 1);
	$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Deprecated version running' , 'epl-wpimport') );
	$live_import	=	function_exists('epl_get_option')  ?  epl_get_option('epl_wpimport_skip_update') : 'off';
	if ( $live_import == 'on' && get_post_meta($pid,'property_mod_date',true) != '' ) {
		/** only update posts if new data is available **/
		$postmodtime 		= epl_feedsync_format_date(get_post_meta($pid, 'property_mod_date',true ));
		$updatemodtime		= epl_feedsync_format_date($xml_node['@attributes']['modTime']);
		$updatemodtime		= apply_filters('epl_import_mod_time',$updatemodtime,$xml_node,$pid);

		if( strtotime($updatemodtime) > strtotime($postmodtime) ) {

			epl_wpimport_notification( 'update'  , $pid );

			// update
			return true;
		}

		epl_wpimport_notification( 'skip_unchanged'  , $pid );

		// Don't update
		return false;
	}

	epl_wpimport_notification( 'skip' , $pid );

	// Don't update
	return true;
}

/**
 * Only update post of mod date is newer for WP ALL Import Pro version > = 4.5.0
 *
 * @since 1.0
 */
function epl_wpimport_is_post_to_update( $continue_import,$pid , $xml_node,$import_id) {

	global $epl_wpimport;
	//add_action('pmxi_before_post_import', 'epl_wpimport_post_saved_notification', 10, 1);
	$epl_wpimport->log( __( 'EPL IMPORTER' , 'epl-wpimport' ) . ': ' . __('Latest version running' , 'epl-wpimport') );

	$live_import	=	function_exists('epl_get_option')  ?  epl_get_option('epl_wpimport_skip_update') : 'off';
	if ( $live_import == 'on' && get_post_meta($pid,'property_mod_date',true) != '' ) {
		/** only update posts if new data is available **/
		$postmodtime 		= epl_feedsync_format_date(get_post_meta($pid, 'property_mod_date',true ));
		$updatemodtime		= epl_feedsync_format_date($xml_node['@attributes']['modTime']);
		$updatemodtime		= apply_filters('epl_import_mod_time',$updatemodtime,$xml_node,$pid);

		if( strtotime($updatemodtime) > strtotime($postmodtime) ) {

			epl_wpimport_notification( 'update'  , $pid );

			// update
			return true;
		}

		epl_wpimport_notification( 'skip_unchanged'  , $pid );

		// Don't update
		return false;
	}

	epl_wpimport_notification( 'skip' , $pid );

	// Don't update
	return true;
}
if( defined('PMXI_VERSION') && version_compare( PMXI_VERSION, '4.5.0', '<' ) ) {
	add_filter('wp_all_import_is_post_to_update', 'epl_wpimport_is_post_to_update_depricated', 10, 2);
} else {
	add_filter('wp_all_import_is_post_to_update', 'epl_wpimport_is_post_to_update', 10, 4);
}

/**
 * Format Date function for EAC API
 *
 * @since 1.0.7
 */
function epl_feedsync_format_date_eac( $date , $sep = '/') {

	if ( empty ( $date ) )
		return;

	$date_example = '4/11/2015';

	$tempdate = explode( $sep , $date );
	$date = $tempdate[0].'-'.$tempdate[1].'-'.$tempdate[2].' '.$tempdate[3];

	return  date("Y-m-d H:i:s",strtotime($date));
}
// Usage
// [epl_feedsync_format_date_eac({ORIG_LDATE[1]},'/')]
