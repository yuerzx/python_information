<!DOCTYPE html><meta charset="UTF-8"><header><title>XKB Information Injection</title></header><body><?phpset_time_limit(800);  //As this is a picker, more resource and time are required.if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){ //windows is \ unix is /    $adds = explode("\\", __FILE__, -5);    $adds = implode("\\", $adds).'\\';}else{    $adds = explode("/", __FILE__, -5);    $adds = implode("/", $adds).'/';}include $adds."wp-config.php";require_once(ABSPATH . 'wp-admin/includes/image.php');function existed_check($hash) {    global $wpdb;    $results = $wpdb->get_var( $wpdb->prepare(        "			SELECT post_id 			FROM $wpdb->postmeta 			WHERE meta_key = '_webhash'			AND meta_value = %s		",        $hash    ) );    if($results) {return 0;} else {return 1;}; //if we got the url, ignore it. Or else we continue.}function database_logout($db, $client){	$db -> command(array("logout" => 1));	$client -> close(); }function mark_as_publish($mongo_id, $news_collection){	$news_collection -> update(    	array( "_id" => new MongoId($mongo_id)),     	array('$set' => array("status" => intval("1")))    	);}?><?phpecho "Good start from here!<br>";if(isset($_GET['act']) && $_GET['act']=='kslr'){    //setting area Start    $article_category = 3;    //setting area end    echo 'Article injection starts...<br>';    $client = new MongoClient("mongodb://EZYProperty:8jshf7asd@localhost/EZYProperty");    $db = $client->EZYProperty;	$news_collection = $db->news;    //Prepare the document root for future use.    $wp_upload_dir = wp_upload_dir();    $dir = $wp_upload_dir['path'].'/';    if(!is_dir($dir)) mkdir($dir);    // Find all articles which has been not yet updated yet    $query = array('status'=> 0);	$projection = array('title'=> 1, '_id'=>1);	$cursor = $news_collection->find($query, $projection);	// if no record has been found, we will close the connection and ready for next. 	if($cursor->count() == 0){		echo "Can't really find anything, Hopefully all documents are done :)";		database_logout($db, $client);        exit;	}    foreach ($cursor as $document) {    	$article_title = $document['title'];    	$mongo_id = $document['_id'];        echo 'existed check -- <'.$article_title.'><br>';        $webhash = md5($article_title);        if(existed_check($webhash)):            echo 'Start to inject -- <span style="color:red"><'.$article_title.'></span><br>';            $article_details = $news_collection -> findONe(array('_id' => new MongoId($mongo_id)));            $article_content = $article_details['content'];               $pre_content = array(                'post_title'	=>	$article_title,                'post_status'   =>  'publish',                'post_content'	=>	$article_content,                'post_author'	=>	1,                'post_date'		=>	$article_details['pub_time'],                'post_category'	=>	array($article_category));            $caiji_id = wp_insert_post($pre_content);            if (!$caiji_id) {echo '预入库失败，请联系管理员 Han Sun !'; break 1;}            #start to download images into local server            if (!empty($article_details['imgs']) && isset($article_details['imgs'])){            	$counter = 0;            	foreach ($article_details['imgs'] as $key => $img) {            		if(!@fopen($img,"r")){echo "The image links are unavilable :( <br>";break 1;}            		$file_result = file_put_contents($dir.basename($img), file_get_contents($img));            		if(!$file_result){            			echo 'Display photo are unable to be downloaded. <br>';}        			else{        				$counter ++;         				$wp_filetype = wp_check_filetype(basename($img), null );        				$dizhiarr = explode('/',$img);                		$filename = $dizhiarr[count($dizhiarr)-1];		                $attachment_address = $wp_upload_dir['path'].'/'.$filename;		                echo $attachment_address."<br>";		                $attachment = array(		                    'post_mime_type' => $wp_filetype['type'],		                    'guid' => $wp_upload_dir['url'] . '/' . basename( $img ),		                    'post_title' => $article_title.'_'.$counter,		                    'post_content' => '',		                    'post_status' => 'inherit'                		);                		$attachment_id = wp_insert_attachment( $attachment, $attachment_address, $caiji_id );                		require_once(ABSPATH . 'wp-admin/includes/image.php');                		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $attachment_address );                		wp_update_attachment_metadata( $attachment_id, $attachment_data );        			}        		set_post_thumbnail( $caiji_id, $attachment_id );	            	} // end of foreach loop  	            } // end of imgs section            add_post_meta( $caiji_id,'_webhash', $webhash, true );            add_post_meta( $caiji_id,'_weibo', 0 , true );            echo "Finish on ".$article_title."<br>";				mark_as_publish($mongo_id, $news_collection);            else:  //if the post existed                echo "<span style='color:red;'><".$article_title."></span>已经采集过<br>";                // marke it as existed.                 mark_as_publish($mongo_id, $news_collection);            endif;        } // end of articles loop, the main loop	database_logout($db, $client);}else {    echo "Good Night";	//wp_redirect("http://www.maifang.com.au", 302);	exit; } ?></body><footer></footer>