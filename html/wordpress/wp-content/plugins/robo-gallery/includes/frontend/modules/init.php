<?php
/* 
*      Robo Gallery     
*      Version: 5.0.5 - 31754
*      By Robosoft
*
*      Contact: https://robogallery.co/ 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */
 
if ( ! defined( 'WPINC' ) )  die;

require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/abstraction.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/assets.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/config.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/cache.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/cachedb.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/scss.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/element.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/jsoptions.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/stats.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/customcss.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/addtexts.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/source/class.source.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/loader.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'class/protection.php';


require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/assets.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/menu/menu.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/grid/grid.v1.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/grid/grid.columns.v1.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/hover.v1.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/layout.v1.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/effects.set1.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/polaroid.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/resize.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/seo.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/size.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/tags.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/lightbox.php';
//require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'base-grid/search.php';

require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'slider/layout.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'slider/assets.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'slider/options.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'slider/content.php';

require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'simple/layout.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'simple/assets.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'simple/content.php';

require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'robogrid/layout.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'robogrid/assets.php';
require_once ROBO_GALLERY_FRONTEND_MODULES_PATH.'robogrid/content.php';