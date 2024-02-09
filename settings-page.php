<?php
//
// Using WP settings api to build a complex setting page for the plugin
//

function blpwp_add_settings_page() {
    add_options_page( 'Bright-link-preview', 'Bright Links', 'manage_options', 'blpwp-plugin', 'blpwp_render_plugin_settings_page' );
}
add_action( 'admin_menu', 'blpwp_add_settings_page' );


//
// get stats (totals)
//
function blpwp_get_stats(){
	global $wpdb;
	$TOT_CLICK = $wpdb->get_var("SELECT SUM(click) FROM ".$wpdb->prefix."blpwp_links");
	$TOT_HOVER = $wpdb->get_var("SELECT SUM(hover) FROM ".$wpdb->prefix."blpwp_links");
	$TOT_LINKS = $wpdb->get_var("SELECT COUNT(1) FROM ".$wpdb->prefix."blpwp_links");

	$TOT_INTERNALS1 = $wpdb->get_var("SELECT COUNT(1) FROM ".$wpdb->prefix."blpwp_links WHERE de_url LIKE '%" . get_site_url() . "%'");
	$TOT_INTERNALS2 = $wpdb->get_var("SELECT COUNT(1) FROM ".$wpdb->prefix."blpwp_links WHERE de_url NOT LIKE 'http://%' and de_url NOT LIKE 'https://%'");

	return array(
		'TOT_CLICKS' => $TOT_CLICK,
		'TOT_HOVERS' => $TOT_HOVER,
		'TOT_LINKS' => $TOT_LINKS,
		'TOT_INTERNALS' => $TOT_INTERNALS1 + $TOT_INTERNALS2
	);
}

//
// get monthly stats
//
function blpwp_get_monthly_stats() {
    global $wpdb;
	$sql = "SELECT
			MONTH(thedate) AS month,
			SUM(CASE WHEN action = 'click' THEN count ELSE 0 END) AS monthly_clicks,
			(
				SELECT
					SUM(CASE WHEN action = 'click' THEN count ELSE 0 END)
				FROM
                ".$wpdb->prefix."blpwp_stats
				WHERE
					MONTH(thedate) = MONTH(bs.thedate) - 1
					AND YEAR(thedate) = YEAR(bs.thedate)
			) AS previous_month_clicks,
			(
				SUM(CASE WHEN action = 'click' THEN count ELSE 0 END) -
				(
					SELECT
						SUM(CASE WHEN action = 'click' THEN count ELSE 0 END)
					FROM
                    ".$wpdb->prefix."blpwp_stats
					WHERE
						MONTH(thedate) = MONTH(bs.thedate) - 1
						AND YEAR(thedate) = YEAR(bs.thedate)
				)
			) AS click_variation,
			ROUND(
				(
					(
						SUM(CASE WHEN action = 'click' THEN count ELSE 0 END) -
						(
							SELECT
								SUM(CASE WHEN action = 'click' THEN count ELSE 0 END)
							FROM
                            ".$wpdb->prefix."blpwp_stats
							WHERE
								MONTH(thedate) = MONTH(bs.thedate) - 1
								AND YEAR(thedate) = YEAR(bs.thedate)
						)
					) /
					(
						SELECT
							SUM(CASE WHEN action = 'click' THEN count ELSE 0 END)
						FROM
                        ".$wpdb->prefix."blpwp_stats
						WHERE
							MONTH(thedate) = MONTH(bs.thedate) - 1
							AND YEAR(thedate) = YEAR(bs.thedate)
					) * 100
				),
				2
			) AS click_variation_percentage
		FROM
        ".$wpdb->prefix."blpwp_stats AS bs
		GROUP BY
			MONTH(thedate), YEAR(thedate)
		ORDER BY
			YEAR(thedate), MONTH(thedate)
		LIMIT 0,12";

	global $wpdb;
	
	$rs = $wpdb->get_results($sql);
	return $rs;
}


