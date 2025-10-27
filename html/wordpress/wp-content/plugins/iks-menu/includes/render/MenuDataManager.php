<?php

/**
 * Iks Menu
 *
 *
 * @package   Iks Menu
 * @author    IksStudio
 * @license   GPL-3.0
 * @link      https://iks-menu.com
 * @copyright 2019 IksStudio
 */
namespace IksStudio\IKSM\render;

use IksStudio\IKSM_CORE\Plugin;
use IksStudio\IKSM_CORE\settings\SettingsManager;
use IksStudio\IKSM_CORE\utils\Utils;
use IksStudio\IKSM;
use IksStudio\IKSM\utils\UtilsLocal;
use WP_Post;
class MenuDataManager {
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var string
     */
    private $error = null;

    /**
     * @var array
     */
    private $args = [];

    /**
     * @var string
     */
    private $source = null;

    /**
     * @var SettingsManager|null
     */
    private $settings_manager = null;

    /**
     * @var string|null
     */
    private $page_url = null;

    /**
     * @var object|null
     */
    private $queried_object = null;

    /**
     * @var integer
     */
    private $initial_parent_id = 0;

    /**
     * @var string
     */
    private $post_id_prefix = "post-";

    /**
     * @var string
     */
    private $faq_id_prefix = "faq-";

    /**
     * MenuDataManager constructor.
     *
     * @param $settings_manager SettingsManager
     */
    public function __construct( $settings_manager ) {
        $this->settings_manager = $settings_manager;
        $this->source = $this->settings_manager->get_value( "source" );
        $this->prepare_current_query_params();
        $this->prepare_initial_parent_id();
        if ( $this->is_source_menu() ) {
            $this->get_WP_menu();
        } else {
            if ( $this->is_source_taxonomy() ) {
                $this->get_WP_terms();
            } else {
                if ( $this->is_source_faqs() ) {
                    $this->get_faqs();
                }
            }
        }
        if ( !$this->get_error() ) {
            $this->append_children();
            $this->process_includes();
        }
        //		Utils::pretty_dump( $this->get_data() );
    }

    private function prepare_current_query_params() {
        // Query Object
        $this->queried_object = get_queried_object();
        // Page Url
        $term_id = $this->get_queried_object_term_id();
        if ( !!$term_id ) {
            $this->page_url = get_term_link( $term_id );
        } else {
            if ( is_single() || is_page() ) {
                $this->page_url = get_permalink();
            } else {
                global $wp;
                $this->page_url = home_url( $wp->request );
            }
        }
        $this->page_url = Utils::url_with_slash( $this->page_url );
    }

    private function prepare_initial_parent_id() {
        if ( $this->is_source_taxonomy() ) {
            if ( $this->settings_manager->has_setting( "child_of" ) ) {
                $this->initial_parent_id = $this->settings_manager->get_value( "child_of" );
            } else {
                if ( $this->settings_manager->has_setting( "parent" ) ) {
                    $this->initial_parent_id = $this->settings_manager->get_value( "parent" );
                }
            }
        }
    }

    private function get_WP_menu() {
        $menu_id = $this->settings_manager->get_value( "menu_id" );
        if ( $menu_id ) {
            $this->args = [
                "output"     => ARRAY_A,
                'output_key' => 'menu_order',
            ];
            $items = wp_get_nav_menu_items( $menu_id, $this->args );
            if ( is_array( $items ) ) {
                foreach ( $items as $key => $item ) {
                    $id = (int) $item->ID;
                    $object_id = (int) $item->object_id;
                    $link = $item->url;
                    $is_term = $item->type === 'taxonomy';
                    $item_data = [
                        "id"                    => $id,
                        "initial_id"            => $id,
                        "object_id"             => $object_id,
                        "title"                 => $item->title,
                        "link"                  => $link,
                        "parent"                => (int) $item->menu_item_parent,
                        "is_current"            => $this->is_current_page_url( $link ),
                        "is_term_includes_post" => ( !$is_term ? false : $this->is_term_includes_post( $object_id, $item->object ) ),
                        "is_page_includes_post" => false,
                        "is_expanded"           => false,
                        "posts_count"           => null,
                        "is_post"               => false,
                        "target"                => $item->target,
                        "classes"               => $item->classes,
                    ];
                    $this->data[] = $item_data;
                }
            } else {
                $this->error = Utils::t( "Cannot get WordPress menu" ) . " (id = {$menu_id})";
            }
        }
    }

