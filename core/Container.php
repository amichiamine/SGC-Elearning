<?php

namespace SGC\Core;

use Closure;
use Exception;

/**
 * Conteneur d'injection de dépendances simple
 * Gère l'instanciation et la résolution des services de l'application.
 */
class Container
{
    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @var array
     */
    protected $instances = [];

    /**
     * Lie un service dans le conteneur.
     *
     * @param string $key
     * @param Closure|string|null $resolver
     * @param bool $shared
     */
    public function bind($key, $resolver = null, $shared = true)
    {
        if (is_null($resolver)) {
            $resolver = $key;
        }

        $this->bindings[$key] = compact('resolver', 'shared');
    }

    /**
     * Lie un service partagé (singleton) dans le conteneur.
     *
     * @param string $key
     * @param Closure|string|null $resolver
     */
    public function singleton($key, $resolver = null)
    {
        $this->bind($key, $resolver, true);
    }

    /**
     * Résout un service du conteneur.
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function make($key)
    {
        // Si l'instance partagée existe déjà, la retourner.
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // Si le binding n'existe pas, essayer de le résoudre automatiquement.
        if (!isset($this->bindings[$key])) {
            return $this->resolveClass($key);
        }

        $binding = $this->bindings[$key];
        $resolver = $binding['resolver'];

        // Si le resolver est une classe, la résoudre.
        if ($resolver instanceof Closure) {
            $object = $resolver($this);
        } else {
            $object = $this->resolveClass($resolver);
        }

        // Si le service est partagé, stocker l'instance.
        if ($binding['shared']) {
            $this->instances[$key] = $object;
        }

        return $object;
    }

    /**
     * Résout une classe et ses dépendances.
     *
     * @param string $class
     * @return mixed
     * @throws Exception
     */
    protected function resolveClass($class)
    {
        try {
            $reflector = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new Exception("La classe de destination [$class] n'existe pas.", 0, $e);
        }

        // Si la classe n'est pas instanciable, on ne peut pas continuer.
        if (!$reflector->isInstantiable()) {
            throw new Exception("La classe [$class] n'est pas instanciable.");
        }

        $constructor = $reflector->getConstructor();

        // Si pas de constructeur, on peut juste créer une nouvelle instance.
        if (is_null($constructor)) {
            return new $class;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Résout les dépendances d'une méthode.
     *
     * @param \ReflectionParameter[] $dependencies
     * @return array
     * @throws Exception
     */
    protected function resolveDependencies(array $dependencies)
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $type = $dependency->getType();

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                // Ne peut pas résoudre les dépendances sans type ou primitives.
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }
                throw new Exception("Impossible de résoudre le paramètre non typé {$dependency->name} dans {$dependency->getDeclaringClass()->getName()}");
            }

            $results[] = $this->make($type->getName());
        }

        return $results;
    }

    /**
     * Accesseur magique pour résoudre les services.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->make($key);
    }
}