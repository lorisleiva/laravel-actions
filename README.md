# Laravel Actions
⚡️ Laravel components that take care of one specific task

This package introduces a new way of organising the logic of your Laravel applications by focusing on the actions your application provide.

Similarly to how VueJS components regroup HTML, JavaScript and CSS together, Laravel Actions regroup the authorisation, validation and execution of a task in one class that can be used as an **invokable controller**, as a **plain object**, as a **dispatchable job**, as an **event listener** and as an **artisan command**.

![Cover picture](https://user-images.githubusercontent.com/3642397/58073806-87342680-7b9b-11e9-9669-df35fba71f6b.png)

## Installation

```sh
composer require lorisleiva/laravel-actions
```

## Documentation

:books: Read the full documentation at [laravelactions.com](https://laravelactions.com/)

## Basic usage

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
        return redirect()->route('article.show', $article);
    }
}
```

#### As an artisan command.

```php
class PublishANewArticle extends Action
{
    protected static $commandSignature = 'make:article {title} {body}';
    protected static $commandDescription = 'Publishes a new article';

    // ...
}
```


<sub>_Full documentation available at [laravelactions.com](https://laravelactions.com/)_</sub>
