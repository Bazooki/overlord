<?php

//--------------------------POST and GET calls done here after being passed through call_api()--------------------------

function post_api_resource($url, $hash, $slug = NULL, $post){

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url.$slug,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$hash,
        ),
    ));

    $return = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    }

    return $return;
}

function get_api_resource($url, $hash, $slug = NULL){

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url.$slug,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$hash,
        ),
    ));

    $return = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    }

    return $return;
}

function put_api_resource($url, $hash, $slug = NULL, $post){

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url.$slug,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$hash,
        ),
    ));

    $return = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    }

    return $return;
}

function delete_api_resource($url, $hash, $slug = NULL, $post){

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url.$slug,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_HTTPHEADER => array(
            "Authorization: ".$hash,
            "Content-Type: application/json"
        ),
    ));

    $return = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        echo $return;
    }

    return $return;
}

//---------------------------------------logs in to the API present on the master---------------------------------------

function call_api($slug = NULL, $restType, $post = NULL){

    $details = getAccessDetails();
    $result = false;

    //Encode the login details
    $password = $details['password'];
    $username = $details['username'];
    $url = $details['url'];
    $string = $username.":".$password;
    $hash = "Basic ".base64_encode($string);

    if($restType == 'get'){
        $result = get_api_resource($url, $hash, $slug);
    }elseif ($restType == 'post'){
        $result = post_api_resource($url, $hash, $slug, $post);
    }elseif($restType == 'put'){
        $result = put_api_resource($url, $hash, $slug, $post);
    }elseif($restType == 'delete'){
        $result = delete_api_resource($url, $hash, $slug, $post);
    }

//    $result = json_decode($result);
    return $result;
}


//------------------------------------looks for the access details in ~/.kube/config------------------------------------

function getAccessDetails(){

    $filename = '/var/www/.kube/config';
    $data = file_get_contents($filename);

    if(!empty($data)) {


        foreach(preg_split("/((\r?\n)|(\r\n?))/", $data) as $line){

            if(strstr($line, 'username:')){
                $username = $line;
                $username = str_replace('username:','',$username);
                $username = trim($username);
            }elseif(strstr($line, 'password:')){
                $password = $line;
                $password = str_replace('password:','',$password);
                $password = trim($password);
            }elseif(strstr($line, 'server:')){
                $url = $line;
                $url = str_replace('server:','',$url);
                $url = trim($url);
            }

        }

    }else{
        die('./kube/config not found.');
    }

    $details = array(
        'username' => $username,
        'password' => $password,
        'url' => $url
    );


    return $details;

}

function get_string_between($string, $start, $end){
    $string = " ".$string;
    $ini = strpos($string,$start);
    if ($ini == 0) return "";
    $ini += strlen($start);
    $len = strpos($string,$end,$ini) - $ini;
    return substr($string,$ini,$len);
}

//-----------------------------------------Creates cluster based on form values-----------------------------------------

