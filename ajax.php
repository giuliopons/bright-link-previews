<?php


//
// substring that doesn't truncate words
// and remove html tags
function smartSubstr($txt,$max=150) {
	$txt = trim(preg_replace("/(\r\n)/"," ",strip_tags($txt)));
	$txt = preg_replace("'!\s+!'"," ",$txt);
	if(strlen($txt) > $max ) { 
		$txt = substr($txt,0,$max);
		$txt = substr($txt,0,strlen($txt)-strpos(strrev($txt)," "));
		$txt = trim($txt).'...';
    }
    return $txt;
}



//
// save data and retrieve data from transient
function getUrlData($url) {
	
	if(!preg_match("/^https?:\/\//",$url) &&
		!preg_match("#^mailto:#",$url) &&
		!preg_match("/^#/",$url))
		$url = site_url() . $url; 

    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['cache']) ? $options['cache'] : "";
	$transient_key=sha1($url);

	if($val=="on") {
		// if setting, use cache
		$ret=get_transient( $transient_key );
		if ($ret !== false ) return $ret;

	}
	$mb = new Minibots();
	$obj = $mb->getUrlInfo($url,15,0,true);

	set_transient( $transient_key, $obj ,60 * 60 * 72); // 72 hours cache
	return $obj;
}


function blpwp_is_internal($url){
    if(!preg_match("/^https?:\/\//",$url)) return true;
    $parts = parse_url($url);
    return $parts['host'] == $_SERVER['HTTP_HOST'];
}
function blpwp_get_domain($url){
    if(!preg_match("/^https?:\/\//",$url)) return esc_url( $_SERVER['HTTP_HOST'] );
    $parts = parse_url($url);
	return $parts['host'];
}

//
// ajax call to fetch link info
add_action( 'wp_ajax_nopriv_blpwpgetinfo', 'blpwp_getinfo_callback' );
add_action( 'wp_ajax_blpwpgetinfo', 'blpwp_getinfo_callback' );
function blpwp_getinfo_callback() {
	ob_clean();
	global $wpdb;

	// FILTER AND SANITIZE POST VALUES
	$url = isset($_POST['geturl']) ? trim(stripslashes($_POST['geturl'])) : "";
	
	$rel = isset($_POST['rel']) ? trim(stripslashes($_POST['rel'])) : "";
	// $rel = filter_var($rel, FILTER_SANITIZE_STRING);	//deprecated PHP 8.1
	$rel = htmlspecialchars($rel);
	
	$idpost = isset($_POST['idpost']) ? $_POST['idpost'] : 0;
	$idpost = preg_replace("/[^0-9]/", "",$idpost);


	if($url && filter_var($url, FILTER_VALIDATE_URL) ){

		$internal = blpwp_is_internal($url) ? 1 : 0;
		$domain = blpwp_get_domain($url);
		
		// LINKS
		$q = $wpdb->query($sql = "update ".$wpdb->prefix."blpwp_links set hover=hover+1, de_rel='".addslashes($rel)."' where de_url='".addslashes($url)."' and id_post=".$idpost."");
		if ($q==0) {
			$q = $wpdb->query($sql = "insert into ".$wpdb->prefix."blpwp_links (id_post,de_url,hover,click,fl_internal,de_domain,de_rel) VALUES (".$idpost.",'".addslashes($url)."',1,0,".$internal.",'".addslashes($domain)."','".addslashes($rel)."')");
		}

		// HOVERS
		$cd_link = $wpdb->get_var("select id_link from ".$wpdb->prefix."blpwp_links where de_url='".addslashes($url)."' and id_post=".$idpost."");
		if($cd_link > 0) {
			$q = $wpdb->query($sql = "update ".$wpdb->prefix."blpwp_stats set count=count+1 where cd_link='".($cd_link)."' and thedate='".date("Y-m-d")."' and action='hover'");
			if ($q==0) {
				$q = $wpdb->query($sql = "insert into ".$wpdb->prefix."blpwp_stats (cd_link,thedate,action,count) VALUES (".$cd_link.",'".date("Y-m-d")."','hover',1)");
			}
		}
			

		$qClick = blpwp_getclick($url,$idpost);

		$obj = getUrlData($url);

		$links = "";
		if(count($obj["images"])>0) foreach($obj["images"] as $pic) $links.= is_string($pic) ? ( ($links=="" ? "" : "|" ) .$pic ) : "";

		if(is_array($obj) && !isset($obj["err"])) {
			$meta = "";
			if($obj["lastmodified"]) $meta .= $obj["lastmodified"];
			if($qClick) $meta .= ($meta=="" ? "" : " &middot; ") . "<span class='blpwpclick'>".esc_html($qClick) . " Click</span>";
			?>
			<div data-rel="<?php echo esc_url($url);?>" class='blpwp_wrap'>
				<div class='blpwp_pic'>
					<?php if(!empty($obj["images"])) echo "<span class='blpwp_pics' data-images=\"" . esc_attr($links) ."\"></span>"; ?>
					<?php if(isset($obj["price"]) && $obj["price"] && $obj["price"] > 0) echo "<span class='blpwp_spot'>" . ($obj["price"] ? "<b><i>".esc_html($obj["price"])."</i></b>" : "") . "</span>";?>
				</div>
				<div class="blpwp_info">
					<div class='h5'><?php echo esc_html(smartSubstr($obj['title']));?></div>
					<div class='h6'><?php echo esc_html(smartSubstr($obj['description']));?></div>
					<div class='h4'>
						<?php if($obj['favicon']) echo "<img src='".esc_url($obj['favicon'])."' onerror=\"jQuery(this).hide();\"/>";?>
						<?php echo esc_html($obj["domain"]);?>
					</div>
					<?php if($meta) echo "<div class='h3'>".$meta."</div>"; ?>
				</div>
			</div>
			<?php
		} else {

			$wpdb->query($sql = "update ".$wpdb->prefix."blpwp_links set error=1 where de_url='".addslashes($url)."' and id_post=".$idpost."");

			if(!is_array($obj)) $obj["err"] = "Not found.";
			echo "<div class='mlpErrorFound' data-rel=\"" . esc_url($url) . "\">" . esc_html($obj["err"]) . "</div>";
		}

	}

	wp_die();
}



