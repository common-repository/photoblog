<?php
function image_stats_page(){
    global $wpdb;
    $table_name = $wpdb->prefix . "statpress";
    ?>
<table><tr valign="top"><td>
            <h2>Estadisticas de las imagenes</h2>
            <table><tr><td align='center'><h3>Siempre</h3></td><td align='center'><h3>Hoy</h3></td><td align='center'><h3>Ayer</h3></td></tr><tr><td valign='top'>
                        <table>
                            <?
                            $res = $wpdb->get_results("SELECT count(*) as count,post_id FROM `$table_name` where `spider`='' group by `post_id` order by count desc limit 0,10");
                            foreach ($res as $fila) {
                                $img=ftblg_post_thumbnail(get_post($fila->post_id),false);
                                $url=get_permalink($fila->post_id);
                                echo "<tr><td>{$fila->count}</td><td><a href='$url'>{$img}</a></td></tr>";
                            }
                            ?>
                        </table>
                    </td><td valign='top'>
                        <table>
                            <?
                            $hoy=gmdate("Ymd",current_time("timestamp"));
                            $res = $wpdb->get_results("SELECT count(*) as count,post_id FROM `$table_name` where `spider`='' and `date`='{$hoy}' group by `post_id` order by count desc limit 0,10");
                            foreach ($res as $fila) {
                                $img=ftblg_post_thumbnail(get_post($fila->post_id),false);
                                $url=get_permalink($fila->post_id);
                                echo "<tr><td>{$fila->count}</td><td><a href='$url'>{$img}</a></td></tr>";
                            }
                            ?>
                        </table>
                    </td><td valign='top'>
                        <table>
                            <?
                            $ayer=gmdate("Ymd",(current_time("timestamp")-(24 * 60 * 60)));
                            $res = $wpdb->get_results("SELECT count(*) as count,post_id FROM `$table_name` where `spider`='' and `date`='{$ayer}' group by `post_id` order by count desc limit 0,10");
                            foreach ($res as $fila) {
                                $img=ftblg_post_thumbnail(get_post($fila->post_id),false);
                                $url=get_permalink($fila->post_id);
                                echo "<tr><td>{$fila->count}</td><td><a href='$url'>{$img}</a></td></tr>";
                            }
                            ?>
                        </table>
            </td></tr></table>
        </td><td>
            <h2>Ultimos accesos</h2>
            <h3>Accesos ultimas 24Hr</h3><br>
            <?php ultimosAccesosHoras(); ?>
            <h3>Accesos ultimos 31 D&iacute;as</h3><br>
            <?php ultimosAccesosDias(); ?>
</td></tr></table>
<?
}
?>