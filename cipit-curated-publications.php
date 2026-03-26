<?php
/**
 * Plugin Name: CIPIT Publications
 * Description: A curated publication list with A4-ratio cards, PDF downloads, and Golden Ratio UI.
 * Version: 1.0
 * Author: Kevin Muchwat
 * Text Domain: cipit-publications
 */

if (!defined('ABSPATH')) exit;

// 1. Register Custom Post Type & Taxonomy
add_action('init', function () {
    register_post_type('publication', [
        'labels' => ['name' => 'Publications', 'singular_name' => 'Publication'],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-book-alt',
        'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
    ]);

    register_taxonomy('publication_group', 'publication', [
        'hierarchical' => true,
        'labels' => ['name' => 'Publication Groups', 'singular_name' => 'Group'],
        'show_admin_column' => true,
        'show_in_rest' => true,
    ]);
});

// 2. Meta Boxes for Publication Details
add_action('add_meta_boxes', function () {
    add_meta_box('pub_details', 'Publication Metadata', 'render_pub_metabox', 'publication', 'normal', 'high');
});

function render_pub_metabox($post) {
    wp_nonce_field('pub_save_meta', 'pub_meta_nonce');
    $author = get_post_meta($post->ID, '_pub_author', true);
    $date = get_post_meta($post->ID, '_pub_display_date', true);
    $pdf = get_post_meta($post->ID, '_pub_pdf_url', true);
    ?>
    <p>
        <label><strong>Author Name:</strong></label><br>
        <input type="text" name="pub_author" value="<?php echo esc_attr($author); ?>" style="width:100%;" placeholder="e.g. Kampini Tamika, Ph.D">
    </p>
    <p>
        <label><strong>Display Date:</strong></label><br>
        <input type="text" name="pub_display_date" value="<?php echo esc_attr($date); ?>" style="width:100%;" placeholder="e.g. November 2025">
    </p>
    <p>
        <label><strong>PDF Download URL:</strong></label><br>
        <input type="url" name="pub_pdf_url" value="<?php echo esc_attr($pdf); ?>" style="width:100%;" placeholder="https://...">
    </p>
    <?php
}

add_action('save_post', function ($post_id) {
    if (!isset($_POST['pub_meta_nonce']) || !wp_verify_nonce($_POST['pub_meta_nonce'], 'pub_save_meta')) return;
    $fields = ['pub_author', 'pub_display_date', 'pub_pdf_url'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
    }
});

