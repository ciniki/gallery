<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_gallery_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Album Sequence')),
        array('flag'=>array('bit'=>'2', 'name'=>'Album Start Date')),
        array('flag'=>array('bit'=>'3', 'name'=>'Album End Date')),
        array('flag'=>array('bit'=>'4', 'name'=>'Categories')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
