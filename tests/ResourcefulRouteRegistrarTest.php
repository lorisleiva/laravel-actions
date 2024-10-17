<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Lorisleiva\Actions\Routing\ActionResourceRegistrar;

beforeEach(function () {
    // Allows us to test routes with a single word
    Route::actions('addresses')->middleware('auth');

    // Allows us to test routes with kebab-case words
    Route::actions('order-items');

    // Allows us to test routes where we specify the actions
    Route::actions('products')->only('index', 'show');

    // Allows us to test nesting
    Route::actions('photos.comments');

    // Allows us to test shallow nesting, with a custom namespace
    Route::actions('users.comments', 'Custom\Namespace')->shallow();
});

it('registers index route for addresses', function () {
    expect(Route::has('addresses.index'))->toBeTrue();
    $route = Route::getRoutes()->getByName('addresses.index');
    expect($route->getAction()['uses'])->toEqual('App\Actions\GetAddresses@__invoke');
    expect($route->uri())->toEqual('addresses'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers index route for order items', function () {
    expect(Route::has('order-items.index'))->toBeTrue();
    $route = Route::getRoutes()->getByName('order-items.index');
    expect($route->getAction()['uses'])->toEqual('App\Actions\GetOrderItems@__invoke');
    expect($route->uri())->toEqual('order-items'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers index route for products', function () {
    expect(Route::has('products.index'))->toBeTrue();
    $route = Route::getRoutes()->getByName('products.index');
    expect($route->getAction()['uses'])->toEqual('App\Actions\GetProducts@__invoke');
    expect($route->uri())->toEqual('products'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers index route for photos comments', function () {
    expect(Route::has('photos.comments.index'))->toBeTrue();
    $route = Route::getRoutes()->getByName('photos.comments.index');
    expect($route->getAction()['uses'])->toEqual('App\Actions\GetPhotosComments@__invoke');
    expect($route->uri())->toEqual('photos/{photo}/comments'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers index route for users comments', function () {
    expect(Route::has('users.comments.index'))->toBeTrue();
    $route = Route::getRoutes()->getByName('users.comments.index');
    expect($route->getAction()['uses'])->toEqual('Custom\Namespace\GetUsersComments@__invoke');
    expect($route->uri())->toEqual('users/{user}/comments'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers create routes for addresses', function () {
    expect(Route::has('addresses.create'))->toBeTrue();
    $route = Route::getRoutes()->getByName('addresses.create');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowCreateAddress@__invoke');
    expect($route->uri())->toEqual('addresses/create'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers create routes for order items', function () {
    expect(Route::has('order-items.create'))->toBeTrue();
    $route = Route::getRoutes()->getByName('order-items.create');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowCreateOrderItem@__invoke');
    expect($route->uri())->toEqual('order-items/create'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('omits create routes for products', function () {
    expect(Route::has('products.create'))->toBeFalse();
});

it('registers create routes for photos comments', function () {
    expect(Route::has('photos.comments.create'))->toBeTrue();
    $route = Route::getRoutes()->getByName('photos.comments.create');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowCreatePhotosComment@__invoke');
    expect($route->uri())->toEqual('photos/{photo}/comments/create'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers create routes for users comments', function () {
    expect(Route::has('users.comments.create'))->toBeTrue();
    $route = Route::getRoutes()->getByName('users.comments.create');
    expect($route->getAction()['uses'])->toEqual('Custom\Namespace\ShowCreateUsersComment@__invoke');
    expect($route->uri())->toEqual('users/{user}/comments/create'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers store routes for addresses', function () {
    expect(Route::has('addresses.store'))->toBeTrue();
    $route = Route::getRoutes()->getByName('addresses.store');
    expect($route->getAction()['uses'])->toEqual('App\Actions\CreateAddress@__invoke');
    expect($route->uri())->toEqual('addresses'); // Assert URL structure
    expect($route->methods())->toContain('POST');
});

it('registers store routes for order items', function () {
    expect(Route::has('order-items.store'))->toBeTrue();
    $route = Route::getRoutes()->getByName('order-items.store');
    expect($route->getAction()['uses'])->toEqual('App\Actions\CreateOrderItem@__invoke');
    expect($route->uri())->toEqual('order-items'); // Assert URL structure
    expect($route->methods())->toContain('POST');
});

it('omits store routes for products', function () {
    expect(Route::has('products.store'))->toBeFalse();
});

it('registers store routes for photos comments', function () {
    expect(Route::has('photos.comments.store'))->toBeTrue();
    $route = Route::getRoutes()->getByName('photos.comments.store');
    expect($route->getAction()['uses'])->toEqual('App\Actions\CreatePhotosComment@__invoke');
    expect($route->uri())->toEqual('photos/{photo}/comments'); // Assert URL structure
    expect($route->methods())->toContain('POST');
});

it('registers store routes for users comments', function () {
    expect(Route::has('users.comments.store'))->toBeTrue();
    $route = Route::getRoutes()->getByName('users.comments.store');
    expect($route->getAction()['uses'])->toEqual('Custom\Namespace\CreateUsersComment@__invoke');
    expect($route->uri())->toEqual('users/{user}/comments'); // Assert URL structure
    expect($route->methods())->toContain('POST');
});

it('registers update routes for addresses', function () {
    expect(Route::has('addresses.update'))->toBeTrue();
    $route = Route::getRoutes()->getByName('addresses.update');
    expect($route->getAction()['uses'])->toEqual('App\Actions\UpdateAddress@__invoke');
    expect($route->uri())->toEqual('addresses/{address}'); // Assert URL structure
    expect($route->methods())->toContain('PATCH');
});

it('registers update routes for order items', function () {
    expect(Route::has('order-items.update'))->toBeTrue();
    $route = Route::getRoutes()->getByName('order-items.update');
    expect($route->getAction()['uses'])->toEqual('App\Actions\UpdateOrderItem@__invoke');
    expect($route->uri())->toEqual('order-items/{order_item}'); // Assert URL structure
    expect($route->methods())->toContain('PATCH');
});

it('omits update routes for products', function () {
    expect(Route::has('products.update'))->toBeFalse();
});

it('registers update routes for photos comments', function () {
    expect(Route::has('photos.comments.update'))->toBeTrue();
    $route = Route::getRoutes()->getByName('photos.comments.update');
    expect($route->getAction()['uses'])->toEqual('App\Actions\UpdatePhotosComment@__invoke');
    expect($route->uri())->toEqual('photos/{photo}/comments/{comment}'); // Assert URL structure
    expect($route->methods())->toContain('PATCH');
});

it('registers update routes for comments', function () {
    expect(Route::has('comments.update'))->toBeTrue();
    $route = Route::getRoutes()->getByName('comments.update');
    expect($route->getAction()['uses'])->toEqual('Custom\Namespace\UpdateComment@__invoke');
    expect($route->uri())->toEqual('comments/{comment}'); // Assert URL structure
    expect($route->methods())->toContain('PATCH');
});

it('registers destroy routes for addresses', function () {
    expect(Route::has('addresses.destroy'))->toBeTrue();
    $route = Route::getRoutes()->getByName('addresses.destroy');
    expect($route->getAction()['uses'])->toEqual('App\Actions\DeleteAddress@__invoke');
    expect($route->uri())->toEqual('addresses/{address}'); // Assert URL structure
    expect($route->methods())->toContain('DELETE');
});

it('registers destroy routes for order items', function () {
    expect(Route::has('order-items.destroy'))->toBeTrue();
    $route = Route::getRoutes()->getByName('order-items.destroy');
    expect($route->getAction()['uses'])->toEqual('App\Actions\DeleteOrderItem@__invoke');
    expect($route->uri())->toEqual('order-items/{order_item}'); // Assert URL structure
    expect($route->methods())->toContain('DELETE');
});

it('omits destroy routes for products', function () {
    expect(Route::has('products.destroy'))->toBeFalse();
});

it('registers destroy routes for photos comments', function () {
    expect(Route::has('photos.comments.destroy'))->toBeTrue();
    $route = Route::getRoutes()->getByName('photos.comments.destroy');
    expect($route->getAction()['uses'])->toEqual('App\Actions\DeletePhotosComment@__invoke');
    expect($route->uri())->toEqual('photos/{photo}/comments/{comment}'); // Assert URL structure
    expect($route->methods())->toContain('DELETE');
});

it('registers destroy routes for comments', function () {
    expect(Route::has('comments.destroy'))->toBeTrue();
    $route = Route::getRoutes()->getByName('comments.destroy');
    expect($route->getAction()['uses'])->toEqual('Custom\Namespace\DeleteComment@__invoke');
    expect($route->uri())->toEqual('comments/{comment}'); // Assert URL structure
    expect($route->methods())->toContain('DELETE');
});

it('registers show routes for addresses', function () {
    expect(Route::has('addresses.show'))->toBeTrue();
    $route = Route::getRoutes()->getByName('addresses.show');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowAddress@__invoke');
    expect($route->uri())->toEqual('addresses/{address}'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers show routes for order items', function () {
    expect(Route::has('order-items.show'))->toBeTrue();
    $route = Route::getRoutes()->getByName('order-items.show');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowOrderItem@__invoke');
    expect($route->uri())->toEqual('order-items/{order_item}'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers show routes for products', function () {
    expect(Route::has('products.show'))->toBeTrue();
    $route = Route::getRoutes()->getByName('products.show');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowProduct@__invoke');
    expect($route->uri())->toEqual('products/{product}'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers show routes for photos comments', function () {
    expect(Route::has('photos.comments.show'))->toBeTrue();
    $route = Route::getRoutes()->getByName('photos.comments.show');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowPhotosComment@__invoke');
    expect($route->uri())->toEqual('photos/{photo}/comments/{comment}'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers show route for comments', function () {
    expect(Route::has('comments.show'))->toBeTrue();
    $route = Route::getRoutes()->getByName('comments.show');
    expect($route->getAction()['uses'])->toEqual('Custom\Namespace\ShowComment@__invoke');
    expect($route->uri())->toEqual('comments/{comment}'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers edit routes for addresses', function () {
    expect(Route::has('addresses.edit'))->toBeTrue();
    $route = Route::getRoutes()->getByName('addresses.edit');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowEditAddress@__invoke');
    expect($route->uri())->toEqual('addresses/{address}/edit'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers edit routes for order items', function () {
    expect(Route::has('order-items.edit'))->toBeTrue();
    $route = Route::getRoutes()->getByName('order-items.edit');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowEditOrderItem@__invoke');
    expect($route->uri())->toEqual('order-items/{order_item}/edit'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('omits edit routes for products', function () {
    expect(Route::has('products.edit'))->toBeFalse();
});

it('registers edit routes for photos comments', function () {
    expect(Route::has('photos.comments.edit'))->toBeTrue();
    $route = Route::getRoutes()->getByName('photos.comments.edit');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowEditPhotosComment@__invoke');
    expect($route->uri())->toEqual('photos/{photo}/comments/{comment}/edit'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('registers edit routes for comments', function () {
    expect(Route::has('comments.edit'))->toBeTrue();
    $route = Route::getRoutes()->getByName('comments.edit');
    expect($route->getAction()['uses'])->toEqual('Custom\Namespace\ShowEditComment@__invoke');
    expect($route->uri())->toEqual('comments/{comment}/edit'); // Assert URL structure
    expect($route->methods())->toContain('GET');
});

it('applies middleware to a route', function () {
    // Retrieve the index route
    $route = Route::getRoutes()->getByName('addresses.index');

    // Assert middleware is applied
    expect($route->gatherMiddleware())->toContain('auth');
});

it('allows you to customise the name of each action class, but falls back to the default behaviour if unresolved', function () {
    ActionResourceRegistrar::resolveActionClassNameUsing(function (string $resource, string $method) {
        return match ($method) {
            'index' => 'ShowIndexPage',
            'create' => 'ShowCreatePage',
            'show' => 'ShowPage',
            'edit' => 'ShowEditPage',
            'store' => 'Store',
            'update' => 'Update',
            default => null,
        };
    });

    Route::actions('addresses');

    $route = Route::getRoutes()->getByName('addresses.index');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowIndexPage@__invoke');

    $route = Route::getRoutes()->getByName('addresses.create');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowCreatePage@__invoke');

    $route = Route::getRoutes()->getByName('addresses.show');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowPage@__invoke');

    $route = Route::getRoutes()->getByName('addresses.edit');
    expect($route->getAction()['uses'])->toEqual('App\Actions\ShowEditPage@__invoke');

    $route = Route::getRoutes()->getByName('addresses.store');
    expect($route->getAction()['uses'])->toEqual('App\Actions\Store@__invoke');

    $route = Route::getRoutes()->getByName('addresses.update');
    expect($route->getAction()['uses'])->toEqual('App\Actions\Update@__invoke');

    // This one uses the default definition because it was never overridden
    $route = Route::getRoutes()->getByName('addresses.destroy');
    expect($route->getAction()['uses'])->toEqual('App\Actions\DeleteAddress@__invoke');
});

namespace App\Actions;

class GetAddresses { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowCreateAddress { use \Lorisleiva\Actions\Concerns\AsAction; }
class CreateAddress { use \Lorisleiva\Actions\Concerns\AsAction; }
class UpdateAddress { use \Lorisleiva\Actions\Concerns\AsAction; }
class DeleteAddress { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowAddress { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowEditAddress { use \Lorisleiva\Actions\Concerns\AsAction; }

class GetPhotosComments { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowCreatePhotosComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class CreatePhotosComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class UpdatePhotosComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class DeletePhotosComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowPhotosComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowEditPhotosComment { use \Lorisleiva\Actions\Concerns\AsAction; }

class ShowIndexPage { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowCreatePage { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowPage { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowEditPage { use \Lorisleiva\Actions\Concerns\AsAction; }
class Store { use \Lorisleiva\Actions\Concerns\AsAction; }
class Update { use \Lorisleiva\Actions\Concerns\AsAction; }

class GetOrderItems { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowCreateOrderItem { use \Lorisleiva\Actions\Concerns\AsAction; }
class CreateOrderItem { use \Lorisleiva\Actions\Concerns\AsAction; }
class UpdateOrderItem { use \Lorisleiva\Actions\Concerns\AsAction; }
class DeleteOrderItem { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowOrderItem { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowEditOrderItem { use \Lorisleiva\Actions\Concerns\AsAction; }

class GetProducts { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowProduct { use \Lorisleiva\Actions\Concerns\AsAction; }

namespace Custom\Namespace;

class ShowComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowEditComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class UpdateComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class DeleteComment { use \Lorisleiva\Actions\Concerns\AsAction; }

class GetUsersComments { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowCreateUsersComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class CreateUsersComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class UpdateUsersComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class DeleteUsersComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowUsersComment { use \Lorisleiva\Actions\Concerns\AsAction; }
class ShowEditUsersComment { use \Lorisleiva\Actions\Concerns\AsAction; }
