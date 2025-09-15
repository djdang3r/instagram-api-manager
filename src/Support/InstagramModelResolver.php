<?php

namespace ScriptDevelop\InstagramApiManager\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class InstagramModelResolver
{
    /**
     * Devuelve una instancia de la clase del modelo resuelto desde el archivo de configuración
     */
    public static function make(string $key, array $attributes = []): \Illuminate\Database\Eloquent\Model
    {
        $class = config("instagram.models.$key");

        if (!class_exists($class)) {
            throw new InvalidArgumentException("La clase para el modelo [$key] no existe: [$class]");
        }

        return new $class($attributes);
    }

    /**
     * Forward de métodos Eloquent estáticos, como ModelResolver::message()->firstOrCreate()
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $class = config("instagram.models.$method");

        if (!class_exists($class)) {
            throw new InvalidArgumentException("No se encontró el modelo para [$method]. Verifica instagram.models.$method en tu configuración.");
        }

        return $class::query();
    }

    /**
     * Alternativa explícita: ModelResolver::resolve('contact')->firstOrCreate()
     */
    public static function resolve(string $key)
    {
        $class = config("instagram.models.$key");

        if (!class_exists($class)) {
            throw new InvalidArgumentException("No se encontró el modelo para [$key]");
        }

        return $class::query();
    }

    /**
     * Permite sustituir el modelo dinámicamente (conveniente para testing).
     */
    public static function fake(string $key, string $class): void
    {
        config()->set("instagram.models.$key", $class);
    }
}
