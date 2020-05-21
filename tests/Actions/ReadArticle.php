<?php

namespace Lorisleiva\Actions\Tests\Actions;

use Lorisleiva\Actions\Action;
use Lorisleiva\Actions\Tests\Stubs\Article;
use Lorisleiva\Actions\Tests\Stubs\User;

class ReadArticle extends Action
{
    protected function handle(User $user, Article $article)
    {
        $output = $user->exists ? "Author: {$user->name}\n" : '';

        return $output . "Article: {$article->title}";
    }
}
