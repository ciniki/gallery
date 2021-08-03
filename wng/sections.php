<?php
//
// Description
// -----------
// This function will return the list of available sections to the ciniki.wng module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_gallery_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.gallery']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.36', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    $sections = array();

    //
    // Get the categories
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_gallery_albums "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (webflags&0x01) = 0 "
        . "ORDER BY category "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'albums', 'fname'=>'name', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.gallery.37', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $albums = isset($rc['albums']) ? $rc['albums'] : array();

    //
    // Image, Menu with no drop downs/submenus
    //
    $sections['ciniki.gallery.album'] = array(
        'name'=>'Single Album',
        'module' => 'Gallery',
        'settings'=>array(
            'album-id' => array('label'=>'Album', 'type'=>'select', 'options'=>$albums, 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                ),
            'title-show' => array('label'=>'Album Title & Description', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Hidden',
                'yes' => 'Visible',
                )),
            'speed'=>array('label'=>'Speed', 'type'=>'toggle', 'default'=>'medium', 'toggles'=>array(    
                'none' => 'No Auto Advance',
                'xslow' => 'X-Slow',
                'slow' => 'Slow',
                'medium' => 'Medium',
                'fast' => 'Fast',
                'xfast' => 'X-Fast',
                )),
            'padded' => array('label'=>'Image Format', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'no' => 'Cropped',
                'yes' => 'Padded',
                )),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
