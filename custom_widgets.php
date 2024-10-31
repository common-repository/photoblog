<?php

function ftblg_widget_init(){

    function ftblg_widget_post($args){
        extract($args);
        echo "</div>";
        echo $after_widget;
    }

    function ftblg_widget_pre($args){
        extract($args);
        echo "{$before_widget}{$before_title}{$titulo}{$after_title}";
    }

    function ftblg_widget_def_control($widget,$var) {
        $options = $newoptions = get_option($widget);

        if ( isset($_POST["ftblg_widget_$widget-submit"]) ) {
            $newoptions[$var] = strip_tags(stripslashes($_POST["ftblg_widget_{$widget}-{$var}"]));
        }
        if ( $options != $newoptions ) {
            $options = $newoptions;
            update_option($widget, $options);
        }
        $options = $newoptions = get_option($widget);
        $res = attribute_escape($options[$var]);

        return $res;
    }

    function ftblg_widget_def_input($widget,$var) {
        $value=ftblg_widget_def_control($widget,$var);
        echo "<p><label for=\"ftblg_widget_{$widget}-{$var}\">{$var}";
        echo "<input id=\"ftblg_widget_{$widget}-{$var}\" class=\"widefat\" name=\"ftblg_widget_{$widget}-{$var}\" type=\"text\" value=\"{$value}\" />";
        echo '</label></p>';
    }

    function ftblg_widget_TopFive_control() {
        ftblg_widget_def_input("topfive","titulo");
        echo '<input type="hidden" id="ftblg_widget_topfive-submit" name="ftblg_widget_topfive-submit" value="1" />';
    }

    function ftblg_widget_TopFive($args){
        extract($args);
        $opt=get_option("topfive");
        $titulo = empty($opt['titulo']) ? __('Las fotos mas vistas') : $opt['titulo'];
        echo "{$before_widget}{$before_title}{$titulo}{$after_title}";
        ftblg_Top_Five();
        echo $after_widget;
    }

    function ftblg_widget_LastPosts($args){
        extract($args);
        $opt=get_option("lasttop");
        $titulo = empty($opt['titulo']) ? __('Las ultimas fotos') : $opt['titulo'];
        echo "{$before_widget}{$before_title}{$titulo}{$after_title}";
        ftblg_last();
        echo $after_widget;
    }

    function ftblf_widget_BarraNavegacion($args=null){
        extract($args);
        $opt=get_option("barra");
        $titulo = empty($opt['titulo']) ? __('Barra Navecai&oacute;n') : $opt['titulo'];
        echo "{$before_widget}{$before_title}{$titulo}{$after_title}";
        ftblf_barra_navega();
        echo $after_widget;
    }


    register_sidebar_widget("Las fotos mas vistas", "ftblg_widget_TopFive");
    register_widget_control("Las fotos mas vistas", "ftblg_widget_TopFive_control");
    register_sidebar_widget("Las ultimas fotos", "ftblg_widget_LastPosts");
    register_widget_control("Las ultimas fotos", null);
    register_sidebar_widget("Barra Navecai&oacute;n", "ftblf_widget_BarraNavegacion");
    register_widget_control("Barra Navecai&oacute;n", null);
}

add_action('init','ftblg_widget_init');
?>
