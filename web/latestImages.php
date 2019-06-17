<?php
//
// Description
// -----------
// This funciton will return a list of the latest added items in the art catalog. 
// These are used on the homepage of the tenant website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get images for.
// limit:           The maximum number of images to return.
//
// Returns
// -------
// <images>
//      [title="Slow River" permalink="slow-river" image_id="431" 
//          caption="Based on a photograph taken near Slow River, Ontario, Pastel, size: 8x10" sold="yes"
//          last_updated="1342653769"],
//      [title="Open Field" permalink="open-field" image_id="217" 
//          caption="An open field in Ontario, Oil, size: 8x10" sold="yes"
//          last_updated="1342653769"],
//      ...
// </images>
//
function ciniki_gallery_web_latestImages($ciniki, $settings, $tnid, $limit) {

    $strsql = "SELECT name AS title, permalink, image_id, "
        . "IF(ciniki_images.last_updated > ciniki_gallery.last_updated, "
        . "UNIX_TIMESTAMP(ciniki_images.last_updated), "
        . "UNIX_TIMESTAMP(ciniki_gallery.last_updated)) AS last_updated "
        . "FROM ciniki_gallery "
        . "LEFT JOIN ciniki_images ON (ciniki_gallery.image_id = ciniki_images.id) "
        . "WHERE ciniki_gallery.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_gallery.image_id > 0 "
        . "AND (ciniki_gallery.webflags&0x01) = 0 "
        . "";
    if( $limit != '' && $limit > 0 && is_int($limit) ) {
        $strsql .= "ORDER BY ciniki_gallery.date_added DESC "
            . "LIMIT " . intval($limit) . " ";
    } else {
        $strsql .= "ORDER BY ciniki_gallery.date_added DESC "
            . "LIMIT 4 ";
    }

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
