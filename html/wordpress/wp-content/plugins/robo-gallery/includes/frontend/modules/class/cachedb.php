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

/* todo: singleton */

if (!defined('WPINC')) {
    exit;
}

class roboGalleryModuleCacheDB
{

    private $core       = null;
    private $cache_time = 3000;
    private $table_name = '';

    public function __construct($core)
    {
        $this->core = $core;
        $this->init();
        $this->checkVersion();
    }

    private function init()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'robogallery_cache';
        $this->cache_time = (int) get_site_option(ROBO_GALLERY_PREFIX . 'dbcache_time', 3000);
        $this->initClearCache();
    }

    private function checkVersion()
    {
        $saved_db_version = (int) get_site_option(ROBO_GALLERY_PREFIX . 'dbcache_version', -1);

        if ($saved_db_version == -1) {
            add_site_option(ROBO_GALLERY_PREFIX . 'dbcache_version', 0);
        }

        global $wpdb;
        if (!in_array($this->table_name, $wpdb->Tables())) {
            $this->createTables();
        }

        if ($saved_db_version < 100) {
            update_site_option(ROBO_GALLERY_PREFIX . 'dbcache_version', 100);
        }
    }

    public function initClearCache()
    {
        if (!wp_next_scheduled(ROBO_GALLERY_PREFIX . 'clear_db_cache_hook')) {
            wp_schedule_event(time(), 'hourly', ROBO_GALLERY_PREFIX . 'clear_db_cache_hook');
        }
        add_action('clear_db_cache_hook', array($this, 'clear_old_cache'));
    }

    public function clear_old_cache($resourceId = '')
    {
        global $wpdb;

        if (!$resourceId) {
            $wpdb->get_results(
                $wpdb->prepare("DELETE FROM {$this->table_name} WHERE time < %d", array(
                    time(),
                )
                )
            );
            return;
        }

        $wpdb->get_results(
            $wpdb->prepare("DELETE FROM {$this->table_name}  WHERE time < %d AND cache_id = %s ",
                array(
                    time(),
                    $resourceId,
                )
            )
        );
    }

    private function createTables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                    id int(11) NOT NULL AUTO_INCREMENT,
                    cache_id varchar(255) DEFAULT NULL,
                    cache_content longtext NOT NULL,
                    time bigint(11) DEFAULT '0' NOT NULL,
                    UNIQUE KEY id (id)
		        ) {$charset_collate} ;"
        );
    }

    public function update($resourceId, $data, $time_end)
    {
        $oldCache = $this->getContent($resourceId);

        if (is_array($oldCache)) {
            $this->clear_old_cache($resourceId);
        }

        global $wpdb;
        $wpdb->insert(
            $this->table_name,
            array(
                'cache_id'      => $resourceId,
                'cache_content' => json_encode($data),
                'time'          => $time_end,
            ),
            array('%s', '%s', '%d')
        );
    }

    public function delete($resourceId)
    {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('cache_id' => $resourceId));
    }

    public function getContent($resourceId)
    {

        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE cache_id = %s order by time DESC  limit 1",
                array(
                    $resourceId,
                )
            )
        );

        if (!is_object($row) || !$row->cache_content || !$row->time) {
            return false;
        }

        if (time() > $row->time) {
            $this->clear_old_cache();
            return false;
        }
        return json_decode($row->cache_content, 1);
    }

}