//
// get daily stats
//
function blpwp_get_daily_stats(){
    global $wpdb;
	$sql = "SELECT thedate, 
        SUM(CASE WHEN action = 'click' THEN count ELSE 0 END) AS daily_clicks,
        SUM(CASE WHEN action = 'hover' THEN count ELSE 0 END) AS daily_hovers
        FROM ".$wpdb->prefix."blpwp_stats
        GROUP BY thedate 
        ORDER BY thedate DESC
        LIMIT 0,30 
	";
	$rs = $wpdb->get_results($sql);
	return $rs;
}

//
// render spot value
//
function blpwp_render_spot($label,$number,$var = ""){
	?>
	<div class="spot">
		<span class="label"><?php esc_html_e($label,"blpwp");?></span>
		<span class="number"><?php echo esc_html(number_format($number));?></span>
		<span class="variation"><?php echo $var!="" ? "<b>".esc_html($var)."</b>" : "";?></span>
	</div>
	<?php
}



//
// render plugin settings page
// with tabs for appearance, settings and statistics
//
function blpwp_render_plugin_settings_page() {
    ?>
	<div class="wrap blpwp">
		<h1><?php _e("Bright Links: link previews and link analyzer settings","blpwp");?></h1>

				<form action="options.php" method="post">
					<?php 
					settings_fields( 'blpwp_plugin_options' );
					?>

					<nav>
						<a class="sel" href="#tab1"><?php esc_html_e("Appearance");?></a> | 
						<a href="#tab2"><?php esc_html_e("Settings");?></a> | 
						<a href="#tab3"><?php esc_html_e("Statistics","blpwp");?></a> |
						<a href="<?php echo esc_url(plugin_dir_url( __FILE__ )."docs/index.php");?>" target="_blank"><?php esc_html_e("Documentation");?></a>
					</nav>
					<div class="tabs">
						<div id="tab1">
							<?php do_settings_sections( 'blpwp_plugin2' ); ?>
							<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
						</div>
						<div id="tab2">
							<?php do_settings_sections( 'blpwp_plugin' ); ?>
							<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
						</div>
						<div id="tab3">
							<div>
								<br>
								<!--  ----------------------------------------------------- -->
                                <h2><?php _e("Monthly stats","blpwp");?></h2>
								<div id="table0"></div>
								<?php

								$data = blpwp_get_monthly_stats();
								
                                // $data is safe because it's extracted from db
                                // and contains data ready for tabulator object
								?><script>
									var tabledata0 = JSON.parse(<?php echo wp_json_encode(json_encode($data)); ?>);
									var table = new Tabulator("#table0", {
                                    height:"auto",
									layout:"fitColumns", //fit columns to width of table (optional)
                                    data: tabledata0,
                                    progressiveLoad:"scroll",
                                    paginationSize: 20,                                    
									columns:[
                                        {title:"<?php _e("Month","blpwp");?>", field:"month", formatter:"html"},
                                        {title:"<?php _e("Monthly clicks","blpwp");?>", field:"monthly_clicks", sorter:"number"},
                                        {title:"<?php _e("Previous month clicks","blpwp");?>", field:"previous_month_clicks", sorter:"number"},
                                        {title:"<?php _e("Click variation","blpwp");?>", field:"click_variation", sorter:"number"},
                                        {title:"<?php _e("Click variation percentage","blpwp");?>", field:"click_variation_percentage", sorter:"number"},
									],
								});
								</script>


								<!--  ----------------------------------------------------- -->
								<?php
								$data1 = blpwp_get_daily_stats();
								?>
                                <h2><?php _e("Daily trend","blpwp");?></h2>
								<div style='width:100%;height:400px'>
									<canvas id='myChart' style='width:100vw;height:400px'></canvas>
								</div>
								<script>
								jQuery(document).ready(function() {

									var tabledata1 = JSON.parse(<?php echo wp_json_encode(json_encode($data1), true); ?>);
									// Get the data from the tabledata1 array
									var data = tabledata1.map(function(entry) {
									return {
										day: entry.thedate,
										daily_clicks: parseInt(entry.daily_clicks),
                                        daily_hovers: parseInt(entry.daily_hovers)
									};
									});

									// Extract the day and daily_clicks values into separate arrays
									var days = data.map(function(entry) {
									return entry.day;
									});
									var dailyClicks = data.map(function(entry) {
									    return entry.daily_clicks;
									});
                                    var dailyHovers = data.map(function(entry) {
									    return entry.daily_hovers;
									});

									// Create the chart
									var ctx = document.getElementById('myChart').getContext('2d');
									var chart = new Chart(ctx, {
									type: 'line',
									data: {
										labels: days,
										datasets: [{
										    label: "<?php _e("Daily Clicks","blpwp");?>",
                                            data: dailyClicks,
                                            backgroundColor: 'rgba(0, 123, 255, 0.4)',
                                            borderColor: 'rgba(0, 123, 255, 1)',
                                            borderWidth: 2,
                                            pointRadius: 5,
                                            pointBackgroundColor: 'rgba(0, 123, 255, 1)'
                                            },{
										    label: "<?php _e("Daily Hovers","blpwp");?>",
                                            data: dailyHovers,
                                            backgroundColor: 'rgba(123, 123, 255, 0.4)',
                                            borderColor: 'rgba(123, 123, 255, 1)',
                                            borderWidth: 2,
                                            pointRadius: 5,
                                            pointBackgroundColor: 'rgba(123, 123, 255, 1)'
                                            },
                                        
                                        
                                        ]
									},
									options: {
										scales: {
										x: {
											title: {
											display: true,
											text: "<?php _e("Day","blpwp");?>"
											}
										},
										y: {
											title: {
                                                display: true,
                                                text: "<?php _e("Daily Clicks","blpwp");?>"
											}
										}
										}
									}
									});


								});
								</script>

								<?php



								// TOTAL STATS
								$stats = blpwp_get_stats();
								?>
                                <h2><?php esc_html_e("Totals","blpwp");?></h2>
								<div class="spotcontainer">
									<?php
									blpwp_render_spot("Total clicks",$stats['TOT_CLICKS']);
									blpwp_render_spot("Total hovers",$stats['TOT_HOVERS']);
									blpwp_render_spot("Internal links",$stats['TOT_INTERNALS'], 
										number_format( $stats['TOT_INTERNALS'] / $stats['TOT_LINKS'] * 100,0) . "%"
									);
									blpwp_render_spot("External links",$stats['TOT_LINKS'] - $stats['TOT_INTERNALS'],
										number_format( ($stats['TOT_LINKS'] - $stats['TOT_INTERNALS']) / $stats['TOT_LINKS'] * 100,0) . "%"
									);
									?>
								</div>
								
                                <h2><?php esc_html_e("All links","blpwp");?></h2>
								<div class="nearh2"><a href="#" id='allShow' class='sel'><?php _e("Show all","blpwp");?></a> | <a href="#" id='brokenShow'><?php _e("Broken","blpwp");?></a> | <a href="#" id='sponsoredShow'><?php _e("Sponsored","blpwp");?></a> | <a href="#" id='nofollowShow'><?php _e("Nofollow","blpwp");?></a></div>
								<div id="table"></div>
								<script>


								
								var table = new Tabulator("#table", {
                                    height:"400px",
									layout:"fitColumns", //fit columns to width of table (optional)
                                    ajaxURL: "<?php echo esc_url(admin_url('admin-ajax.php')."?action=blpwpgetstats"); ?>",
                                    progressiveLoad:"scroll",
                                    paginationSize: 500,                                    
									columns:[
                                        {title:"Post Title", field:"post_title", formatter:"html"},
                                        {title:"URL", field:"de_url"},
                                        {title:"Hover", field:"hover", sorter:"number"},
                                        {title:"Click", field:"click", sorter:"number"},
                                        {title:"Status", field:"error", sorter:"number", formatter:"tickCross"},
                                        {title:"Rel", field:"de_rel"},
									],
								});
								</script>
							</div>
						</div>						
					</div>
					<pre><?php
                    // eventually debug
					//  $options = get_option( 'blpwp_plugin_options' );
                    // print_r($options);
					?></pre>

				</form>

		
	</div>
    <?php
}





