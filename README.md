# Nolla
Spartan minimum for backend. There are no "Service containers" or "Factories for creating factories for creating ...". There are no stupid "Query builders" which makes your database engine cry. There are only 3 things you need in each app.

1. **Router** for bind controllers to desired URLs.
2. **Request & response abstractions** for properly handling requests and responses as objects.
3. **Global facade system** to make any system capabilities like `Config` or `Log` accessible from any part of your code.

This project provides only this 3 things and makes them work together. I used [PSR7](https://www.php-fig.org/psr/psr-7) implementation ([nyholm/psr7](https://github.com/Nyholm/psr7)) as request and response abstraction layer and router from Laravel ([nikic/fast-route](https://github.com/nikic/FastRoute)).




## Installation

1. Set up local server with PHP > 7.1
2. Clone this repo
3. Run `composer install`




## Structure

The basic Nolla project consists of 18 (!) files:

```
nolla
├── Controller                  place for controllers
│   ├── AbstractController.php  basic class for building controllers
│   ├── Footer.php              controller displaying footer
│   ├── Header.php              controller displaying header
│   └── Page.php                front controller returning the HTML page
├── Model                       place for your models
├── System                      place where system things are
│   ├── configs                 place for YAML configs for your app
│   │   └── aliases.yaml        short names for globally used classes
│   ├── App.php                 the main class of app
│   └── helpers.php             you can put any useful function here
├── view                        place for views
│   ├── 404.php                 example of the 404 page
│   ├── footer.php              example of the footer
│   ├── header.php              example of the header
│   └── home.php                example of the homepage
├── .gitignore                  -
├── .htaccess                   -
├── LICENSE                     -
├── README.md                   file you're reading right now
├── composer.json               dependencies of the project. You can edit it.
├── composer.lock               Composer's lock-file. You should not edit it, but should commit it.
└── index.php                   the entry point

```

With this structure you already able to build your web app with:
- advanced routing system
- PSR7 support
- any middleware you want
- static facades
- MVC architecture

This framework implements MVC paradigm. Structure is clear and straightforward. Models are placed in folder `Model` and should be defined in namespace `\Model`. Controllers - in folder `Controller` and in namespace `\Controller`. Your conrollers should extend `\Controller\AbstractController` class. Views - in folder `view`. I deliberately chosen plain PHP for templating. We are all smart guys and will never put SQL queries or buisness logic inside templates.




## Request handling
Core of the app is written in functional style and in index.php you can see main request handling chain:

```php
<?php
define("ROOT_DIR", __DIR__);
require('vendor/autoload.php');
use \System\App;

App::sendHttpResponse(
  App::processHttpRequest(
    App::httpRequestFromGlobals()
  )
);
```

It means that App:
1. Creates PSR7 request object from PHP's global values like `$_SERVER`.
2. Convert request to response object which also is PSR7-compatible.
3. Sends response to the client.

This architecture allows you to organize integration tests easily and straightforward:

```php

// construct $request with any PSR7 library

$response = App::processHttpRequest($request);

// make assertions on $response
```

`App::sendHttpResponse` method clears all HTTP headers before sending so you can be sure that only headers that present in response object will be sent.




## What is inside the System/App.php
There is a class extending `\Nolla\Core\App`. You should implement 3 static methods:

1. `createRoutingMap`
2. `defineMiddleware`
3. `defineErrorHandlers`

As an example you may use file `System/App.php`. Here you need to understand some terms:

*Routing*. Is literally process of matching the URL we received to one of the patterns we've defined in the method `createRoutingMap`. Result of this process is the _route_.

*Route*. It is object, which can have 2 states: found and not found. If it has "found" state it also has details of the found route:
1. Controller _string_
2. Parameters _array_
3. Error handlers _array_
4. Array of middlewares _array_
In the "not found" state there is no such data at all.

*Controller*. Is class responsible for creating response. It is recommended to define it in namespace `\Controller`. Controller mentioned in Route object which will be called first should return PSR7 response. Other nested controllers can return anything. Across the system controllers always are presented by a _class path_.

*Class path*. Is string declaring which method of which class system should call. It has format [path/]class[::method]. Path should use "/" symbol for delimiter. If method will be omitted method `index` will be called. If path starts with "/" it is absolute path. Otherwise it is relative and system will resolve it depending on situation. For example, for controllers you can omit "/Controller/..." because there is only one namespace for them. Controller's path "Pages/Admin::login" points to method `login` of class `\Controller\Pages\Admin` from file `Controller/Pages/Admin.php`.

*Error handler*. Is a controller which will be called when particular HTTP 4xx error will be thrown.

*Middleware*. Is a class which has special methods affecting on the system's workflow. Middleware can affect on routing, modify response and request.




## More about routing
Method `createRoutingMap` receives one parameter - the Router object. In most cases you should provide method, URI pattern and handler for create route. About first two parameters you can read [here](https://github.com/nikic/FastRoute).

**Routing handler** can be a string or array. Let's see an example of simple route definition. This route responses on URL `/article/...` and receives ID of aricle as parameter.

```php
$router->addRoute('GET', '/article/{id:\d+}', 'Articles::getById');
#                                             ^^^^^^^^^^^^^^^^^^^
#                                             this is routing handler
```

In this case _routing hangler_ is a class path string, and system will use it for call method `getById` from class `\Controller\Articles` which shoud be defined in file `Controller/Articles.php`. System expects that your controller will return correct PSR7 response. Each controller have access to request object by `$this->request` property.

System will call your controller so that you'll automatically get parameters fetched from URL by router:

```php
class Articles extends AbstractController
{
  public function getById($id) // System reads this definition and understands that method expects parameter with name "id".
  {
    // searching using $id
  }
}
```

The second example shows how to define routing handler using array. With this syntax you can define middleware and error handlers for each route separately.

```php
$router->addRoute('GET', '/article/{id:\d+}', [
  'controller' => 'Articles::getById',
  'middleware' => ['System\Middleware\RedirectFromId123To124'],
  'errorHandlers' => [
    404 => 'Articles::articleNotFound'
  ]
]);
```

If you already had 404 error handler defined globally it will be replaced by new one which is defined in routing handler. Middleware you defined also will be used.




## Middleware
Middleware is specific class which can perform some actions on a different stages of request handling. It can affects on request and response objects. There are 4 method names reserved for this.

| Method              | Will be called...                                            | Parameters                                          | Result                |
| ------------------- | ------------------------------------------------------------ | --------------------------------------------------- | --------------------- |
| `onStartup`         | ...rigth after system start  and before routing. Result, if it is PSR7 request, will substitute request which system had before. | 1. PSR7 request                                     | PSR7 request or null  |
| `beforeRouting`     | ...before routing. If it returns Route in state "found" sustem will use this route and will not perform routing at all. | 1. PSR7 request                                     | Route or null         |
| `afterRouting`      | ...after routing, if the system performed one. If it returns Route in state "found" sustem will use it. | 1. PSR7 request<br />2. Route                       | Route or null         |
| `onResponseCreated` | ...after all controllers. Returned PSR7 response will be the result of whole request handling process | 1. PSR7 request<br />2. Route<br />3. PSR7 response | PSR7 response or null |

By default system search for middleware in the namespace `\System\Middleware` in `System/Middleware` folder (you should create it). You can define middleware globally or only for desired route. Global definition should be described in the method `defineMiddleware` of your `\System\App` class:

```php
protected function defineMiddleware()
{
  return [
    'MyMiddleware1',
    '/Nolla/Core/Middleware/NativeSession'
  ];
}
```

## Tips on middleware usage

Case 1.

Q: My system expects Authorization header but by default browser adds only cookie.

A: Use `onStartup` method. You will get request and you can read Set-Cookie header and based on this data set desired Authorization header.

Case 2.

Q: I have route which I can't describe within router's syntax.

A: Use `beforeRouting`. You can programmatically detect that you need to display particular page there. Return null of Route dependidng on conditions.

Case 3.

Q: I want that address `/articles/random` displays random article.

A: Use `afterRouting`. You can return a new Route with article ID generated by rand(). It is better to link this middleware only to this route (not globally).

Case 4.

Q: I need to send CORS headers to the client.

A: Use `onResponseCreated` for adding headers to the response and `onStartup` method for handling pre-flight request.




## Error handlers
Error handlers are just controllers, which can handle HTTP errors like 404. There is specific class defined to represent HTTP 4xx errors. This class is `\Nolla\Core\Http\Error4xx`. It is _throwable_, so for making an 404 error in your controller you should use `throw` statement.

```php
throw new \Nolla\Core\Http\Error4xx(404);
```

System will catch this error and run _error handler_ defined for error 404. If there is no handler for the status code, system will die due to `UnexpectedValueException`;

You can define error handlers globally or only for desired route. Global definition should be described in `defineErrorHandlers` method of your app class. For example:

```php
protected function defineErrorHandlers()
{
  return [
    404 => 'Pages::error404',
    403 => 'Auth::login'
  ];
}
```




## Views
Each controller has method `loadView` and you can use it like in example below:
```php
$html = $this->loadView('my-controller/my-view', $data);
```
System will load file from `views/my-controller/my-view.php` and `$data` will be extracted on the view's scope.




## Nested controllers
Each controller has method `loadController` for calling nested controllers and you can use it like in example below:
```php
$html = $this->loadController('Auth::signUpForm');
```




## Models
There is no rules for creating models. Feel free to create anything.
