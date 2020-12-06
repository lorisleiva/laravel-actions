<?php

namespace Lorisleiva\Actions\Tests;

use Lorisleiva\Actions\Concerns\AsJob;

class AsJobWithCustomNameAndTagsTest
{
    use AsJob;

    public function handle()
    {
        //
    }

    public function getJobDisplayName(): string
    {
        return 'My custom job name';
    }

    public function getJobTags(): array
    {
        return [
            'my_custom_tag_1',
            'my_custom_tag_2',
        ];
    }
}

it('can customise the job display name', function () {
    // When we make a job from the action.
    $job = AsJobWithCustomNameAndTagsTest::makeJob();

    // Then it uses the display name provided on the action.
    expect($job->displayName())->toBe('My custom job name');
});

it('can customise the job tags', function () {
    // When we make a job from the action.
    $job = AsJobWithCustomNameAndTagsTest::makeJob();

    // Then it uses the tags provided on the action.
    expect($job->tags())->toBe([
        'my_custom_tag_1',
        'my_custom_tag_2',
    ]);
});
