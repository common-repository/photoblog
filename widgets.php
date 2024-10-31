<?php

function ftblg_widget_init(){
    global $wp_registered_widgets;

    function ftblg_widget_post($args){
        extract($args);
        echo "</div>";
        echo $after_widget;
    }

    function ftblg_widget_TopFive_c($args){

    }
    function ftblg_widget_TopFive($args){
        ftblg_widget_pre($args);
        ftblg_Top_Five();
        ftblg_widget_post($args);
    }

    function ftblg_widget_pre($args){
        extract($args);
        debug("args = ".var_export($args,true));
        debug("solo_txt = ".var_export($solo_txt,true));
        echo $before_widget;
        echo "<div class='top_five'>{$before_title}Top Five Post{$after_title}";
    }

    function ftblg_widget_LastPosts($args){
        ftblg_widget_pre($args);
        ftblg_last();
        ftblg_widget_post($args);
    }

    function ftblf_widget_BarraNavegacion($args=null){
        ftblg_widget_pre($args);
        ftblf_barra_navega();
        ftblg_widget_post($args);
    }


    register_sidebar_widget("Las fotos mas vistas", "ftblg_widget_TopFive");
    register_widget_control("Las fotos mas vistas", "ftblg_widget_TopFive_c");
    /*register_sidebar_widget("Las ultimas fotos", "ftblg_widget_LastPosts");
    register_sidebar_widget("Barra Navecai&oacute;n", "ftblf_widget_BarraNavegacion");*/
}

add_action('init','ftblg_widget_init');
?>
