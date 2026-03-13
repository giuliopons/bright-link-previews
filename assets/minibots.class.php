<?php
/* ------------------------------------------------------------------------- */
/* minibotsWP Ver.1                                                          */
/* ------------------------------------------------------------------------- */
/* Mini Bots class is a small php class that helps you to create bots,       */
/* it uses some free web seriveces online to retrive usefull data.           */
/* ------------------------------------------------------------------------- */

Class MinibotsWP
{
	//
	// If you call $minibot->findType(...) this variable will be populated with data that describe
	// common filetypes. Used in $minibot->getUrlInfo(...) method
	private $fileInfoJson = false;

	public function __construct () {
		
	}




	// ------------------------------------------------------------------------------------
	// HELPER FUNCTIONS
	// ------------------------------------------------------------------------------------


	//
	//	this function return the html attribute of a given tag
	//	(use for scraping data)
	public function attr($s,$attrname) {
		preg_match_all('#\s*('.$attrname.')\s*=\s*["]([^"]*)["]\s*#i', $s, $x); 
		if (count($x)>=3 && isset($x[2][0])) return isset($x[2][0]) ? $x[2][0] : "";
		preg_match_all('#\s*('.$attrname.')\s*=\s*[\']([^\']*)[\']\s*#i', $s, $x); 
		if (count($x)>=3 && isset($x[2][0])) return isset($x[2][0]) ? $x[2][0] : "";
		preg_match_all('#\s*('.$attrname.')\s*=\s*([^ ]*)\s*#i', $s, $x); 
		if (count($x)>=3 && isset($x[2][0])) return isset($x[2][0]) ? $x[2][0] : "";
		return "";
	}


	//
	// return the part of the string $s between strings $a and $b
	public function betweenTags($s,$a,$b) {
		$s1  =  str_replace($a,"",stristr($s,$a));
		if($s1) {
			$s2 = str_replace(stristr($s1,$b), "", $s1);
		}
		return $s2;
	}
	/*function betweenTags($s,$a,$b) {
		$s1  =  str_replace($a,"",stristr($s,$a));
		return $s1 ? str_replace(stristr($s1,$b), "", $s1) : $s1;
	}*/



	//
	//	return the array of matches when searching for a 
	//	tag serie while scraping html
	//	$return can be "ALL" | "INNER" | "OUTER"
	public function getTags($tagname,$text,$return="ALL") {
		if($tagname=="img" || $tagname=="br" || $tagname=="input") {
			// autoclose
			preg_match_all('#<'.$tagname.'[^>]*?>#Uis', $text, $s);
		} else {
			preg_match_all('#<'.$tagname.'[^>]*?>(.*)</'.$tagname.'>#Uis', $text, $s);
		}
		if($return=="ALL") return $s;
		if($return=="INNER") return $s[1];
		if($return=="OUTER") return $s[0];
		return $s;
	}




	// 
	//	this function makes a relative url an absolute merging
	//	properly the url and the link.
	public function makeabsolute($url,$link) {
		$p = parse_url($url);
		if (strpos( $link,"//")===0 ) return trim($link);
		if (strpos( $link,"http://")===0 ) return trim($link);
		if (strpos( $link,"https://")===0 ) return trim($link);
		if($p['scheme']."://".$p['host']==$url && $link[0]!="/" && $link!=$url) return trim($p['scheme']."://".$p['host']."/".$link);
		if (strpos( $link, "/")===0) return trim($p['scheme']."://".$p['host'].$link);
		return trim(str_replace(substr(strrchr($url, "/"), 1),"",$url).$link);
	}




	//
	//	this method gets a page using WordPress HTTP API.
	//	Returns array($body, $headers_string).
	//	This method doesn't handle POST data.
	public function getPage($url, $max_file_size=0) {

		$args = array(
			'timeout'     => 15,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
			'sslverify'   => false,
			'headers'     => array(
				'Accept-Language' => 'en-US,en;q=0.8',
				'Accept-Encoding' => 'gzip, deflate',
			),
		);

		if ($max_file_size > 0) {
			$args['limit_response_size'] = $max_file_size;
		}

		$response = wp_remote_get($url, $args);

		if (is_wp_error($response)) {
			return array($response->get_error_message(), '');
		}

		$body = wp_remote_retrieve_body($response);
		$headers_raw = wp_remote_retrieve_headers($response);
		$header_string = '';
		foreach ($headers_raw as $key => $value) {
			$header_string .= $key . ': ' . $value . "\n";
		}

		return array($body, $header_string);
	}





	//
	//	this method returns all the emails contained
	//	in the page.
	public function findEmails($page) {
		preg_match_all(
			'/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}\b/i',
			$page,
			$matches
		);
		$outAr = array();
		foreach(array_unique($matches[0]) as $email) {
			//echo "<code>".$email."</code><br/>";
			$outAr[] = $email;
		}
		return $outAr;
	}




	//
	//	remove all html and tags from a url and get only the text
	//	TO DO: could be improved to use only useful tags (headings and paragraphs)
	public function justText($text) {
		$text = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $text);
		$text = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $text);
		$text = preg_replace("/[\n\r\t]/"," ",strip_tags($text));
		$text = preg_replace("/(  +)/"," ",strip_tags($text));
		return trim($text);
	}







	//
	//	function to get remote file size
	//	TO DO: Does it work with https?
	public function getRemoteFileSize($url) {
		if (substr($url,0,4)=='http') {
			$h = @get_headers($url, 1);
			if($h) {
				$x = array_change_key_case($h,CASE_LOWER);
			} else return false;
			if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) { $x = $x['content-length'][1]; }
			else { $x = $x['content-length']; }
		}
		else { $x = @filesize($url); }
		return $x;
	} 




	//
	// walk recursively throught an object and extract matching values
	// by property name or by key. Useful to extract data from
	// ldjson data in pages. Used in $minibot->getUrlInfo(...) method.
	public function walk_recursive($obj, $key) {
		$found = array();
	  if ( is_object($obj) ) {
		foreach ($obj as $property => $value) 
			if($property === $key) $found[] = $value;
			elseif (is_array($value) || is_object($value)) 
				$found = array_merge( $found,  $this->walk_recursive($value, $key) );

	  } elseif ( is_array($obj) ) {
		foreach ($obj as $keyar => $value) 
			if($keyar === $key) $found[] = $value;
				elseif (is_array($value) || is_object($value)) $found = array_merge( $found,  $this->walk_recursive($value, $key) );
	  }
	  return $found;
	}


	//
	// sometimes downloaded pages have javascript object that are
	// automatically convertible to json objects (for single quotes)
	// so this functions make some replaces. Used with Amazon pages
	// in $minibot->getUrlInfo(...) method.
	public function fixDecodeJson( $code ) {
		$code = preg_replace("/[ \t\n\r]+/", " ", $code);
		$code = preg_replace("/{( *)?'/","{\"",$code);
		$code = preg_replace("/'( *)?}/","\"}",$code);
		$code = preg_replace("/:( *)?'/",":\"",$code);
		$code = preg_replace("/'( *)?:/","\":",$code);
		$code = preg_replace("/,( *)?'/",",\"",$code);
		$code = preg_replace("/'( *)?,/","\",",$code);
		return $code;		
	}


	//
	//	return info on a file extension, lib here (downloaded locally)
	//	https://gist.github.com/giuliopons/0913e0bcd1ed5a9c7e0ef012248d15e3
	public function findType($ext) {
		if(!$this->fileInfoJson) {
			$this->fileInfoJson = json_decode(file_get_contents(dirname(__FILE__) . "/fileinfo.json"));
		}
		foreach($this->fileInfoJson as $t => $a) {
			if($t == $ext || $t==strtoupper($ext)) {
				return $a->descriptions[0];
			}
		}
		return "";
	}

	//
	// extract the ldjson object or the oembed object from a webpage.
	// this object can be used to search data with walk_recursive method.
	public function getLdJsonStringOembed($webpage) {
		$o = array();
		preg_match_all("/<script( *)?type( *)?=( *)?\"application\/ld\+json\"([^>]*)>(.*)<\/script>/imsU", $webpage, $matches);
		if(isset($matches[5]) && !empty($matches[5])) {
			foreach( $matches[5] as $obj) {
				$o[] = json_decode($obj);
			}
		} 
		preg_match_all("/<link rel=\"alternate\" type=\"application\/json\+oembed\" href=\"(.*)\">/imsU",$webpage,$matches);
		if(isset($matches[1]) && !empty($matches[1])) {
			$ar = $this->getPage($matches[1][0]);
			if($ar[0]) $o[] = json_decode($ar[0]);
		}
		return !empty($o) ? $o : null;
	}

	// ------------------------------------------------------------------------------------
	// MAIN METHOD
	// ------------------------------------------------------------------------------------


	//
	//	Fetch info for a specified URL, maximages and minkbimg are usefull to get useful images,
	//	so if there is a small icon this image will be skipped, to find an image bigger.
	//	Usage example:
	//	$obj = new Minibots();
	//	$infos = $obj->getUrlInfo("http://piccsy.com/2013/10/cute-dog"); 
	//	--> array( ... )
	public function getUrlInfo($url,$maximages=5,$minkbimg=10) {

		//
		// DEFAULTS
		$data['favicon']="";
		$data['images']=array();
		$data["domain"] = "";
		$data['title']= "";
		$data["lastmodified"] = "";
		$data['description']= "";


		//
		// ANCHOR
		if(preg_match("/^#/",$url)) { $data["err"] = "Local anchor url"; return $data; }


		//
		// IS A MAILTO URL
		if(preg_match("#^mailto:#",$url)) {
			$emails = $this->findEmails($url);
			$e = array_pop($emails);
			$data['title']= isset($e) ? $e : "Mailto command";
			$data['description']= isset($e) ? "Send an email to this address" : "Send email";
			return $data;
		}

		//
		// EMPTY
		if($url=="") { $data["err"] = "Empty url"; return $data; }


		//
		// JAVASCRIPT
		if(preg_match("/^javascript\:/",$url)) { $data["err"] = "Javascript code"; return $data; }

    

		//
		// PARSE URL OBJECT
		$parsed_url =  parse_url($url);
		$data["domain"] = isset($parsed_url["host"]) ? $parsed_url["host"] : "";
        $data["favicon"] = $parsed_url["scheme"]."://".$parsed_url["host"]."/favicon.ico"; //guess

		//
		// IS AN IMAGE FILE
		if(preg_match("/(\.(jpe?g|gif|png|webp))$/i",$url,$matches)) {
			// defaults
			$data['description']= "This is an image file";
			$data['title']=basename($url);
			$data['images']=array($url);
			$data['favicon'] = $parsed_url["scheme"]."://".$parsed_url["host"]."/favicon.ico"; //guess
			return $data;
		}


		//
		// IS A PDF FILE
		if(preg_match("/(\.pdf)$/i",$url)) {
			$data['description']="This is a PDF file.";
			$data['title']=basename($url);
			$data['images']=array();
			return $data;
		}


		//
		// IS A FACEBOOK URL
		if(preg_match("#^https?://www\.facebook\.com#",$url)) {
			$data['favicon']="https://www.facebook.com/favicon.ico";
			$data['title']= preg_replace("#^/#","",$parsed_url['path']);
			if(substr_count($parsed_url['path'],"/")==1 && $parsed_url['path']!="/sharer.php") {
				$data['description']="This url should be a Facebook url page";
			} elseif($parsed_url['path']=="/sharer.php"){
				$data['title'] = "Share";
				$data['description'] = "Share this content on Facebook";
			}else {
				 $data['description']="This is a Facebook url";
			 }
			return $data;
		}


		//
		// IS A TWITTER URL
		if(preg_match("#^https?://(www\.)?twitter\.com#",$url)) {
			$data['favicon']="https://twitter.com/favicon.ico";
			$data['title']= preg_replace("#^/#","",$parsed_url['path']);
			if(substr_count($parsed_url['path'],"/")==1) {
				$data['description']="This url should be a Twitter url page";
			} elseif($parsed_url['path']=="/intent/tweet"){
				$data['title'] = "Share";
				$data['description'] = "Share this content on Twitter";
			}else {
				 $data['description']="This is a Twitter url";
			 }
			return $data;
		}

		//
		// IS A LINKEDIN URL
		if(preg_match("#^https?://(www\.)?linkedin\.com#",$url) && !preg_match("#^https?://(www\.)?linkedin\.com/feed/update#",$url)) {
			$data['favicon']="https://www.linkedin.com/favicon.ico";
			if(substr_count($parsed_url['path'],"/")==3 && preg_match("#^/in/([^/]*))/?$#",$parsed_url['path'],$m)) {
				$data['description']="This is a Linkedin url page";
				$data['title'] = $m[1];
			} elseif($parsed_url['path']=="/shareArticle"){
				$data['title'] = "Share";
				$data['description'] = "Share this content on Linkedin";
			}else {
				$data['title'] = "Linkedin content";
				 $data['description']="This is a Linkedin url";
			 }
			return $data;
		}


		//
		// IS INSTAGRAM URL
		if(preg_match("#^https?://(www\.)?instagram\.com#",$url)) {
			// TRY SPECIFIC BOT
			$metas = $this->getInstagramUrl($url);
			if(isset($metas["og:description"])) {
				$data['title']= $metas["twitter:title"];
				$data['description']= $metas["og:description"];
			} else {
				$data['title']="Instagram URL";
				$data['description']="Sorry can't fetch content";
			}
			return $data;
		}



		//
		// LOCAL URL
		if(!preg_match("/^https?:\/\//",$url)) { $data["err"] = "Url must begin with http"; return $data; }




		//
		// FETCH URL
		$web_page_ar = $this->getPage($url, $maximages == 0 ? 5000 : 0);


		// IF META REFRESH WITH HTML GET NEW URL
		$metas = $this->getMetaTags($web_page_ar[0],["http-equiv"]);
		if(isset($metas["refresh"]))  {
			$metas["refresh"] = preg_replace("/^([0-9]*)\;URL=/i","",$metas["refresh"]);
			if($metas["refresh"]!="") {
				$url = $metas["refresh"];
				$web_page_ar = $this->getPage($url, $maximages == 0 ? 5000 : 0);
				$parsed_url =  parse_url($url);
				$data["domain"] = isset($parsed_url["host"]) ? $parsed_url["host"] : "";
			}
		}

		
		// IF THE FETCHED URL WAS A REDIRECT (CURL FOLLOWS REDIRECT)
		// UPDATE URL INFO
		preg_match("#\nlocation: (.*)\n#Uis",$web_page_ar[1],$newurl);
		if(isset($newurl[1]) && $newurl[1]!="") {
			$url = $newurl[1];
			$parsed_url =  parse_url($url);
			$data["domain"] = isset($parsed_url["host"]) ? $parsed_url["host"] : "";
		}


		// ADDITIONAL DATA
		$ldJsonOembed = $this->getLdJsonStringOembed( $web_page_ar[0] );


		//
		// SEARCH TITLE
		$title_array = $this->getTags("title", $web_page_ar[0], "INNER");
		/*preg_match_all('#<title([^>]*)?>(.*)</title>#Uis', $web_page_ar[0], $title_array);*/
		$data['title'] = isset($title_array[0]) ? $title_array[0] : "";

		//
		// SEARCH DESCRIPTION AND TITLE
		// 1 LDJSON / OEMBED
		$arDescription = $this->walk_recursive( $ldJsonOembed, "description" );
		if(is_array($arDescription) && isset($arDescription[0])) $data['description'] = $arDescription[0]; // o array_pop ?
			else $data['description']="";


		// 2 META
		if($data['description']=="") {
			$metas = $this->getMetaTags($web_page_ar[0]);
			if(isset($metas["description"])) $data["description"] = $metas["description"];
			if(isset($metas["og:description"])) $data["description"] = $metas["og:description"];
			if(isset($metas["og:title"])) $data["title"] = $metas["og:title"];
		}

		// 3 FIRST <P>
		if($data['description']=="") {
			preg_match_all('#<p([^>]*)>(.*)</p>#Uis', $web_page_ar[0], $p_array);
			$text = "";
			for($i=0;$i<count($p_array[0]);$i++) if(strlen($text)<200) $text.=$this->justText($p_array[0][$i])." ";
			$data['description']=$text;
		}
		// 4 TEXT
		if($data['description']=="") {
			$text = "";
			$text =$this->justText( $web_page_ar[0]);
			$data['description']=substr($text,0,200);
		}


		//
		// SEARCH FAVICON
		preg_match_all('#<link([^>]*)(.*)>#Uis', $web_page_ar[0], $link_array);
		for($i=0;$i<count($link_array[0]);$i++) {
			$rel = strtolower($this->attr($link_array[0][$i],"rel"));
			if (stristr($rel,"icon") )
				$data['favicon'] = $this->makeabsolute($url,$this->attr($link_array[0][$i],"href"));
		}
  
		//
		// SEARCH PRICE (WOOCOMMERCE / SHOPIFY)
		$arPrice = $this->walk_recursive( $ldJsonOembed, "price" );
		if(is_array($arPrice)) {
			$data['price'] = array_pop($arPrice);
			$ar = $this->walk_recursive( $ldJsonOembed, "priceCurrency" );
			$currency = array_pop( $ar );// (WOOCOMMERCE)
			$ar = $this->walk_recursive( $ldJsonOembed, "currency_code" );
			$currency = $currency !="" ? $currency : array_pop( $ar  );// (SHOPIFY)
			if($currency == "USD") $currency = "$";
			if($currency == "EUR") $currency = "€";
			if( $data['price'] > 1) $data['price'] = number_format($data['price'],0);
			$data['price'] .= $currency;
			$data['price'] = trim($data['price']);
		}




		//
		// SEARCH MAIN IMAGES ON OPEN GRAPH, META AND SCHEMA ORG
		$imgs0 = array(); // correct
		$imgs = array();  // other
		$arImgs = $this->walk_recursive( $ldJsonOembed, "image" );
		if(is_array($arImgs)) $imgs0 = $arImgs;
		$metas = $this->getMetaTags($web_page_ar[0]);
		foreach($metas as $k => $v) if(in_array($k, array("og:image","image") )) array_push($imgs0,$v);



		//
		// AMAZON URLS
		if(preg_match("/^https?:\/\/www\.amazon\.([^\.\/]*)\//i",$url)) {

			// description
			$desc = trim($this->betweenTags($web_page_ar[0],"bookDescEncodedData =",'",'));
			$desc = trim( urldecode(str_replace("\"","",$desc)) );

			if(strlen($desc)<5) {
				$desc = trim($this->justText($this->betweenTags($web_page_ar[0],"<div id=\"feature-bullets\" class=\"a-section a-spacing-medium a-spacing-top-small\">",'</div>')));
			}

			if(strlen($desc)<5) {
				$d = "";
				$ar = $this->getTags("noscript",$web_page_ar[0],"INNER");
				if(!empty($ar)) {
					$d="";
					foreach($ar as $o) {
						$o = trim($this->justText($o));
						if ($o!="") { $d = $o; break;}
					}
				}
			}

			$data['description']= $desc;

			
			// IL PRIMO PREZZO
			// "displayPrice":"7,99 €"   o   <span class=\"a-offscreen\">7,99
			//preg_match_all("/<span class=\"a-offscreen\">([^<]*)<\/span>/imsU",$web_page_ar[0],$price); //price
			preg_match_all("/(\"|')displayPrice(\"|')( *)?:( *)?(\"|')(.*)(\"|')/imsU",$web_page_ar[0],$price); //price
			if(isset($price[6][0]) && !empty($price[6][0])) {
				$data["price"] = $this->justText($price[6][0]);
				if( $data['price'] > 1) $data['price'] = number_format($data['price'],0);
				$data['price'] = str_replace(" ","",$data['price']);
			}

			// IMMAGINI
			preg_match_all("/(\"|')colorImages(\"|')( *)?:(( *)(.*)\}\]\})/imsU",$web_page_ar[0],$images2); // images
			if(isset($images2[4][0])) {
				$code = $this->fixDecodeJson( $images2[4][0] );
				$obj = json_decode( $code );
				$imgs0 = $this->walk_recursive( $obj, "large" );
			}

			$data["favicon"] = "https://upload.wikimedia.org/wikipedia/commons/archive/d/de/20171005153411%21Amazon_icon.png";

		}
	






		//
		// SEARCH OTHER IMAGES TAGS
		preg_match_all('#<img([^>]*)(.*)/?>#Uis', $web_page_ar[0], $imgs_array);
		for($i=0;$i<count($imgs_array[0]);$i++) {
			$src = $this->attr($imgs_array[0][$i],"src");
			$width = $this->attr($imgs_array[0][$i],"width");
			if ( $src && !stristr($src,"data:image") && preg_match('/(\.(jpe?g|gif|png|svg))(#(.*))?$/i',$src)) {
				$src = $this->makeabsolute($url,$src);
				// if search for image size > 0
				if($minkbimg>0) {
					$kb = $this->getRemoteFileSize($src);
					if(!in_array($src,$imgs) && $kb>$minkbimg*1000) {
						if(preg_match('/(\.jpe?g)(#(.*))?$/i',$src)) array_unshift($imgs,$src);
							else array_push($imgs,$src);
					}
				} else {
					if(preg_match('/(\.jpe?g)(#(.*))?$/i',$src)) array_unshift($imgs,$src);
						else array_push($imgs,$src);
				}
			}
			if (count($imgs)>$maximages-1) break;
		}
		$data['images']=array_merge($imgs0, $imgs);




		//
		// LAST MODIFIED DATE
		$data['lastmodified']="";
		$h = explode("\n",str_replace("\r","",$web_page_ar[1]));
		foreach($h as $header) {
			if(stristr($header,"last-modified")) {
				$data["lastmodified"] = trim(substr($header,strlen("last-modified")+1));
			}
		}


		//
		// NO INFO?
		if($data["title"]=="" && $data["description"]=="") {
			
			//
			// find generic information on file type
			//
			if(preg_match("/(\.([^\.]*))$/i",$url,$matches)) {
				$desc = $this->findType($matches[2]);
				if($desc) {
					// defaults
					$data['description']= $this->findType($matches[2]);
					$data['title']=basename($url);
					$data['images']=array();
					$data['favicon'] = $parsed_url["scheme"]."://".$parsed_url["host"]."/favicon.ico";
					return $data;
				}
			}
			$data["err"] = "Can't fetch content or empty page.";


		}
		

		return $data;
	}




	//
	// extract meta tags
	public function getMetaTags($html, $ar = array( "name", "property", "itemprop") ) {
		preg_match_all('#<meta([^>]*)(.*)>#Uis', $html, $meta_array);
		$data = [];
		for($i=0;$i<count($meta_array[0]);$i++) {
			foreach($ar as $k)	{
				$key = $this->attr($meta_array[0][$i], $k );
				if ($key!="") $data[$key] = trim($this->attr($meta_array[0][$i],"content"));
			}
		}			
		return $data;
	}


	//
	// get some info on an instagram image url
	public function getInstagramUrl($url) {
		$response = wp_remote_get($url, array(
			'timeout'    => 15,
			'sslverify'  => false,
			'user-agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
			'headers'    => array(
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
				'Accept-Language'  => 'it-IT,it;q=0.9',
				'Cache-Control'    => 'no-cache',
				'Accept-Encoding'  => 'gzip, deflate',
			),
		));
		if (is_wp_error($response)) return array();
		$result = wp_remote_retrieve_body($response);

		return $this->getMetaTags($result);
	}

}
?>