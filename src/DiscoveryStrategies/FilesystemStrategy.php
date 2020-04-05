<?php


namespace Lorisleiva\Actions\DiscoveryStrategies;


use Illuminate\Support\Collection;
use Lorisleiva\Actions\Action;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FilesystemStrategy implements ActionDiscoveryStrategy
{
    /**
     * @var array
     */
    private $folders;

    /**
     * FilesystemDiscovery constructor.
     * @param array $folders
     */
    public function __construct(array $folders = [])
    {
        $this->folders = $folders;
    }

    public function getActionClasses(): Collection
    {
        if (empty($this->folders)) {
            return collect();
        }
        $finder = Finder::create()
            ->in($this->folders)
            ->ignoreDotFiles(true)
            ->name('*.php')
            ->files();
        return collect(iterator_to_array($finder))
            ->map(function (SplFileInfo $fileInfo) {
                $namespace = $this->getFileNamespace($fileInfo->getPathname());
                if (!$namespace) {
                    return null;
                }
                $classname = $fileInfo->getBasename('.php');
                return sprintf('%s\\%s', $namespace, $classname);
            })->filter(function (string $fqn) {
                return $fqn !== null && is_subclass_of($fqn, Action::class);
            })->values();
    }

    private function getFileNamespace($path)
    {
        $tokens = token_get_all(file_get_contents($path));
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }

        if (!$namespace_ok) {
            return null;
        }

        return $namespace;
    }
}
