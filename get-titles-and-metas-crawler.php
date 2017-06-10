<?php

function crawl_page($url, $depth = 5)
{
    static $seen = array();
	//stop if either of these are true
    if (isset($seen[$url]) || $depth === 0) {
        return;
    }
	
	
	//log seen pages 
    $seen[$url] = true;
	//get the DOM
    $dom = new DOMDocument('1.0');
	//parse the url into DOM object  
    @$dom->loadHTMLFile($url);
	//get the anchor tags
    $anchors = $dom->getElementsByTagName('a');
	//get title tags
	$titles = $dom->getElementsByTagName("title");
	//get meta tags
	$metas = get_meta_tags($url);
	//iterate metas array

	$title = $titles->item(0)->textContent;
		
	//loop through anchors 
    foreach ($anchors as $element) {
        $href = $element->getAttribute('href');
		
		//detect relative links
        if (0 !== strpos($href, 'http')) {
			
            $path = '/' . ltrim($href, '/');
            if (extension_loaded('http')) {
                $href = http_build_url($url, array('path' => $path));
            } else {
                $parts = parse_url($url);
                $href = $parts['scheme'] . '://';
                if (isset($parts['user']) && isset($parts['pass'])) {
                    $href .= $parts['user'] . ':' . $parts['pass'] . '@';
                }
                $href .= $parts['host'];
                if (isset($parts['port'])) {
                    $href .= ':' . $parts['port'];
                }
                $href .= $path;
            }
        }
        //recursive call
        crawl_page($href, $depth - 1);
    }
	
		
	echo "
	array(
			
		['url'] => '".basename($url)."',";
		echo "\r\n";
		if(isset($title)){
			echo "\t\t['title'] => '".$title."',";
			echo "\r\n";
		}
		if(isset($metas['description'])){
			echo "\t\t['description'] => '".$metas['description']."',";
			echo "\r\n";
		}
		if(isset($metas['keywords'])){
			echo "\t\t['description'] => '".$metas['keywords']."',";
			echo "\r\n";
		}
		echo "\r\n";
		echo "\t\t),";
	

}
//initial call
crawl_page('http://somesite.com', 2)


?>


