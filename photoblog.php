<?php
/*
Plugin Name: PhotoBlog
Plugin URI: http://www.laullon.com/wordpress_photoblog_plugin/
Description: Transform WP blog into a photo blog
Version: 1.3.0
Author: German Laullon
Author URI: http://www.laullon.com/
*/
/*  Copyright YEAR  PLUGIN_AUTHOR_NAME  (email : laullon@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once("statpress.php");
require_once("dashboard.php");
require_once("fotos.php");
require_once("codigos.php");
require_once("custom_widgets.php");

class PhotoBlog {

    function PhotoBlog() {

        $this->DOC_ROOT = get_option('DOC_ROOT');
        $this->DOMAIN = get_option('DOMAIN');

        //require(ABSPATH . 'wp-admin/includes/upgrade.php');

        add_filter('the_content', array(&$this, 'filtro'), 2);
        add_filter('get_the_excerpt', array(&$this, 'wp_trim_excerpt'), 2);


        $this->DOC_ROOT = get_option('DOC_ROOT');
        $this->DOMAIN = get_option('DOMAIN');
    }

    function get_exif($exif) {
        $time = (float)$exif['shutter_speed'];
        if(isset($time) && $exif['aperture']!='0'){
            if($time<1 && $time!=0){
                $time=1/$time;
                $time="1/$time";
            }
            $EXIF = "f/".$exif['aperture']." &#8212; $time Sec &#8212; ISO {$exif[iso]} &#8212; {$exif[focal_length]} mm";
        }else{
            $EXIF="";
        }
        return $EXIF;
    }

    // formatting.php
    function wp_trim_excerpt($text) {
            $text = get_the_content('');

            $text = strip_shortcodes( $text );

            //$text = apply_filters('the_content', $text);
            $text = str_replace(']]>', ']]&gt;', $text);
            $text = strip_tags($text);
            $excerpt_length = apply_filters('excerpt_length', 55);
            $words = explode(' ', $text, $excerpt_length + 1);
            if (count($words) > $excerpt_length) {
                array_pop($words);
                array_push($words, '[...]');
                $text = implode(' ', $words);
            }
        if ( '' == $text ) $text=" ";
        return $text;
    }

    function filtro($text) {
        global $wpdb,$post;

        if(!is_page() && !is_feed()){
            iriStatAppend();
        }

        if($_GET['_debug']){
            debug(var_export(htmlspecialchars($text),true));
            debug(var_export($post,true));
            debug("{wpdb}".var_export($wpdb,true));
        }

        if(!preg_match('#(\[caption[^>]*)?(<img[^>]*>)(\[/caption\])?#',$text,$valores)) return $text;
        debug("valores=".var_export($valores,true));
        list($delete_tag,,$img_tag)=$valores;

        $img_url=get_URL($img_tag);
        debug('get_children = post_type=attachment&post_mime_type=image&post_parent='.$post->ID);
        $images = get_children( 'post_type=attachment&post_mime_type=image&post_parent='.$post->ID );
        debug("images=".var_export($images,true));
        if(!$images) return $text;

        foreach ($images as &$image) {
            if($image->guid == $img_url){
                $id=$image->ID;
                debug("image ID=".$image->ID);
                continue;
            }
        }

        $image_metadata = wp_get_attachment_metadata($id);
        debug("image_metadata = ".var_export($image_metadata,true));

        list($src)=wp_get_attachment_image_src($id,"full");
        $width=$image_metadata['width'];
        $height=$image_metadata['height'];
        $alt=get_post_tags($post->ID);
        $title=$post->post_title;

        $img_tag="<img src='$src' width='$width' height='$height' alt='$alt' title='$title'/>";
        debug("img_tag = ".htmlspecialchars($img_tag));

        //CAPTION
        $title=str_replace("\"","'",$post->post_title);
        $exif=$this->get_exif($image_metadata['image_meta']);
        $visitas=get_page_count(get_permalink($post->ID));
        $caption_tag="[PhotoBlogImg width=\"$width\" height=\"$height\" exif=\"$exif\" visitas=\"$visitas\" aption=\"$title\"]";
        $close_caption_tag="[/PhotoBlogImg]";
        debug("caption_tag = ".htmlspecialchars($caption_tag));

        $delete_tag=preg_replace("#\[#","\[",$delete_tag);
        $text=preg_replace("#$delete_tag#","",$text);

        if(!is_feed()){
            $txt.="\t$caption_tag$img_tag$close_caption_tag\n";
        } else {
            list($src)=wp_get_attachment_image_src($id,'rss');
            $txt.='<a href="'.get_permalink($post->ID).'" alt="'.$post->post_title.'">';
            $txt.="<img src=\"$src\" alt=\"$post->post_title\"/>";
            $txt.='</a>';
        }
        $txt.="<div class='text'>".$text."</div>";
        debug("txt = ".htmlspecialchars($txt));


        return $txt;
    }

}

function debug($txt){
    if($_GET['debug']){
        echo "<div class='debug'><pre>".htmlspecialchars($txt)."</pre></div>";
    }
}



function ultimosAccesosHoras($rss='false'){
    global $wpdb;
    $table_name = $wpdb->prefix . "statpress";
    ultimosAccesos("SELECT SUBSTRING_INDEX(`time`,':',1) as x, CONCAT(`date`,SUBSTRING_INDEX(`time`,':',1)) as b, count(*) as count FROM `$table_name` where `spider`='' group by b ORDER BY b  desc limit 0,24");
}

function ultimosAccesosDias($rss='false'){
    global $wpdb;
    $table_name = $wpdb->prefix . "statpress";
    ultimosAccesos("SELECT SUBSTRING(`date`,7) as x, `date` as b, count(*) as count FROM `$table_name` where `spider`='' group by b ORDER BY b  desc limit 0,31");
}

//la query tiene que devolver almenos 2 campos... 
// count = valores de la grafica
// x = valores de la leyenda de X
function ultimosAccesos($query){
    global $wpdb;
    $res = $wpdb->get_results($query);
    //var_dump($res,$query);
    $max=1;
    foreach ($res as $fila) {
        $chart.=$fila->count.',';
        $chart_x.='|'.$fila->x;
        $max=max($max,$fila->count);
    }
    $max=ceil($max/5)*5;
    $chart = substr($chart, 0, -1);
    $datos=split(',',$chart);
    $chart="";
    foreach ($datos as $dato) {
        $chart.=(($dato/$max)*100).',';
    }
    $chart = substr($chart, 0, -1);
    echo "<img src='http://chart.apis.google.com/chart?chs=444x180&cht=lc&chd=t:$chart&chxt=x,y&chxl=0:$chart_x|1:|0|".(($max/5)*1)."|".(($max/5)*2)."|".(($max/5)*3)."|".(($max/5)*4)."|$max&chg=8.333,20,2,4'/>";
}



function setOptions () {
    add_option("DOC_ROOT","/home/laullon/public_html","Images Document root of web server");
    add_option("DOMAIN","http://www.laullon.com","Images server domain");
}
function unsetOptions () {
    delete_option("DOC_ROOT");
    delete_option("DOMAIN");
}

// a�ade unas columnas (count,image) a la lista de post en MANAGE
function addManageColumns($defaults) {
    $ks=array_keys($defaults);
    foreach($ks as $k){
        if($k=="title") {
            $res['image'] = __('Foto');
        }
        $res[$k]=$defaults[$k];
    }
    return $res;
}

//controla las columnas
add_action('manage_posts_custom_column', 'ftblg_image_column', 10, 2);
function ftblg_image_column($column_name, $id) {
    global $post,$PhotoBlog,$wpdb;

    if( $column_name == 'image' ) {
        //var_dump( $post );
    if(preg_match('#<img[^>]*>#s',$post->post_content,$valores)) list($tag)=$valores;
    if(preg_match('#src=\"(.*?)\"#s', $tag, $valores)) list(,$img_url)=$valores;

        $id=get_photo_id($_post->ID,$img_url);
        list($src)=wp_get_attachment_image_src($id,"thumbnail");
        echo "<img src='{$src}'/>";
    }
}

register_activation_hook(__FILE__,"setOptions");
register_deactivation_hook(__FILE__,"unsetOptions");


add_action('plugins_loaded', $PhotoBlog);
add_filter('manage_posts_columns', 'addManageColumns');

$PhotoBlog = new PhotoBlog();

function get_URL($img_tag){
    preg_match('#src=\"(.*?)\"#s', $img_tag, $valores);
    debug(var_export($valores,true));
    return $valores[1];
}

function process_URL($img_url){
    preg_match('#(\w+://[^/]*)?(.*)/(.*)#', $img_url, $valores);
    debug(var_export($valores,true));
    list(,$server,$path,$img)=$valores;
    return array($server,$path,$img);
}

function ftblg_get_img_tag($post){
    $res=null;
    if(preg_match('#<img[^>]*>#s',$post->post_content,$valores)){
        list($tag)=$valores;
        $url=get_URL($tag);
        list($server,$path,$img)=process_URL($url);
        $res="$path/$img";
    }
    return $res;
}

// RSS MEDIA

add_action('rss2_ns', 'rss2_ns');
function rss2_ns(){
    echo 'xmlns:media="http://search.yahoo.com/mrss/"';
}

add_action('rss2_item', 'rss2_item');
function rss2_item(){
    global $post;

    //var_dump($post);

    if(preg_match('#<img[^>]*>#s',$post->post_content,$valores)){
        list($tag)=$valores;
        if(preg_match('#src=\"(.*?)\"#s', $tag, $valores)) list(,$url)=$valores;

        $id=get_photo_id($post->ID,$url);
        list($thb)=wp_get_attachment_image_src($id,"rss");

        echo "<media:thumbnail url='$thb'/>";
        echo "<media:content url='$url'/>";
    }
}

function ftblg_todas($size,$nopaging=null){
    return todas(null,$size,$nopaging);
}

function ftblg_categoria($id,$size="medium",$nopaging=null){
    $var_array = array("id" => "$id");
    return todas($var_array,$size,$nopaging);
}

function todas($atts=null,$size="medium",$nopaging=null) {
    global $paged,$wp_query,$post,$wpdb;
    debug("todas (atts) = ".var_export($atts,true));
    debug("todas (wp_query) = ".var_export($wp_query,true));
    debug("todas (wp_count_posts) = ".var_export(wp_count_posts(),true));

    if(!is_numeric ($paged)) $paged=1;

    $cat_id=$atts['id'];
    debug("category={$cat_id}");

    if(is_numeric($cat_id)){
        $published_posts = $wpdb->get_var("SELECT count FROM {$wpdb->prefix}term_taxonomy WHERE term_taxonomy_id = '{$cat_id}'");
        $cat="&category={$cat_id}";
    }elseif(is_numeric($atts['year'])){
        $cat="year={$atts[year]}&monthnum={$atts[monthnum]}";
    }else{
        $count_posts = wp_count_posts();
        $published_posts = $count_posts->publish;
    }
    debug("published_posts=$published_posts");
    $pages=($published_posts/16)+1;
    debug("pages=$pages");

    if(is_category()){
        $title=single_cat_title("",false);
        $link=get_category_link( $cat_id );
    }else{
        $title=$post->post_title;
        $link=get_permalink();
    }

    if(!isset($nopaging))
    if($pages>2)
    for($p=1;$p<$pages;$p++){
        if($p!=$paged){
            $pagin.="<a href=\"{$link}page/{$p}\" title=\"{$title} page {$p}\">$p</a>";
        } else {
            $pagin.="<span>$p</span>";
        }
    }

    $init_post=16*($paged-1);

    $posts = get_posts("numberposts=16&nopaging={$nopaging}&offset={$init_post}{$cat}");
    foreach( $posts as $_post ){
        $text=$_post->post_content;
        if(preg_match('#<img[^>]*>#',$text,$valores)){
            $img_tag=$valores[0];
            $img_url=get_URL($img_tag);

            $id=get_photo_id($_post->ID,$img_url);
            list($src,$width,$height)=wp_get_attachment_image_src($id,$size);

            $link=get_permalink($_post->ID);

            $res.="<a href=\"{$link}\" title=\"{$_post->post_title}\">";
            $res.="<img src=\"$src\" title=\"$_post->post_title\" width=\"$width\" height=\"$height\"/>";
            $res.="</a>";
        }
    }

    return "<div class=\"paging\">{$pagin}</div><div class=\"photos\">{$res}</div><div class=\"paging\">{$pagin}</div>";
}

function get_photo_id($post_id,$img_url){

    debug("get_children = post_parent={$post_id}&post_type=attachment&post_mime_type=image");
    $images = get_children( "post_parent={$post_id}&post_type=attachment&post_mime_type=image" );
    debug("images=".var_export($images,true));
    if($images){
        foreach ($images as &$image) {
            if($image->guid == $img_url){
                $id=$image->ID;
                debug("image ID=".$image->ID);
                continue;
            }
        }
    }
    return $id;
}

add_shortcode('PhotoBlog', 'todas');

//Stats...
function get_page_count($page){
    global $wpdb;
    $urlRequested=preg_replace("#http.?://[^/]*#","",$page);
    $table_name = $wpdb->prefix . "statpress";
    $query="SELECT count(*) as count FROM `$table_name` where `urlRequested`='$urlRequested' and `spider`=''";
    debug("query = $query");
    $cont = $wpdb->get_var($query);
    return($cont);
}

function get_Thumb_From_Permalink($url){
    global $wpdb,$PhotoBlog,$wp_rewrite;
    $queryreplace = array ('','','','','','','post_name=','ID=','','','','','');
    $permalink = get_option('permalink_structure');

    preg_match_all('/%.+?%/', $permalink, $tokens);
    $num_tokens = count($tokens[0]);
    for ($i = 0; $i < $num_tokens; ++$i) {
        if(strpos($tokens[0][$i],"post")>-1){
            if (strlen($query) > 0) {
                $query .= ' AND ';
            } else {
                $query = '';
            }

            $query_token = str_replace($wp_rewrite->rewritecode, $queryreplace, $tokens[0][$i]) . "'" . $wp_rewrite->preg_index($i+1) . "'";
            $query .= $query_token;
        }
    }

    $permalink=str_replace($wp_rewrite->rewritecode, $wp_rewrite->rewritereplace,$permalink);

    debug("tokens = ".var_export($tokens,true));
    debug("query = $query");
    debug("url = $url");
    debug("permalink = $permalink");

    $q=preg_replace("#.*{$permalink}#", $query, $url);
    debug("q = $q");

    $table_name = $wpdb->prefix . "posts";
    $q="Select ID from $table_name where $q AND post_status='publish'";
    $ID=$wpdb->get_var($q);
    if(is_numeric($ID)){
        $post=get_post($ID);

        if(preg_match('#(\[caption[^>]*)?(<img[^>]*>)(\[/caption\])?#',$post->post_content,$valores))
        {
            list(,,$img_tag,)=$valores;
            $img_url=get_URL($img_tag);
            $id=get_photo_id($ID,$img_url);
            list($src)=wp_get_attachment_image_src($id,"thumbnail");
            $res = "<img src=\"{$src}\" alt=\"$post->post_title\"/>";
        }else{
            $res = "<li><a href='$url'>{$post->post_title}</a></li>";
        }
    }
    debug("q = $q");
    debug("ID = $ID");
    debug("post = ".var_export($post,true));
    debug("res = $res");
    return(array($res,$post->post_title,$ID));
}

function _ftblg_post_link_thumbnail($prev){
    global $wp_query;
    $old=$wp_query->is_single;
    $wp_query->is_single = 1;
    $post=get_adjacent_post(is_category(),"",$prev);
    if(!empty($post)){
        $img=_ftblg_post_img($post,"thumbnail",false);
        $url=get_permalink($post->ID);
        echo "<a href=\"{$url}\" title=\"{$post->post_title}\">$img</a>";
    }
    $wp_query->is_single=$old;
}

function ftblg_next_post_link_thumbnail(){
    _ftblg_post_link_thumbnail(false);
}

function ftblg_prev_post_link_thumbnail($print=true){
    _ftblg_post_link_thumbnail(true);
}

function ftblg_post_img($post,$print=true){
    return _ftblg_post_img($post,"full",$print);
}

function ftblg_post_thumbnail($post,$print=true){
    return _ftblg_post_img($post,"thumbnail",$print);
}

function ftblg_post_medium($post,$print=true){
    return _ftblg_post_img($post,"medium",$print);
}

function _ftblg_post_img($post,$size,$print=true){
    if(preg_match('#(\[caption[^>]*)?(<img[^>]*>)(\[/caption\])?#',$post->post_content,$valores))
    {
        list(,,$img_tag,)=$valores;
        $img_url=get_URL($img_tag);
        $id=get_photo_id($post->ID,$img_url);
        list($src,$width,$height)=wp_get_attachment_image_src($id,$size);
        if($width!=0){
        $res = "<img src=\"{$src}\" width=\"{$width}\" height=\"{$height}\" alt=\"$post->post_title\"/>";
        }else{
            $res = "<img src=\"{$src}\"  alt=\"$post->post_title\"/>";
    }
    }else{
        $res = "{$post->post_title} - Imagen Not Found";
    }
    if($print) echo $res;
    return $res;
}

// return list($src,$width,$height)
function ftblg_get_post_img_meta($post,$size='full'){
    if(preg_match('#(\[caption[^>]*)?(<img[^>]*>)(\[/caption\])?#',$post->post_content,$valores))
    {
        list(,,$img_tag,)=$valores;
        $img_url=get_URL($img_tag);
        $id=get_photo_id($post->ID,$img_url);
        $res=wp_get_attachment_image_src($id,$size);
    }else{
        $res = false;
    }
    return $res;
}

function ftblg_get_post_img_eixf($post){
    if(preg_match('#(\[caption[^>]*)?(<img[^>]*>)(\[/caption\])?#',$post->post_content,$valores))
    {
        list(,,$img_tag,)=$valores;
        $img_url=get_URL($img_tag);
        $id=get_photo_id($post->ID,$img_url);
        $image_metadata = wp_get_attachment_metadata($id);
        $exif=$image_metadata['image_meta'];
        $time = (float)$exif['shutter_speed'];
        if(isset($time) && $exif['aperture']!='0'){
            if($time<1 && $time!=0){
                $time=1/$time;
                $time="1/$time";
            }
            echo '<div class="exif"><h3>Exif</h3><dl>';
            echo "<dt>".__('Aper.','PhotoBlog').":</dt><dd>f/{$exif['aperture']}</dd>";
            echo "<dt>".__('Expo.','PhotoBlog').":</dt><dd>$time Sec</dd>";
            echo "<dt>".__('ISO','PhotoBlog').":</dt><dd>{$exif[iso]}</dd>";
            echo "<dt>".__('Focal','PhotoBlog').":</dt><dd>{$exif[focal_length]} mm</dd>";
            echo '</ul></div>';
        }
    }
}

function ftblf_barra_navega(){
    global $wpdb,$post;
    echo "<ul>";
    $table_name = $wpdb->prefix . "statpress";

    $query="(SELECT * FROM `{$wpdb->posts}` where `ID`>{$post->ID} AND `post_type`='post' AND `post_status`='publish' ORDER BY `ID`  ASC limit 4)
        UNION ALL
        (SELECT * FROM `{$wpdb->posts}` where `ID`<={$post->ID} AND `post_type`='post' AND `post_status`='publish' ORDER BY `ID`  DESC limit 5)
        ORDER BY id desc";

    $res = $wpdb->get_results("$query");
    $total=count($res);
    $sal="total={$total} ";

    $ini=0;
    for($p=0;$p<count($res);$p++) {
        if($res[$p]->ID == $post->ID){
            $sal.="p={$p} ";
            if($p>=3) $ini=$p-2;
            while($total-$ini<5){$ini--;}
        }
    }
    $sal.="ini={$ini} ";
    $sal="";
    foreach ($res as $_post) {
        if($cont<($ini+5) && $cont>=$ini){
            $img=ftblg_post_thumbnail($_post,false);
            if($solo_txt=="on") $img=$title;
            $sal="<li><a href='".get_permalink($_post->ID)."' title='$title'>$img</a></li>".$sal;
        }
        $cont++;
    }
    echo "$sal</ul>";
}

function ftblg_Top_Five(){
    global $wpdb;
    echo '<ul class="ftblg_Top_Five">';
    $table_name = $wpdb->prefix . "statpress";
    $res = $wpdb->get_results("SELECT count(*) as count,post_id FROM `$table_name` where `spider`='' group by `post_id` order by count desc limit 0,5");
    foreach ($res as $fila) {
        $post=get_post($fila->post_id);
        $img=ftblg_post_thumbnail($post,false);
        echo "<li><a href='".get_permalink($post->ID)."' title='{$post->post_title}'>$img</a></li>";
    }
    echo "</ul>";
}

function ftblg_last(){
    echo '<ul class="ftblg_last">';
    $posts = get_posts('numberposts=5');
    foreach ($posts as $post) {
        $img=ftblg_post_thumbnail($post,false);
        echo "<li><a href='".get_permalink($post->ID)."' title='{$post->post_title}'>$img</a></li>";
    }
    echo "</ul>";
}

function get_post_tags($ID){
    global $post;
    debug(var_export(get_the_tags($ID),true));
    debug(var_export(get_the_category($ID),true));

    $tags = get_bloginfo('description');

    if(!is_home()){
        $tags = get_the_tags($ID);
        if ($tags && is_array($tags)) {
            foreach ($tags as $tag) {
                $tag_string .= ", {$tag->name}";
            }
        }
        $tags = get_the_category($ID);
        if ($tags && is_array($tags)) {
            foreach ($tags as $tag) {
                $tag_string .= ", {$tag->name}";
            }
        }
    }
    debug("tag_string = '$tag_string'");
    return $tag_string;
}




////****************////
add_shortcode('PhotoBlogImg', 'ftblg_def_img_caption_PhotoBlogImg');

