<?php
/*
Plugin Name: WP Form Create Post
Description: A plugin that creates a shortcode to display a form with title and text fields, and sends an email to the administrator when the form is submitted.
Version: 1.0
Author: marwa ghanmi
*/


// Sécuriser l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
} 
function wpfcp_enqueue_styles() {
    wp_enqueue_style('wpfcp-form-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
}
add_action('wp_enqueue_scripts', 'wpfcp_enqueue_styles');


// Enregistrer le shortcode pour afficher le formulaire
function wpfcp_form() {
    ob_start();
    include(plugin_dir_path(__FILE__) . 'template/wpfcp-form.php');
    return ob_get_clean();
}
add_shortcode('wpfcp_form', 'wpfcp_form');

// Traiter la soumission du formulaire
function wpfcp_create_post() {
    if (isset($_POST['submit'])) {
        $titre = sanitize_text_field($_POST['titre']);
        $texte = wp_kses_post($_POST['texte']);

        // Vérifier les autorisations
        if (!current_user_can('publish_posts')) {
            wp_die('Vous n\'avez pas les autorisations nécessaires pour créer un message.');
        }

        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'title' => $titre,
        ));
        
        $existe_deja = $query->have_posts();

        if ($existe_deja) {
            // Afficher un message d'erreur
            wp_die('Un message avec ce titre existe déjà. Veuillez en choisir un autre.');
        } else {
            // Créer un nouveau message non publié
            $nouveau_message = array(
                'post_type' => 'post',
                'post_status' => 'draft',
                'post_title' => $titre,
                'post_content' => $texte,
            );

            $id_message = wp_insert_post($nouveau_message);
            echo 'Article créé avec succès';
            // wp_send_json_error( array( 'message' => 'Article créé avec succès.' ) );

            // Envoyer un e-mail à l'administrateur
            $adresse_admin = get_option('admin_email');
            $sujet = 'Nouveau message créé';
            $message = "Un nouveau message intitulé '$titre' a été créé.\n\n$texte";
            wp_mail($adresse_admin, $sujet, $message);

            exit;
        }
    }
}
add_action('init', 'wpfcp_create_post');