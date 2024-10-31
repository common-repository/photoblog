<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(ABSPATH . 'wp-admin/includes/template.php');
add_meta_box('codediv', __('Codigos Foros'), 'ftblg_codios', 'post', 'side', 'low');

function ftblg_codios($post){
    if(isset($post->ID)){
        $img=ftblg_get_post_img_meta($post,'rss');
        if($img){
            list($src)=$img;
            $url=get_permalink($post->ID);
            $title=$post->post_title;

            $forum="[URL={$url}][IMG]{$src}[/IMG][/URL]";
            $html="<a target='_blank' title='{$title}' href='{$url}'><img src='{$src}' border='0'/></a>";

            echo '
<script type="text/javascript">
jQuery(document).ready( function($) {
    $("#ftblg_url").click(function() { $(this).select(); });
    $("#ftblg_bb").click(function() { $(this).select(); });
    $("#ftblg_html").click(function() { $(this).select(); });
});
</script>
<p>Imagen URL:<br/><input id="ftblg_url" style="width: 100%;" type="text" value="'.htmlspecialchars($src).'"/><br/></p>
<p>Entrada URL:<br/><input id="ftblg_url" style="width: 100%;" type="text" value="'.htmlspecialchars($url).'"/><br/></p>
<p>BBCode:<br/><input id="ftblg_bb" style="width: 100%;" type="text" value="'.htmlspecialchars($forum).'"/><br/></p>
<p>HTML:<br/><input id="ftblg_html" style="width: 100%;" type="text" value="'.htmlspecialchars($html).'"/></p>
<p>Resultado:<br/>'.$html.'</p>
';
        }
    }
}

/*****
add_action('generate_rewrite_rules', 'ftblg_codios_rw');
add_action('init', 'ftblg_codios_init');
function ftblg_codios_init()
{
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
}

function ftblg_codios_rw( $wp_rewrite )
{
    $blog_rules = array( 'foto/(.*).jpg' => 'wp-content/mu-plugins/photoblog/codigos.php?id='. $wp_rewrite->preg_index(1) );
    $wp_rewrite->rules =  $blog_rules + $wp_rewrite->rules;
    //$wp_rewrite->non_wp_rules = $blog_rules;
    //print_r($wp_rewrite->mod_rewrite_rules());
}
***/
?>