function ftblg_def_img_caption_PhotoBlogImg($attr, $content = null) {
    global $post;

    extract(shortcode_atts(array(
        'width'    => '',
        'height'    => '',
        'exif'    => '',
        'visitas'    => '',
        'caption' => ''
            ), $attr));

    if(empty($caption)) $caption=get_the_title($post->ID);

    $link=get_permalink($post->ID);
    $titulo=$caption;

    if (is_home() && is_sticky()){
        $content=ftblg_post_medium($post,false);
    }
    $img="<div class=\"imagen\">$content</div>";
    $tit="<div class=\"titulo\"><a href=\"$link\" title=\"Permanent Link to $titulo\">$titulo</a></div>";
    $exif="<div class='exif'>$exif</div>";
    $visitas="\t<div class='visitas'>Visitas: $visitas</div>\n";

    return "$img$exif$visitas";
}

update_option('rss_size_w',250);
update_option('rss_size_h',250);

add_filter('intermediate_image_sizes', "ftblg_rss_intermediate_image_sizes");
function ftblg_rss_intermediate_image_sizes($sizes){
    $sizes[]="rss";
    return $sizes;
}

/*
 * TIMER
 */
$ftblg_timer_starttime=0;
function ftblg_timer_start(){
    global $ftblg_timer_starttime;
    if(!isset($_GET["timer"])) return;
    $mtime = microtime();
    $mtime = explode(' ', $mtime);
    $mtime = $mtime[1] + $mtime[0];
    $ftblg_timer_starttime = $mtime;
}

function ftblg_timer_stop($txt="timer"){
    global $ftblg_timer_starttime;
    if(!isset($_GET["timer"])) return;
    $mtime = microtime();
    $mtime = explode(" ", $mtime);
    $mtime = $mtime[1] + $mtime[0];
    $endtime = $mtime;
    $totaltime = ($endtime - $ftblg_timer_starttime);
    echo "$txt => $totaltime sec.";
}

?>