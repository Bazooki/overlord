<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/styles.css">
    <link type="text/css" href="css/base.css" rel="stylesheet" />
    <link type="text/css" href="css/spacetree.css" rel="stylesheet" />

    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="js/core.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="http://d3js.org/d3.v3.min.js"></script>
    <script src="js/jit.js"></script>
    <script src="js/tree.js"></script>


    <!--    Innitialize bootstrap tool-tip-->
    <script>
    $(function () {
    $('[data-toggle="tooltip"]').tooltip()
    })
    function abc() {
            alert(document.getElementById("dockerCode").placeholder);
    }
    $(document).ready(function(){
        $('#refresh').click( function() {
            $(this).button('loading');
            $('.tooltip').hide();
        });
        $('#delete_service').click( function() {
            $(this).button('loading');
            $('.tooltip').hide();
        });
        $('#delete_rc').click( function() {
            $(this).button('loading');
            $('.tooltip').hide();
        });
        $('#image').click( function() {
            $("#exists_true_from").validate();
            if($("#exists_true_from").valid()){
                $(this).button('loading');
                $('.tooltip').hide();
            }
        });
        $('#no_image').click( function() {
            $("#exists_false_from").validate();
            if($("#exists_false_from").valid()){
                $(this).button('loading');
                $('.tooltip').hide();
            }
        });
    });
    </script>

    <?php
    include_once('index.php');
    include_once('functions.php');
    include_once('json_objects.php');
    ?>

</head>

