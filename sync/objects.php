<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_gallery_sync_objects($ciniki, &$sync, $business_id, $args) {

	$objects = array();
	$objects['item'] = array(
		'name'=>'Gallery Item',
		'table'=>'ciniki_gallery',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'album'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_gallery_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
