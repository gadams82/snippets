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
version 1.0 
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
			//add a slash,but remove slashes if they are present. "/something" or "something" both return "/somthing"
            $path = '/' . ltrim($href, '/');
			
			//if we still have http somewhere, go ahead and build url
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

	$dom = $resources['dom'];
	$url = $resources['url'];
	//get title tags
	if(isset($plunder['title'])){
		$titles = $dom->getElementsByTagName("title");
		$title = $titles->item(0)->textContent;		
	}	
	if(isset($plunder['metas'])){
		//get meta tags
		$metas = get_meta_tags($url);
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
			echo "\t\t['keywords'] => '".$metas['keywords']."',";
			echo "\r\n";
		}
		echo "\r\n";
		echo "\t\t),";
	
}




$myPlunder = array(

	'titles' => true,
	'metas' => true,
	
	
);


anchorsAway('http://kinneynursery.com', $myPlunder, 2);


?>




