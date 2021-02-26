 # Nolla
Spartan minimum for backend. There are no "Service containers" or "Factories for creating factories for creating ...". There are no stupid "Query builders" that make your database engine cry. There are only 3 things you need in each app.

1. **Router** for binding controllers to desired URLs.
2. **Request & response abstractions** for properly handling requests and responses as objects.
3. **Global facade system** to make any system capabilities like `Config` or `Log` accessible from any part of your code.

This project provides only these 3 things and makes them work together. I used the [PSR7](https://www.php-fig.org/psr/psr-7) implementation ([nyholm/psr7](https://github.com/Nyholm/psr7)) as request and response abstraction layer and the router from Laravel ([nikic/fast-route](https://github.com/nikic/FastRoute)).

## MVC
This framework implements the MVC paradigm. The structure is clear and straightforward. Models are placed into folder `Model` and should be defined in namespace `\Model`. Controllers - in folder `Controller` and in namespace `\Controller`. Your conrollers should extend `the \Controller\AbstractController` class. Views - in folder `view`. I deliberately have chosen plain PHP for templating. We are all smart guys and will never put SQL queries or buisness logic inside templates.

## How to build an app
There is an abstract class you should extend. This is class `\Nolla\Core\App`. You should implement 3 static methods:

1. `createRoutingMap`
2. `defineMiddleware`
3. `defineErrorHandlers`

As an example you may use file `System/App.php`. After your app class is ready you can launch your app.

## Routing, middleware and error handlers
Method `createRoutingMap` receives one parameter - the Router object. In most cases you should provide method, URI pattern and handler for create route. About first two parameters you can read [here](https://github.com/nikic/FastRoute).

**Routing handler** can be a string or array. Let's see an example of a simple route definition. This route responds on a URL `/article/...` and receives ID of the article as a parameter.

```php
$router->addRoute('GET', '/article/{id:\d+}', 'Articles::getById');
```

In this case _routing hangler_ is a string, and the system will use it for calling the method `getById` from class `\Controller\Articles`, which shoud be defined in the file `controller/Articles.php`. If you define a routing handler without a method then the system will call method `index`. The system expects that your controller will return a correct PSR7 response. Each controller has access to the request object through the `$this->request` property.

The system will call your controller so that you'll automatically get parameters fetched from the URL by the router:

```php
class Articles extends AbstractController
{
  public function getById($id) // System reads this definition and understands that the method expects a parameter with name "id".
  {
    // lookup using $id
  }
}
```

**Middleware** is a specific class which can perform some actions on different stages of request handling. It can affect request and response objects. There are 3 method names reserved for this:

1. `onStartup` If in your middleware you've defined this method then it will be called right after system start up (before routing). It accepts PSR7 request object and _may_ return a request object, which will substitute the received one. Any other return values will be ignored.
2. `beforeRouting` This method can substitute the whole routing process. It accepts an request object. It may return an instance of `Nolla\Core\Route`. If the route will be non-empty then the system will not perform routing at all, but will call the controller defined in the returned route object.
2. `afterRouting` This method will be called after routing (only if it was performed by system). It accepts request and route objects. It can substitute the result of routing, by returning an own Route object.
3. `onResponseCreated` This method will be called after all controllers. It accepts requests, responses and routes. If it returns PSR7 a response object then it will substitute the received one. Any other return values will be ignored.

Your own middleware should be placed in `System/Middleware`. You can define middleware globally or only for a desired route. Global definition should be described in the`defineMiddleware` method of your app class. For example:

```php
protected function defineMiddleware()
{
  return [
    'System/Middleware/MyMiddleware1',
    'Nolla/Core/Middleware/NativeSession'
  ];
}
```

In this example the system will search for classes `\System\Middleware\MyMiddleware1` and `\Nolla\Core\Middleware\NativeSession`.

**Error handlers** are just controllers, which can handle HTTP errors like 404. There is a specific class defined to represent HTTP 4xx errors. This class is `\Nolla\Core\Http\Error4xx`. It is _throwable_, so for making an 404 error in your controller you should use the `throw` statement.

```php
throw new \Nolla\Core\Http\Error4xx(404);
```

The system will catch this error and run _error handler_ defined for error 404. If there is no handler for the status code, the system will die due to `UnexpectedValueException`;

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
The syntax for error handlers is the same as for string routing handlers.

**Advanced routing**. As mentioned above, you can define middleware and error handlers for each route separately. Use the array form of the routing handler. For example:

```php
$router->addRoute('GET', '/article/{id:\d+}', [
  'controller' => 'Articles::getById',
  'middleware' => ['System\Middleware\RedirectFromId123To124'],
  'errorHandlers' => [
    404 => 'Articles::articleNotFound'
  ]
]);
```

If you already had a 404 error handler defined globally it will be replaced by a new one which is defined in routing handler.

## Views
Each controller has method `loadView` and you can use it like in example below:
```php
$html = $this->loadView('my-controller/my-view', $data);
```
System will load file from `views/my-controller/my-view.php` and `$data` will be extracted on the view's scope.

## Nested controllers
Each controller has a method `loadController` for calling nested controllers.  You can use it like in example below:
```php
$html = $this->loadController('Auth::signUpForm');
```

## Models
There are no rules for creating models. Feel free to create anything.
