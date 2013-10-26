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
	$objects['image'] = array(
		'name'=>'Image',
		'sync'=>'yes',
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