//
// ajax call track clicks
add_action( 'wp_ajax_nopriv_blpwpclick', 'blpwp_click_callback' );
add_action( 'wp_ajax_blpwpclick', 'blpwp_click_callback' );
function blpwp_click_callback() {
	ob_clean();
	global $wpdb;

	// FILTER AND SANITIZE POST VALUES
	$url = isset($_POST['url']) ? trim($_POST['url']) : "";
	
	$rel = isset($_POST['rel']) ? trim($_POST['rel']) : "";
	$rel = filter_var($rel, FILTER_SANITIZE_STRING);

	$idpost = isset($_POST['idpost']) ? $_POST['idpost'] : 0;
	$idpost = preg_replace("/[^0-9]/", "",$idpost);

	if($url && filter_var($url, FILTER_VALIDATE_URL) ){

		$q = $wpdb->query($sql = "update ".$wpdb->prefix."blpwp_links set click=click+1, de_rel='".addslashes($rel)."' where de_url='".addslashes($url)."' and id_post=".$idpost."");
		if ($q==0) {
			 $q = $wpdb->query($sql = "insert into ".$wpdb->prefix."blpwp_links (id_post,de_url,hover,click,de_rel) VALUES (".$idpost.",'".addslashes($url)."',1,1,'".addslashes($rel)."')");
		}

		// COUNT CLICKS
		$cd_link = $wpdb->get_var("select id_link from ".$wpdb->prefix."blpwp_links where de_url='".addslashes($url)."' and id_post=".$idpost."");
		if($cd_link > 0) {
			$q = $wpdb->query($sql = "update ".$wpdb->prefix."blpwp_stats set count=count+1 where cd_link='".($cd_link)."' and thedate='".date("Y-m-d")."' and action='click'");
			if ($q==0) {
				$q = $wpdb->query($sql = "insert into ".$wpdb->prefix."blpwp_stats (cd_link,thedate,action,count) VALUES (".$cd_link.",'".date("Y-m-d")."','click',1)");
			}
		}

	}
	wp_die();
}

function blpwp_getclick($url,$idpost) {
	global $wpdb;
	$sql = "select click from `".$wpdb->prefix."blpwp_links` where de_url='".addslashes($url)."' and id_post=".(integer)$idpost;
	$rs = $wpdb->get_results($sql, OBJECT);
	$q = 0;
	foreach($rs as $row) $q = $row->click;
	return $q;
}

//
// ajax call to show clicks
add_action( 'wp_ajax_nopriv_blpwpgetclick', 'blpwp_getclick_callback' );
add_action( 'wp_ajax_blpwpgetclick', 'blpwp_getclick_callback' );
function blpwp_getclick_callback() {
	ob_clean();

	// FILTER AND SANITIZE POST VALUES
	$url = isset($_POST['url']) ? trim($_POST['url']) : "";

	$idpost = isset($_POST['idpost']) ? $_POST['idpost'] : 0;
	$idpost = preg_replace("/[^0-9]/", "",$idpost);

	if($url && filter_var($url, FILTER_VALIDATE_URL) && $idpost > 0){
		echo blpwp_getclick($url,$idpost);
	}
	wp_die();
}
?>