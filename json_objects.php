<?php
/**
 * Created by PhpStorm.
 * User: chrisvv1
 * Date: 15/07/16
 * Time: 12:02 PM
 */

//------------------------------------------------Generic post function-------------------------------------------------



function create_rc($name, $replicas, $image, $slug){

    $json = '{
              "kind": "ReplicationController",
              "apiVersion": "v1",
              "metadata": {
                "name": "",
                "labels": {
                  "name": ""
                }
              },
              "spec": {
                "replicas": "",
                "selector": {
                    "name": ""
                },
                "template": {
                  "metadata": {
                    "labels": {
                      "name": ""
                    }
                  },
                  "spec": {
                    "volumes": null,
                    "containers": [
                      {
                        "name": "",
                        "image": "",
                        "ports": [
                          {
                            "containerPort": 80,
                            "protocol": "TCP"
                          }
                        ],
                        "imagePullPolicy": "IfNotPresent"
                      }
                    ],
                    "restartPolicy": "Always",
                    "dnsPolicy": "ClusterFirst"
                  }
                }
              }
    }';

    $json_obj = json_decode($json);

    $json_obj->metadata->name = $name;
    $json_obj->metadata->labels->name = $name;
    $json_obj->spec->replicas = $replicas;
    $json_obj->spec->selector->name = $name;
    $json_obj->spec->template->metadata->name = $name;
    $json_obj->spec->template->metadata->labels->name = $name;
    $json_obj->spec->template->spec->containers[0]->name = $name;
    $json_obj->spec->template->spec->containers[0]->image = $image;

//    $json_obj->spec->template->spec->containers[0]->ports[0]->containerPort = $port;

    $post = json_encode($json_obj, JSON_PRETTY_PRINT);
    $restType = 'post';


    $returned = call_api($slug, $restType, $post);
//    $returned = json_decode($apiCall);
    return $returned;
}

function create_service($name, $slug){

    $json= '{
              "kind": "Service",
              "apiVersion": "v1",
              "metadata": {
                "name": ""
              },
              "spec": {
                "ports": [{
                  "port": 80,
                  "targetPort": 80
                }],
                "selector": {
                  "name": ""
                },
                "type": "LoadBalancer",
		"loadBalancerIP": "107.167.188.10"
              }
    }';

    $json_obj = json_decode($json);

    $json_obj->metadata->name = $name;
    $json_obj->spec->selector->name = $name;

    $post = json_encode($json_obj, JSON_PRETTY_PRINT);
    $restType = 'post';

    $returned = call_api($slug, $restType, $post);
//    $returned = json_decode($apiCall);
    return $returned;
}

function delete_rc($name, $image, $slug){

    $json= '{
              "kind": "ReplicationController",
              "apiVersion": "v1",
              "metadata": {
                "name": "",
                "labels": {
                  "name": ""
                }
              },
              "spec": {
                "replicas": 0,
                "selector": {
                    "name": ""
                },
                "template": {
                  "metadata": {
                    "labels": {
                      "name": ""
                    }
                  },
                  "spec": {
                    "volumes": null,
                    "containers": [
                      {
                        "name": "",
                        "image": "",
                        "ports": [
                          {
                            "containerPort": 80,
                            "protocol": "TCP"
                          }
                        ],
                        "imagePullPolicy": "IfNotPresent"
                      }
                    ],
                    "restartPolicy": "Always",
                    "dnsPolicy": "ClusterFirst"
                  }
                }
              }
    }';

    $json_obj = json_decode($json);

    $json_obj->metadata->name = $name;
    $json_obj->metadata->labels->name = $name;
    $json_obj->spec->selector->name = $name;
    $json_obj->spec->template->metadata->name = $name;
    $json_obj->spec->template->metadata->labels->name = $name;
    $json_obj->spec->template->spec->containers[0]->name = $name;
    $json_obj->spec->template->spec->containers[0]->image = $image;

    $post = json_encode($json_obj, JSON_PRETTY_PRINT);
    $restType = 'put';

    call_api($slug, $restType, $post);

    $json = '{
              "kind": "",
              "apiVersion": "",
              "metadata": {
                "selfLink": "",
                "resourceVersion": ""
              },
              "status": "",
              "message": "",
              "reason": "",
              "details": {
                "name": "",
                "kind": "",
                "causes": [
                  {
                    "reason": "",
                    "message": "",
                    "field": ""
                  }
                ],
                "retryAfterSeconds": 0
              },
              "code": 0
    }';

    $json_obj = json_decode($json);
    $post = json_encode($json_obj, JSON_PRETTY_PRINT);
    $restType = 'delete';

    call_api($slug, $restType, $post);
}



function delete_service($name, $slug){

    $json= '{
                "kind": "Service",
                "apiVersion": "v1",
                "metadata": {
                    "selfLink": "",
                    "resourceVersion": ""
                },
                "status": "",
                "message": "",
                "reason": "",
                "details": {
                    "name": "",
                "kind": "",
                "causes": [
                    {
                        "reason": "",
                        "message": "",
                        "field": ""
                    }
                ],
                "retryAfterSeconds": 0
                },
                "code": 0
    }';

    $json_obj = json_decode($json);

    $json_obj->details->name = $name;

    $post = json_encode($json_obj, JSON_PRETTY_PRINT);
    $restType = 'delete';

    call_api($slug, $restType, $post);

}
//TODO ----------------------------------------Just Use replication controller------------------------------------------
function create_pod($name, $image){

    $json= '{
                "apiVersion": "v1",
                "kind": "Pod",
                "metadata": {
                    "name": "",
                    "labels": {
                        "name": "test"
                    }
                },
                "spec": {
                "containers": [
                  {
                    "name": "",
                    "image": "",
                    "ports": [
                      {
                        "containerPort": 80
                      }
                    ]
                  }
                ]
                }
    }';

    $json_obj = json_decode($json);

    $json_obj->metadata->name = $name;
    $json_obj->metadata->labels->name = $name;
    $json_obj->spec->containers[0]->name = $image;
    $json_obj->spec->containers[0]->image = $image;
    $post = json_encode($json_obj);
    $slug = '/api/v1/namespaces/default/pods';
    $restType = 'post';
    call_api($slug, $restType, $post);

}

function deleteService(){

}



