<?php
//
// Description
// -----------
// This function will process a web request for the blog module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_gallery_web_processRequest(&$ciniki, $settings, $business_id, $args) {

    if( !isset($ciniki['business']['modules']['ciniki.gallery']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.gallery.24', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }
    $page = array(
        'title'=>$args['page_title'],
        'breadcrumbs'=>$args['breadcrumbs'],
        'blocks'=>array(),
        );

    //
    // Setup titles
    //
    if( count($page['breadcrumbs']) == 0 ) {
        $page['breadcrumbs'][] = array('name'=>'Gallery', 'url'=>$args['base_url']);
    }

    //
    // Default to display list of albums
    //
    $display = 'albums';
    $ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

    //
    // Setup the base url as the base url for this page. This may be altered below
    // as the uri_split is processed, but we do not want to alter the original passed in.
    //

    //
    // Parse the url
    //
    $uri_split = $args['uri_split'];

    //
    // Check if categories enabled, get the list and display as submenu
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.gallery', 0x08) ) {
        $selected_category = '';
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $strsql = "SELECT DISTINCT category "
            . "FROM ciniki_gallery_albums "
            . "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY category "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.gallery', 'categories', 'category');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $page['submenu'] = array();
        foreach($rc['categories'] as $cat) {
            $permalink = ciniki_core_makePermalink($cat);
            $page['submenu'][] = array('name'=>$cat, 'url'=>$args['base_url'] . '/' . $permalink);
            //
            // Check if category selected
            //
            if( isset($uri_split[0]) && $uri_split[0] == $permalink ) {
                $selected_category = $cat;
                $cat_permalink = array_shift($uri_split);
            }
        }
    }

    //
    // Get the list of albums
    //
    $strsql = "SELECT ciniki_gallery_albums.id, "
        . "ciniki_gallery_albums.name AS title, "
        . "ciniki_gallery_albums.permalink, "
        . "ciniki_gallery_albums.description, "
        . "COUNT(ciniki_gallery.album_id) AS num_images "
        . "FROM ciniki_gallery_albums "
        . "LEFT JOIN ciniki_gallery ON ("
            . "ciniki_gallery_albums.id = ciniki_gallery.album_id "
            . "AND ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND (ciniki_gallery.webflags&0x01) = 0 "
            . ") "
        . "WHERE ciniki_gallery_albums.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_gallery_albums.webflags&0x01) = 0 ";
    if( isset($selected_category) ) {
        $strsql .= "AND ciniki_gallery_albums.category = '" . ciniki_core_dbQuote($ciniki, $selected_category) . "' ";
    }
    $strsql .= "AND ciniki_gallery_albums.name <> '' "
        . "GROUP BY ciniki_gallery_albums.id "
        . "HAVING num_images > 0 "
        . "";
    if( !isset($settings['page-gallery-album-sort']) 
        || $settings['page-gallery-album-sort'] == 'name-asc' ) {
        $strsql .= "ORDER BY ciniki_gallery_albums.name ";
    } elseif( $settings['page-gallery-album-sort'] == 'name-desc' ) {
        $strsql .= "ORDER BY ciniki_gallery_albums.name DESC ";
    } elseif( $settings['page-gallery-album-sort'] == 'sequence-asc' ) {
        $strsql .= "ORDER BY ciniki_gallery_albums.sequence ASC, ciniki_gallery_albums.name ";
    } elseif( $settings['page-gallery-album-sort'] == 'sequence-desc' ) {
        $strsql .= "ORDER BY ciniki_gallery_albums.sequence DESC, ciniki_gallery_albums.name ";
    } elseif( $settings['page-gallery-album-sort'] == 'startdate-desc' ) {
        $strsql .= "ORDER BY ciniki_gallery_albums.start_date DESC, ciniki_gallery_albums.name ";
    } elseif( $settings['page-gallery-album-sort'] == 'startdate-desc' ) {
        $strsql .= "ORDER BY ciniki_gallery_albums.start_date DESC, ciniki_gallery_albums.name ";
    }
        
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
        array('container'=>'albums', 'fname'=>'permalink', 
            'fields'=>array('id', 'title', 'permalink', 'description', 'num_images')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['albums']) ) {
        $page['blocks'][] = array('type'=>'content', 'content'=>"There are currently no photos available. Please check back soon.");
        return array('stat'=>'ok', 'page'=>$page);
    }
    $albums = $rc['albums'];

    //
    // Check if album specified
    //
    $cur_album = null;
    if( isset($uri_split[0]) && $uri_split[0] != '' ) {
        foreach($albums as $album) {
            if( $album['permalink'] == $uri_split[0] ) {
                $cur_album = $album;
                break;
            }
        }
        if( $cur_album == null ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.gallery.25', 'msg'=>'Album not found'));
        }
    }

    //
    // Check if only one album
    //
    if( count($albums) == 1 ) {
        $cur_album = array_pop($albums);
    }

    //
    // Load the list of images for the album
    //
    if( $cur_album != null ) {
        $display = 'album';
        $page['breadcrumbs'][] = array('name'=>$cur_album['title'], 'url'=>$args['base_url'] . '/' . $cur_album['permalink']);

        $strsql = "SELECT ciniki_gallery.id, "
            . "ciniki_gallery.name AS title, "
            . "ciniki_gallery.permalink, "
            . "ciniki_gallery.image_id, "
            . "ciniki_gallery.description, "
            . "IF(ciniki_images.last_updated > ciniki_gallery.last_updated, "
                . "UNIX_TIMESTAMP(ciniki_images.last_updated), "
                . "UNIX_TIMESTAMP(ciniki_gallery.last_updated)) AS last_updated "
            . "FROM ciniki_gallery "
            . "LEFT JOIN ciniki_images ON ("
                . "ciniki_gallery.image_id = ciniki_images.id "
                . "AND ciniki_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
            . "WHERE ciniki_gallery.album_id = '" . ciniki_core_dbQuote($ciniki, $cur_album['id']) . "' "
            . "AND ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND ciniki_gallery.image_id > 0 "
            . "AND (ciniki_gallery.webflags&0x01) = 0 "
            . "ORDER BY ciniki_gallery.date_added DESC ";

        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.gallery', array(
            array('container'=>'images', 'fname'=>'id', 'fields'=>array('id', 'title', 'permalink', 'image_id', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['images']) || count($rc['images']) == 0 ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.gallery.26', 'msg'=>'No images found.'));
        }
        $album_images = $rc['images'];
    }

    //
    // Check if image specified
    //
    $first_image = null;
    $prev_image = null;
    $cur_image = null;
    $next_image = null;
    $last_image = null;
    if( isset($uri_split[1]) && $uri_split[1] != '' ) {
        //
        // Find the image
        //
        foreach($album_images as $image) {
            if( $first_image == null ) {
                $first_image = $image;
            }
            if( $image['permalink'] == $uri_split[1] ) {
                $cur_image = $image;
            } elseif( $cur_image != null && $next_image == null ) {
                $next_image = $image;
            }
            if( $cur_image == null ) {
                $prev_image = $image;
            }
            $last_image = $image;
        }
        if( $cur_image == null ) {
            return array('stat'=>'404', 'err'=>array('code'=>'ciniki.gallery.27', 'msg'=>"I'm sorry, but we couldn't find the image your requested."));
        }
        if( count($album_images) == 1 ) {
            $prev_image = null;
            $next_image = null;
        } elseif( $prev_image == null ) {
            $prev_image = $last_image;
        } elseif( $next_image == null ) {
            $next_image = $first_image;
        }
        $display = 'image';
        $page['breadcrumbs'][] = array('name'=>$cur_image['title'], 'url'=>$args['base_url'] . '/' . $cur_album['permalink'] . '/' . $cur_image['permalink']);
    }


    //
    // Display the list of albums
    //
    if( $display == 'albums' ) {
        foreach($albums as $aid => $album) {
            //
            // Look for the highlight image, or the most recently added image
            //
            $strsql = "SELECT ciniki_gallery.image_id, ciniki_images.image "
                . "FROM ciniki_gallery, ciniki_images "
                . "WHERE ciniki_gallery.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND album_id = '" . ciniki_core_dbQuote($ciniki, $album['id']) . "' "
                . "AND ciniki_gallery.image_id = ciniki_images.id "
                . "AND (ciniki_gallery.webflags&0x01) = 0 "
                . "ORDER BY (ciniki_gallery.webflags&0x10) DESC, ciniki_gallery.date_added DESC "
                . "LIMIT 1";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.gallery', 'image');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['image']) ) {
                $albums[$aid]['image_id'] = $rc['image']['image_id'];
            }
        }
        $page['blocks'][] = array('type'=>'tagimages', 'base_url'=>$args['base_url'], 'tags'=>$albums);
    } 
   
    //
    // Display the list of thumbnails for an album
    //
    elseif( $display == 'album' ) {
        if( isset($cur_album['description']) && $cur_album['description'] != '' ) {
            $page['blocks'][] = array('type'=>'content', 'content'=>$cur_album['description']);
        }
        $page['blocks'][] = array('type'=>'gallery', 'base_url'=>$args['base_url'] . '/' . $cur_album['permalink'], 'images'=>$album_images);
    } 
    
    //
    // Display an image from an album
    //
    elseif( $display == 'image' ) {
        $block = array('type'=>'galleryimage', 'image'=>$cur_image, 
            'quality'=>((isset($settings['page-gallery-image-quality']) && $settings['page-gallery-image-quality'] != '') ? $settings['page-gallery-image-quality'] : 'regular'),
            'size'=>((isset($settings['page-gallery-image-size']) && $settings['page-gallery-image-size'] != '') ? $settings['page-gallery-image-size'] : 'regular'),
            );
        if( $prev_image != null ) {
            $block['prev'] = array('url'=>$args['base_url'] . '/' . $cur_album['permalink'] . '/' . $prev_image['permalink']);
        }
        if( $next_image != null ) {
            $block['next'] = array('url'=>$args['base_url'] . '/' . $cur_album['permalink'] . '/' . $next_image['permalink']);
        }
        $page['blocks'][] = $block;
    }
    
    return array('stat'=>'ok', 'page'=>$page);
}
?>
