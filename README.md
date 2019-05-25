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
- Explain attributes with `__constructor`, `fill`, `all`, `only`, `except`, `has`, `get` and `__get`, `set` and `__set`.

## Dependency injections
- Resolves dependencies from the method’s argument. `handle`.

## Authorization
- Explain authorization with `authorize`\*, `can`, `user` and `actingAs`.

## Validation
- Explain validation with `rules`, `withValidator`\*, `afterValidator`\*, `validate`.

## Actions as objects
- For each type, explain how the data is fetched and how to override that logic.
- From constructor, setters, or `run(attributes)`

## Actions as jobs
- For each type, explain how the data is fetched and how to override that logic.
- `dispatch(data)`
- Using ShouldQueue

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class PublishANewArticle extends Action implements ShouldQueue
{
    // ...
}
```

## Actions as listeners
- For each type, explain how the data is fetched and how to override that logic.
- `public` properties of event
- Override `getAttributesFromEvent` method.

## Actions as controllers
- For each type, explain how the data is fetched and how to override that logic.
- From `request->all()` and the route parameters. Explain what happens with conflict.
- Explain the actions macro (example with and without).

```php
// routes/web.php
Route::actions(function () {
    Route::post('articles', 'PublishANewArticle');
});
// routes/action.php
Route::post('articles', 'PublishANewArticle');
```

- Explain how to register middleware.
- Explain how to respond with `response`, `jsonResponse` and `htmlResponse`.

## Before hook
- Explain the before hooks `asController`, etc. (Note: called just before running and not when created, hence use `register` to add middleware and not `asController`).
- Explain `runningAs`.

## Use actions within actions
- Explain how to call multiple actions from one action (`createFrom`).
