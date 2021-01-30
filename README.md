# ⚡️ Laravel Actions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/lorisleiva/laravel-actions.svg)](https://packagist.org/packages/lorisleiva/laravel-actions)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/lorisleiva/laravel-actions/Tests?label=tests)](https://github.com/lorisleiva/laravel-actions/actions?query=workflow%3ATests+branch%3Anext)
[![Total Downloads](https://img.shields.io/packagist/dt/lorisleiva/laravel-actions.svg)](https://packagist.org/packages/lorisleiva/laravel-actions)

![hero](https://user-images.githubusercontent.com/3642397/104024620-4e572400-51bb-11eb-97fc-c2692b16eaa7.png)

⚡ **Classes that take care of one specific task.**

This package introduces a new way of organising the logic of your Laravel applications by focusing on the actions your application provide.

Instead of creating controllers, jobs, listeners and so on, it allows you to create a PHP class that handles a specific task and run that class as anything you want.

Therefore it encourages you to switch your focus from:

> "What controllers do I need?", "should I make a FormRequest for this?", "should this run asynchronously in a job instead?", ect.

to:

> "What does my application actually do?"

## Installation

```bash
composer require lorisleiva/laravel-actions
```

## Documentation

:books: Read the full documentation at [laravelactions.com](https://laravelactions.com/)

## Basic usage

Create your first action using `php artisan make:action PublishANewArticle` and define the `asX` methods when you want your action to be running as `X`. E.g. `asController`, `asJob`, `asListener` and/or `asCommand`.

``` php
class PublishANewArticle
{
    use AsAction;

    public function handle(User $author, string $title, string $body): Article
    {
        return $author->articles()->create([
            'title' => $title,
            'body' => $body,
        ]);
    }

    public function asController(Request $request): ArticleResource
    {
        $article = $this->handle(
            $request->user(),
            $request->get('title'),
            $request->get('body'),
        );

        return new ArticleResource($article);
    }

    public function asListener(NewProductReleased $event): void
    {
        $this->handle(
            $event->product->manager,
            $event->product->name . ' Released!',
            $event->product->description,
        );
    }
}
```

### As an object

Now, you can run your action as an object by using the `run` method like so:

```php
PublishANewArticle::run($author, 'My title', 'My content');
```

### As a controller

Simply register your action as an invokable controller in a routes file.

```php
Route::post('articles', PublishANewArticle::class)->middleware('auth');
```

### As a listener

Simply register your action as a listener of the `NewProductReleased` event.

```php
Event::listen(NewProductReleased::class, PublishANewArticle::class);
```

Then, the `asListener` method of your action will be called whenever the `NewProductReleased` event is dispatched.

```php
event(new NewProductReleased($manager, 'Product title', 'Product description'));
```

### And more...

On top of running your actions as objects, controllers and listeners, Laravel Actions also supports jobs, commands and even mocking your actions in tests.

📚 [Check out the full documentation to learn everything that Laravel Actions has to offer](https://laravelactions.com/).
