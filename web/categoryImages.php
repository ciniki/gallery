<?php
//
// Description
// -----------
// This function will return a list of categories for the web galleries
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
// type:			The list to return, either by category or year.
//
//					- category
//					- year
//
// type_name:		The name of the category or year to list.
//
// Returns
// -------
// <images>
//		[title="Slow River" permalink="slow-river" image_id="431" 
//			caption="Based on a photograph taken near Slow River, Ontario, Pastel, size: 8x10" sold="yes"
//			last_updated="1342653769"],
//		[title="Open Field" permalink="open-field" image_id="217" 
//			caption="An open field in Ontario, Oil, size: 8x10" sold="yes"
//			last_updated="1342653769"],
//		...
// </images>
//
function ciniki_gallery_web_categoryImages($ciniki, $settings, $business_id, $args) {

	$strsql = "SELECT name AS title, permalink, image_id, "
		. "IF(ciniki_images.last_updated > ciniki_gallery.last_updated, "
		. "UNIX_TIMESTAMP(ciniki_images.last_updated), "
		. "UNIX_TIMESTAMP(ciniki_gallery.last_updated)) AS last_updated "
		. "FROM ciniki_gallery "
		. "LEFT JOIN ciniki_images ON (ciniki_gallery.image_id = ciniki_images.id) "
		. "WHERE ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (webflags&0x01) = 0 "
		. "";
	if( $args['type'] == 'album' ) {
		$strsql .= "AND album = '" . ciniki_core_dbQuote($ciniki, $args['type_name']) . "' "
			. "";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'268', 'msg'=>"Unable to find images."));
	}

	//
	// Put the latest additions first
	//
	$strsql .= "ORDER BY ciniki_gallery.date_added DESC ";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$images = array();
	foreach($rc['rows'] as $rownum => $row) {
		$caption = $row['title'];
		array_push($images, array('title'=>$row['title'], 'permalink'=>$row['permalink'], 
			'image_id'=>$row['image_id'], 
			'caption'=>$caption, 'last_updated'=>$row['last_updated']));
	}
	
	return array('stat'=>'ok', 'images'=>$images);
}
?>
