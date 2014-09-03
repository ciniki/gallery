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
function ciniki_gallery_objects($ciniki) {
	
	$objects = array();
	$objects['album'] = array(
		'name'=>'Album',
		'sync'=>'yes',
		'table'=>'ciniki_gallery_albums',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'sequence'=>array(),
			'start_date'=>array(),
			'end_date'=>array(),
			'description'=>array(),
			),
		'history_table'=>'ciniki_gallery_history',
		);
	$objects['image'] = array(
		'name'=>'Image',
		'sync'=>'yes',
		'table'=>'ciniki_gallery',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'album_id'=>array('ref'=>'ciniki.images.album'),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_gallery_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
