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

Create your own PHP class, then add the `AsAction` trait and define the `asX` methods when you want your action to be running as `X`. E.g. `asController`, `asJob`, `asListener` and/or `asCommand`.

``` php
class CreateNewArticle
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

Finally, register your PHP class as you normally would for each of these patterns. In the example above, we'll need to:
- Add `CreateNewArticle::class` as an invokable controller in our routes files
- And register `CreateNewArticle::class` as an event listener of `NewProductReleased::class` in our `EventServiceProvider`.

<sub>_Full documentation available at [laravelactions.com](https://laravelactions.com/)_</sub>
