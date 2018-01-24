<?php 
$app->get('/session', function() {
    $db = new DbHandler();
    $session = $db->getSession();
    $response["userid"] = $session['userid'];
    $response["fullname"] = $session['fullname'];
    $response["id"] = $session['id'];
    $response["userinfo"] = $session['userinfo'];
    echoResponse(200, $session);
});

$app->post('/login', function() use ($app) {
    require_once 'passwordHash.php';
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('userid', 'password'),$r->customer);
    $response = array();
    $db = new DbHandler();
    $password = $r->customer->password;
    $userid = $r->customer->userid;
    $user = $db->getOneRecord("select * from users where userid='$userid'");
    if ($user != NULL) {
        if(passwordHash::check_password($user['password'],$password)){
        $response['status'] = "success";
        $response['message'] = 'Success.';
        $response['id'] = $user['id'];
        $response['fullname'] = substr($user['lastname'],0,2) .'.'.  $user['firstname'];
        $response['userid'] = $user['userid'];
        $response['email'] = $user['email'];
        $response['usergroupid'] = $user['usergroupid'];
        $response['createdAt'] = $user['regdate'];
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['userid'] = $userid;
        $_SESSION['id'] = $user['id'];
        $_SESSION['fullname'] = substr($user['lastname'],0,2) .'.'. $user['firstname'];
        } else {
            $response['status'] = "error";
            $response['message'] = 'Error.';
        }
    }else {
            $response['status'] = "error";
            $response['message'] = 'Error.';
        }
    echoResponse(200, $response);
});
$app->post('/signUp', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email', 'name', 'password'),$r->customer);
    require_once 'passwordHash.php';
    $db = new DbHandler();
    $phone = $r->customer->phone;
    $name = $r->customer->name;
    $email = $r->customer->email;
    $address = $r->customer->address;
    $password = $r->customer->password;
    $isUserExists = $db->getOneRecord("select 1 from customers_auth where phone='$phone' or email='$email'");
    if(!$isUserExists){
        $r->customer->password = passwordHash::hash($password);
        $tabble_name = "customers_auth";
        $column_names = array('phone', 'name', 'email', 'password', 'city', 'address');
        $result = $db->insertIntoTable($r->customer, $column_names, $tabble_name);
        if ($result != NULL) {
            $response["status"] = "success";
            $response["message"] = "User account created successfully";
            $response["id"] = $result;
            if (!isset($_SESSION)) {
                session_start();
            }
            $_SESSION['id'] = $response["id"];
            $_SESSION['phone'] = $phone;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            echoResponse(200, $response);
        } else {
            $response["status"] = "error";
            $response["message"] = "Failed to create customer. Please try again";
            echoResponse(201, $response);
        }            
    }else{
        $response["status"] = "error";
        $response["message"] = "An user with the provided phone or email exists!";
        echoResponse(201, $response);
    }
});
$app->get('/logout', function() {
    $db = new DbHandler();
    $session = $db->destroySession();
    $response["status"] = "info";
    $response["message"] = "Logout...";
    echoResponse(200, $response);
});
?>