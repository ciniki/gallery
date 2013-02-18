<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// gallery_image_id:	The ID of the item in the art catalog to get the history for.
// field:				The field to get the history for. This can be any of the 
//						elements returned by the ciniki.gallery.get method.
//
// Returns
// -------
// <history>
//	<action user_id="2" date="May 12, 2012 10:54 PM" value="photographs" age="2 months" user_display_name="Andrew" />
//	...
// </history>
//
function ciniki_gallery_imageHistory($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'gallery_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Image ID'), 
		'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner, or sys admin
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'private', 'checkAccess');
	$rc = ciniki_gallery_checkAccess($ciniki, $args['business_id'], 'ciniki.gallery.imageHistory');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
	return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.gallery', 'ciniki_gallery_history', $args['business_id'], 'ciniki_gallery', $args['gallery_image_id'], $args['field']);
}
?>