// 3. Shortcode Implementation
add_shortcode('curated_publication', function ($atts) {
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $atts = shortcode_atts([
        'group'      => '',
        'show'       => 5,
        'pagination' => 'false',
        'order'      => 'DESC',
        'orderby'    => 'date'
    ], $atts);

    $args = [
        'post_type'      => 'publication',
        'posts_per_page' => intval($atts['show']),
        'paged'          => $paged,
        'order'          => $atts['order'],
        'orderby'        => $atts['orderby'],
    ];

    if (!empty($atts['group'])) {
        $args['tax_query'] = [[
            'taxonomy' => 'publication_group',
            'field'    => 'slug',
            'terms'    => $atts['group'],
        ]];
    }

    $query = new WP_Query($args);
    if (!$query->have_posts()) return '<p>No publications found.</p>';

    ob_start();
    ?>
    <div class="cipit-pub-container">
        <div class="cards-list">
            <?php while ($query->have_posts()): $query->the_post(); 
                $author = get_post_meta(get_the_ID(), '_pub_author', true);
                $disp_date = get_post_meta(get_the_ID(), '_pub_display_date', true);
                $pdf_link = get_post_meta(get_the_ID(), '_pub_pdf_url', true);
                $img = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://via.placeholder.com/212x300?text=No+Cover';
                
                // Get taxonomy for the red label
                $terms = get_the_terms(get_the_ID(), 'publication_group');
                $group_label = ($terms && !is_wp_error($terms)) ? $terms[0]->name : 'Publication';
            ?>
                <div class="pub-card">
                    <div class="card-image-section">
                        <img src="<?php echo esc_url($img); ?>" class="card-image" alt="<?php the_title_attribute(); ?>">
                        <div class="card-image-overlay">
                            <h3 class="card-overlay-title"><?php the_title(); ?></h3>
                            <?php if($author || $disp_date): ?>
                            <div class="card-meta">
                                <?php if($author): ?><div><strong>Author:</strong> <?php echo esc_html($author); ?></div><?php endif; ?>
                                <?php if($disp_date): ?><div><strong>Date:</strong> <?php echo esc_html($disp_date); ?></div><?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-content">
                        <div class="card-header">
                            <div class="card-category-date">
                                <span class="card-category"><?php echo esc_html($group_label); ?></span>
                                <span class="card-date">/ <?php echo get_the_date('M j, Y'); ?></span>
                            </div>
                            <h2 class="card-title"><?php the_title(); ?></h2>
                            <div class="card-description"><?php echo wp_trim_words(get_the_excerpt(), 35); ?></div>
                        </div>
                        <div class="card-buttons">
                            <a href="<?php the_permalink(); ?>" class="btn btn-primary">Read More <span>→</span></a>
                            <?php if($pdf_link): ?>
                                <a href="<?php echo esc_url($pdf_link); ?>" class="btn btn-secondary" target="_blank">Download PDF <span>⬇</span></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($atts['pagination'] === 'true'): ?>
            <div class="pub-pagination">
                <?php 
                echo paginate_links([
                    'total'   => $query->max_num_pages,
                    'current' => $paged,
                    'format'  => '?paged=%#%',
                    'prev_text' => '← Previous',
                    'next_text' => 'Next →',
                ]); 
                ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .cipit-pub-container {
            --gr: 1.618;
            --primary-color: #c02126;
            --secondary-color: #2a2c32;
            --border-radius: 12px;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.05);
            --card-hover-shadow: 0 12px 30px rgba(0,0,0,0.15);
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .cards-list { display: flex; flex-direction: column; gap: 2.5rem; margin-bottom: 2rem; }
        .pub-card { 
            display: flex; background: #fff; border-radius: var(--border-radius); 
            overflow: hidden; box-shadow: var(--card-shadow); transition: all 0.3s ease;
            height: 300px; border: 1px solid #eee;
        }
        .pub-card:hover { transform: translateY(-5px); box-shadow: var(--card-hover-shadow); }
        
        /* A4 Ratio Thumbnail (1:1.414) */
        .card-image-section { position: relative; width: 212px; min-width: 212px; height: 300px; background: #f0f0f0; }
        .card-image { width: 100%; height: 100%; object-fit: cover; }
        .card-image-overlay { 
            position: absolute; bottom: 0; left: 0; right: 0; 
            background: linear-gradient(to bottom, transparent, rgba(192, 33, 38, 0.95));
            padding: 15px; color: #fff; 
        }
        .card-overlay-title { font-size: 14px; font-weight: 700; margin-bottom: 5px; line-height: 1.2; }
        .card-meta { font-size: 10px; opacity: 0.9; }

        .card-content { padding: 2rem; flex: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .card-category { color: var(--primary-color); font-weight: 700; text-transform: uppercase; font-size: 12px; }
        .card-date { color: #888; font-size: 12px; }
        .card-title { font-size: 1.6rem; color: var(--secondary-color); margin: 10px 0; font-weight: 700; }
        .card-description { color: #666; font-size: 1rem; line-height: 1.6; text-align: justify; }

        .card-buttons { display: flex; gap: 12px; }
        .btn { 
            padding: 0.7rem 1.4rem; border-radius: 30px; font-weight: 600; text-decoration: none; 
            font-size: 14px; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary { background: var(--primary-color); color: #fff; }
        .btn-secondary { background: var(--secondary-color); color: #fff; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        .pub-pagination { text-align: center; margin-top: 3rem; }
        .pub-pagination .page-numbers { 
            padding: 8px 16px; margin: 0 4px; border: 1px solid #ddd; 
            border-radius: 4px; text-decoration: none; color: var(--secondary-color);
        }
        .pub-pagination .current { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }

        @media (max-width: 768px) {
            .pub-card { flex-direction: column; height: auto; }
            .card-image-section { width: 100%; height: 250px; }
        }
    </style>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
});