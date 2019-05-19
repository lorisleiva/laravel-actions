# laravel-actions
⚡️ [WIP] Laravel components that take care of one specific task

**Work in progress: do not use.**

## Installation

TODO

## Usage

```php
class PublishANewArticle extends Action
{
    public function authorize()
    {
        return $this->can('create', Article::class);
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

### As an object

```php
$action = new PublishANewArticle([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum',
]);

$action->run();
```

### As a job

```php
PublishANewArticle::dispatch([
    'title' => 'My blog post',
    'body' => 'Lorem ipsum',
]);
```

Using ShouldQueue

```php
use Illuminate\Contracts\Queue\ShouldQueue;

class PublishANewArticle extends Action implements ShouldQueue
{
    // ...
}
```

### As a listener

```php
TODO
```


### As a controller

```php
TODO
```
