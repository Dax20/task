<?php
/*
Plugin Name: Project Submission
Plugin URI: 
Description: Submit projects via frontend form and store them as custom post types.
Version: 1.0
Author: Dakshesh
Author URI:
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Project_Submission {
    

    public function __construct() {
        add_action('init', [$this, 'register_custom_post_type']);
        add_shortcode('project_submission_form', [$this, 'render_submission_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_project_submit', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_project_submit', [$this, 'handle_form_submission']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function register_custom_post_type() {
        register_post_type('project_submission', [
            'labels' => [
                'name' => __('Project Submissions'),
                'singular_name' => __('Project Submission')
            ],
            'public' => false,
            'has_archive' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor', 'custom-fields']
        ]);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('project-submission-js', plugin_dir_url(__FILE__) . 'js/project-submission.js', ['jquery'], null, true);
        wp_localize_script('project-submission-js', 'ps_ajax_obj', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('project_submission_nonce')
        ]);
    }

    public function render_submission_form() {
        ob_start();
        ?>
        <form id="project-submission-form">
            <input type="email" name="email" placeholder="Your Email" required><br>
            <input type="text" name="project_name" placeholder="Project Name" required><br>
            <input type="url" name="project_link" placeholder="Project Link"><br>
            <textarea name="project_description" placeholder="Project Description" required></textarea><br>
            <input type="hidden" name="action" value="project_submit">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('project_submission_nonce'); ?>">
            <button type="submit">Submit Project</button>
        </form>
        <div id="project-submission-response"></div>
        <?php
        return ob_get_clean();
    }

    public function handle_form_submission() {
        check_ajax_referer('project_submission_nonce', 'nonce');

        $email = sanitize_email($_POST['email']);
        $name = sanitize_text_field($_POST['project_name']);
        $link = esc_url_raw($_POST['project_link']);
        $description = sanitize_textarea_field($_POST['project_description']);

        $post_id = wp_insert_post([
            'post_type' => 'project_submission',
            'post_title' => $name,
            'post_content' => $description,
            'post_status' => 'publish'
        ]);

        if ($post_id) {
            update_post_meta($post_id, 'email', $email);
            update_post_meta($post_id, 'project_link', $link);
            wp_send_json_success('Project submitted successfully!');
        } else {
            wp_send_json_error('Submission failed.');
        }
    }

    public function add_admin_menu() {
        add_menu_page('Project Submissions', 'Project Submissions', 'manage_options', 'project-submissions', [$this, 'render_admin_page'], 'dashicons-clipboard');
    }

    public function render_admin_page() {
        $args = [
            'post_type' => 'project_submission',
            'post_status' => 'publish',
            'numberposts' => -1
        ];
        $projects = get_posts($args);
        echo '<div class="wrap"><h1>Project Submissions</h1><table class="widefat fixed"><thead><tr><th>Email</th><th>Project Name</th><th>Link</th><th>Description</th></tr></thead><tbody>';
        foreach ($projects as $project) {
            $email = get_post_meta($project->ID, 'email', true);
            $link = get_post_meta($project->ID, 'project_link', true);
            echo '<tr>';
            echo '<td>' . esc_html($email) . '</td>';
            echo '<td>' . esc_html($project->post_title) . '</td>';
            echo '<td><a href="' . esc_url($link) . '" target="_blank">' . esc_html($link) . '</a></td>';
            echo '<td>' . esc_html($project->post_content) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}

new Project_Submission();