    private function get_WP_terms() {
        $taxonomy = $this->get_taxonomy();
        if ( $taxonomy ) {
            $this->args = [
                "taxonomy"   => $taxonomy,
                "pad_counts" => $this->settings_manager->get_value( 'posts_count_calc_type' ) === 'pad_count',
            ];
            $this->save_args( [
                "orderby",
                "order",
                "hide_empty",
                "hierarchical",
                "include",
                "exclude",
                "search",
                "child_of",
                "parent",
                "childless"
            ] );
            /**
             * Include type
             * @since 1.12.0
             */
            $include = $this->settings_manager->get_value( "include" );
            $include_array = Utils::split_numbers_by_comma_space( $include );
            $has_include = !empty( $include );
            $is_include_with_children = $this->settings_manager->get_value( "include_type" ) === "with_children";
            if ( $has_include && $is_include_with_children ) {
                // Removing include parameter to "filter" included elements manually (filter_terms_with_children)
                unset($this->args["include"]);
            }
            if ( !!$this->settings_manager->get_value( "show_current_terms_tree" ) ) {
                $queried_term_id = $this->get_queried_object_term_id();
                if ( $queried_term_id ) {
                    $args = array_merge( [], $this->args );
                    $args["parent"] = $queried_term_id;
                    unset($args["child_of"]);
                    $sub_terms = get_terms( $args );
                    if ( count( $sub_terms ) ) {
                        // Displaying entire menu, when no items detected for current element
                        // All checks passed: set term ID + un-parent to avoid conflicts
                        $this->args['child_of'] = $queried_term_id;
                        // child_of = with sub items
                        unset($this->args['parent']);
                        // Save initial parent ID
                        $this->initial_parent_id = $queried_term_id;
                    }
                }
            }
            if ( version_compare( get_bloginfo( 'version' ), '4.5', '>=' ) ) {
                $terms = get_terms( $this->args );
            } else {
                $terms = get_terms( $taxonomy, $this->args );
            }
            if ( $has_include && $is_include_with_children ) {
                $terms = $this->filter_terms_with_children( $terms, $include_array );
            }
            if ( is_array( $terms ) ) {
                if ( !empty( $terms ) ) {
                    $index = 0;
                    $show_posts = $this->settings_manager->get_value( "show_posts" );
                    /* Posts */
                    if ( $show_posts ) {
                        $post_type = Utils::get_post_type_by_taxonomy( $taxonomy );
                        $posts_order_by = $this->settings_manager->get_value( 'posts_order_by' );
                        $posts_order = $this->settings_manager->get_value( 'posts_order' );
                        $posts = get_posts( [
                            'post_type'        => $post_type,
                            'posts_per_page'   => -1,
                            'orderby'          => $posts_order_by,
                            'order'            => $posts_order,
                            'tax_query'        => [[
                                'taxonomy' => $taxonomy,
                                'operator' => ( $has_include ? 'IN' : 'EXISTS' ),
                                'terms'    => ( $has_include ? Utils::get_terms_ids( $terms ) : [] ),
                            ]],
                            'suppress_filters' => false,
                        ] );
                        $posts_by_terms = [];
                        foreach ( $posts as $post ) {
                            $post_terms = get_the_terms( $post, $taxonomy );
                            if ( !empty( $post_terms ) ) {
                                foreach ( $post_terms as $post_term ) {
                                    if ( !isset( $posts_by_terms[$post_term->term_id] ) ) {
                                        $posts_by_terms[$post_term->term_id] = [];
                                    }
                                    $posts_by_terms[$post_term->term_id][] = $post;
                                }
                            }
                        }
                    }
                    foreach ( $terms as $key => $term ) {
                        $id = (int) $term->term_id;
                        $link = get_term_link( $id );
                        $term_data = [
                            "id"                    => $id,
                            "initial_id"            => $id,
                            "title"                 => $term->name,
                            "link"                  => $link,
                            "parent"                => (int) $term->parent,
                            "is_current"            => $this->is_current_page_url( $link ) || $this->get_queried_object_term_id() === $id,
                            "is_term_includes_post" => $this->is_term_includes_post( $id, $this->get_taxonomy() ),
                            "is_page_includes_post" => false,
                            "index"                 => $index,
                            "is_expanded"           => false,
                            "posts_count"           => $term->count,
                            "is_post"               => false,
                            "target"                => null,
                            "classes"               => null,
                        ];
                        $index++;
                        $this->data[] = $term_data;
                        if ( $show_posts ) {
                            if ( isset( $posts_by_terms[$id] ) ) {
                                $result_posts = $posts_by_terms[$id];
                                foreach ( $result_posts as $post ) {
                                    $post_id = $post->ID;
                                    $result_id = $this->post_id_prefix . $post_id;
                                    // Sometimes WP has the same IDs for terms & posts (for some reason), so it's better to handle this situation
                                    $post_link = get_permalink( $post_id );
                                    $this->data[] = [
                                        "id"                    => $result_id,
                                        "initial_id"            => $post_id,
                                        "title"                 => $post->post_title,
                                        "link"                  => $post_link,
                                        "parent"                => $id,
                                        "is_current"            => $this->is_current_page_url( $post_link ),
                                        "is_term_includes_post" => false,
                                        "is_page_includes_post" => false,
                                        "index"                 => $index,
                                        "is_expanded"           => false,
                                        "posts_count"           => false,
                                        "is_post"               => true,
                                        "target"                => null,
                                        "classes"               => null,
                                    ];
                                    $index++;
                                }
                            }
                        }
                    }
                }
                /*
                 * Processing exclude without children
                 * @since 1.8.0
                 */
                $this->process_exclude_without_children();
            } else {
                $this->error = Utils::t( "Cannot get WordPress terms for taxonomy" ) . " ({$taxonomy})";
            }
        }
        /* Sorting items */
        usort( $this->data, function ( $a, $b ) {
            $aIsPost = Utils::get( $a, "is_post" ) === true;
            $bIsPost = Utils::get( $b, "is_post" ) === true;
            if ( $aIsPost === $bIsPost ) {
                // Saving order
                return Utils::get( $a, "index" ) - Utils::get( $b, "index" );
            } else {
                return $aIsPost - $bIsPost;
                // Terms first
                // TODO: New Setting for ordering posts and terms + posts
                //return $bIs - $aIs; // Posts first
            }
        } );
        // Removing unnecessary index
        foreach ( $this->data as $index => $item ) {
            unset($this->data[$index]["index"]);
        }
    }