function blpwp_register_settings() {
    register_setting( 'blpwp_plugin_options', 'blpwp_plugin_options', 'blpwp_plugin_options_validate' );
    add_settings_section( 'save_these_options', null, 'blpwp_plugin_section_text', 'blpwp_plugin' );
	add_settings_section( 'save_these_options2', null, null, 'blpwp_plugin2' );
	
    // MAIN COLORS
    add_settings_field( 'blpwp_plugin_setting_color0', esc_html__('Choose main colors','blpwp'), 'blpwp_plugin_setting_color0', 'blpwp_plugin2', 'save_these_options2' );

    // HOT SPOT COLORS
    add_settings_field( 'blpwp_plugin_setting_color', esc_html__('Choose price hot spot colors','blpwp'), 'blpwp_plugin_setting_color', 'blpwp_plugin2', 'save_these_options2' );

 	// SIZE
    add_settings_field( 'blpwp_plugin_setting_size', esc_html__('Choose size','blpwp'), 'blpwp_plugin_setting_size', 'blpwp_plugin2', 'save_these_options2' );

    // STYLE
    add_settings_field( 'blpwp_plugin_setting_style', esc_html__('Choose style','blpwp'), 'blpwp_plugin_setting_style', 'blpwp_plugin2', 'save_these_options2' );

     
	// ON LINK IN CONTENTS
	add_settings_field( 'blpwp_plugin_setting_contentlinks', esc_html__('Apply on'), 'blpwp_plugin_setting_contentlinks', 'blpwp_plugin', 'save_these_options' );

	// ON LINK IN COMMENTS
	add_settings_field( 'blpwp_plugin_setting_commentlinks', '', 'blpwp_plugin_setting_commentlinks', 'blpwp_plugin', 'save_these_options' );

	// SELECTOR
    add_settings_field( 'blpwp_plugin_setting_selector', '', 'blpwp_plugin_setting_selector', 'blpwp_plugin', 'save_these_options' );

	// SKIP IMAGES
	add_settings_field( 'blpwp_plugin_setting_imagelinks', '', 'blpwp_plugin_setting_imagelinks', 'blpwp_plugin', 'save_these_options' );

	// SHOW CLICK COUNTER
    add_settings_field( 'blpwp_plugin_setting_counter', esc_html__('Show click counters','blpwp'), 'blpwp_plugin_setting_counter', 'blpwp_plugin', 'save_these_options' );

	// EXTERNAL/INTERNAL
    add_settings_field( 'blpwp_plugin_setting_extint', esc_html__('Apply to internal or external links','blpwp'), 'blpwp_plugin_setting_extint', 'blpwp_plugin', 'save_these_options' );

	// CACHE PREVIEWS
    add_settings_field( 'blpwp_plugin_setting_cache', esc_html__('Cache','blpwp'), 'blpwp_plugin_setting_cache', 'blpwp_plugin', 'save_these_options' );

	// FORCE TARGET BLANK ON EXTERNAL LINKS
    add_settings_field( 'blpwp_plugin_setting_blank', esc_html__('Force target','blpwp'), 'blpwp_plugin_setting_blank', 'blpwp_plugin', 'save_these_options' );

	// DISABLE ON MOBILE
    add_settings_field( 'blpwp_plugin_setting_disable_mobile', esc_html__('Disable on mobile','blpwp'), 'blpwp_plugin_setting_disable_mobile', 'blpwp_plugin', 'save_these_options' );

}
add_action( 'admin_init', 'blpwp_register_settings' );



