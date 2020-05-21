<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Lorisleiva\Actions\Tests\Stubs\Article;
use Lorisleiva\Actions\Tests\Stubs\User;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Lorisleiva\Actions\ActionServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        //
    }

    public function createUser($data = [])
    {
        return User::create(array_merge([
            'name' => 'John Doe',
            'email' => rand() . '@gmail.com',
            'password' => bcrypt('secret'),
        ], $data));
    }

    public function createArticle($user = null, $data = [])
    {
        return Article::create(array_merge([
            'user_id' => $user ? $user->id : $this->createUser()->id,
            'title' => 'My Blog Post',
            'slug' => 'my-blog-post',
            'body' => 'Lorem ipsum',
        ], $data));
    }

    public function createRequest($method, $route, $url, $data = [], $user = null)
    {
        $request = Request::createFromBase(
            SymfonyRequest::create($url, $method)
        );

        $request->setRouteResolver(function () use ($method, $route, $request) {
            return (new Route($method, $route, []))->bind($request);
        });

        return $request->merge($data);
    }
}
