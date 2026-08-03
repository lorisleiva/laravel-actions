<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Routing\ActionResourceRegistrar;

beforeEach(function () {
    // Allows us to test routes with a single word
    Route::resourceActions('addresses')->middleware('auth');

    // Allows us to test routes with kebab-case words
    Route::resourceActions('order-items');

    // Allows us to test routes where we specify the actions
    Route::resourceActions('products')->only('index', 'show');

    // Allows us to test nesting
    Route::resourceActions('photos.comments');

    // Allows us to test shallow nesting, with a custom namespace
    Route::resourceActions('users.comments', 'Custom\Namespace')->shallow();
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

it('allows you to override the default action naming conventions', function () {
    ActionResourceRegistrar::resolveResourceAction('index', function (string $resource) {
        return 'GetAll'.ucfirst($resource);
    });

    ActionResourceRegistrar::resolveResourceAction('create', function (string $resource) {
        return 'Create'.Str::singular(ucfirst($resource));
    });

    ActionResourceRegistrar::resolveResourceAction('show', function (string $resource) {
        return 'Get'.Str::singular(ucfirst($resource));
    });

    ActionResourceRegistrar::resolveResourceAction('edit', function (string $resource) {
        return 'Edit'.Str::singular(ucfirst($resource));
    });

    ActionResourceRegistrar::resolveResourceAction('store', function (string $resource) {
        return 'Store'.Str::singular(ucfirst($resource));
    });

    ActionResourceRegistrar::resolveResourceAction('update', function (string $resource) {
        return 'Patch'.Str::singular(ucfirst($resource));
    });

    ActionResourceRegistrar::resolveResourceAction('destroy', function (string $resource) {
        return 'Destroy'.Str::singular(ucfirst($resource));
    });

    Route::resourceActions('addresses');

    $route = Route::getRoutes()->getByName('addresses.index');
    expect($route->getAction()['uses'])->toEqual('App\Actions\GetAllAddresses@__invoke');

    $route = Route::getRoutes()->getByName('addresses.create');
    expect($route->getAction()['uses'])->toEqual('App\Actions\CreateAddress@__invoke');

    $route = Route::getRoutes()->getByName('addresses.show');
    expect($route->getAction()['uses'])->toEqual('App\Actions\GetAddress@__invoke');

    $route = Route::getRoutes()->getByName('addresses.edit');
    expect($route->getAction()['uses'])->toEqual('App\Actions\EditAddress@__invoke');

    $route = Route::getRoutes()->getByName('addresses.store');
    expect($route->getAction()['uses'])->toEqual('App\Actions\StoreAddress@__invoke');

    $route = Route::getRoutes()->getByName('addresses.update');
    expect($route->getAction()['uses'])->toEqual('App\Actions\PatchAddress@__invoke');

    // This one uses the default definition because it was never overridden
    $route = Route::getRoutes()->getByName('addresses.destroy');
    expect($route->getAction()['uses'])->toEqual('App\Actions\DestroyAddress@__invoke');
});

namespace App\Actions;

use Lorisleiva\Actions\Concerns\AsAction;

class GetAddresses { use AsAction; }
class ShowCreateAddress { use AsAction; }
class CreateAddress { use AsAction; }
class UpdateAddress { use AsAction; }
class DeleteAddress { use AsAction; }
class ShowAddress { use AsAction; }
class ShowEditAddress { use AsAction; }

class GetPhotosComments { use AsAction; }
class ShowCreatePhotosComment { use AsAction; }
class CreatePhotosComment { use AsAction; }
class UpdatePhotosComment { use AsAction; }
class DeletePhotosComment { use AsAction; }
class ShowPhotosComment { use AsAction; }
class ShowEditPhotosComment { use AsAction; }

class GetAllAddresses { use AsAction; }
class StoreAddress { use AsAction; }
class GetAddress { use AsAction; }
class EditAddress { use AsAction; }
class PatchAddress { use AsAction; }
class DestroyAddress { use AsAction; }

class GetOrderItems { use AsAction; }
class ShowCreateOrderItem { use AsAction; }
class CreateOrderItem { use AsAction; }
class UpdateOrderItem { use AsAction; }
class DeleteOrderItem { use AsAction; }
class ShowOrderItem { use AsAction; }
class ShowEditOrderItem { use AsAction; }

class GetProducts { use AsAction; }
class ShowProduct { use AsAction; }

namespace Custom\Namespace;

use Lorisleiva\Actions\Concerns\AsAction;

class ShowComment { use AsAction; }
class ShowEditComment { use AsAction; }
class UpdateComment { use AsAction; }
class DeleteComment { use AsAction; }

class GetUsersComments { use AsAction; }
class ShowCreateUsersComment { use AsAction; }
class CreateUsersComment { use AsAction; }
class UpdateUsersComment { use AsAction; }
class DeleteUsersComment { use AsAction; }
class ShowUsersComment { use AsAction; }
class ShowEditUsersComment { use AsAction; }
