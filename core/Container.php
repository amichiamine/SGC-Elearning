<?php

namespace SGC\Core;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Conteneur d'injection de dépendances simple pour SGC E-Learning.
 * Gère l'instanciation et la résolution des services de l'application.
 */
class Container
{
    /** @var array */
    protected $bindings = [];

    /** @var array */
    protected $instances = [];

    /**
     * Lie un service dans le conteneur.
     *
     * @param string $key Clé d'identification du service (généralement le nom de la classe).
     * @param Closure|string|null $resolver Le resolver pour créer l'objet.
     * @param bool $shared Indique si l'instance doit être partagée (singleton).
     */
    public function bind($key, $resolver = null, $shared = false)
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
        // Si une instance partagée existe déjà, la retourner.
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // Si aucun resolver n'est défini, on tente une résolution automatique.
        if (!isset($this->bindings[$key])) {
            return $this->resolve($key);
        }

        $binding = $this->bindings[$key]['resolver'];

        // Si le resolver est une Closure, on l'exécute.
        if ($binding instanceof Closure) {
            $object = $binding($this);
        } else {
            // Sinon, on résout la classe.
            $object = $this->resolve($binding);
        }

        // Si le service est partagé, on stocke l'instance pour les prochains appels.
        if ($this->bindings[$key]['shared']) {
            $this->instances[$key] = $object;
        }

        return $object;
    }

    /**
     * Résout une classe et ses dépendances par réflexion.
     *
     * @param string $class
     * @return mixed
     * @throws Exception
     */
    protected function resolve($class)
    {
        try {
            $reflector = new ReflectionClass($class);
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
     * Résout les dépendances d'une méthode (le constructeur ici).
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

            // On ne peut résoudre que les dépendances qui ont un type de classe.
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                // Si un paramètre sans type a une valeur par défaut, on l'utilise.
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                    continue;
                }
                throw new Exception("Impossible de résoudre le paramètre non typé {$dependency->name} dans {$dependency->getDeclaringClass()->getName()}");
            }

            // On résout récursivement la dépendance via le conteneur.
            $results[] = $this->make($type->getName());
        }

        return $results;
    }
}