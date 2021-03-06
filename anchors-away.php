<?php

/*
  ___                                               ___                                 
 -   -_,            ,,                             -   -_,                           /\ 
(  ~/||             ||                            (  ~/||  ;        _                \/ 
(  / ||  \\/\\  _-_ ||/\\  /'\\ ,._-_  _-_,       (  / ||  \\/\/\  < \, '\\/\\  _-_, }{ 
 \/==||  || || ||   || || || ||  ||   ||_.         \/==||  || | |  /-||  || ;' ||_.  \/ 
 /_ _||  || || ||   || || || ||  ||    ~ ||        /_ _||  || | | (( ||  ||/    ~ ||    
(  - \\, \\ \\ \\,/ \\ |/ \\,/   \\,  ,-_-        (  - \\, \\/\\/  \/\\  |/    ,-_-  <> 
                      _/                                                (               
version 1.0
									  P___----....
									! __
						  ' ~~ ---.#..__ `  ~  ~    -  -  .   .:
						   `             ~~--.               .F~~___-__.
						   ;                   ,       .- . _!  
						  ,                     '       ;      ~ .
						 ,        ____           ;      ' _ ._    ; 
						,_ . - '___#,  ~~~ ---. _,   . '  .#'  ~ .;
					  =---==~~~    ~~~==--__     ; '~ -. ,#_     .'
					   '                     `~=.;           `  /
												 '  '          '.      
						'                         '               
				\                                  ' '            '
				`.`\    '                          . ;             ,
				  \  `  '                            '             ;
				   ;   '                           '               '
				 /_ .,                           /   __...---./   '
				 ',_,   __.--- ~~;#~ --..__    _'.-~;#     //  `.'
				 / / ~~ .' .     #;         ~~  /// #;   //   /
			   /    ' . __ .  ' ;#;_ .        ////.;#;./ ;  /
			   \ .        /    ,##' /   _   /. '(/    ~||~\'
				\  ` - . /_ . -==-  ~ '   / (/ '     . ;;. ', 
			   /' .       ' -^^^...--- ``(/'    _  '   '' `,;
	##,. .#...(       '   .c  c .c  c  c.    '..      ;; ../ 
	%%#%;,..##.\_                           ,;###;,. ;;.:##;,.    
	%%%%########%%%%;,.....,;%%%%%%;,.....,;%%%%%%%%%%%%%%%%%%%%............      
	
Anchors away is a simple crawler & scraper script written by Gary Adams of Wormhole Web Works. With it you can
crawl recursively while striping, anchor tags, imgs urls, text content, html blocks, page titles & metas tags from any site.
It's just been born so there is no error handling yet. 

If you find it usefull maybe toss some work our way at http://www.wormholewebworks.com
or like us on facebook @ https://www.facebook.com/wormholewebworks/.
Have fun swashbuckling 


*/



function anchorsAway($url, $plunder, $depth = 5)
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
	
	//add dom and url to plunder args 
	$resources['dom'] = $dom;
	$resources['url'] = $url;
	
	//get the anchor tags
    $anchors = $dom->getElementsByTagName('a');
	//loop through anchors 
    foreach ($anchors as $element) {
		//get the hrefs
        $href = $element->getAttribute('href');
		//if the url doesnt start with http, relative url detection and global url reconstruction
        if (0 !== strpos($href, 'http')) {
		//add a slash, but remove slashes if they are present. "/something" or "something" both return "/somthing"
            $path = '/' . ltrim($href, '/');
			
		//if we still have http, go ahead and build url
            if (extension_loaded('http')) {
                $href = http_build_url($url, array('path' => $path));
            } else {
		//parse the url in parts 'scheme' == http || https and 'host' = domain
                $parts = parse_url($url);
		//add domain and ://
                $href = $parts['scheme'] . '://';
		//if parts 'user' and 'pass' exist
                if (isset($parts['user']) && isset($parts['pass'])) {
		//add them back at the begining
                    $href .= $parts['user'] . ':' . $parts['pass'] . '@';
                }
		//add host "domain"
                $href .= $parts['host'];
		//add port if set
                if (isset($parts['port'])) {
                    $href .= ':' . $parts['port'];
                }
		//finally add the path back in, we now now have constructed our full global url from a relative one
                $href .= $path;
            }
        }
		//recursive call
        anchorsAway($href,$plunder, $depth - 1);	
    }
	plunder($plunder,$resources);

}


