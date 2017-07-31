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
            'name'=>array('name'=>'Album Name'),
            'permalink'=>array('name'=>'Permalink'),
            'category'=>array('name'=>'Cateogry', 'default'=>''),
            'webflags'=>array('name'=>'Options', 'default'=>'0'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'start_date'=>array('name'=>'Start Date', 'default'=>''),
            'end_date'=>array('name'=>'End Date', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_gallery_history',
        );
    $objects['image'] = array(
        'name'=>'Image',
        'sync'=>'yes',
        'table'=>'ciniki_gallery',
        'fields'=>array(
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'album_id'=>array('name'=>'Album', 'ref'=>'ciniki.images.album'),
            'webflags'=>array('name'=>'Options', 'default'=>'0'),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_gallery_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
