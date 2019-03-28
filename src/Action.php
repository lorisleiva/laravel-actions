<?php

namespace Lorisleiva\Actions;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ReflectionClass;
use ReflectionProperty;

abstract class Action extends Controller
{
    use Concerns\DependencyResolver;
    use Concerns\HasAttributes;
    use Concerns\ValidatesAttributes;
    use Concerns\VerifyAuthorization;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function __invoke(Request $request)
    {
        return $this->runAsController($request);
    }

    public function runAsController(Request $request)
    {
        $this->fill($this->getAttributesFromRequest($request));

        $this->resolveAuthorization();
        $this->resolveValidation();
        $result = $this->resolveHandle();

        return method_exists($this, 'response') ? $this->response($result, $request) : $result;
    }

    public function runAsListener()
    {
        $this->fill($this->resolveAttributesFromEvent(...func_get_args()));

        $this->resolveAuthorization();
        $this->resolveValidation();
        return $this->resolveHandle();
    }

    public function run()
    {
        $this->resolveAuthorization();
        $this->resolveValidation();
        return $this->resolveHandle();
    }

    public function resolveHandle()
    {
        $parameters = $this->resolveMethodDependencies($this, 'handle');

        return $this->handle(...$parameters);
    }

    public function getAttributesFromRequest(Request $request)
    {
        return array_merge(
            $request->route()->parametersWithoutNulls(),
            $request->all()
        );
    }

    public function resolveAttributesFromEvent($event = null)
    {
        if (method_exists($this, 'getAttributesFromEvent')) {
            return $this->getAttributesFromEvent(...func_get_args());
        }

        if ($event && is_object($event)) {
            return $this->getPublicPropertiesOfEvent($event);
        }
        
        return [];
    }

    protected function getPublicPropertiesOfEvent($event)
    {
        $class = new ReflectionClass(get_class($event));
        $attributes = [];

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes[$property->name] = $property->getValue($event);
        }

        return $attributes;
    }
}