function plunder($plunder, $resources){
	//get our dom object and url
	$dom = $resources['dom'];
	$url = $resources['url'];
	//setup or xpath query object 
	$finder = new DomXPath($dom);
	//set or booty
	$booty = array();
	//get title tags
	if(isset($plunder['titles'])){
		$titles = $dom->getElementsByTagName("title");	
		if(isset($titles->item(0)->textContent)){
			$title = $titles->item(0)->textContent;	
		}
	}	
	if(isset($plunder['metas'])){
		//get meta tags
		$metas = @get_meta_tags($url);
	}

	if(isset($plunder['drop dashes'])){
		$url_key = str_replace('-'," ",basename($url));
	}
	else{
		//reduce url to basename
		$url_key = basename($url);
	}
	$booty[$url_key] = array();
	
	
	if(isset($title)){
		$booty[$url_key]['title'] = addslashes($title);
	}
	if(isset($metas['description'])){
		$booty[$url_key]['description'] = addslashes($metas['description']);
	}
	if(isset($metas['keywords'])){
		$booty[$url_key]['keywords'] = addslashes($metas['keywords']);
	}
	
	//scrape images
	if(isset($plunder['img'])){
		$booty[$url_key]['imgs'] = array();
		//get images 
		$images = $dom->getElementsByTagName("img");
		
		//iterate through and get paths
		foreach($images as $image){
			$image_paths[]=urldecode($image->getAttribute('src'));
		}
		//filter out all null, false and empty values
		$image_paths = array_filter($image_paths,'strlen');
		//reset keys after removing empties
		$image_paths = array_values($image_paths);
		//write our array
		foreach($image_paths as $key => $image_path){
			$booty[$url_key]['imgs'][$key] = $image_path;
		}

	}
	

		if(isset($plunder['elements by id'])){
			
			$element = $dom->getElementById($plunder['elements by id'])->textContent;
			$booty[$url_key]['text content'] = trim($element);

		}
		if(isset($plunder['img xpath'])){
			
			$xpath = $plunder['img xpath'];

			$content = $finder->query($xpath);
			
			foreach ($content as $img) {
				$booty[$url_key]['xpath imgs'][] = $img->getAttribute('src');
			}
		}
		if(isset($plunder['description xpath'])){
			
			$xpath = $plunder['description xpath'];
			
			$content = $finder->query($xpath);
			
			foreach ($content as $desc) {
				$booty[$url_key]['xpath description'][] = $desc->textContent;
			}
		}
		if(isset($plunder['children xpath'])){
			
			$xpath = $plunder['children xpath'];
			
			$content = $finder->query($xpath);
			

			foreach ($content as $desc) {
				
				
				var_dump(DOMinnerHTML($desc));
				
				
				$booty[$url_key]['xpath html'][] = $desc;
			}
		}
		//print it out
		echo '<pre>';
		var_dump($booty); 
		echo '</pre>';
		//or return it.
		return $booty;
}

function DOMinnerHTML(DOMNode $element) 
{ 
    $innerHTML = ""; 
    $children  = $element->childNodes;

    foreach ($children as $child) 
    { 
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }

    return $innerHTML; 
} 


$my_plunder_args = array(
	//rip all images from page.
	'img' => true, 
	//rip text content from any given ID
	'elements by id' => 'some Id',
	//rip images from inside any xpath
	'img xpath' => "xpath to element",
	//rip text content from any xpath
	'description xpath' => "xpath to element",
	//rip text inner html from any xpath
	'children xpath' => "xpath to element",
	//rip metas from any url
	'metas' => true,
	//rip titles form url 
	'titles' => true,
	//drop dashes from ulr keys
	'drop dashes' => true,
	
);


anchorsAway('http://yourwebsite.com/', $my_plunder_args, 2);


?>




