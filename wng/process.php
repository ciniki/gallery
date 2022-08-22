<?php
//
// Description
// -----------
// This function will return the b
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_gallery_wng_process(&$ciniki, $tnid, &$request, $section) {

    $blocks = array();
    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.gallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.28', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.29', 'msg'=>"No category specified."));
    }

    if( isset($section['ref']) && $section['ref'] == 'ciniki.gallery.album' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'wng', 'albumProcess');
        return ciniki_gallery_wng_albumProcess($ciniki, $tnid, $request, $section);
    } elseif( isset($section['ref']) && $section['ref'] == 'ciniki.gallery.flexalbum' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'wng', 'flexAlbumProcess');
        return ciniki_gallery_wng_flexAlbumProcess($ciniki, $tnid, $request, $section);
    } elseif( isset($section['ref']) && $section['ref'] == 'ciniki.gallery.albums' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'gallery', 'wng', 'albumProcess');
        return ciniki_gallery_wng_albumProcess($ciniki, $tnid, $request, $section);
    }

    return array('stat'=>'ok');
}
?>
