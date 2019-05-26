# laravel-actions
⚡️ Laravel components that take care of one specific task

This package introduces a new way of organising the logic of your Laravel applications by focusing on the actions your application provide.

Similarly to how VueJS components regroup HTML, JavaScript and CSS together, Laravel Actions regroup the authorisation, validation and execution of a task in one class that can be used as an **invokable controller**, as a **plain object**, as a **dispatchable job** and as an **event listener**.

![Cover picture](https://user-images.githubusercontent.com/3642397/58073806-87342680-7b9b-11e9-9669-df35fba71f6b.png)


**[Documentation in progress.]**

## Installation

```sh
composer require lorisleiva/laravel-actions
```

## Basic Usage

Create your first action using `php artisan make:action PublishANewArticle` and fill the authorisation logic, the validation rules and the handle method. Note that the `authorize` and `rules` methods are optional and default to `true` and `[]` respectively.

```php
// app/Actions/PublishANewArticle.php
class PublishANewArticle extends Action
{
    public function authorize()
    {
        return $this->user()->hasRole('author');
    }
    
    public function rules()
    {
        return [
            'title' => 'required',
            'body' => 'required|min:10',
        ];
    }
    
    public function handle()
    {
        return Article::create($this->validated());
    }
}
```

You can now start using that action in multiple ways:

#### As a plain object.

```php
$action = new PublishANewArticle([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum.',
]);

$article = $action->run();
```

#### As a dispatchable job.

```php
PublishANewArticle::dispatch([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum.',
]);
```

#### As an event listener.

```php
class ProductCreated
{
    public $title;
    public $body;
    
    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }
}

Event::listen(ProductCreated::class, PublishANewArticle::class);

event(new ProductCreated('My new SaaS application', 'Lorem Ipsum.'));
```

#### As an invokable controller.

```php
// routes/web.php
Route::post('articles', '\App\Actions\PublishANewArticle');
```
If you need to specify an explicit HTTP response for when an action is used as a controller, you can define the `response` method which provides the result of the `handle` method as the first argument.

```php
class PublishANewArticle extends Action
{
    // ...
    
    public function response($article)
    {
        return redirect()->route('article.show' $article);
    }
}
```

## Action’s attributes
In order to unify the various forms an action can take, the data of an action is implemented as a set of attributes (similarly to models).

This means when interacting with an instance of an `Action`, you can manipulate its attributes with the following methods:

```php
$action = new Action(['key' => 'value']);   // Initialise an action with the provided attribute.
$action->fill(['key' => 'value']);          // Merge the new attributes with the existing attributes.
$action->all();                             // Retrieve all attributes of an action as an array.
$action->only('title', 'body');             // Retrieve only the attributes provided.
$action->except('body');                    // Retrieve all attributes excepts the one provided.
$action->has('title');                      // Whether the action has the provided attribute.
$action->get('title');                      // Get an attribute.
$action->get('title', 'Untitled');          // Get an attribute with default value.
$action->set('title', 'My blog post');      // Set an attribute.
$action->title;                             // Get an attribute.
$action->title = 'My blog post';            // Set an attribute.
```
Depending on how an action is ran, its attributes are filled with the relevant information available. For example, as a controller, an action’s attributes will contain all of the input from the request. For more information see:

- [How are attributes filled as objects](TODO).
- [How are attributes filled as jobs](TODO).
- [How are attributes filled as listeners](TODO).
- [How are attributes filled as controllers](TODO).

## Dependency injections
The `handle` method support dependency injections. That means, whatever arguments you enter in the handle method, Laravel Actions will try to resolve them from the container but also from its own attributes. Let’s have a look at some examples.

```php
// Resolved from the IoC container.
public function handle(Request $request) {/* ... */}
public function handle(MyService $service) {/* ... */}

// Resolved from the attributes.
// -- $title and $body are equivalent to $action->title and $action->body
// -- When attributes are missing, null will be returned unless a default value is provided.
public function handle($title, $body) {/* ... */}
public function handle($title, $body = 'default') {/* ... */}

// Resolved from the attributes using route model binding.
// -- If $action->comment is already an insteance of Comment, it provides it.
// -- If $action->comment is an id, it will provide the right instance of Comment from the database or fail.
// -- This will also update $action->comment to be that instance.
public function handle(Comment $comment) {/* ... */}

// They can all be combined.
public function handle($title, Comment $comment, MyService $service) {/* ... */}
```

As you can see, both the action’s attributes and the IoC container are used to resolve dependency injections. When a matching attribute is type-hinted, the library will do its best to provide an instance of that class from the value of the attribute.

## Authorization

### The `authorize` method.
Actions can define their authorisation logic using the `authorize` method. It will throw a `AuthorizationException` whenever this method returns false.

```php
public function authorize()
{
    // Your authorisation logic here...
}
```

It is worth noting that, just like the `handle` method, the `authorize` method [supports dependency injections](#dependency-injections).

### The `user` and `actingAs` methods.
If you want to access the authenticated user from an action you can simply use the `user` method.

```php
public function authorize()
{
    return $this->user()->isAdmin();
}
```

When ran as a controller, the user is fetched from the incoming request, otherwise `$this->user()` is equivalent to `Auth::user()`.

If you want to run an action acting on behalf of another user you can use the `actingAs` method. In this case, the `user` method will always return the provided user.

```php
$action->actingAs($admin)->run();
```

### The `can` method.
If you’d still like to use Gates and Policies to externalise your authorisation logic, you can use the `can` method to verify that the user can perform the provided ability.

```php
public function authorize()
{
    return $this->can('create', Article::class);
}
```

## Validation
Just like in Request classes, you can defined your validation logic using the `rules` and `withValidator` methods.

The `rules` method enables you to list validation rules for your action’s attributes.

```php
public function rules()
{
    return [
        'title' => 'required',
        'body' => 'required|min:10',
    ];
}
```

The `withValidator` method provide a convenient way to add custom validation logic.

```php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if ($this->somethingElseIsInvalid()) {
            $validator->errors()->add('field', 'Something is wrong with this field!');
        }
    });
}
```

If all you want to do is add an after validation hook, you can use the `afterValidator` method instead of the `withValidator` method. The following example is equivalent to the one above.

```php
public function afterValidator($validator)
{
    if ($this->somethingElseIsInvalid()) {
        $validator->errors()->add('field', 'Something is wrong with this field!');
    };
}
```

It is worth noting that, just like the `handle` method, the `withValidator` and `afterValidator` methods [support dependency injections](#dependency-injections).

Finally, if you want to validate some data directly within the `handle` method, you can use the `validate` method.

```php
public function handle()
{
    $this->validate([
        'comment' => 'required|min:10|spamfree',
    ]);
}
```

This will validate the provided rules against the action’s attributes.

## Actions as objects

### How are attributes filled?

When running actions as plain PHP objects, their attributes have to be filled manually using the various helper methods mentioned above. For example:

```php
$action = new PublishANewArticle;
$action->title = 'My blog post';
$action->set('body', 'Lorem ipsum.');
$action->run();
```

Note that the `run` method also accepts additional attributes to be merged.

```php
(new PublishANewArticle)->run([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum.',
]);
```

## Actions as jobs

### How are attributes filled?

Similarly to actions as objects, attributes are filled manually when you dispatch the action.

```php
PublishANewArticle::dispatch([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum.',
]);
```

### Queueable actions

Just like jobs, actions can be queued by implementing the `ShouldQueue` interface.

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class PublishANewArticle extends Action implements ShouldQueue
{
    // ...
}
```

Note that you can also use the `dispatchNow` method to force a queueable action to be executed immediately.

## Actions as listeners

### How are attributes filled?

By default, all of the event’s public properties will be used as attributes.

```php
class ProductCreated
{
    public $title;
    public $body;
    
    // ...
}
```

You can override that behaviour by defining the `getAttributesFromEvent`.

```php
// Event
class ProductCreated
{
    public $product;
}

// Listener
class PublishANewArticle extends Action
{
    public function getAttributesFromEvent($event)
    {
        return [
            'title' => '[New product] ' . $event->product->title,
            'body' => $event->product->description,
        ];
    }
}
```

This can also work with events defined as strings.

```php
// Event
Event::listen('product_created', PublishANewArticle::class);

// Dispatch
event('product_created', ['My SaaS app', 'Lorem ipsum']);

// Listener
class PublishANewArticle extends Action
{
    public function getAttributesFromEvent($title, $description)
    {
        return [
            'title' => "[New product] $title",
            'body' => $description,
        ];
    }

    // ...
}
```

## Actions as controllers

### How are attributes filled?

By default, all input data from the request and the route parameters will be used to fill the action’s attributes.

You can change this behaviour by  overriding the `getAttributesFromRequest` method. This is its default implementation:

```php
public function getAttributesFromRequest(Request $request)
{
    return array_merge(
        $this->getAttributesFromRoute($request),
        $request->all()
    );
}
```

Note that, since we’re merging two sets of data together, a conflict is possible when a variable is defined on both sets. As you can see, by default, the request’s data takes priority over the route’s parameters. However, when resolving dependencies for the `handle` method’s argument, the route parameters will take priority over the request’s data.

That means in case of conflict, you can access the route parameter as a method argument and the request’s data as an attribute. For example:

```php
// Route endpoint: PATCH /comments/{comment}
// Request input: ['comment' => 'My updated comment']
public function handle(Comment $comment)
{
    $comment;        // <- Comment instance matching the given id.
    $this->comment;  // <- 'My updated comment'
}
```

### Defining routes with actions

Because your actions are located by default in the `\App\Action` namespace and not the `\App\Http\Controller` namespace, you have to provide the full qualified name of the action if you want to define them in your `routes/web.php` or `routes/api.php` files.

```php
// routes/web.php
Route::post('articles', '\App\Actions\PublishANewArticle');
```

<sub>*Note that the initial `\` here is important to ensure the namespace does not become `\App\Http\Controller\App\Actions\PublishANewArticle`.*</sub>

Alternatively you can set define them in a group that re-defines the namespace.

```php
// routes/web.php
Route::namespace('\App\Actions')->group(function () {
    Route::post('articles', 'PublishANewArticle');
});
```

Laravel Actions provides a `Route` macro that does exactly this:

```php
// routes/web.php
Route::actions(function () {
    Route::post('articles', 'PublishANewArticle');
});
```

Another solution would be to create a new route file `routes/action.php` and register it in your `RouteServiceProvider`.

```php
// app/Providers/RouteServiceProvider.php
Route::middleware('web')
     ->namespace('App\Actions')
     ->group(base_path('routes/action.php'));

// routes/action.php
Route::post('articles', 'PublishANewArticle');
```

### Registering middleware
- Explain how to register middleware.

### Returning HTTP responses
- Explain how to respond with `response`, `jsonResponse` and `htmlResponse`.

## Before hook
- Explain the before hooks `asController`, etc. (Note: called just before running and not when created, hence use `register` to add middleware and not `asController`).
- Explain `runningAs`.

## Use actions within actions
- Explain how to call multiple actions from one action (`createFrom`).