function create_cluster($nodes, $nodeSizeM, $nodeSize){

    //Open file in kubernetes/cluster/gce/config-default.sh to access values
    $filename = '/var/www/kubernetes/cluster/gce/config-default.sh';
    $input = file_get_contents($filename);
    //Find old values
    $parsedNumNodesM = get_string_between($input, "MASTER_SIZE:-", "}");
    $parsedNumNodes = get_string_between($input, "MINION_SIZE:-", "}");
    $parsedNodes = get_string_between($input, "NUM_MINIONS:-", "}");

    //Replace old values with new
    $oldNodeValueNodeSizeM = "MASTER_SIZE:-".$parsedNumNodesM."}";
    $newNodeValueNodeSizeM = "MASTER_SIZE:-".$nodeSizeM."}";
    $writeValNodesSizeM = str_replace($oldNodeValueNodeSizeM, $newNodeValueNodeSizeM, $input);

    $oldNodeValueNodeSize = "MINION_SIZE:-".$parsedNumNodes."}";
    $newNodeValueNodeSize = "MINION_SIZE:-".$nodeSize."}";
    $writeValNodesSize = str_replace($oldNodeValueNodeSize, $newNodeValueNodeSize, $writeValNodesSizeM);

    $oldNodeValueNodes = "NUM_MINIONS:-".$parsedNodes."}";
    $newNodeValueNodes = "NUM_MINIONS:-".$nodes."}";
    $writeValNodes = str_replace($oldNodeValueNodes, $newNodeValueNodes, $writeValNodesSize);

    //Write values
    file_put_contents($filename, $writeValNodes);


    //Give terminal output for script in kubernetes/run_cluster.sh
    $getCwd = getcwd();
    chdir('/var/www/kubernetes');
    $a = popen('sh run_cluster.sh 2>&1', 'r');
    $file ='';

    if(!empty($a)) {
        while ($b = fgets($a, 2048)) {
            echo "<p style='color:#337ab7; font-size: 12px;'>$b</p><br>\n";
            $file .= "<p style='color:#337ab7; font-size: 12px;'>$b</p><br>\n";
            ob_flush();
            flush();
        }
    }else{
        die('run_cluster.sh not found.');
    }

    //Check for success
    $success = strpos($file, 'Cluster validation succeeded');
    chdir($getCwd);

    return $success;

}

//-----------------------Reads the attached CSV and divides up to the amount of nodes specified.------------------------
function readCsv($nodes){
    $message = fileUpload();

    if($message['uploaded'] == 1) {

        if (($handle = fopen("Uploads/".$message['file'], "r")) !== FALSE) {

            while (($data = fgetcsv($handle, 1000, "\r\n", ",")) !== FALSE) {
                for ($c = 0; $c < 1; $c++) {
                    $values[] = $data[$c] . "\n";
                }
            }

            fclose($handle);
            $count = count($values);
            $result = $count / $nodes;
            $fileCount = $count / $result;

            $chunk = (array_chunk($values, $result, true));
            $names = getSuffixes();

            for ($i = 0; $i < $fileCount; $i++) {

                $c = $i + 1;

                file_put_contents('/var/www/uploads/' . $names[$c] . '.csv', $chunk[$i]);
            }
        }else{
            return 'handle failed.';
        }
    }

    return $message;
}

//-----------------------------------------------Check file upload------------------------------------------------------
function fileUpload()
{
    $message = array();

    if ($_FILES['file']['name'] && $_FILES['file']['type'] == 'text/csv'){

        if (!$_FILES['file']['error']){

            if ($_FILES['file']['size'] > (1024000)){  //10mb
                $message[] = 'File size too large.';
            }else{
                $message['uploaded'] = move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);
                $message['file'] = $_FILES['file']['name'];
                $message[] = print_r($_FILES, true);
            }
        }else{
            $message[] = 'Error:  ' . $_FILES['file']['error'];
        }

    }else{
        $message[] = 'Please upload a csv file with the relevant data';
    }

    return $message;
}

//---------------------------------------------Get Node suffixes--------------------------------------------------------

function getSuffixes(){

    $nodeName = array();

    $json_obj = json_decode(call_api('/api/v1/nodes'));


    $itemcount = count($json_obj->items);

    for($i =0; $i < $itemcount; $i++){

        $nodeName[] .= $json_obj->items[$i]->metadata->name;

    }

    return $nodeName;

}

