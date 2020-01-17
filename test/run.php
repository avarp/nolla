<?php
chdir('..');
require('vendor/autoload.php');

$files = [
  'test/assets/TestApp.php' => 'system/TestApp.php',
  'test/assets/controller/Test.php' => 'controller/Test.php',
  'test/assets/controller/TestNested.php' => 'controller/TestNested.php',
  'test/assets/middleware/Test.php' => 'system/middleware/Test.php',
  'test/assets/middleware/RedirectFromId123To124.php' => 'system/middleware/RedirectFromId123To124.php',
  'test/assets/view/testView.php' => 'view/testView.php'
];
foreach ($files as $from => $to) copy($from, $to);

function url($url)
{
  $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
  $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
  );
  $request = $creator->fromGlobals();
  return $request->withUri(
    $request->getUri()->withPath($url)
  );
}

$app = new \System\TestApp;

//////////////////////////////////// TEST #1 ///////////////////////////////////

echo "1. Test of default method 'index' in controllers. ";
$response = $app->run(url('/'))->getResponse();
$s = $response->getBody();
if ($s == 'Test 1 completed') {
  echo "OK\n";
} else {
  echo "Failed with value `$s`\n";
}

//////////////////////////////////// TEST #2 ///////////////////////////////////

echo "2. Nested controllers test. ";
$response = $app->run(url('/nested'))->getResponse();
$s = $response->getBody();
if ($s == 'Test 2 completed') {
  echo "OK\n";
} else {
  echo "Failed with value `$s`\n";
}

//////////////////////////////////// TEST #3 ///////////////////////////////////

echo "3. Middleware test. ";
$response = $app->run(url('/test-middleware'))->getResponse();
$s = $response->getBody();
if ($s == 'Test 3 completed') {
  echo "OK\n";
} else {
  echo "Failed with value `$s`\n";
}

//////////////////////////////////// TEST #4 ///////////////////////////////////

echo "4. Error handler test. ";
$response = $app->run(url('/not-existing-url'))->getResponse();
$body = $response->getBody();
$status = $response->getStatusCode();
$reason = $response->getReasonPhrase();
if ($body == 'Test 4 completed' && $status == 404 && $reason == 'Not Found') {
  echo "OK\n";
} else {
  echo "Failed with value body=`$body`, status=`$status`, reason=`$reason`\n";
}

//////////////////////////////////// TEST #5 ///////////////////////////////////

echo "5. Parsing URL parameters test. ";
$response = $app->run(url('/article/12'))->getResponse();
$s = $response->getBody();
if ($s == 'id=12') {
  echo "OK\n";
} else {
  echo "Failed with value `$s`\n";
}

//////////////////////////////////// TEST #6 ///////////////////////////////////

echo "6. Parsing optional URL parameters test. ";
$response = $app->run(url('/article/15/some-title'))->getResponse();
$s = $response->getBody();
if ($s == 'id=15, title=some-title') {
  echo "OK\n";
} else {
  echo "Failed with value `$s`\n";
}

//////////////////////////////////// TEST #7 ///////////////////////////////////

echo "7. Error handler overriding test. ";
$response = $app->run(url('/article/999999999/not-existing-article'))->getResponse();
$body = $response->getBody();
$status = $response->getStatusCode();
$reason = $response->getReasonPhrase();
if ($body == 'Article not found' && $status == 404 && $reason == 'Not Found') {
  echo "OK\n";
} else {
  echo "Failed with value body=`$body`, status=`$status`, reason=`$reason`\n";
}

//////////////////////////////////// TEST #8 ///////////////////////////////////

echo "8. Redirect test. ";
$response = $app->run(url('/article/123/some-title'))->getResponse();
$location = $response->getHeader('Location')[0];
$status = $response->getStatusCode();
$reason = $response->getReasonPhrase();
if ($location == '/article/124/some-title' && $status == 301 && $reason == 'Moved Permanently') {
  echo "OK\n";
} else {
  echo "Failed with value location=`$location`, status=`$status`, reason=`$reason`\n";
}

//////////////////////////////////// TEST #9 ///////////////////////////////////

echo "9. Negative test on controller missing. ";
$success = false;
$msg = 'The controller \\Controller\\Test->blabla() does not exist.';
try {
  $response = $app->run(url('/not-existing-controller'));
} catch(\Exception $e) {
  $success = $e instanceof \BadMethodCallException && $e->getMessage() == $msg;
}
if ($success) {
  echo "OK\n";
} else {
  echo "Failed\n";
}

/////////////////////////////////// TEST #10 ///////////////////////////////////

echo "10. Negative test of controller's wrong return value. ";
$success = false;
$msg = 'The controller \\Controller\\Test->badMethod() returned unexpected value instead of instance of \\Psr\\Http\\Message\\ResponseInterface';
try {
  $response = $app->run(url('/bad-controller'));
} catch(\Exception $e) {
  $success = $e instanceof \UnexpectedValueException && $e->getMessage() == $msg;
}
if ($success) {
  echo "OK\n";
} else {
  echo "Failed\n";
}

/////////////////////////////////// TEST #11 ///////////////////////////////////

echo "11. Negative test on error handler missing. ";
$success = false;
$msg = 'Error handler for error code 418 not defined.';
try {
  $response = $app->run(url('/not-existing-error-handler'));
} catch(\Exception $e) {
  $success = $e instanceof \UnexpectedValueException && $e->getMessage() == $msg;
}
if ($success) {
  echo "OK\n";
} else {
  echo "Failed\n";
}

/////////////////////////////////// TEST #12 ///////////////////////////////////

echo "12. Negative test on middleware class missing. ";
$success = false;
$msg = 'The middleware \\Middleware\\NotExistingMiddleware does not exist. Check your implementation of createRoutingMap() and defineMiddleware() methods in \\System\\App class.';
try {
  $response = $app->run(url('/not-existing-middleware'));
} catch(\Exception $e) {
  $success = $e instanceof \UnexpectedValueException && $e->getMessage() == $msg;
}
if ($success) {
  echo "OK\n";
} else {
  echo "Failed\n";
}

/////////////////////////////////// TEST #13 ///////////////////////////////////

echo "13. Negative test on prohibited routing placeholders. ";
$success = false;
$msg = 'Usage of routing map\'s placeholder {controller:...} is prohibited. Check implementation of createRoutingMap() in \\System\\App class.';
try {
  $response = $app->run(url('/bad-parameter-name/some-parameter'));
} catch(\Exception $e) {
  $success = $e instanceof \UnexpectedValueException && $e->getMessage() == $msg;
}
if ($success) {
  echo "OK\n";
} else {
  echo "Failed\n";
}

/////////////////////////////////// TEST #14 ///////////////////////////////////

echo "14. Negative test on wrong routing handler format. ";
$success = false;
$msg = 'Router returned an unknown route type. Check implementation of createRoutingMap() in \\System\\App class.';
try {
  $response = $app->run(url('/bad-route'));
} catch(\Exception $e) {
  $success = $e instanceof \UnexpectedValueException && $e->getMessage() == $msg;
}
if ($success) {
  echo "OK\n";
} else {
  echo "Failed\n";
}

/////////////////////////////////// TEST #15 ///////////////////////////////////

echo "15. Test of views. ";
$response = $app->run(url('/test-view'))->getResponse();
$s = $response->getBody();
if ($s == 'Test 15 completed') {
  echo "OK\n";
} else {
  echo "Failed with value `$s`\n";
}

//////////////////////////////////// CLEANUP ///////////////////////////////////

foreach ($files as $from => $to) unlink($to);