<body>

    <div class="container">
        <div class="TerminalContainer">
            <div class="left_tab">
                <div class="panel panel-primary" style="height: 350px">
                    <div class="panel-heading">
                        Console:
                        <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="bottom" title="The console displays feedback from the API server, along with docker image feedback."></span>
                    </div>
                    <div class="panel-body">
                        <?php
                        set_time_limit(0);
                        ini_set('memory_limit','10M');

                        //Grab POST data
                        $appId = $_POST["appId"];
                        $appSecret = $_POST["appSecret"];
                        $openids = $_POST["openIds"];
                        $nodes = $_POST["nodes"];
                        $nodeSizeM = $_POST['nodeSizeM'];
                        $nodeSize = $_POST['nodeSize'];
                        $kubeDown = $_POST['kubeDown'];
                        $dockerCode = $_POST['dockerCode'];
                        $podUp = $_POST['podUp'];
                        $numPods = $_POST['numPods'];
                        $podInfo = $_POST['podInfo'];
                        $serviceInfo = $_POST['serviceInfo'];
                        $repInfo = $_POST['repInfo'];
                        $nodeInfo = $_POST['nodeInfo'];
                        $refresh = $_POST['refresh'];
                        $docker_name = $_POST['docker_name'];
                        $app_name = $_POST['app_name'];

                        $deleteRc = $_POST['deleteRc'];
                        $deleteService = $_POST['deleteService'];
                        $showPods = $_POST['showPods'];

                        if(isset($_POST['podName']) && isset($_POST['imageName'])){

                            $_SESSION['podName'] = $_POST['podName'];
                            $_SESSION['imageName'] = $_POST['imageName'];
                        }elseif(isset($_POST["prName"]) && isset($_POST['nodeSize'])){
                            $_SESSION['prName'] = $_POST["prName"];
                            $_SESSION['nodeSize'] = $_POST["nodeSize"];
                        }

                        //Get access details
                        $details = getAccessDetails();

                        //If access details empty, no cluster
                        if (empty($details['username']) && empty($details['password']) && empty($details['url'])){
                            $_SESSION['clusterUp'] = 'false';
                        }else{
                            $_SESSION['clusterUp'] = 'exists';
                            //Set access details
                            $username = $details['username'];
                            $password = $details['password'];
                            $url = $details['url'];

                        }
                        //Create cluster
                        if(isset($_POST['submit'])) {

                            //if no cluster exists, create one
                            if ($_SESSION['clusterUp'] == 'false'){

                                $success = create_cluster($nodes, $nodeSizeM, $nodeSize);

                                //if a string is not found, there was an error
                                if ($success == false) {
                                    $_SESSION['clusterUp'] = 'error';
                                } elseif ($success == true) {
                                    $_SESSION['clusterUp'] = 'true';
                                    //Get access details
                                    $readCsv = readCsv($nodes);
                                    $details = getAccessDetails();
                                    $username = $details['username'];
                                    $password = $details['password'];
                                    $url = $details['url'];
                                }
                            }
                        }
                        //Tear it down
                        if(isset($_POST['kubeDown'])) {

                            $old_path = getcwd();
                            chdir('/var/www/kubernetes');
                            $a = popen('sh down_cluster.sh', 'r');

                            while ($b = fgets($a, 2048)) {
                                echo "<p style='color:#337ab7; font-size: 12px;'>.$b.</p>" . "<br>\n";
                                ob_flush();
                                flush();
                            }
                            pclose($a);
                            $_SESSION['clusterUp'] = 'false';
                            chdir($old_path);
                        }

                        if($_SESSION['clusterUp'] == 'true' OR $_SESSION['clusterUp'] == 'exists') {

                            $repcont_count = getRcInfo();
                            $service_count = getServiceInfo();
                            $node_count = getNodeInfo();

                            if(isset($deleteRc)){

                                foreach($deleteRc as $key=>$value){
                                    echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                    delete_rc($value, $_SESSION['imageName'], '/api/v1/namespaces/default/replicationcontrollers/'.$value);
                                    echo "</pre>";
                                }
                                $repcont_count = getRcInfo();
                            }

                            if(isset($deleteService)){

                                foreach($deleteService as $key=>$value){
                                    echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                    delete_service($value, '/api/v1/namespaces/default/services/'.$value);
                                    echo "</pre>";
                                }
                                $service_count = getServiceInfo();
                            }

                            if (isset($podUp)) {

                                (int)$totalPods = $_SESSION['numPods'] += $_POST['numPods'];
//                                if(!empty($dockerCode)){
//                                    writeDockerFile($dockerCode);
//                                   create_docker($docker_name, $app_name);
//                                    echo 'here';
//                                }
//                                echo 'else here';

                                //TODO The json ouput doesnt format correctly, cant figure out why
                                echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                print_r(json_decode(create_rc($_SESSION['podName'], (int)$numPods, $_SESSION['imageName'], '/api/v1/namespaces/default/replicationcontrollers')));
                                echo "</pre>";
                                $repcont_count = getRcInfo();

                                echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                print_r(json_decode(create_service($_SESSION['podName'], '/api/v1/namespaces/default/services')));
                                echo "</pre>";
                                $service_count = getServiceInfo();
                                //TODO The json ouput doesnt format correctly, cant figure out why

                            }

                            if(isset($podInfo)){
                                echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                print_r(json_decode(call_api('/api/v1/pods/', 'get')));
                                echo "</pre>";
                            }
                            if(isset($serviceInfo)){
                                echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                print_r(json_decode(call_api('/api/v1/services/', 'get')));
                                echo "</pre>";
                            }
                            if(isset($repInfo)){
                                echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                print_r(json_decode(call_api('/api/v1/replicationcontrollers/', 'get')));
                                echo "</pre>";
                            }
                            if(isset($nodeInfo)){
                                echo "<pre style='background-color: #f5f5f5; border: 0px; color:#337ab7; font-size: 10px;'>";
                                print_r(json_decode(call_api('/api/v1/nodes/', 'get')));
                                echo "</pre>";
                            }

                            $pod_count = getPodInfo();
                            $json = compile_tree($node_count, $pod_count, $_SESSION['imageName'], $_SESSION['podName'], $_SESSION['nodeSize'], $_SESSION['prName']);
                        }

                        chdir($old_path);

                        ?>

                        <script>
                            var json = $.parseJSON('<?php echo $json; ?>');
                        </script>
                    </div>
                </div>


                <?php if($_SESSION['clusterUp'] == 'false'){ ?>
                <div class="well" style="padding-top: 20px; height: 350px;">
                    <form action="index.php" method="post" enctype="multipart/form-data" style="padding-top: 30px;">
                        <div class="row">
                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="left" title="Optional">Insert your AppId:</span>
                            </div>
                            <div class="col-sm-3">
                                <input type="text" name="appId">
                            </div>
                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="left" title="Optional">Insert your AppSecret:</span>
                            </div>
                            <div class="col-sm-3">
                                <input type="text" name="appSecret">
                            </div>
                            <br><br>
                        </div>

                        <div class="row">
                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="left" title="This will be used to describe your pods">Enter the name of your project</span>
                            </div>
                            <div class="col-sm-3">
                                <input type="text" name="prName" required>
                            </div>
                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="left" title="Optional">Insert the OpenId's you want to send a push message to:</span>
                            </div>
                            <div class="col-sm-3">
                                <input type="text" name="openIds">
                            </div>
                            <br><br>
                        </div>


                        <div class="row" >
                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="top" title="A node is a physical or virtual machine running Kubernetes, onto which pods can be scheduled.">Select the number of minion nodes you want to create:</span>
                            </div>
                            <div class="col-sm-3">
                                <select name="nodes">
                                    <?php
                                    for ($i=1; $i<=10; $i++){
                                        ?>
                                        <option><?php echo $i; ?></option>
                                    <?php }?>
                                </select>
                            </div>
                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="left" title="The master is the host or hosts that contain the master components, including the API server, controller manager server, and etcd. The master manages nodes in its Kubernetes cluster and schedules pods to run on nodes.">
                                   <a href="https://cloud.google.com/compute/docs/machine-types" target="_blank">Select the hardware specifications of your master node:</a>
                                </span>
                            </div>
                            <div class="col-sm-3">
                                <select name="nodeSizeM">
                                    <?php
                                    $specs = array('n1-standard-1', 'n1-standard-2', 'n1-standard-4', 'n1-standard-8',
                                        'n1-standard-16', 'n1-highmem-2', 'n1-highmem-4', 'n1-highmem-8', 'n1-highmem-16',
                                        'n1-highcpu-2', 'n1-highcpu-4', 'n1-highcpu-8', 'n1-highcpu-16'
                                    );

                                    foreach ($specs as $count){
                                        ?>
                                        <option><?php echo $count; ?></option>
                                    <?php }?>
                                </select>
                                <br><br>
                            </div>
                            <br><br>
                        </div>

                        <div class="row">
                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="left" title="Optional">Or upload a csv file</span>
                            </div>
                            <div class="col-sm-3">
                                <input type="file" name="file" size="50" maxlength="25">
                            </div>

                            <div class="col-sm-3">
                                <span data-toggle="tooltip" data-placement="left" title="A node is a physical machine that will be running your app and it's associated pods and containers.">
                                    <a href="https://cloud.google.com/compute/docs/machine-types" target="_blank">Select the hardware specifications of your minion nodes:</a>
                                </span>
                            </div>
                            <div class="col-sm-3">
                                <select name="nodeSize">
                                    <?php
                                    $specs = array('n1-standard-1', 'n1-standard-2', 'n1-standard-4', 'n1-standard-8',
                                        'n1-standard-16', 'n1-highmem-2', 'n1-highmem-4', 'n1-highmem-8', 'n1-highmem-16',
                                        'n1-highcpu-2', 'n1-highcpu-4', 'n1-highcpu-8', 'n1-highcpu-16'
                                    );

                                    foreach ($specs as $count){
                                        ?>
                                        <option><?php echo $count; ?></option>
                                    <?php }?>
                                </select>
                            </div>
                            <br><br>
                        </div>
                        <input type="submit" class="btn btn-success" name="submit"value="Bring Cluster Up" style="width: 150px; float: left" />
                    </form>
                </div>
                <?php }elseif($_SESSION['clusterUp'] == 'exists' OR $_SESSION['clusterUp'] == 'true'){ ?>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                        Controll interface
                        <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="View Json feedback from the API, delete the cluster or view a graphical representation of your project here"></span>

                        </div>
                        <div class="panel-body">
                            <div class="buttonDiv">
                                <form action="index.php" method="post" enctype="multipart/form-data" id="info buttons form">
                                    <div class="btn" style="float: left; padding-left: 0px; ">
                                        <button class="btn btn-default" type="submit" name="podInfo">Pod Info</button>
                                    </div>
                                    <div class="btn" style="float: left; padding-left: 0px;">
                                        <button class="btn btn-default" type="submit" name="repInfo">Replication controller Info</button>
                                    </div>
                                    <div class="btn" style="float: left; padding-left: 0px;">
                                        <button class="btn btn-default" type="submit" name="serviceInfo">Service Info</button>
                                    </div>
                                    <div class="btn" style="float: left; padding-left: 0px;">
                                        <button class="btn btn-default" type="submit" name="nodeInfo">Node Info</button>
                                    </div>
                                    <div class="btn" style="float: right; padding-right: 0px;">
                                        <button type="button" class="btn btn-success"  data-toggle="modal" data-target="#treeModal" onclick="setTimeout('init(json)', 1000)">
                                            <i class="glyphicon glyphicon-eye-open"></i>
                                        </button>
                                    </div>
                                    <div class="btn" style="float: right; padding-right: 0px;">
                                        <input type="submit" class="btn btn-danger" name="kubeDown"value="Bring Cluster Down"  data-toggle="tooltip" data-placement="left" title="This destroys the entire cluster along with all nodes, pods, replication controllers and services."/>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="panel panel-primary" style="height: 210px">
                        <div class="panel-heading">
                            Docker Creation Panel
                            <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="Use an existing docker image or create one from this panel."></span>
                            </span>
                        </div>
                        <div class="panel-body">
                            <div class="well_container">
                                <div class="col-sm-6" style="padding-left: 0; padding-top: 10px">
                                    <div class="well well_container" style="display: inline-block">
                                        <h5>I would like to compose an image using a Dockerfile:</h5>
                                        <div class="btn_right" style="padding-bottom: 26px; padding-right: 22px">
                                            <button type="button" class="btn btn-primary"  data-toggle="modal" data-target="#newImage" style="float: right">
                                                Create
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6" style="padding-left: 0; padding-right: 0px; padding-top: 10px">
                                    <div class="well well_container" style="display: inline-block">
                                        <h5>I would like to use an existing/public image I uploaded to Docker Hub:</h5>
                                        <div class="btn_right" style="padding-bottom: 26px; padding-right: 8px">
                                            <button type="button" class="btn btn-primary"  data-toggle="modal" data-target="#ownImage" style="float: right">
                                                Create
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="modal fade" id="newImage" tabindex="-1" role="dialog" aria-labelledby="newImageLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="newImageLabel">Create a custom Docker container environment</h4>
                                </div>
                                <div class="modal-body">
                                        <div class="well">
                                            <form action="index.php" method="post" enctype="multipart/form-data" id="exists_false_from">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <span data-toggle="tooltip" data-placement="left" title="Required">Your Docker Username</span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="docker_name" style="width: 100%" required>
                                                    </div>
                                                    <br>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <span data-toggle="tooltip" data-placement="left" title="Required">Your App name: (Without the .git)</span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="app_name" style="width: 100%" required>
                                                    </div>
                                                    <br>
                                                </div>
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <span data-toggle="tooltip" data-placement="left" title="Required">Name to be assigned to the pods:</span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="podName" style="width: 100%" required>
                                                    </div>
                                                    <br>
                                                </div>
                                                <div class="row" >
                                                    <div class="col-sm-6">
                                                        <span data-toggle="tooltip" data-placement="left" title="Dependant on the hardware you have selected for the nodes in your cluster. If some of your pods aren't running, it may be because of memory constraints.">Enter the number of pods you want to create:</span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="number" name="numPods" style="width: 100%" required>
                                                    </div>
                                                    <br><br>
                                                </div>
                                                <div class="row" >
                                                    <div class="col-sm-6">
                                            <span data-toggle="tooltip" data-placement="left" title="A docker file allows you to build your own images using docker commands. Make sure to use the correct syntax and practices as outlined.">
                                                <a href="http://docs.docker.com/articles/dockerfile_best-practices/" target="_blank">Enter your custom Dockerfile text here:</a>
                                            </span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <textarea class="dockerFile" name="dockerCode" id="dockerCode" style="font-size: 12px; width: 100%;" placeholder="FROM tutum/lamp:latest RUN rm -fr /app && git clone https://github.com/username/customapp.git/ app EXPOSE 80 3306 CMD ['/run.sh']" required></textarea>
                                                    </div>
                                                </div>
                                        </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button onclick="abc()" type="button" class="btn btn-info">Example Code</button>
                                    <input type="submit" class="btn btn-success" name="podUp" id="no_image" value="Create Containers" data-toggle="tooltip" data-placement="bottom" title="This will create a pod on every node with your docker image running in its own environment, along with a service that will expose a public ip."/>
                                </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="ownImage" tabindex="-1" role="dialog" aria-labelledby="ownImageLabel">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="ownImageLabel">Your container strucure</h4>
                                </div>
                                <div class="modal-body">

                                        <div class="well">
                                            <form action="index.php" method="post" enctype="multipart/form-data" id="exists_true_from">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                    <span data-toggle="tooltip" data-placement="left" title="Type in your unique docker image name here. The docker image will be searched for and downloaded automatically. A docker image is a virtual container that runs your app on a platform of your choice.">
                                                        <a href="https://training.docker.com/">Docker image name:</a>
                                                    </span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="imageName" required>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <span data-toggle="tooltip" data-placement="left" title="Required">Name to be assigned to the pods:</span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text" name="podName" required>
                                                    </div>
                                                    <br><br>
                                                </div>

                                                <div class="row" >
                                                    <div class="col-sm-6">
                                                        <span data-toggle="tooltip" data-placement="left" title="Dependant on the hardware you have selected for the nodes in your cluster. If some of your pods aren't running, it may be because of memory constraints.">Enter the number of pods you want to create:</span>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="number" name="numPods" required>
                                                    </div>
                                                    <br><br>
                                                </div>
                                                <br>
                                        </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <input type="submit" class="btn btn-success" name="podUp" id="image" value="Create Containers" data-toggle="tooltip" data-placement="bottom" title="This will create a pod on every node with your docker image running in its own environment, along with a service that will expose a public ip."/>
                                    </form>
                                    <script>

                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>


                 <?php  }?>
                </div>
            </div>

            <div class="right_tab">
                <div class="refreshable">
                    <div class="panel panel-primary" style="min-height: 165px ">
                        <div class="panel-heading">Important information
                                <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="This panel contains all the necessary information required to access your app. If you don't see external IPs for your app after having created your container/s, they may still be in the process of being created, or something went wrong with the creation of your service."></span>
                                <form action="index.php" method="post" enctype="multipart/form-data" id="refresh_form" style="float:right">
                                    <button type="submit" style="padding: 0 12px;" class="btn btn-primary" name="refresh" id="refresh"  data-toggle="tooltip" data-placement="right" title="Checks for external ip's that may have become available">
                                        <i class="glyphicon glyphicon-refresh"></i>
                                    </button>
                                </form>
                        </div>
                        <div class="panel-body">
                            <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true" style="padding-top: 20px;">
                                <div class="panel panel-default">
                                    <div class="panel-heading" role="tab" id="headingTwo">
                                        <h4 class="panel-title">
                                                <span class="input-group-addon">
                                                    <div class="row-fluid">
                                                        <div class="span12">
                                                            <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#CollapseThree" aria-expanded="false" aria-controls="CollapseThree">
                                                                Click to expand
                                                            </a>
                                                        </div>
                                                    </div>
                                                </span>
                                        </h4>
                                    </div>
                                    <div id="CollapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                                        <div class="panel-body" style="max-height: ">
                                            <?php
                                            //Final output
                                            if($_SESSION['clusterUp'] == 'true' OR $_SESSION['clusterUp'] == 'exists') {
                                                echo "<b style='font-size: 20px; text-align: center;'>Master Access details:</b>" . "<br><br>";
                                                echo "<p style='font-size: 14px;'><b>Master Username:</b> ".$username."</p>";
                                                echo "<p style='font-size: 14px;'><b>Master Password:</b> ".$password."</p>";
                                                echo "<p style='font-size: 14px;'><b>Master URL:</b> ".$url."</p>";
						echo "<p style='font-size: 14px;'><b>Kubedns:</b> ".$url."/api/v1/proxy/namespaces/kube-system/services/kube-dns"."</p>";
						echo "<p style='font-size: 14px;'><b>KubeUI:</b> ".$url."/api/v1/proxy/namespaces/kube-system/services/kube-ui"."</p>";
						echo "<p style='font-size: 14px;'><b>Grafana:</b> ".$url."/api/v1/proxy/namespaces/kube-system/services/monitoring-grafana"."</p>";
						echo "<p style='font-size: 14px;'><b>Heapster:</b> ".$url."/api/v1/proxy/namespaces/kube-system/services/monitoring-heapster"."</p>";
						echo "<p style='font-size: 14px;'><b>Influx DB:</b> ".$url."/api/v1/proxy/namespaces/kube-system/services/monitoring-influxdb"."</p>";
						
                                                if(!empty($service_count['external_ip_services'])){
                                                    echo "<br><br>"."<b style='font-size: 20px; text-align: center;'>Container Access details:</b>" . "<br><br>";
                                                    foreach($service_count['external_ip_services'] as $key=>$value){
                                                        echo "<p style='font-size: 14px;'><b>".$key."</b> ".$value."</p>";
                                                    }
                                                }

                                            }elseif($_SESSION['clusterUp'] == 'false'){
                                                echo "<b font-size: 26px; text-align: center'>No cluster detected.</b>" . "<br><br>";
                                                echo "<p font-size: 14px;'>Your cluster doesn't seem to have been created. Please click on submit after your values have been defined.</p>";
                                            }elseif($_SESSION['clusterUp'] == 'error'){
                                                echo "<b font-size: 26px; text-align: center'>Cluster creation failure</b>" . "<br><br>";
                                                echo "<p font-size: 14px;'> Please check your /cluster/gce/config-default.sh file for errors</p>";
                                            }
                                            ?>
                                                </br>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel panel-primary"style="min-height: 165px ">
                    <div class="panel-heading">
                        <a href="https://cloud.google.com/container-engine/docs/services/" target="_blank" style="color: #ffffff">Services</a>
                        <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="A service is an abstraction which defines a logical set of pods and a policy by which to access them. It allows you to access your pods from a central point."></span>
                    </div>
                    <div class="panel-body">
                        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true" style="padding-top: 20px;">
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingTwo">
                                    <h4 class="panel-title">
                                            <span class="input-group-addon">
                                                <div class="row-fluid">
                                                    <div class="span12">
                                                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                            Click to expand
                                                        </a>
                                                    </div>
                                                </div>
                                            </span>
                                    </h4>
                                </div>
                                <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                                    <div class="panel-body-1" style="max-height: none">
                                        <?php
                                        if(!empty($service_count)){

                                            $get_lb_name = $service_count;

                                            foreach($service_count['all_services'] as $key =>  $value){ ?>
                                            <form action="index.php" method="post">
                                                <div class="row">
                                                    <div class="input-group">
                                                                <span class="input-group-addon">
                                                                    <input type="checkbox" aria-label="..." value="<?php echo $service_count['all_services'][$key] ?>" name="deleteService[]">
                                                                </span>
                                                        <input type="text" readonly class="form-control" aria-label="..." value="<?php echo $service_count['all_services'][$key] ?>">
                                                    </div>
                                                </div>

                                        <?php } ?>
                                                </br>
                                                <div class="row">
                                                    <input type="submit" class="btn btn-normal" name="formSubmit" id="delete_service" value="Delete" style="width: 100px; float: left" />
                                                </div>
                                            </form>
                                        <?php }else{
                                                echo "No Services found.<br>";
                                        }
                                            ?>


                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <div class="panel panel-primary"style="min-height: 165px ">
                    <div class="panel-heading">
                        <a href="https://cloud.google.com/container-engine/docs/replicationcontrollers/" target="_blank" style="color: #ffffff;">Replication controllers</a>
                        <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="The Replication controller is in charge of keeping your pods up and running. If one of your pods die, the replication controller brings it back up."></span>
                    </div>
                    <div class="panel-body">
                        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true" style="padding-top: 20px;">
                            <div class="panel panel-default">
                                <div class="panel-heading" role="tab" id="headingOne">
                                    <h4 class="panel-title">
                                        <span class="input-group-addon">
                                            <div class="row-fluid">
                                                <div class="span12">
                                                    <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                                        Click to expand
                                                    </a>
                                                </div>
                                            </div>
                                        </span>
                                    </h4>
                                </div>
                                <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                                    <div class="panel-body" style="max-height: 500px">
                                        <?php
                                        if(!empty($repcont_count)){

                                            foreach ($repcont_count as $key1 => $checkbox){ ?>

                                            <form action="index.php" method="post" id="delete_rc_form">
                                                <div class="row">
                                                    <div class="input-group">
                                                              <span class="input-group-addon">
                                                                <input type="checkbox" aria-label="..."
                                                                       value="<?php echo $checkbox ?>" name="deleteRc[]">
                                                              </span>
                                                        <input type="text" readonly class="form-control" aria-label="..."
                                                               value="<?php echo $checkbox ?>">
                                                    </div>
                                                </div>

                                                <?php } ?>
                                                <br>
                                                <div class="row">
                                                    <input type="submit" class="btn btn-normal" name="formSubmit" value="Delete" id="delete_rc" style="width: 100px; float: left" />
                                                </div>
                                            </form>

                                        <?php }else{
                                                    echo "No Replication controllers found.<br>";
                                        }
                                            ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>





                <div class="panel panel-primary"style="min-height: 165px ">
                    <div class="panel-heading">
                        <a href="https://cloud.google.com/container-engine/docs/pods/" target="_blank" style="color: #ffffff">Pods</a>
                        <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" data-placement="right" title="A pod is a virtual container environment and consists of one or more Docker containers. Think of it as a basket that holds identical copies of your app."></span>
                    </div>
                <div class="panel-body">
                    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true" style="padding-top: 20px;">
                        <div class="panel panel-default">
                            <div class="panel-heading" role="tab" id="headingThree">
                                <h4 class="panel-title">
                                    <span class="input-group-addon">
                                        <div class="row-fluid">
                                            <div class="span12">
                                                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                    Click to expand
                                                </a>
                                            </div>
                                        </div>
                                    </span>
                                </h4>
                            </div>
                            <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                                <div class="panel-body" style="max-height: 500px">
                                    <?php
                                    if(!empty($pod_count)) {

                                        foreach ($pod_count as $key => $checkbox) { ?>
                                            <div class="row">
                                                <input type="text" readonly class="form-control" aria-label="..."
                                                       value="<?php echo $key . ": " . $checkbox ?>">
                                            </div>
                                        <?php }
                                    }else{
                                        echo "No Pods found.<br>";
                                    }

                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal -->
        <div class="modal fade" id="treeModal" tabindex="-1" role="dialog" aria-labelledby="treeModalLabel">
            <div class="modal-dialog" role="document" style="width: 1170px;">
                <div class="modal-content" >
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <span class="glyphicon glyphicon-exclamation-sign" data-toggle="tooltip" data-placement="left" style="float: left;" title="Please note, the amount of containers listed vary according to load and scales. It is limited by the hardware you selected for your nodes."></span>
                            <p class="modal-title" id="myModalLabel">&nbsp A visual representation of your cluster and its components:</p>
                    </div>
                    <div class="modal-body">
                        <div class="test">
                            <div id="container">
                                <div id="center-container">
                                    <div id="log"></div>
                                    <div id="infovis"></div>
                                </div>
                                <div id="right-container" style="visibility: hidden">
                                    <table>
                                        <tr>
                                            <td>
                                                <label for="s-normal">Normal </label>
                                            </td>
                                            <td>
                                                <input type="radio" id="s-normal" name="selection" checked="checked" value="normal" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <label for="s-root">Set as Root </label>
                                            </td>
                                            <td>
                                                <input type="radio" id="s-root" name="selection" value="root" />
                                            </td>
                                        </tr>
                                    </table>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal" onclick="$('#infovis').html('');" >Close</button>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>
