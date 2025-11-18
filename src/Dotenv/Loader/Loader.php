<?php

declare(strict_types=1);

namespace Dotenv\Loader;

use Dotenv\Parser\Entry;
use Dotenv\Parser\Value;
use Dotenv\Repository\RepositoryInterface;

require_once __DIR__ . '/../Repository/RepositoryInterface.php';

final class Loader implements LoaderInterface
{
    /**
     * Load the given entries into the repository.
     *
     * We'll substitute any nested variables, and send each variable to the
     * repository, with the effect of actually mutating the environment.
     *
     * @param \Dotenv\Repository\RepositoryInterface $repository
     * @param \Dotenv\Parser\Entry[]                 $entries
     *
     * @return array<string, string|null>
     */
    public function load(RepositoryInterface $repository, array $entries)
    {
        /** @var array<string, string|null> */
        require_once __DIR__ . '/../Parser/Entry.php';
        return \array_reduce($entries, static function (array $vars, Entry $entry) use ($repository) {
            $name = $entry->getName();

            require_once __DIR__ . '/../Parser/Value.php';
            $value = $entry->getValue()->map(static function (Value $value) use ($repository) {
                require_once __DIR__ . '/Resolver.php';
                return Resolver::resolve($repository, $value);
            });

            if ($value->isDefined()) {
                $inner = $value->get();
                if ($repository->set($name, $inner)) {
                    return \array_merge($vars, [$name => $inner]);
                }
            } else {
                if ($repository->clear($name)) {
                    return \array_merge($vars, [$name => null]);
                }
            }

            return $vars;
        }, []);
    }
}
