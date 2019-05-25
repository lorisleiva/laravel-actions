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

```php
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

### Actions as objects

```php
$action = new PublishANewArticle([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum.',
]);

$action->run();
```

### Actions as controller

```php
// routes/web.php
Route::post('articles', '\App\Actions\PublishANewArticle');
```

```php
// routes/web.php
Route::actions(function () {
    Route::post('articles', 'PublishANewArticle');
});
```

```php
// routes/action.php
Route::post('articles', 'PublishANewArticle');
```

### Actions as jobs

```php
PublishANewArticle::dispatch([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum.',
]);
```

Using ShouldQueue

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class PublishANewArticle extends Action implements ShouldQueue
{
    // ...
}
```

### Actions as listeners

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

TODO: 
- Explain the actions macro (example with and without).
- Explain how to register middleware.
- Explain how to respond with `response`, `jsonResponse` and `htmlResponse`.
- For each type, explain how the data is fetched and how to override that logic.
- Explain the before hooks `asController`, etc. (Note: called just before running and not when created, hence use `register` to add middleware and not `asController`).
- Explain `runningAs`.
- Explain attributes with `__constructor`, `fill`, `run(attributes)`, `all`, `only`, `except`, `has`, `get` and `__get`, `set` and `__set`.
- Explain authorization with `authorize`\*, `can`, `user` and `actingAs`.
- Explain validation with `rules`, `withValidator`\*, `afterValidator`\*, `validate`.
- Explain how to call multiple actions from one action (`createFrom`).
- Explain `make:action` command.

\* = Resolves dependencies from the method's argument.
