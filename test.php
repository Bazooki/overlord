<script>
//    var json = <?php //echo json_encode($ar)?>
</script>

<?php
/**
 * Created by PhpStorm.
 * User: chrisvv1
 * Date: 15/08/05
 * Time: 9:37 AM
 */
//
//include ('json_objects.php');
include ('functions.php');



//var json = {
//    "id": "aUniqueIdentifier",
//        "name": "usually a nodes name",
//        "data":
//         {
//            "some key": "some value",
//            "some other key": "some other value"
//         },
//    "children": []
//    };


//$json = array();
//
//$json['id'] = 'master01';
//$json['name'] = 'Chris Master';
//$json['data'] = (object)array();
////$json['children'] = array((object)$json);
////
//$json2 = array();
//$json2['id'] = 'master02';
//$json2['name'] = 'Chris Master2';
//$json2['data'] = (object)array();
//$json2['children'] = array();
////
////
////
//$json['children'] = array( (object)$json2);

$node_count = array('1' => 'one', '2' => 'two', '3' => 'three');
$pod_count = array('1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four');

echo compile_tree($node_count, $pod_count, 'bob');




//$ar = array('apple', 'orange', 'banana', 'strawberry');

//echo create_rc('test', '/api/v1/namespaces/default/replicationcontrollers/test', 'patch');
//print_r(getPodInfo());

//print_r($array['external_ip_services']);
//print_r($data);
//print_r($json->no_ip_services[1]);
//delete_service('test', '/api/v1/namespaces/default/services/test');
//$arrm = '';

//$array = array("id"=>"aUniqueIdentifier", "name"=>"usually a nodes name", "data" => array("some key"=>"some value"));
// echo json_encode($json);
//$arrm = json_encode($arrm);

//var_dump($arrm);