    private function get_faqs() {
        $args = [
            'post_type'        => UtilsLocal::get_faqs_type(),
            'posts_per_page'   => -1,
            'orderby'          => $this->settings_manager->get_value( 'faqs_order_by' ),
            'order'            => $this->settings_manager->get_value( 'faqs_order' ),
            'suppress_filters' => false,
        ];
        $group = $this->settings_manager->get_value( 'faq_groups' );
        if ( $group && $group !== 'all' ) {
            $args['tax_query'] = [[
                'taxonomy' => UtilsLocal::get_faqs_taxonomy_type(),
                'field'    => 'id',
                'terms'    => $group,
            ]];
        }
        $posts = get_posts( $args );
        $index = 0;
        foreach ( $posts as $key => $post ) {
            $post_id = $post->ID;
            $question_id = $this->faq_id_prefix . $post_id . "-question";
            $answer_id = $this->faq_id_prefix . $post_id . "-answer";
            // Question
            $this->data[] = [
                "id"                    => $question_id,
                "initial_id"            => $post_id,
                "title"                 => $post->post_title,
                "link"                  => null,
                "parent"                => 0,
                "is_current"            => false,
                "is_term_includes_post" => false,
                "is_page_includes_post" => false,
                "index"                 => $index,
                "is_expanded"           => false,
                "posts_count"           => false,
                "is_post"               => true,
                "sub_type"              => "faq_question",
                "target"                => null,
                "classes"               => null,
            ];
            // Answer
            $index++;
            $this->data[] = [
                "id"                    => $answer_id,
                "initial_id"            => $post_id,
                "title"                 => $post->post_content,
                "link"                  => null,
                "parent"                => $question_id,
                "is_current"            => false,
                "is_term_includes_post" => false,
                "is_page_includes_post" => false,
                "index"                 => $index,
                "is_expanded"           => false,
                "posts_count"           => false,
                "is_post"               => true,
                "sub_type"              => "faq_answer",
                "target"                => null,
                "classes"               => null,
            ];
            $index++;
        }
    }

