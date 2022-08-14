<?php

namespace Lorisleiva\Actions\Tests;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Lorisleiva\Actions\Contracts\BaseActionContract;
use Reflection;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

interface DoesNothing extends BaseActionContract
{
    public function handle(): string;
}

class DoNothing
{
    use AsAction;

    public function handle(): string
    {
        return 'did nothing';
    }
}

class FailToImplementHandle
{
    use AsAction;
}

class FailsToUseConcern
{
    public function handle(): string
    {
        return 'did nothing';
    }
}

it('actually does something, sanity check for base feature functionality', function () {
    $baseContractReflection = new ReflectionClass(BaseActionContract::class);
    $doesNothingReflection = new ReflectionClass(DoesNothing::class);
    $concernReflection = new ReflectionClass(FailsToUseConcern::class);

    $unimplementedMethods = getClassMethodStubs($doesNothingReflection)->diff(
        getClassMethodStubs($concernReflection)->toArray(),
    );

    $this->assertCount(
        count($baseContractReflection->getMethods()),
        $unimplementedMethods,
    );
});

it('fulfills the base interface', function () {
    $doNothingReflection = new ReflectionClass(DoNothing::class);
    $doesNothingReflection = new ReflectionClass(DoesNothing::class);

    $unimplementedMethods = getClassMethodStubs($doesNothingReflection)->diff(
        getClassMethodStubs($doNothingReflection)->toArray(),
    );

    $errorMessage = 'Missing concrete implementations in the AsAction concern for the following methods in the BaseActionContract. Please add an implementation or update the contract.
[
    ' . implode('
    ', $unimplementedMethods->toArray()) . '
]';

    $this->assertCount(0, $unimplementedMethods, $errorMessage);
});

it('checks for extra methods defined on service contract', function () {
    $doesNothingReflection = new ReflectionClass(DoesNothing::class);
    $handleFailReflection = new ReflectionClass(FailToImplementHandle::class);

    $unimplementedMethods = getClassMethodStubs($doesNothingReflection)->diff(
        getClassMethodStubs($handleFailReflection)->toArray(),
    );

    $this->assertCount(
        1,
        $unimplementedMethods,
        'More or less than one method is missing from the failToImplementHandle class',
    );

    $this->assertSame('public function handle(): string;', $unimplementedMethods->first());
});

/**
 * Take a class reflection and return string interpolations for all
 * methods. Removes abstract declarations from interface classes
 * Seems like there should be a better way to check validity.
 */
function getClassMethodStubs(ReflectionClass $reflection): Collection
{
    $isInterface = $reflection->isInterface();

    return collect($reflection->getMethods())->map(function (ReflectionMethod $method) use ($isInterface) {
        $modifiers = implode(' ', Reflection::getModifierNames($method->getModifiers()));
        $name = $method->getName();
        $return = is_null($method->getReturnType()) ? '' : ': ' . $method->getReturnType()->getName();
        $parameters = '';

        if (count($method->getParameters()) > 0) {
            $parameters = implode(
                ', ',
                collect($method->getParameters())->map(
                    function (ReflectionParameter $parameter) {
                        $type = is_null($parameter->getType()) ? '' : $parameter->getType()->getName() .  ' ';
                        $variadic = $parameter->isVariadic() ? '...' : '';
                        return $type . $variadic . '$' . $parameter->getName();
                    }
                )->toArray(),
            );
        }

        $concatenated = "$modifiers function $name($parameters)$return;";
        return $isInterface ? str_replace('abstract ', '', $concatenated) : $concatenated;
    });
};
