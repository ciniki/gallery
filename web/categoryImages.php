<?php
//
// Description
// -----------
// This function will return a list of categories for the web galleries
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
// type:            The list to return, either by category or year.
//
//                  - category
//                  - year
//
// type_name:       The name of the category or year to list.
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
function ciniki_gallery_web_categoryImages($ciniki, $settings, $tnid, $args) {
    //
    // Get the gallery information
    //
    if( isset($args['type']) && $args['type'] == 'album' ) {
        $strsql = "SELECT ciniki_gallery_albums.name, "
            . "ciniki_gallery_albums.permalink, "
            . "ciniki_gallery_albums.description "
            . "FROM ciniki_gallery_albums "
            . "WHERE ciniki_gallery_albums.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_gallery_albums.webflags&0x01) = 0 "
            . "AND ciniki_gallery_albums.permalink = '" . ciniki_core_dbQuote($ciniki, $args['type_name']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'album');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['album']) ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.gallery.19', 'msg'=>'I\'m sorry, we are unable to find an album by that name.'));
        }
        $album = $rc['album'];
    } else {
        $album = array('name'=>'');
    }

    $strsql = "SELECT ciniki_gallery.name AS title, "
        . "ciniki_gallery.permalink, "
        . "ciniki_gallery.image_id, "
        . "ciniki_gallery_albums.name AS album_name, "
        . "IF(ciniki_images.last_updated > ciniki_gallery.last_updated, "
            . "UNIX_TIMESTAMP(ciniki_images.last_updated), "
            . "UNIX_TIMESTAMP(ciniki_gallery.last_updated)) AS last_updated "
        . "FROM ciniki_gallery_albums "
        . "LEFT JOIN ciniki_gallery ON (ciniki_gallery_albums.id = ciniki_gallery.album_id "
            . "AND ciniki_gallery.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_gallery.image_id > 0 "
            . "AND (ciniki_gallery.webflags&0x01) = 0 "
            . ") "
        . "LEFT JOIN ciniki_images ON (ciniki_gallery.image_id = ciniki_images.id "
            . "AND ciniki_images.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_gallery_albums.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_gallery_albums.webflags&0x01) = 0 "
        . "";
    if( isset($args['type']) && $args['type'] == 'album' ) {
        $strsql .= "AND ciniki_gallery_albums.permalink = '" . ciniki_core_dbQuote($ciniki, $args['type_name']) . "' "
            . "";
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.20', 'msg'=>"Unable to find images."));
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
    $album_name = '';
    foreach($rc['rows'] as $rownum => $row) {
        $caption = $row['title'];
        $album_name = $row['album_name'];
        array_push($images, array('title'=>$row['title'], 'permalink'=>$row['permalink'], 
            'image_id'=>$row['image_id'], 
            'caption'=>$caption, 'last_updated'=>$row['last_updated']));
    }
    
    return array('stat'=>'ok', 'album'=>$album, 'album_name'=>$album_name, 'images'=>$images);
}
?>