    private function add_element_and_descendants( $element, &$filtered_elements, $elements ) {
        foreach ( $filtered_elements as $filtered_element ) {
            // skip, if element already added
            if ( $filtered_element->term_id == $element->term_id ) {
                return;
            }
        }
        $filtered_elements[] = $element;
        // add parent element
        foreach ( $elements as $child_element ) {
            // add all children
            if ( $child_element->parent == $element->term_id ) {
                $this->add_element_and_descendants( $child_element, $filtered_elements, $elements );
            }
        }
    }

    /**
     * Filter terms with children
     * @since 1.8.0
     */
    private function filter_terms_with_children( $elements, $ids_to_keep ) {
        $result = [];
        foreach ( $elements as $element ) {
            if ( in_array( $element->term_id, $ids_to_keep ) ) {
                $this->add_element_and_descendants( $element, $result, $elements );
            }
        }
        return $result;
    }

    /**
     * Exclude without children
     * @since 1.8.0
     */
    private function process_exclude_without_children() {
        $exclude_without_children = Utils::split_numbers_by_comma_space( $this->settings_manager->get_value( "exclude_without_children" ) );
        if ( !empty( $exclude_without_children ) ) {
            foreach ( $this->data as $index => $item ) {
                // Backward compatibility: users trying to remove posts without setting the prefix, so removing it before check:
                $id = $item["initial_id"];
                $exclude_term = in_array( $id, $exclude_without_children );
                if ( $exclude_term ) {
                    if ( !$item["is_post"] ) {
                        foreach ( $this->data as $inner_index => $inner_item ) {
                            if ( (int) $inner_item["parent"] === $id ) {
                                // For each child
                                if ( $inner_item["is_post"] ) {
                                    // Removing posts
                                    unset($this->data[$inner_index]);
                                } else {
                                    // New parent for children - is not excluded parent of item
                                    $new_parent_id = $this->get_not_excluded_parent_id( $item, $exclude_without_children );
                                    $this->data[$inner_index]["parent"] = $new_parent_id;
                                }
                            }
                        }
                    }
                    unset($this->data[$index]);
                    // Removing item
                }
            }
        }
    }

    /**
     * Recursively finds parent of item, that is not in $excludes array
     *
     * @param array $item
     * @param array $excludes
     *
     * @return int|mixed
     * @since 1.8.0
     */
    private function get_not_excluded_parent_id( $item, $excludes ) {
        $parent_id = $item["parent"];
        if ( in_array( $parent_id, $excludes ) ) {
            $parent = $this->find_term( $parent_id );
            if ( $parent ) {
                return $this->get_not_excluded_parent_id( $parent, $excludes );
            } else {
                return 0;
            }
        } else {
            return $parent_id;
        }
    }

    private function append_children() {
        foreach ( $this->data as $index => $item ) {
            $children = $this->get_term_children( $item["id"], true );
            $this->data[$index]["children"] = ( count( $children ) > 0 ? $children : null );
        }
    }

