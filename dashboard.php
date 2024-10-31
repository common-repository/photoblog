<?php

add_action('wp_dashboard_setup', 'ftblg_stats_init_dashboard' );

function ftblg_stats_init_dashboard(){
	global $wp_meta_boxes;
	wp_add_dashboard_widget("ftblg_stats_dashboard", "Estadisticas", "ftblg_stats_dashboard");
	$wp_meta_boxes['dashboard']['side']['core']['ftblg_stats_dashboard']=$wp_meta_boxes['dashboard']['normal']['core']['ftblg_stats_dashboard'];
	unset($wp_meta_boxes['dashboard']['normal']['core']['ftblg_stats_dashboard']);
}




function ftblg_stats_dashboard(){
	global $wpdb;
	$table_name = $wpdb->prefix . "statpress";

	echo '<div class="inside"><p class="sub">P&aacute;ginas vistas<p/>';	
	$query="
		SELECT COUNT( id ) count , DAY(  `fechahora` ) x
		FROM  `$table_name` 
		WHERE  `feed` =  ''
		AND  `spider` =  ''
		AND date(`fechahora`)>SUBDATE(date(now()),INTERVAL 30 DAY)
		GROUP BY DATE(  `fechahora` ) 
		ORDER BY  `fechahora` 
		LIMIT 30
	";
	ftblg_stats_get_graficos($query);

	echo '<p class="sub">Visitantes</p>';
	$query="
		SELECT COUNT( DISTINCT ip ) count , DAY(  `fechahora` ) x
		FROM  `$table_name` 
		WHERE  `feed` =  ''
		AND  `spider` =  ''
		AND date(`fechahora`)>SUBDATE(date(now()),INTERVAL 30 DAY)
		GROUP BY DATE(  `fechahora` ) 
		ORDER BY  `fechahora` 
		LIMIT 30
	";
	ftblg_stats_get_graficos($query);
    echo '<p class="textright"><a class="button" href="index.php?page=ftblg_stats">Ver todo</a></p>';
	echo "</div>";
}

function ftblg_stats_get_graficos($query){
	global $wpdb;
	
	$res = $wpdb->get_results($query);

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
	echo "<img src='http://chart.apis.google.com/chart?chs=484x180&chco=21759b&cht=bvs&chd=t:$chart&chxt=x,y&chxl=0:$chart_x|1:|0|".(($max/5)*1)."|".(($max/5)*2)."|".(($max/5)*3)."|".(($max/5)*4)."|$max&chg=100,20,2,4&chbh=10,5'/>";
}
?>