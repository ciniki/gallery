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
        // 0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Large UI Images')),
//        array('flag'=>array('bit'=>'6', 'name'=>'')),
//        array('flag'=>array('bit'=>'7', 'name'=>'')),
//        array('flag'=>array('bit'=>'8', 'name'=>'')),
        // 0x0100
        array('flag'=>array('bit'=>'9', 'name'=>'Photoframe')),
//        array('flag'=>array('bit'=>'10', 'name'=>'')),
//        array('flag'=>array('bit'=>'11', 'name'=>'')),
//        array('flag'=>array('bit'=>'12', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