    /**
     * Checks "include" setting: If the term doesn't have a retrieved parent, then change it's parent to "0"
     *
     * @since 1.7.7
     */
    private function process_includes() {
        $include = $this->settings_manager->get_value( "include" );
        if ( $include ) {
            $include_array = Utils::split_numbers_by_comma_space( $include );
            foreach ( $include_array as $id ) {
                $term = $this->find_term( $id );
                if ( $term && $term["parent"] !== 0 && !$this->is_term_has_retrieved_parent( $term ) ) {
                    $this->change_term_data( $id, [
                        "parent" => 0,
                    ] );
                }
            }
        }
    }

    public function get_term_level( $item, $current_level = 1 ) {
        $parent_id = $item["parent"];
        if ( !$parent_id ) {
            return $current_level;
        } else {
            $parent = $this->find_term( $parent_id );
            if ( $parent ) {
                return $this->get_term_level( $parent, $current_level + 1 );
            }
        }
        return null;
    }

    public function get_term_children( $item_id, $only_ids = false ) {
        $children = [];
        foreach ( $this->data as $item ) {
            if ( $item["parent"] === $item_id ) {
                array_push( $children, ( $only_ids ? $item["id"] : $item ) );
            }
        }
        return $children;
    }

    public function is_term_has_retrieved_parent( $term ) {
        return !!$this->find_term( $term["parent"] );
    }

    public function is_term_has_children( $term ) {
        return !empty( $term["children"] );
    }

    /**
     * Finds term data or index by id
     *
     * @param int $id
     * @param bool $return_index
     *
     * @return int|string|array|null
     */
    public function find_term( $id, $return_index = false ) {
        foreach ( $this->data as $index => $item ) {
            if ( $item["id"] === $id ) {
                return ( $return_index ? $index : $item );
            }
        }
        return null;
    }

    /**
     * Checks, term is parent of current post (WP_Post)
     *
     * @param integer $term_id ID of the term
     * @param string $taxonomy Taxonomy of the term
     *
     * @return bool
     * @since 1.8.0
     */
    private function is_term_includes_post( $term_id, $taxonomy ) {
        if ( $this->queried_object instanceof WP_Post ) {
            $post_id = $this->queried_object->ID;
            $terms = wp_get_post_terms( $post_id, $taxonomy );
            $terms_ids = Utils::get_terms_ids( $terms );
            return in_array( $term_id, $terms_ids );
        }
        return false;
    }

    /**
     * Checks, is menu item's link is the same as current page url
     *
     * @param string $link
     *
     * @return bool
     */
    private function is_current_page_url( $link ) {
        return $this->page_url === Utils::url_with_slash( $link );
    }

    public function get_queried_object_term_id() {
        return ( Utils::object_has_property( $this->queried_object, 'term_id' ) ? $this->queried_object->term_id : null );
    }

    public function is_source_menu() {
        return $this->source === "menu";
    }

    public function is_source_taxonomy() {
        return $this->source === "taxonomy";
    }

    public function is_source_faqs() {
        return $this->source === "faqs";
    }

    public function get_taxonomy() {
        return $this->settings_manager->get_value( "taxonomy" );
    }

    public function get_source() {
        return $this->source;
    }

    public function get_initial_parent_id() {
        return $this->initial_parent_id;
    }

    /**
     * @param $id integer
     * @param $changes array
     */
    public function change_term_data( $id, $changes ) {
        $index = $this->find_term( $id, true );
        $this->data[$index] = array_merge( $this->data[$index], $changes );
    }

    private function save_args( $args ) {
        foreach ( $args as $key ) {
            $value = $this->settings_manager->get_value( $key );
            if ( $value !== null ) {
                $this->args[$key] = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function get_args() {
        return $this->args;
    }

    /**
     * @return array
     */
    public function get_data() {
        return $this->data;
    }

    /**
     * @return string
     */
    public function get_error() {
        return $this->error;
    }

}
