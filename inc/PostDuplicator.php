<?php

namespace Wichaksono\WordPress\Features;

use WP_Post;
use WP_Error;

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class PostDuplicator
 *
 * Adds a "Duplicate" feature for posts, pages, and custom post types in WordPress.
 *
 * @package Wichaksono\WordPress\Features
 */
final class PostDuplicator
{
    /**
     * PostDuplicator constructor.
     *
     * Initializes the feature by adding the necessary action and filter hooks.
     */
    public function __construct()
    {
        // Hook into admin actions to handle the duplication logic.
        add_action('admin_action_wps_duplicate_post', [$this, 'handleDuplicatePostAction']);

        // Add the "Duplicate" link to the row actions for posts and CPTs.
        add_filter('post_row_actions', [$this, 'addDuplicationLink'], 10, 2);

        // Add the "Duplicate" link to the row actions for pages.
        add_filter('page_row_actions', [$this, 'addDuplicationLink'], 10, 2);
    }

    /**
     * Adds the "Duplicate" link to the list of actions for a post.
     *
     * @param array   $actions The existing array of action links.
     * @param WP_Post $post    The post object.
     * @return array The modified array of action links.
     */
    public function addDuplicationLink(array $actions, WP_Post $post): array
    {
        // Only show the link if the user has permission to edit the post.
        if ( ! current_user_can('edit_post', $post->ID)) {
            return $actions;
        }

        // Create a secure URL with a nonce.
        $url = wp_nonce_url(
            admin_url('admin.php?action=wps_duplicate_post&post=' . $post->ID),
            'wps_duplicate_post_nonce_' . $post->ID,
            'wps_nonce'
        );

        // Add the "Duplicate" link to the actions array.
        $actions['duplicate'] = sprintf(
            '<a href="%s" aria-label="%s">%s</a>',
            esc_url($url),
            /* translators: %s: post title */
            esc_attr(sprintf(__('Duplicate "%s"', 'text-domain'), get_the_title($post->ID))),
            __('Duplicate', 'text-domain')
        );

        return $actions;
    }

    /**
     * Handles the duplication request.
     *
     * Verifies security and user permissions, then initiates the duplication process.
     */
    public function handleDuplicatePostAction(): void
    {
        // 1. Verify a post ID has been supplied.
        if (empty($_GET['post'])) {
            wp_die(__('No post to duplicate has been supplied!', 'text-domain'));
        }
        $post_id = absint($_GET['post']);

        // 2. Verify the nonce for security.
        if ( ! isset($_GET['wps_nonce']) || ! wp_verify_nonce($_GET['wps_nonce'], 'wps_duplicate_post_nonce_' . $post_id)) {
            wp_die(__('Security check failed. Please try again.', 'text-domain'));
        }

        // 3. Verify the user has permission to edit the original post.
        if ( ! current_user_can('edit_post', $post_id)) {
            wp_die(__('You do not have permission to duplicate this item.', 'text-domain'));
        }

        // 4. Create the duplicate.
        $new_post_id = $this->createDuplicate($post_id);

        // 5. Redirect to the edit screen of the new draft if successful.
        if ($new_post_id && ! is_wp_error($new_post_id)) {
            wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            exit;
        }

        // If something went wrong, show an error message.
        wp_die(
            esc_html__('An error occurred, the post could not be duplicated.', 'text-domain'),
            esc_html__('Duplication Failed', 'text-domain')
        );
    }

    /**
     * Creates a duplicate of a post.
     *
     * @param int $post_id The ID of the post to duplicate.
     * @return int|WP_Error The new post ID on success, or a WP_Error object on failure.
     */
    private function createDuplicate(int $post_id)
    {
        $original_post = get_post($post_id);

        // Ensure the original post exists.
        if (null === $original_post) {
            return new WP_Error('invalid_post', __('Original post not found.', 'text-domain'));
        }

        // 1. Prepare new post data from the original.
        $new_post_args = [
            'post_author'    => get_current_user_id(),
            'post_content'   => $original_post->post_content,
            'post_title'     => sprintf('%s (Copy)', $original_post->post_title),
            'post_excerpt'   => $original_post->post_excerpt,
            'post_status'    => 'draft', // Always create as a draft.
            'comment_status' => $original_post->comment_status,
            'ping_status'    => $original_post->ping_status,
            'post_password'  => $original_post->post_password,
            'post_name'      => '', // WordPress will generate a new slug.
            'post_parent'    => $original_post->post_parent,
            'menu_order'     => $original_post->menu_order,
            'post_type'      => $original_post->post_type,
            'post_mime_type' => $original_post->post_mime_type,
        ];

        // 2. Insert the new post into the database.
        $new_post_id = wp_insert_post($new_post_args, true);

        if (is_wp_error($new_post_id)) {
            return $new_post_id;
        }

        // 3. Duplicate all taxonomies (categories, tags, etc.).
        $taxonomies = get_object_taxonomies($original_post->post_type);
        if ( ! empty($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'slugs']);
                wp_set_object_terms($new_post_id, $terms, $taxonomy, false);
            }
        }

        // 4. Duplicate all post meta (custom fields).
        $post_meta = get_post_meta($post_id);
        if ( ! empty($post_meta)) {
            foreach ($post_meta as $meta_key => $meta_values) {
                // Skip protected meta keys (e.g., _edit_lock).
                if (is_protected_meta($meta_key, 'post')) {
                    continue;
                }
                foreach ($meta_values as $meta_value) {
                    add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
                }
            }
        }

        return $new_post_id;
    }
}