function blpwp_plugin_options_validate( $input ) {
    $newinput['selector'] = trim( $input['selector'] );
	$newinput['selectorchk'] = trim( $input['selectorchk'] );
	$newinput['commentlinks'] = $input['commentlinks'] == "on" ? "on" : "";
	$newinput['contentlinks'] = $input['contentlinks'] == "on" ? "on" : "";
	$newinput['imagelinks'] = $input['imagelinks'] == "on" ? "on" : "";
	$newinput['extint'] = trim( $input['extint'] );
	$newinput['style'] = trim( $input['style'] );
	$newinput['color'] = trim( $input['color'] ); // SPOT bg
    $newinput['fgcolor'] = trim( $input['fgcolor'] );	// SPOT fg
	$newinput['color0'] = trim( $input['color0'] );  // MAIN bg
    $newinput['fgcolor0'] = trim( $input['fgcolor0'] ); // MAIN fg
	$newinput['size'] = trim( $input['size'] );
	$newinput['counter'] = trim( $input['counter'] );
	$newinput['cache'] = trim( $input['cache'] );
	$newinput['blank'] = $input['blank'] == "on" ? "on" : "";
	$newinput['disable_mobile'] = $input['disable_mobile'] == "on" ? "on" : "";
    return $newinput;
}


function blpwp_plugin_section_text() {
    _e('<p>Here you can set all the options and see statistics.</p>','blpwp');


}


