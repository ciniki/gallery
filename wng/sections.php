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
    // Album image carousel/slider
    //
    $sections['ciniki.gallery.album'] = array(
        'name'=>'Single Album Carousel',
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
            'image-format' => array('label'=>'Image Format', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
                'cropped' => 'Cropped',
                'padded' => 'Padded',
                )),
            ),
        );

    $sections['ciniki.gallery.flexalbum'] = array(
        'name'=>'Single Album',
        'module' => 'Gallery',
        'settings'=>array(
            'album-id' => array('label'=>'Album', 'type'=>'select', 'options'=>$albums, 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                ),
//            'title-show' => array('label'=>'Album Title & Description', 'type'=>'toggle', 'default'=>'no', 'toggles'=>array(
//                'no' => 'Hidden',
//                'yes' => 'Visible',
//                )),
            ),
        );

    $sections['ciniki.gallery.flexalbums'] = array(
        'name' => 'Multiple Albums',
        'module' => 'Gallery',
        'settings' => array(),
        );
    array_unshift($albums, array('id' => 0, 'name' => 'None'));
    for($i = 1; $i <= 10; $i++) {
        $sections['ciniki.gallery.flexalbums']['settings']["album-{$i}-id"] = array(
            'label' => 'Album', 
            'type' => 'select', 
            'options' => $albums, 
            'complex_options' => array('value'=>'id', 'name'=>'name'),
            );
    }

    $sort_options = [
        'name-asc' => 'Name A-Z',
        'name-desc' => 'Name Z-A',
        ];
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.gallery', 0x01) ) {
        $sort_options['sequence-asc'] = 'Sequence, 1-999';
        $sort_options['sequence-desc'] = 'Sequence, 999-1';
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.gallery', 0x02) ) {
        $sort_options['date-desc'] = 'Date, newest first';
        $sort_options['date-asc'] = 'Date, oldest first';
    }
    
    $sections['ciniki.gallery.albums'] = array(
        'name' => 'Albums',
        'module' => 'Gallery',
        'settings' => array(
            'title' => array('label'=>'Title', 'type'=>'text'),
            'content' => array('label'=>'Intro', 'type'=>'textarea'),
            'sort-by' => array('label'=>'List Albums by', 'type'=>'select', 
                'options' => $sort_options,
                ),
        ));

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
