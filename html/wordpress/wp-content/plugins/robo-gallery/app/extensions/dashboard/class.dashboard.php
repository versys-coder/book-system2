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

if ( !class_exists( 'rbsGalleryDashboard' ) ){


    class rbsGalleryDashboard
    {
        protected $title = '';
        protected $config = array();
        
        protected $default_item = array( 
        				'title' => '',
	                    'menu_title' 	=> '',
	                    'name' 			=> '',
	                    'content' 		=> '',
	                    'parent_slug' 	=> '',
	                    'url' 			=> '',
                	);

        protected $slug = '';
        protected $active_content = '';
        protected $url_external_button1 = '';
        protected $url_external_button2 = '';
        protected $active_tab = '';
        protected $page_name = '';
        protected $page_menu = '';
        protected $page_title = '';

        protected $tag = '';

        public function __construct()
        {
            $this->title = __('Welcome to Robo Gallery', 'robo-gallery');
            $this->slug = 'edit.php?post_type='.ROBO_GALLERY_TYPE_POST;
            $this->page_name = __('Overview', 'robo-gallery');

            $this->page_menu = 'overview';
            $this->tag = 'robo-gallery-overview';

            $this->page_title = 'Robo Gallery Overview';

            $this->url_external_button1 = 'https://www.robogallery.co/go.php?product=gallery&task=showcase';
            $this->url_external_button2 = 'https://www.robogallery.co/go.php?product=gallery&task=gopro';

            $config = array(
                
                array(
                    'title' => __('Overview', 'robo-gallery'),
                    'name' => 'overview',
                    'content' => 'overview.php',
                    'parent_slug' => $this->slug
                ),
				array(
                    'title' => __('Add-ons', 'robo-gallery'),
                    'url' => $this->slug.'&page=robo_gallery_table-addons',

                ),
				array(
                    'title' => __('Help & Support', 'robo-gallery'),
                    'name' => 'video-guide',
                    'content' => 'video_guide.php',
                    'parent_slug' => $this->slug

                ),
				array(
                    'title' => __('Demos', 'robo-gallery'),
                    'url' => 'https://www.robogallery.co/go.php?product=gallery&task=showcase',

                ),
				array(
                    'title' => __('Get Pro Version', 'robo-gallery'),
                    'url' => 'https://www.robogallery.co/go.php?product=gallery&task=gopro',

                )
            );

       		for ($i = 0; $i < count($config); $i++) {
       			$this->config[] = array_merge( $this->default_item, $config[$i] );
       		}

            
            if ( count($this->config) && is_array($this->config) && isset($this->slug) ){
                add_action('admin_menu', array($this, 'add_menu_items'));
            }

            if( isset($_GET['firstview']) && $_GET['firstview']==1 ){
            	delete_option( 'robo_gallery_redirect_overview' );
            }
        }


        function add_menu_items(){

            $page = add_submenu_page($this->slug, $this->page_title, $this->page_name, 'manage_options', $this->page_menu, array($this, 'view'));
            add_action('admin_print_styles-' . $page, array($this, 'admin_styles'));
            
        }


        function showTabs()
        {
        	$returnHTML = '';
            $this->active_tab = isset($_GET['tab']) && $_GET['tab'] ? sanitize_title($_GET['tab']) : $this->config[0]['name'];

            foreach ($this->config as $item) {

                $link = '#';
                if ( $item['content'] ) {
                    $link = $this->slug . '&page=' . $this->page_menu .'&tab=' . $item['name'];
                } elseif( $item['url'] ) {
                    $link = $item['url'];
                }
                if ( $this->active_tab === $item['name'] ) {
                    $this->active_content = $item['content'];
                }
                $returnHTML .= 
                '<a href="'.$link.'" '.( !$item['content'] ?'target="_blank"':'').' class="nav-tab '.( $this->active_tab == $item['name'] ? 'nav-tab-active' : '').'">
                    '.$item['title'].'
                </a>';
            }
            echo $returnHTML;
        }

        function admin_styles()
        {
            wp_enqueue_style( $this->tag, plugins_url('assets/style.css', __FILE__));
        }

        function view()
        {
            $this->active_tab = isset($_GET['page']) ? sanitize_title($_GET['page']) : $this->config[0]['name']; 
            ?>
            <div class="wrap about-wrap">
                <div class="rbsDashboardGallery-external-button">
                    <h1 class="rbsDashboardGallery-title"><?php echo $this->title; ?></h1>
                </div>

                <div class="about-text">
                    <?php 
                    	_e('Robo Gallery is an advanced, responsive photo gallery plugin with flexible image management tools. 
                        It supports links, video, slider, and a built-in lightbox. 
                        Easily customize layouts and interface styles to match your needs. 
                        If you have any questions or need assistance with installation or configuration, feel free to reach out.', 'robo-gallery'); 

                        echo '<span style="margin: 0 10px;">[</span>';
                        echo wp_sprintf(  
                    		'<a href="%s" target="_blank">%s</a>',
                    		$this->url_external_button1,
                            __('DEMOS', 'robo-gallery')
                    	); 
                        echo '<span style="margin: 0 10px;">|</span>';
                        echo wp_sprintf(  
                    		'<a href="%s" target="_blank" >%s</a>',
                    		$this->url_external_button2,
                            __('Get Pro Version', 'robo-gallery')
                    	); 
                        echo '<span style="margin: 0 10px;">|</span>';
                        echo wp_sprintf(  
                    		'<a href="%s" target="_blank">%s</a>',
                    		'https://wordpress.org/support/plugin/robo-gallery',
                            __('Support', 'robo-gallery')
                    	); 
                        echo '<span style="margin: 0 10px;">]</span>';
                        
                    	?>
                </div>

                <h2 class="nav-tab-wrapper"><?php $this->showTabs(); ?></h2>

                <?php $this->showContent(); ?>
            </div>
            <?php
        }

        function showContent()
        {
            if ( $this->active_content && file_exists(plugin_dir_path( __FILE__ ) . $this->active_content)) {
                require_once plugin_dir_path( __FILE__ ). $this->active_content;
            }
        }
    }
}

add_action( 'init', function(){
    new rbsGalleryDashboard();
} );