// SELECTOR
function blpwp_plugin_setting_selector() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['selector']) ? $options['selector'] : "";
	$valchk = isset($options['selectorchk']) ? $options['selectorchk'] : "";
    $valchk = $valchk=="on" ? "checked" : "";

    echo "<input type='checkbox' name='blpwp_plugin_options[selectorchk]' id='selectorchk' value='on' ".esc_attr($valchk)."/>";
	echo esc_html__("links that match this javascript selector:",'blpwp');
	echo " <input id='blpwp_plugin_setting_selector' name='blpwp_plugin_options[selector]' type='text' value=\"" . esc_attr( $val ) . "\" />
	";

}

// COMMENTS LINKS
function blpwp_plugin_setting_commentlinks() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['commentlinks']) ? $options['commentlinks'] : "";
    $val = $val=="on" ? "checked" : "";
	echo "<input type='checkbox' id='blpwp_plugin_setting_commentlinks' name='blpwp_plugin_options[commentlinks]' value='on' ".esc_attr($val)." /> ";
	echo esc_html__("links in user comments",'blpwp');
}

// CONTENT LINKS
function blpwp_plugin_setting_contentlinks() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['contentlinks']) ? $options['contentlinks'] : "";
    $val = $val=="on" ? "checked" : "";
	echo "<input type='checkbox' id='blpwp_plugin_setting_contentlinks' name='blpwp_plugin_options[contentlinks]' value='on' ".esc_attr($val)." /> ";
	echo esc_html__("links in posts and pages content",'blpwp');
}

// SKIP IMAGES
function blpwp_plugin_setting_imagelinks() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['imagelinks']) ? $options['imagelinks'] : "";
    $val = $val=="on" ? "checked" : "";
	echo "<input type='checkbox' id='blpwp_plugin_setting_imagelinks' name='blpwp_plugin_options[imagelinks]' value='on' ".esc_attr($val)." /> ";
	echo esc_html__("links that point to an image",'blpwp');
}

// MAIN COLORS
function blpwp_plugin_setting_color0() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['color0']) ? $options['color0'] : "";
	echo "<input type='color' id='blpwp_plugin_setting_color0' name='blpwp_plugin_options[color0]' value=\"".esc_attr($val)."\" /> <span class='info'>".esc_html($val)."</span>";
    $val = isset($options['fgcolor0']) ? $options['fgcolor0'] : "";
	echo "<input type='color' id='blpwp_plugin_setting_fgcolor0' name='blpwp_plugin_options[fgcolor0]' value=\"".esc_attr($val)."\" /> <span class='info'>".esc_html($val)."</span></div>";   
}

// HOT SPOT COLORS
function blpwp_plugin_setting_color() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['color']) ? $options['color'] : "";
	echo "<input type='color' id='blpwp_plugin_setting_color' name='blpwp_plugin_options[color]' value=\"".esc_attr($val)."\" /> <span class='info'>".esc_html($val)."</span>";
    $val = isset($options['fgcolor']) ? $options['fgcolor'] : "";
	echo "<input type='color' id='blpwp_plugin_setting_fgcolor' name='blpwp_plugin_options[fgcolor]' value=\"".esc_attr($val)."\" /> <span class='info'>".esc_html($val)."</span></div>";   
}

