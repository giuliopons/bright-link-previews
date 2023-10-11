<?php



function blpwp_getTable( $start, $limit) {
	global $wpdb;
	$o = "";
	$sql = "select LINKS.*,post_title from `".$wpdb->prefix."blpwp_links` as LINKS inner join `".$wpdb->prefix."posts` on id_post=ID order by click DESC limit $start,$limit";
    
	$rs = $wpdb->get_results($sql, OBJECT);
    $a = array();
	foreach ($rs as $row) {
		$o.= "{";
		$p = 0;
        $r = array();
		foreach($row as $k=>$v) {

			if($k=="error") { $v = !$v; }
			if($k=="post_title") {
                $v = "<a href='post.php?post=" . $row->id_post. "&action=edit'>" . $v . "</a>";
                $o.= ($p==0?"":",").$k.":"."\"".addslashes($v)."\"";
           } else {
               $o.= ($p==0?"":",").$k.":"."\"".addslashes($v)."\"";
           }

           $r[$k] = $v;
			$p=1;
		}
        $a[] = $r;
		$o.= "},\n";
	}
	return $a;

}



//
// ajax call to fetch stats data
add_action( 'wp_ajax_nopriv_blpwpgetstats', 'blpwp_getstats_callback' );
add_action( 'wp_ajax_blpwpgetstats', 'blpwp_getstats_callback' );
function blpwp_getstats_callback() {
	ob_clean();
	global $wpdb;

    // output json headr
    header('Content-Type: application/json');

    // SANITIZE AND VALIDATE ps
    $ps = $_REQUEST["size"] ? $_REQUEST["size"] : 20;
    $ps = preg_replace("/[^0-9]/", "",$ps);
    if ($ps < 10) $ps = 10;
    if ($ps > 100) $ps = 100;

    $TOT = $wpdb->get_var("SELECT COUNT(1) FROM ".$wpdb->prefix."blpwp_links");
    $last_page = ceil($TOT / $ps);

    // SANITIZE AND VALIDATE page
    $page = $_REQUEST["page"] ? $_REQUEST["page"] : 1;
	$page = preg_replace("/[^0-9]/", "",$page);
    $page--;
    if($page < 0) $page = 0;
    if($page > $last_page) $page = $last_page;

    $data = blpwp_getTable( $page*$ps, $ps);

    echo(json_encode(["last_page"=>$last_page, "data"=>$data]));

    wp_die();


}

