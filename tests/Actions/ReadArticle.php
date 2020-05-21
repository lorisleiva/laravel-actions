<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;
use Lorisleiva\Actions\Tests\Stubs\Article;
use Lorisleiva\Actions\Tests\Stubs\User;

class ReadArticle extends Action
{
    protected function handle(User $author, Article $article)
    {
        $output = $author->exists() ? "Author: {$author->name}\n" : '';

        return $output . "Article: {$article->title}";
    }
}
