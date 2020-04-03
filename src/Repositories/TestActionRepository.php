<?php


namespace Lorisleiva\Actions\Repositories;


use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class TestActionRepository implements ActionRepository
{
    public function all(): array
    {
        $filesystem = new Filesystem();
        return collect($filesystem->files(__DIR__ . '/../../tests/Actions'))
            ->map(function (SplFileInfo $file) {
                return 'Lorisleiva\\Actions\\Tests\\Actions\\' . $file->getBasename('.php');
            })->toArray();
    }
}
