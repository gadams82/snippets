<?php 
	//Settings
	$GLOBALS['db-backup']['site_address'] = "https://somesite";
	$GLOBALS['db-backup']['email'] = "your-email@gmail.com";
	$GLOBALS['db-backup']['backupDB'] = "name of database to back up to";
	$GLOBALS['db-backup']['backupfile'] = "";
	
	//Database Creds
	$GLOBALS['db-creds']['host'] = "localhost";
	$GLOBALS['db-creds']['dbname'] = "name of database to backup";
	$GLOBALS['db-creds']['user'] = "user";
	$GLOBALS['db-creds']['password'] = "pass";
	
	//Error 
	$GLOBALS['errors'] = array();

	//place to save
	$save_dir			= '../../backups/db/';
	$monthly_dir		= '../../backups/db/monthly/';
	
	backupDatabase($GLOBALS['db-creds'], $tables=false, $backup_name=false, $save_dir,$monthly_dir );
	importDB($GLOBALS['db-creds'],$GLOBALS['db-backup']['backupDB'],$GLOBALS['db-backup']['backupfile']);
	backupCleanUp($save_dir,'daily');
	backupCleanUp($monthly_dir,'monthly');
	validateDB($GLOBALS['db-backup']['site_address'],$GLOBALS['db-backup']['email'],$GLOBALS['db-backup']['backupfile']);

	function backupDatabase($creds, $tables=false, $backup_name=false , $save_dir, $monthly_dir)
    {

		$host = $creds['host'];
		$name = $creds['dbname'];
		$user = $creds['user'];
		$pass = $creds['password'];
			
        $mysqli = new mysqli($host,$user,$pass,$name); 
        $mysqli->select_db($name); 
        $mysqli->query("SET NAMES 'utf8'");
		
		if($mysqli->connect_error){
			$GLOBALS['errors'][] = 'ERROR: function backupDatabase() failed to connect to database';
		}
		else{
			$GLOBALS['errors'][] = 'SUCCESS: function backupDatabase() connected to database';
		}
		
        $queryTables    = $mysqli->query('SHOW TABLES'); 
        while($row = $queryTables->fetch_row()) 
        { 
            $target_tables[] = $row[0]; 
        }   
        if($tables !== false) 
        { 
            $target_tables = array_intersect( $target_tables, $tables); 
        }
        foreach($target_tables as $table)
        {
            $result         =   $mysqli->query('SELECT * FROM '.$table);  
            $fields_amount  =   $result->field_count;  
            $rows_num=$mysqli->affected_rows;     
            $res            =   $mysqli->query('SHOW CREATE TABLE '.$table); 
            $TableMLine     =   $res->fetch_row();
            $content        = (!isset($content) ?  '' : $content) . "\n\n".$TableMLine[1].";\n\n";

            for ($i = 0, $st_counter = 0; $i < $fields_amount;   $i++, $st_counter=0) 
            {
                while($row = $result->fetch_row())  
                { //when started (and every after 100 command cycle):
                    if ($st_counter%100 == 0 || $st_counter == 0 )  
                    {
                            $content .= "\nINSERT INTO ".$table." VALUES";
                    }
                    $content .= "\n(";
                    for($j=0; $j<$fields_amount; $j++)  
                    { 
                        $row[$j] = str_replace("\n","\\n", addslashes($row[$j]) ); 
                        if (isset($row[$j]))
                        {
                            $content .= '"'.$row[$j].'"' ; 
                        }
                        else 
                        {   
                            $content .= '""';
                        }     
                        if ($j<($fields_amount-1))
                        {
                                $content.= ',';
                        }      
                    }
                    $content .=")";
                    //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                    if ( (($st_counter+1)%100==0 && $st_counter!=0) || $st_counter+1==$rows_num) 
                    {   
                        $content .= ";";
                    } 
                    else 
                    {
                        $content .= ",";
                    } 
                    $st_counter=$st_counter+1;
                }
            } $content .="\n\n\n";
        }
		

        $backup_name = $backup_name ? $backup_name : $name."___".date('d-m-Y')."-T".time().".sql";
        $backup_name = $backup_name ? $backup_name : $name.".sql";

		if($save_dir){
			$backup_name = $save_dir . $backup_name;
			}
		else{
			$GLOBALS['errors'][] = "ERROR: function backupDatabase(), backup directory unset or does not exist";
		}
		
		$backup = fopen($backup_name, "w");
		if($backup == false){
			$GLOBALS['errors'][] = "ERROR: function backupDatabase(), failed to open backup file: ".$backup_name;
		}
		else{
			$GLOBALS['errors'][] = 'SUCCESS: function backupDatabase() opened backup file: '.$backup_name;
		}
		

		$write = fwrite($backup, $content);
		if($write == false){
			$GLOBALS['errors'][] = "ERROR: function backupDatabase(), failed to write daily backup file";
		}
		else{
			$GLOBALS['errors'][] = 'SUCCESS: function backupDatabase() wrote daily backup file: '.$backup_name;
		}
		
		$close = fclose($backup);
		if($close == false){
			$GLOBALS['errors'][] = "WARNING: function backupDatabase(), failed to close daily backup file";
		}
		
		
		$day_of_month = date('j');
		
		if($day_of_month == 1){
			
			if($monthly_dir){
				
			$month_backup_name = $monthly_dir .$name."___".date('d-m-Y')."-T".time().".sql";
		
			$month_backup = fopen($month_backup_name, "w") or die("Unable to open file!");
			
			$monthly_BU = fwrite($month_backup, $content);
			
			
			if($monthly_BU == false){
				$GLOBALS['errors'][] = "ERROR: function backupDatabase(), failed to write monthly backup file";
			}
			else{
				$GLOBALS['errors'][] = 'SUCCESS: function backupDatabase() wrote monthly backup file: '.$backup_name;
			}
			
		
			fclose($month_backup);
		
			}			
		}	
		
	$GLOBALS['db-backup']['backupfile'] = $backup_name;

    }
	
	function importDB($creds, $backup_db, $sql_file_OR_content){
		
		$host = $creds['host'];
		$dbname  = $backup_db;
		$user = $creds['user'];
		$pass = $creds['password'];
		
		set_time_limit(3000);
		
		$SQL_CONTENT = (strlen($sql_file_OR_content) > 300 ?  $sql_file_OR_content : file_get_contents($sql_file_OR_content,true)  );  
		
		$allLines = explode("\n",$SQL_CONTENT); 
		
		$mysqli = new mysqli($host, $user, $pass, $dbname); if (mysqli_connect_errno()){$import_results = "ERROR: importDB() function failed to connect to MySQL: " . mysqli_connect_error();} 
		
			$zzzzzz = $mysqli->query('SET foreign_key_checks = 0');	        preg_match_all("/\nCREATE TABLE(.*?)\`(.*?)\`/si", "\n". $SQL_CONTENT, $target_tables); foreach ($target_tables[2] as $table){$mysqli->query('DROP TABLE IF EXISTS '.$table);}         $zzzzzz = $mysqli->query('SET foreign_key_checks = 1');    $mysqli->query("SET NAMES 'utf8'");	
		$templine = '';	// Temporary variable, used to store current query
		foreach ($allLines as $line)	{											// Loop through each line
			if (substr($line, 0, 2) != '--' && $line != '') {$templine .= $line; 	// (if it is not a comment..) Add this line to the current segment
				if (substr(trim($line), -1, 1) == ';') {		// If it has a semicolon at the end, it's the end of the query
					if(!$mysqli->query($templine)){ $GLOBALS['errors'][] = 'ERROR: imortDB() performing query \'<strong>' . $templine . '\': ' . $mysqli->error . '<br /><br />';  }  $templine = ''; // set variable to empty, to start picking up the lines after ";"
				}
			}
		}	
		if(!isset($import_results)){
			$import_results = 'SUCCESS: importDB() function imported db file : "' .$sql_file_OR_content.'". To backup database : "'.$backup_db.'".';
		}

		$GLOBALS['errors'][] = $import_results;
	}   
	
	function validateDB($site_address,$monitoring_email,$backup_file){

		rename("../wp-config.php", "../wp-config.php.tmp");
		rename("../wp-config.php.bu", "../wp-config.php");
		
		$load = file_get_contents($site_address);
		
		//echo $load;
		
		$headers = get_headers($site_address);
		$last_error = error_get_last();
		
		$log = file_get_contents("../wp-content/debug.log");
		unlink("../wp-content/debug.log");
		
		$message ='
			<h1>Database backup & validation complete for : '. $site_address . '</h1>
			<h1>Results:</h1>
			<h2>Last PHP Error returned : '.$last_error["type"].' '. $last_error["message"].' '.$last_error["file"].' '.$last_error['line'].'</h2>
			<h2>Headers of "'.$site_address.'" returned "'.$headers[0].'"
			<h2>WordPress Debug Log returned:</h2>
			'.$log.'<br>';
			
			foreach($GLOBALS['errors'] as $error){
				$message = $message . '<h2>
				'.$error.'
				</h2>';
			}
			
		backupMailer($message,$GLOBALS['db-backup']);
		
		rename("../wp-config.php", "../wp-config.php.bu");
		rename("../wp-config.php.tmp", "../wp-config.php");
		
		
	}


	function backupCleanUp($dir,$interval){
		//print_r( scandir($dir));
		$files = array_diff( scandir($dir), array(".", "..","monthly"));
		$time_stamps = array();
		$counter = 0;
		$cleanup_results = array();
		$cleanup_results[] = "SUCCESS: backupCleanUp('".$interval."') initiated.";
		foreach ($files as $file){
			$time_stamp = get_string_between($file,'T','.');
			$time_stamps[] = $time_stamp;
		}
		$last = max($time_stamps);
		foreach ($files as $file){
			$time_stamp = get_string_between($file,'T','.');

			if($interval == 'daily'){
				$offset = 604800;
			}
			elseif($interval == 'monthly'){
				$offset = 7776000;
			}
			else{
				$cleanup_results[] = "FATAL ERROR: invalid arguement supplied to backupCleanUp()";
			}
			
			$full_path = $dir.$file;
			
			if($time_stamp <= $last - $offset){
				
				$removed = unlink($full_path);
				if($removed){
					$cleanup_results[] = 'SUCCESS: backupCleanUp() checked timestamp of '.$full_path.'. Backup too old. Backup file has been deleted.';
				}
			}
			else{
				$cleanup_results[] = 'SUCCESS: backupCleanUp() checked timestamp of '.$full_path.'. Backup within time range. Backup file kept.';
			}
		}	
		foreach($cleanup_results as $result){	
			$GLOBALS['errors'][] = $result; 	
		}
	}

	function backupMailer($message,$settings,$type = 'General') {

        $from_org = true;
        $to = $settings['email'];
        $from = 'noreply@'. str_replace('http://','',$settings['site_address']);
        $subject = 'WWW-Backup Report For ' .$settings['site_address'];
        $plain_text = strip_tags($message);
        $html_text = "<html><body>$message</body></html>";

        $mime_boundary = uniqid('NGC2082-');

        $body = "--$mime_boundary\r\n";
        $body .= "Content-Type: text/plain;charset=us-ascii\r\n\r\n";
        $body .= $plain_text;
        $body .= "\r\n\r\n--$mime_boundary\r\n";
        $body .= "Content-Type: text/html;charset=iso-8859-1\r\n\r\n";
        $body .= $html_text;
        $body .= "\r\n\r\n--$mime_boundary--";
		$headers = "";
        $headers .= "Return-Path: $from \r\n";
        if($from_org === true) {
            $headers .= "From: \"Wormhole Web Works\" <$from> \r\n";
            $headers .= "Organization: Wormhole Web Works\r\n";
        } else
            $headers .= "From: $from \r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: multipart/alternative;boundary=$mime_boundary\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "X-Mailer: PHP". phpversion() ."\r\n";
        $headers .= "Message-ID: <".time()."-".md5($to.$from)."@".$_SERVER['SERVER_NAME'].">\r\n";

        mail($to, $subject, $body, $headers);
        echo $html_text;
    } 

	
	
	function get_string_between($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
	}

?>
