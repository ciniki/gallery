//
// The app to add/edit gallery images
//
function ciniki_gallery_main() {
	this.uploadCount = 0;
	this.webFlags = {
		'1':{'name':'Hidden'},
		'5':{'name':'Album Highlight'},
		};
	this.init = function() {
		//
		// The panel to list the images by album
		//
		this.list = new M.panel('Album',
			'ciniki_gallery_main', 'list',
			'mc', 'wide', 'sectioned', 'ciniki.gallery.main.list');
		this.list.data = {};
		this.list.album = '';
		this.list.sections = {
			'images':{'label':'', 'type':'simplethumbs'},
			};
		this.list.noData = function(s) {
			return this.sections[s].noData;
		};
		this.list.sectionData = function(s) {
			return this.data;
		};
		this.list.thumbSrc = function(s, i, d) {
			if( d.image.image_id > 0 && d.image.image_data != null && d.image.image_data != '' ) {
				return d.image.image_data;
			} else {
				return '/ciniki-manage-themes/default/img/noimage_75.jpg';
			}
		};
		this.list.thumbTitle = function(s, i, d) {
			if( d.image.name != null ) { return d.image.name; }
			return '';
		};
		this.list.thumbID = function(s, i, d) {
			if( d.image.image_id != null ) { return d.image.image_id; }
			return 0;
		};
		this.list.thumbFn = function(s, i, d) {
			return 'M.ciniki_gallery_main.showEdit(\'M.ciniki_gallery_main.showList();\',\'' + d.image.id + '\');';
		};
		this.list.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.gallery.imageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'album':M.ciniki_gallery_main.list.album});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.list.addDropImageRefresh = function() {
			M.ciniki_gallery_main.showList();
		};
		this.list.addButton('add', 'Add', 'M.ciniki_gallery_main.showEdit(\'M.ciniki_gallery_main.showList();\',0,escape(M.ciniki_gallery_main.list.album));');
		this.list.addButton('tools', 'Tools', 'M.ciniki_gallery_main.tools.show(\'M.ciniki_gallery_main.showList();\');');
		this.list.addClose('Back');

		//
		// The panel to display the list of albums
		//
		this.albums = new M.panel('Albums',
			'ciniki_gallery_main', 'albums',
			'mc', 'medium', 'sectioned', 'ciniki.gallery.main.albums');
		this.albums.data = {};
		this.albums.sections = {
			'albums':{'label':'', 'type':'simplelist'},
		};
		this.albums.sectionData = function(s) {
			return this.data;
		};
		this.albums.listValue = function(s, i, d) {
			return d.album.name;
		};
		this.albums.listCount = function(s, i, d) {
			return d.album.count;
		};
		this.albums.listFn = function(s, i, d) {
			return 'M.ciniki_gallery_main.showList(\'M.ciniki_gallery_main.showAlbums();\',\'' + escape(d.album.name) + '\');';
		};
		this.albums.addButton('add', 'Add', 'M.ciniki_gallery_main.showEdit(\'M.ciniki_gallery_main.showList();\',0,\'\');');
		this.albums.addClose('Back');

		//
		// The panel to display the edit form
		//
		this.edit = new M.panel('Edit Image',
			'ciniki_gallery_main', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.gallery.main.edit');
		this.edit.default_data = {};
		this.edit.data = {};
		this.edit.sections = {
			'_image':{'label':'Photo', 'fields':{
				'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
			}},
			'info':{'label':'Information', 'type':'simpleform', 'fields':{
				'name':{'label':'Title', 'type':'text'},
				'album':{'label':'Album', 'type':'text', 'livesearch':'yes'},
				'webflags':{'label':'Website', 'type':'flags', 'join':'yes', 'flags':this.webFlags},
			}},
			'_description':{'label':'Description', 'type':'simpleform', 'fields':{
				'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_gallery_main.saveImage();'},
				'download':{'label':'Download Original', 'fn':'M.ciniki_gallery_main.downloadImage(M.ciniki_gallery_main.edit.data.image_id,\'original\');'},
				'delete':{'label':'Remove Image', 'fn':'M.ciniki_gallery_main.deleteImage();'},
			}},
		};
		this.edit.fieldValue = function(s, i, d) { 
			if( this.data[i] != null ) { return this.data[i]; } 
			return ''; 
		};
		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'album' ) {
				var rsp = M.api.getJSONBgCb('ciniki.gallery.imageAlbumSearch', 
					{'business_id':M.curBusinessID, 
					'start_needle':value, 'limit':25},
					function(rsp) { 
						M.ciniki_gallery_main.edit.liveSearchShow(s, i, M.gE(M.ciniki_gallery_main.edit.panelUID + '_' + i), rsp.albums); 
					});
			}
		};
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'album' ) { return d.album.name; }
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'album' ) {
				return 'M.ciniki_gallery_main.edit.updateField(\'' + s + '\',\'album\',\'' + escape(d.album.name) + '\');';
			}
		};
		this.edit.updateField = function(s, fid, result) {
			M.gE(this.panelUID + '_' + fid).value = unescape(result);
			this.removeLiveSearch(s, fid);
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.gallery.imageHistory', 'args':{'business_id':M.curBusinessID, 
				'gallery_image_id':this.gallery_image_id, 'field':i}};
		}
		this.edit.addDropImage = function(iid) {
			this.setFieldValue('image_id', iid);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_gallery_main.saveImage();');
		this.edit.addClose('Cancel');

		//
		// The tools available to work on customer records
		//
		this.tools = new M.panel('Gallery Tools',
			'ciniki_gallery_main', 'tools',
			'mc', 'narrow', 'sectioned', 'ciniki.gallery.main.tools');
		this.tools.data = {};
		this.tools.sections = {
			'tools':{'label':'Adjustments', 'list':{
				'categories':{'label':'Edit Album Names', 'fn':'M.startApp(\'ciniki.gallery.fieldupdate\', null, \'M.ciniki_gallery_main.tools.show();\',\'mc\',{\'field\':\'album\',\'fieldname\':\'Album Names\'});'},
				}},
			};
		this.tools.addClose('Back');
	};

	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create container
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_gallery_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		}
	
		if( args.add != null && args.add == 'yes' ) {
			this.showEdit(cb, 0, args.album);
		} else if( args.img_id != null && args.img_id > 0 ) {
			this.showEdit(cb, args.img_id);
		} else {
			this.list.album = null;
			this.showList(cb, null);
		}
		return true;
	};

	this.showList = function(cb, album) {
		if( album != null ) {
			this.list.album = album;
		} 
		// Decide which album to list
		if( this.list.album != null ) {
			var rsp = M.api.getJSONCb('ciniki.gallery.imageList', 
				{'business_id':M.curBusinessID, 'album':this.list.album}, function(rsp) {
					M.ciniki_gallery_main.showListFinish(cb, rsp);
				});
		} else {
			var rsp = M.api.getJSONCb('ciniki.gallery.imageList', 
				{'business_id':M.curBusinessID}, function(rsp) {
					M.ciniki_gallery_main.showListFinish(cb, rsp);
				});
		}
	};

	this.showListFinish = function(cb, rsp) {
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		if( rsp.images != null ) {
			var p = M.ciniki_gallery_main.list;
			if( rsp.album != null ) {
				p.album = rsp.album;
			}
			p.data = rsp.images;
			p.sections.images.label = rsp.album;
			p.refresh();
			p.show(cb);
		} else if( rsp.albums != null ) {
			var p = M.ciniki_gallery_main.albums;
			p.data = rsp.albums;
			p.refresh();
			p.show(cb);
		} else {
			alert('Sorry, no images found');
		}
	};

	this.showAlbums = function() {
		this.list.album = null;
		this.showList();
	};

	this.showEdit = function(cb, iid, album) {
		if( iid != null ) {
			this.edit.gallery_image_id = iid;
		}
		if( this.edit.gallery_image_id > 0 ) {
			var rsp = M.api.getJSONCb('ciniki.gallery.imageGet', 
				{'business_id':M.curBusinessID, 'gallery_image_id':this.edit.gallery_image_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_gallery_main.edit;
					p.data = rsp.image;
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.reset();
			this.edit.data = {};
			if( album != null ) {
				this.edit.data['album'] = unescape(album);
			}
			if( this.edit.data['album'] == 'Uncategorized' ) {
				this.edit.data['album'] = '';
			}
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveImage = function() {
		if( this.edit.gallery_image_id > 0 ) {
			var c = this.edit.serializeFormData('no');
			if( c != '' ) {
				var rsp = M.api.postJSONFormData('ciniki.gallery.imageUpdate', 
					{'business_id':M.curBusinessID, 
					'gallery_image_id':this.edit.gallery_image_id}, c,
						function(rsp) {
							if( rsp.stat != 'ok' ) {
								M.api.err(rsp);
								return false;
							} 
							M.ciniki_gallery_main.edit.close();
						});
			} else {
				M.ciniki_gallery_main.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONFormData('ciniki.gallery.imageAdd', {'business_id':M.curBusinessID}, c,
				function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_gallery_main.edit.close();
				});
		}
	};

	this.downloadImage = function(iid, version) {
		window.open(M.api.getUploadURL('ciniki.images.get', {'business_id':M.curBusinessID,
			'image_id':iid, 'version':version, 'attachment':'yes'}));
	};

	this.deleteImage = function() {
		if( confirm('Are you sure you want to delete this image?') ) {
			var rsp = M.api.getJSONCb('ciniki.gallery.imageDelete', 
				{'business_id':M.curBusinessID, 'gallery_image_id':this.edit.gallery_image_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_gallery_main.edit.close();
				});
		}
	};
}