// PANEL SIZE
function blpwp_plugin_setting_size() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['size']) ? $options['size'] : "";
	echo "<input type=\"range\" name='blpwp_plugin_options[size]' min=\"8\" max=\"12\" value=\"".esc_attr($val)."\" class=\"slider\" id=\"blpwp_plugin_setting_size\" data-divider=1> <span id='sizeis' class='info'>".esc_html($val)."</span>";
}

// STYLE
function blpwp_plugin_setting_style() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['style']) ? $options['style'] : "";
	$styles = array('rounded','square','filled');
	echo "<input type='hidden' id='blpwp_plugin_setting_style' name='blpwp_plugin_options[style]' value=\"".esc_attr($val)."\" />";

	?>
	<div class="flexcontainer">
	<?php
	foreach($styles as $style) {
		?>
		<div class="mblpwp loaded horizontal <?php echo esc_attr($style);?> <?php echo ($val==$style ? "selected" : "")?>" data-class="<?php echo esc_attr($style);?>">
			<div class="blpwp_wrap">
				<div class="blpwp_pic">
					<img src="<?php echo esc_url(plugin_dir_url( __FILE__ )."assets/blp.jpg?1");?>">
					<span class="blpwp_spot"><b><i>199 €</i></b></span>
				</div>
				<div class="blpwp_info">
					<div class='h5'><?php echo esc_html__("This is the main title of the linked page",'blpwp') ;?></div>
					<div class='h6'><?php echo esc_html__("In this section you will find a small description of the page from the linked url, the preview could contain less information than this example.",'blpwp') ;?></div>
					<div class='h4'>
						<img src="<?php echo esc_url(plugin_dir_url( __FILE__ )."assets/ico.jpg?1");?>">
						<?php echo esc_html__("www.linked.url",'blpwp');?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	?></div><?php
}

// SHOW CLICK COUNTER
function blpwp_plugin_setting_counter() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['counter']) ? $options['counter'] : "";
	echo "<select id='blpwp_plugin_setting_counter' name='blpwp_plugin_options[counter]'>
		<option value='yes' ".($val=="yes" ? "selected" : "").">".esc_html__("yes",'blpwp')."</option>
		<option value='no' ".($val=="no" ? "selected" : "").">".esc_html__("no",'blpwp')."</option>
	</select>";
}

// EXTERNAL/INTERNAL
function blpwp_plugin_setting_extint() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['extint']) ? $options['extint'] : "";
	echo "<select id='blpwp_plugin_setting_extint' name='blpwp_plugin_options[extint]'>
		<option value='' ".($val=="" ? "selected" : "").">".esc_html__("both",'blpwp')."</option>
		<option value='internal' ".($val=="internal" ? "selected" : "").">".esc_html__("internal links",'blpwp')."</option>
		<option value='external' ".($val=="external" ? "selected" : "").">".esc_html__("external links",'blpwp')."</option>
	</select>";
}

// CACHE
function blpwp_plugin_setting_cache() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['cache']) ? $options['cache'] : "";
    echo "<input type='checkbox' id='blpwp_plugin_setting_cache' name='blpwp_plugin_options[cache]' value='on' ".($val=="on" ? "checked" : "")." /> ";
	echo esc_html__("Cache previews to load faster",'blpwp');
}

// FORCE TARGET BLANK ON EXTERNAL LINKS
function blpwp_plugin_setting_blank() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['blank']) ? $options['blank'] : "";
    echo "<input type='checkbox' id='blpwp_plugin_setting_blank' name='blpwp_plugin_options[blank]' value='on' ".($val=="on" ? "checked" : "")." /> ";
	echo esc_html__("Force target blank on external links",'blpwp');
}


// DISABLE THE POP UP UN MOBILE
function blpwp_plugin_setting_disable_mobile() {
    $options = get_option( 'blpwp_plugin_options' );
	$val = isset($options['disable_mobile']) ? $options['disable_mobile'] : "";
    echo "<input type='checkbox' id='blpwp_plugin_setting_disable_mobile' name='blpwp_plugin_options[disable_mobile]' value='on' ".($val=="on" ? "checked" : "")." /> ";
	echo esc_html__("Disable the pop up on mobile",'blpwp');
}