//------------------------------------------------Get Nodes-------------------------------------------------------------
function getNodeInfo(){

    $nodeName = array();
    $json_obj = json_decode(call_api('/api/v1/nodes/', 'get'));
    $itemcount = count($json_obj->items);

    for($i =0; $i < $itemcount; $i++){

        $nodeName[] .= $json_obj->items[$i]->metadata->name;
    }

    return $nodeName;
}
//-------------------------------------------------Get Pods-------------------------------------------------------------
function getPodInfo(){

    $nodeName = array();
    $json_obj = json_decode(call_api('/api/v1/pods/', 'get'));
    $itemcount = count($json_obj->items);

    for($i =0; $i < $itemcount; $i++){
        $nodeName[] .= $json_obj->items[$i]->metadata->name;
    }

    return $nodeName;

}
//---------------------------------------Get Replication controllers----------------------------------------------------
function getRcInfo(){

    $nodeName = array();
    $json_obj = json_decode(call_api('/api/v1/replicationcontrollers/', 'get'));
    $itemcount = count($json_obj->items);

    for($i =0; $i < $itemcount; $i++){
        $nodeName[] .= $json_obj->items[$i]->metadata->name;
    }

    return $nodeName;
}
//------------------------------------------------Get Services----------------------------------------------------------
function getServiceInfo(){

    $json_obj = json_decode(call_api('/api/v1/services/', 'get'));
    $itemcount = count($json_obj->items);

    for($i = 0; $i < $itemcount; $i++){

        if (!empty($json_obj->items[$i]->status->loadBalancer->ingress[0]->ip)){
            $external_ip_item[$json_obj->items[$i]->metadata->name] = $json_obj->items[$i]->status->loadBalancer->ingress[0]->ip;
        }else {
            $no_ip_item[] = $json_obj->items[$i]->metadata->name;
        }

        $all_services[] = $json_obj->items[$i]->metadata->name;
    }

    $data['no_ip_services'] = $no_ip_item;
    $data['external_ip_services'] = $external_ip_item;
    $data['all_services'] = $all_services;

    return $data;
}

function writeDockerFile($content){

    $return = file_put_contents($_SERVER['DOCUMENT_ROOT']."docker/Dockerfile", $content);

    return $return;

}

function create_docker($docker_user, $appname){

    if(strpos($appname,'.git')){

        $appname = str_replace('.git', '', $appname);

    }
    strtolower($appname);
    strtolower($docker_user);

    $getCwd = getcwd();
    chdir($_SERVER['DOCUMENT_ROOT']."docker/");

    chdir($getCwd);

}
function compile_tree($node_count, $pod_count, $image_name, $pod_name =null, $node_size =null, $pr_name =null){

    $json = array();
    $json_nodes = array();
    $json_pods = array();
    $json_containers = array();


    //TODO ----- MASTER --------
    $json['id'] = 'Master';
    $json['name'] = 'Master Node';
    $json['data'] = (object)array();
    //$json['children'] = array();

    //TODO ----- MASTER --------

    //TODO ----- NODES --------
        foreach($node_count as $key => $value){
	    if(strstr($node_count[$key], 'minion') != false){
                $json_nodes['id'] = $json['id'].'_node_id_'.$key;
                $json_nodes['name'] = $node_count[$key];
                $json_nodes['data'] = (object)array();
                $json_nodes['children'] = array();
	    }else{
		continue;
            }

        //TODO ----- PODS --------
            foreach($pod_count as $key1 => $value1){

	    if(strstr($pod_count[$key1], $pod_name)) {
                $json_pods['id'] = $json_nodes['id'] . 'pod ' . $key1;
                $json_pods['name'] = $pod_count[$key1];
                $json_pods['data'] = (object)array();
                $json_pods['children'] = array();
            }else{
               
                continue;
            }
            //TODO ----- Containers --------
                if(strstr($pod_count[$key1], $_SESSION['podName']) != false){

                    for($i = 1; $i <= 10; $i++){

                        $json_containers['id'] = $json_pods['id'].'container '.$i;
                        $json_containers['name'] = $image_name.'container #'.$i;
                        $json_containers['data'] = (object)array();
                        $json_containers['children'] = array();

                        $json_pods['children'][] = $json_containers;
                }

                }else{
                    $json_containers['id'] = $json_pods['id'].'container'.$key;
                    $json_containers['name'] = 'No Containers';
                    $json_containers['data'] = (object)array();
                    $json_containers['children'] = array();

                    $json_pods['children'][] = $json_containers;
                }

            //TODO ----- Containers --------
                $json_nodes['children'][] = $json_pods;
        }

        //TODO ----- PODS --------
        $json['children'][] = $json_nodes;

        }
    //TODO ----- NODES --------

    $json = json_encode($json);
    return $json;
}

?>
