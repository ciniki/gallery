<?php
//
// Description
// -----------
// This function will return a list of categories(albums) for the web galleries, 
// along with the images for each category highlight.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
// <categories>
//      <category name="Portraits" image_id="349" />
//      <category name="Landscape" image_id="418" />
//      ...
// </categories>
//
function ciniki_gallery_web_categories($ciniki, $settings, $tnid, $args) {

    $strsql = "SELECT id, name, permalink "
        . "FROM ciniki_gallery_albums "
        . "WHERE ciniki_gallery_albums.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (ciniki_gallery_albums.webflags&0x01) = 0 "
        . "AND name <> '' ";
    if( isset($args['category']) ) {
        $strsql .= "AND category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
    }
    if( !isset($settings['page-gallery-album-sort']) 
        || $settings['page-gallery-album-sort'] == 'name-asc' ) {
        $strsql .= "ORDER BY name ";
    } elseif( $settings['page-gallery-album-sort'] == 'name-desc' ) {
        $strsql .= "ORDER BY name DESC ";
    } elseif( $settings['page-gallery-album-sort'] == 'sequence-asc' ) {
        $strsql .= "ORDER BY sequence ASC, name ";
    } elseif( $settings['page-gallery-album-sort'] == 'sequence-desc' ) {
        $strsql .= "ORDER BY sequence DESC, name ";
    } elseif( $settings['page-gallery-album-sort'] == 'startdate-desc' ) {
        $strsql .= "ORDER BY start_date DESC, name ";
    } elseif( $settings['page-gallery-album-sort'] == 'startdate-desc' ) {
        $strsql .= "ORDER BY start_date DESC, name ";
    }
        
//  $strsql = "SELECT DISTINCT album AS name "
//      . "FROM ciniki_gallery "
//      . "WHERE ciniki_gallery.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//      . "AND (ciniki_gallery.webflags&0x01) = 0 "
//      . "AND album <> '' "
//      . "ORDER BY album "
//      . "";
//  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'categories', 'fname'=>'name', 
            'fields'=>array('id', 'name', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok');
    }
    $categories = $rc['categories'];

    //
    // Load highlight images
    //
    foreach($categories as $cnum => $cat) {
        //
        // Look for the highlight image, or the most recently added image
        //
        $strsql = "SELECT ciniki_gallery.image_id, ciniki_images.image "
            . "FROM ciniki_gallery, ciniki_images "
            . "WHERE ciniki_gallery.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND album_id = '" . ciniki_core_dbQuote($ciniki, $cat['id']) . "' "
            . "AND ciniki_gallery.image_id = ciniki_images.id "
            . "AND (ciniki_gallery.webflags&0x01) = 0 "
            . "ORDER BY (ciniki_gallery.webflags&0x10) DESC, ciniki_gallery.date_added DESC "
            . "LIMIT 1";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'image');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['image']) ) {
            $categories[$cnum]['image_id'] = $rc['image']['image_id'];
        }
    }

    foreach($categories as $cid => $cat) {
        if( !isset($cat['image_id']) || $cat['image_id'] == 0 ) {
            unset($categories[$cid]);
        }
    }

    return array('stat'=>'ok', 'categories'=>$categories);  
}
?>
