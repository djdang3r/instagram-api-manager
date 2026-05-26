# Unificar ApiClient — Una sola capa HTTP compartida

## TL;DR
> **Resumen**: Refactorizar los 8 `new ApiClient(...)` repartidos por el código en un solo cliente Guzzle compartido + instancias transient de ApiClient con configuración fluida por servicio. Actualizar versión API por defecto a v25.0.
> **Entregables**: 2 configs actualizados, ApiClient modificado, ServiceProvider actualizado, 4 servicios refactorizados, archivo de tests eliminado.
> **Esfuerzo**: Corto
> **Paralelo**: SÍ — 4 olas
> **Ruta Crítica**: Tarea 0 → Tarea 1 → Tarea 2 → Tareas 3,4,5,6 (en paralelo) → Tarea 7

## Contexto
### Pedido Original
El usuario identificó que `ApiClient` se instancia con `new` en 8 lugares distintos a lo largo de los servicios, causando defaults inconsistentes, sin pool de conexiones HTTP compartido, y código imposible de mockear en tests. El objetivo: unificar en un solo cliente Guzzle compartido pero preservando distintas URLs base y versiones por servicio.

### Resumen de la Entrevista
- **Tests**: El archivo de tests existente (`tests/Feature/InstagramWebhookMessagesTest.php`) se elimina. Los tests se ejecutan desde el proyecto Laravel que consume el paquete, no desde acá.
- **Facebook**: Unificación total — el servicio de Facebook usa el mismo ApiClient con overrides fluidos `->withBaseUrl()->withVersion()`.
- **Sin tests nuevos**: No se escriben tests nuevos, solo se borran los existentes.

## Objetivos del Trabajo
### Objetivo Central
Reemplazar los 8 `new ApiClient(...)` por instancias resueltas desde el contenedor que comparten un mismo cliente HTTP Guzzle. Cada servicio configura su propia `baseUrl` y `version` mediante métodos fluidos.

### Entregables
- [ ] `ApiClient` acepta Guzzle Client por inyección en el constructor
- [ ] `ApiClient` tiene métodos fluidos `withBaseUrl()` y `withVersion()`
- [ ] Singleton de Guzzle Client registrado en `InstagramServiceProvider`
- [ ] Binding transient de `ApiClient` registrado en `InstagramServiceProvider`
- [ ] 4 servicios actualizados: `InstagramMessageService`, `InstagramAccountService`, `InstagramPersistentMenuService`, `FacebookAccountService`
- [ ] Archivo de tests eliminado
- [ ] Cero `new ApiClient(...)` restantes en `src/`

### Definition of Done
```bash
# Sin `new ApiClient(` en src/ excepto dentro del ServiceProvider
rg "new ApiClient\(" src/ --count
# Esperado: 0 (o 1 si el ServiceProvider lo crea como factory del binding)

# Guzzle Client compartido — el constructor de ApiClient usa DI
rg "new Client\(" src/ --count
# Esperado: 0 (solo en el ServiceProvider)

# Todos los servicios inyectan ApiClient por constructor
rg "app\(ApiClient::class\)" src/Services/ -l
```

### Debe Tener
- Guzzle Client compartido (un solo pool de conexiones)
- Configuración baseUrl/version por servicio vía API fluida
- **TODOS los servicios usan la MISMA versión de API**: leída desde `config('instagram.api.version')` sin hardcodeos. El valor lo define `INSTAGRAM_API_VERSION` en el `.env` del proyecto consumidor.
- Facebook usa `config('facebook.api.version')` para su propia versión
- Cero cambios de comportamiento en requests existentes

### NO Debe Tener
- ApiClient singleton (debe ser transient — cada servicio necesita su propia baseUrl/version)
- Cambios en la firma del método `request()`
- Cambios en archivos de configuración
- Nuevas keys de configuración
- Tests nuevos (por pedido del usuario)
- Ediciones en `tests/` excepto eliminación

## Estrategia de Verificación
> CERO INTERVENCIÓN HUMANA — toda verificación es ejecutada por agentes.
- Decisión de tests: **ninguno** (usuario: tests se hacen desde proyecto consumidor)
- Política QA: Cada tarea tiene escenarios ejecutados por agente vía `rg`/`bash`
- Evidencia: `.sisyphus/evidence/task-{N}-{slug}.txt`

## Estrategia de Ejecución
### Olas de Ejecución en Paralelo
Ola 0: [Versión] Tarea 0 (actualizar configs a v25.0)
Ola 1: [Fundación] Tarea 1 (cambios en ApiClient) + Tarea 2 (ServiceProvider)
Ola 2: [Refactor de Servicios] Tareas 3, 4, 5, 6 en paralelo (una por archivo de servicio)
Ola 3: [Limpieza] Tarea 7 (eliminar tests)

### Matriz de Dependencias
| Tarea | Puede Paralelo | Ola | Bloquea | Bloqueada Por |
|-------|---------------|-----|---------|---------------|
| 0 | NO (va primero) | 0 | 1,2 | — |
| 1 | SÍ (con 2) | 1 | 3,4,5,6 | 0 |
| 2 | SÍ (con 1) | 1 | 3,4,5,6 | 0 |
| 3 | SÍ (con 4,5,6) | 2 | — | 1,2 |
| 4 | SÍ (con 3,5,6) | 2 | — | 1,2 |
| 5 | SÍ (con 3,4,6) | 2 | — | 1,2 |
| 6 | SÍ (con 3,4,5) | 2 | — | 1,2 |
| 7 | NO | 3 | — | 3,4,5,6 |

## TODOs

- [ ] 0. Actualizar versión por defecto de la API a v25.0 en archivos de configuración

  **Qué hacer**: La versión más reciente de Instagram/Facebook Graph API es `v25.0` (Mayo 2026). Actualizar los valores por defecto:
  1. `config/instagram.php` línea 56: cambiar `'v19.0'` → `'v25.0'`
  2. `config/facebook.php` línea 11: cambiar `'v19.0'` → `'v25.0'`

  **NO debe hacer**:
  - NO cambiar las keys de configuración, solo los valores por defecto
  - NO tocar nada más en estos archivos

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: []

  **Paralelización**: Puede Paralelo: SÍ | Ola 0 | Bloquea: [] | Bloqueada Por: —

  **Referencias**:
  - Fuente: `https://developers.facebook.com/docs/instagram-platform/reference/instagram-media/` — documentación oficial usa `v25.0`
  - Archivo: `config/instagram.php` línea 56: `'version' => env('INSTAGRAM_API_VERSION', 'v19.0')`
  - Archivo: `config/facebook.php` línea 11: `'version' => env('FACEBOOK_API_VERSION', 'v19.0')`

  **Criterios de Aceptación**:
  - [ ] `config/instagram.php` tiene `'v25.0'` como fallback de `INSTAGRAM_API_VERSION`
  - [ ] `config/facebook.php` tiene `'v25.0'` como fallback de `FACEBOOK_API_VERSION`

  **Escenarios QA**:
  ```
  Escenario: Versión por defecto actualizada
    Herramienta: Bash
    Pasos: rg "v25\.0" config/instagram.php config/facebook.php
    Esperado: 2 coincidencias (una en cada archivo)
    Evidencia: .sisyphus/evidence/task-0-version.txt
  ```

  **Commit**: SÍ | Mensaje: `chore: actualizar versión por defecto de API a v25.0 (última disponible)` | Archivos: [`config/instagram.php`, `config/facebook.php`]

- [ ] 1. Mejorar ApiClient — Aceptar Guzzle por inyección + agregar overrides fluidos

  **Qué hacer**: Modificar `src/InstagramApi/ApiClient.php`:
  1. Cambiar el constructor para que acepte un parámetro opcional `Client $httpClient = null`. Si es null, crear un cliente Guzzle por defecto internamente (retrocompatibilidad). Si se provee, usarlo.
  2. Agregar `withBaseUrl(string $baseUrl): static` — establece `$this->baseUrl`, retorna `$this`.
  3. Agregar `withVersion(string $version): static` — establece `$this->version`, retorna `$this`.
  4. Mantener el parámetro `$customBaseUrl` en `request()` por retrocompatibilidad; `withBaseUrl()` solo cambia el default.
  5. Mantener el parámetro `$timeout` en el constructor por retrocompatibilidad — al crear un Guzzle por defecto, usarlo.

  **NO debe hacer**:
  - NO cambiar la firma del método `request()` para nada
  - NO eliminar el parámetro `$timeout` del constructor (retrocompatibilidad)
  - NO hacer que `$httpClient` sea requerido — es opcional

  **Perfil de Agente Recomendado**:
  - Categoría: `quick` — un solo archivo, cambios quirúrgicos
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ | Ola 1 | Bloquea: [3,4,5,6] | Bloqueada Por: —

  **Referencias** (el ejecutor NO tiene contexto de entrevista):
  - Archivo: `src/InstagramApi/ApiClient.php` — archivo completo, líneas 1-167
  - Constructor actual: `public function __construct(string $baseUrl, string $version = '', int $timeout = 30)`
  - Import de Guzzle: `use GuzzleHttp\Client;`
  - Patrón: métodos `with*()` fluidos ya usados en `InstagramMessageService` (líneas 36-45) — seguir el mismo patrón `return $this`

  **Criterios de Aceptación**:
  - [ ] Constructor acepta `?Client $httpClient = null` como 4to parámetro opcional
  - [ ] `withBaseUrl(string $baseUrl): static` existe y retorna `$this`
  - [ ] `withVersion(string $version): static` existe y retorna `$this`
  - [ ] La creación por defecto de Guzzle Client sigue funcionando cuando no se inyecta
  - [ ] Todos los callers existentes (con 3 params) siguen compilando

  **Escenarios QA**:
  ```
  Escenario: Inyección de Guzzle funciona
    Herramienta: Bash
    Pasos: rg "new ApiClient\(" src/ | head -5
    Esperado: Los call sites existentes (con constructor de 3 params) siguen compilando
    Evidencia: .sisyphus/evidence/task-1-api-client.txt

  Escenario: Métodos fluidos encadenan correctamente
    Herramienta: Bash
    Pasos: rg "function withBaseUrl|function withVersion" src/InstagramApi/ApiClient.php -A 3
    Esperado: Ambos métodos retornan tipo `static` y retornan `$this`
    Evidencia: .sisyphus/evidence/task-1-fluent.txt
  ```

  **Commit**: SÍ | Mensaje: `refactor(api): agregar inyección de Guzzle y métodos fluidos baseUrl/version a ApiClient` | Archivos: [`src/InstagramApi/ApiClient.php`]

- [ ] 2. Registrar Guzzle Client singleton + binding transient de ApiClient en ServiceProvider

  **Qué hacer**: Modificar `src/Providers/InstagramServiceProvider.php`:
  1. En el método `register()`, agregar un binding singleton para `GuzzleHttp\Client`:
     ```php
     $this->app->singleton(Client::class, function ($app) {
         return new Client([
             'timeout' => (int) config('instagram.api.timeout', 30),
             'connect_timeout' => 10,
         ]);
     });
     ```
  2. Agregar un binding para `ApiClient::class` como transient (no singleton):
     ```php
     $this->app->bind(ApiClient::class, function ($app) {
         return new ApiClient(
             config('instagram.api.graph_base_url', 'https://graph.facebook.com'),
             config('instagram.api.version'),
             (int) config('instagram.api.timeout', 30),
             $app->make(Client::class)  // ← Guzzle compartido
         );
     });
     ```
     **IMPORTANTE**: `config('instagram.api.version')` se lee de `INSTAGRAM_API_VERSION` en el `.env`. No se hardcodea ningún fallback — el valor por defecto está en `config/instagram.php` (`v19.0`). Para usar la versión más reciente, el usuario configura `INSTAGRAM_API_VERSION=v23.0` en su `.env`.
  3. Agregar los imports `use` necesarios al inicio del archivo

  **NO debe hacer**:
  - NO eliminar ninguno de los bindings existentes en el provider
  - NO cambiar el orden de los métodos existentes

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ | Ola 1 | Bloquea: [3,4,5,6] | Bloqueada Por: —

  **Referencias**:
  - Archivo: `src/Providers/InstagramServiceProvider.php` — líneas 20-99, método `register()`
  - Import Guzzle: `use GuzzleHttp\Client;`
  - Import ApiClient: `use ScriptDevelop\InstagramApiManager\InstagramApi\ApiClient;`
  - Patrón existente: `$this->app->singleton('instagram.account', ...)` en línea 26

  **Criterios de Aceptación**:
  - [ ] `GuzzleHttp\Client` está bindeado como singleton en el contenedor
  - [ ] `ApiClient::class` está bindeado como transient (no singleton) en el contenedor
  - [ ] `app(ApiClient::class)` retorna una nueva instancia en cada llamada
  - [ ] `app(Client::class)` retorna la misma instancia en cada llamada
  - [ ] Los bindings existentes (`instagram`, `facebook`, etc.) siguen funcionando

  **Escenarios QA**:
  ```
  Escenario: Guzzle Client es singleton
    Herramienta: Bash
    Pasos: rg "singleton.*Client::class" src/Providers/InstagramServiceProvider.php -A 5
    Esperado: Binding singleton encontrado retornando new Client con timeout config
    Evidencia: .sisyphus/evidence/task-2-guzzle.txt

  Escenario: ApiClient es transient  
    Herramienta: Bash
    Pasos: rg "bind\(ApiClient::class" src/Providers/InstagramServiceProvider.php -A 8
    Esperado: Binding transient encontrado, usa $app->make(Client::class)
    Evidencia: .sisyphus/evidence/task-2-apiclient.txt
  ```

  **Commit**: SÍ | Mensaje: `refactor(provider): registrar Guzzle Client compartido y binding transient de ApiClient` | Archivos: [`src/Providers/InstagramServiceProvider.php`]

- [ ] 3. Refactorizar InstagramMessageService — Constructor + 1 instanciación interna

  **Qué hacer**: Modificar `src/Services/InstagramMessageService.php`:
  1. **Constructor (líneas 20-31)**: Reemplazar `new ApiClient(...)` por `app(ApiClient::class)->withBaseUrl(...)->withVersion(...)`:
     ```php
     $this->apiClient = app(ApiClient::class)
         ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.facebook.com'))
         ->withVersion(config('instagram.api.version'));
     ```
  2. **Método fetchContactProfile() (líneas 692-696)**: Reemplazar el `new ApiClient(...)` temporal por:
     ```php
     $basicClient = app(ApiClient::class)
         ->withBaseUrl($baseUrl)
         ->withVersion($version);
     ```
  3. Eliminar el parámetro `(int) config('instagram.api.timeout', 30)` porque ahora lo maneja el Guzzle compartido

  **NO debe hacer**:
  - NO cambiar el parámetro `$accountService` ni su inicialización
  - NO cambiar la lógica de ningún método — solo cómo se obtiene ApiClient
  - NO eliminar la propiedad `$this->apiClient`

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ | Ola 2 | Bloquea: [] | Bloqueada Por: [1,2]

  **Referencias**:
  - Archivo: `src/Services/InstagramMessageService.php`
  - Líneas 23-27: `$this->apiClient = new ApiClient(config(...), config(...), (int) config(...));`
  - Líneas 692-696: `$basicClient = new ApiClient($baseUrl, $version, (int) config(...));`
  - Patrón a seguir: `app(ApiClient::class)->withBaseUrl($url)->withVersion($ver)`

  **Criterios de Aceptación**:
  - [ ] Constructor usa `app(ApiClient::class)->withBaseUrl(...)->withVersion(...)` en vez de `new ApiClient(...)`
  - [ ] `fetchContactProfile()` usa `app(ApiClient::class)->withBaseUrl($baseUrl)->withVersion($version)` en vez de `new ApiClient(...)`
  - [ ] Cero `new ApiClient(` restantes en este archivo

  **Escenarios QA**:
  ```
  Escenario: Sin `new ApiClient` en InstagramMessageService
    Herramienta: Bash
    Pasos: rg "new ApiClient\(" src/Services/InstagramMessageService.php
    Esperado: Sin coincidencias (exit code 1 o vacío)
    Evidencia: .sisyphus/evidence/task-3-message-service.txt

  Escenario: Todos los accesos a ApiClient usan contenedor
    Herramienta: Bash
    Pasos: rg "app\(ApiClient::class\)" src/Services/InstagramMessageService.php --count
    Esperado: 2 coincidencias (constructor + fetchContactProfile)
    Evidencia: .sisyphus/evidence/task-3-message-app.txt
  ```

  **Commit**: SÍ | Mensaje: `refactor(message): usar ApiClient resuelto por contenedor con configuración fluida` | Archivos: [`src/Services/InstagramMessageService.php`]

- [ ] 4. Refactorizar InstagramAccountService — Constructor + 3 instanciaciones internas

  **Qué hacer**: Modificar `src/Services/InstagramAccountService.php`:
  1. **Constructor (líneas 18-25)**: Reemplazar `new ApiClient(...)` por:
     ```php
     $this->apiClient = app(ApiClient::class)
         ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
         ->withVersion(config('instagram.api.version'));
     ```
  2. **Método handleCallback() (líneas 193-198)**: Cliente OAuth con URL base explícita + sin versión:
     ```php
     $oauthClient = app(ApiClient::class)
         ->withBaseUrl($oauthBaseUrl)
         ->withVersion('');
     ```
  3. **Método exchangeForLongLivedToken() (líneas 356-360)**: Cliente exchange con URL graph + sin versión:
     ```php
     $exchangeClient = app(ApiClient::class)
         ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
         ->withVersion('');
     ```
  4. **Método refreshLongLivedToken() (líneas 418-422)**: Cliente refresh con URL graph + sin versión:
     ```php
     $refreshClient = app(ApiClient::class)
         ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.instagram.com'))
         ->withVersion('');
     ```

  **NO debe hacer**:
  - NO cambiar la lógica de ningún método — solo cómo se obtiene ApiClient
  - NO cambiar la lógica de transacciones de BD ni del flujo OAuth
  - NO eliminar la propiedad `$this->apiClient`

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ | Ola 2 | Bloquea: [] | Bloqueada Por: [1,2]

  **Referencias**:
  - Archivo: `src/Services/InstagramAccountService.php` — archivo completo (490 líneas)
  - Constructor: líneas 18-25 → `$this->apiClient = new ApiClient(...)`
  - `handleCallback()` cliente OAuth: líneas 193-198
  - `exchangeForLongLivedToken()`: líneas 356-360
  - `refreshLongLivedToken()`: líneas 418-422

  **Criterios de Aceptación**:
  - [ ] Constructor usa `app(ApiClient::class)->withBaseUrl(...)->withVersion(...)`
  - [ ] 3 instancias internas de ApiClient usan `app(ApiClient::class)->withBaseUrl(...)->withVersion(...)`
  - [ ] Cliente OAuth tiene `->withVersion('')` explícito (sin prefijo de versión)
  - [ ] Cero `new ApiClient(` restantes en este archivo

  **Escenarios QA**:
  ```
  Escenario: Sin `new ApiClient` en InstagramAccountService
    Herramienta: Bash
    Pasos: rg "new ApiClient\(" src/Services/InstagramAccountService.php
    Esperado: Sin coincidencias
    Evidencia: .sisyphus/evidence/task-4-account-no-new.txt

  Escenario: Los 4 clientes usan contenedor
    Herramienta: Bash
    Pasos: rg "app\(ApiClient::class\)" src/Services/InstagramAccountService.php --count
    Esperado: 4 coincidencias (constructor + oauth + exchange + refresh)
    Evidencia: .sisyphus/evidence/task-4-account-count.txt
  ```

  **Commit**: SÍ | Mensaje: `refactor(account): usar ApiClient resuelto por contenedor con configuración fluida` | Archivos: [`src/Services/InstagramAccountService.php`]

- [ ] 5. Refactorizar InstagramPersistentMenuService — Solo constructor

  **Qué hacer**: Modificar `src/Services/InstagramPersistentMenuService.php`:
  1. **Constructor (líneas 15-22)**: Reemplazar `new ApiClient(...)` por:
     ```php
     $this->apiClient = app(ApiClient::class)
         ->withBaseUrl(config('instagram.api.graph_base_url', 'https://graph.facebook.com'))
         ->withVersion(config('instagram.api.version'));
     ```
  2. Eliminar parámetro timeout porque lo maneja el Guzzle compartido

  **NO debe hacer**:
  - NO cambiar ninguna otra parte de este archivo (389 líneas en total)
  - Solo se modifica el constructor

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ | Ola 2 | Bloquea: [] | Bloqueada Por: [1,2]

  **Referencias**:
  - Archivo: `src/Services/InstagramPersistentMenuService.php` — líneas 15-22
  - Actual: `$this->apiClient = new ApiClient(config(...), config(...), config(...));`

  **Criterios de Aceptación**:
  - [ ] Constructor usa `app(ApiClient::class)->withBaseUrl(...)->withVersion(...)` 
  - [ ] Cero `new ApiClient(` restantes en este archivo

  **Escenarios QA**:
  ```
  Escenario: Sin `new ApiClient` en PersistentMenuService
    Herramienta: Bash
    Pasos: rg "new ApiClient\(" src/Services/InstagramPersistentMenuService.php
    Esperado: Sin coincidencias
    Evidencia: .sisyphus/evidence/task-5-menu.txt
  ```

  **Commit**: SÍ | Mensaje: `refactor(menu): usar ApiClient resuelto por contenedor` | Archivos: [`src/Services/InstagramPersistentMenuService.php`]

- [ ] 6. Refactorizar FacebookAccountService — Solo constructor, usa config de Facebook

  **Qué hacer**: Modificar `src/Services/FacebookAccountService.php`:
  1. **Constructor (líneas 15-22)**: Reemplazar `new ApiClient(...)` usando configuración de Facebook:
     ```php
     $this->apiClient = app(ApiClient::class)
         ->withBaseUrl(config('facebook.api.base_url'))
         ->withVersion(config('facebook.api.version'));
     ```
  2. Eliminar parámetro timeout — lo maneja el Guzzle compartido

  **NO debe hacer**:
  - NO cambiar la lógica de ningún método — solo el constructor
  - NO cambiar las keys de configuración usadas (`facebook.api.*`)
  - Las llamadas a `request()` dentro de los métodos NO deben cambiar — ya usan `$this->apiClient`

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: [`laravel-patterns`]

  **Paralelización**: Puede Paralelo: SÍ | Ola 2 | Bloquea: [] | Bloqueada Por: [1,2]

  **Referencias**:
  - Archivo: `src/Services/FacebookAccountService.php` — líneas 15-22
  - Usa `config('facebook.api.base_url')` y `config('facebook.api.version')` — deben preservarse

  **Criterios de Aceptación**:
  - [ ] Constructor usa `app(ApiClient::class)->withBaseUrl(...)->withVersion(...)` con config de Facebook
  - [ ] Keys de config de Facebook sin cambios: `config('facebook.api.base_url')` y `config('facebook.api.version')`
  - [ ] Cero `new ApiClient(` restantes en este archivo

  **Escenarios QA**:
  ```
  Escenario: Sin `new ApiClient` en FacebookAccountService
    Herramienta: Bash
    Pasos: rg "new ApiClient\(" src/Services/FacebookAccountService.php
    Esperado: Sin coincidencias
    Evidencia: .sisyphus/evidence/task-6-facebook.txt

  Escenario: Usa config de facebook.php
    Herramienta: Bash
    Pasos: rg "config\('facebook\.api" src/Services/FacebookAccountService.php
    Esperado: Coincidencias para base_url y version
    Evidencia: .sisyphus/evidence/task-6-config.txt
  ```

  **Commit**: SÍ | Mensaje: `refactor(facebook): usar ApiClient resuelto por contenedor con configuración de Facebook` | Archivos: [`src/Services/FacebookAccountService.php`]

- [ ] 7. Eliminar archivo de tests obsoleto

  **Qué hacer**: Eliminar el archivo `tests/Feature/InstagramWebhookMessagesTest.php` y el directorio `tests/` si queda vacío.
  1. `rm tests/Feature/InstagramWebhookMessagesTest.php`
  2. Si `tests/Feature/` queda vacío, eliminar el directorio también
  3. Si `tests/` queda vacío, eliminar el directorio raíz de tests
  4. Verificar que `composer.json` no tenga referencias a estos tests en autoload-dev

  **NO debe hacer**:
  - NO tocar ningún otro archivo
  - NO modificar `phpunit.xml` si existe
  - NO modificar configuraciones de testing

  **Perfil de Agente Recomendado**:
  - Categoría: `quick`
  - Skills: []

  **Paralelización**: Puede Paralelo: NO | Ola 3 | Bloquea: [] | Bloqueada Por: [3,4,5,6]

  **Referencias**:
  - Archivo a eliminar: `tests/Feature/InstagramWebhookMessagesTest.php` (167 líneas)
  - `composer.json` — revisar sección `autoload-dev` por si referencia `tests/`
  - Namespace de tests en composer.json: verificar si hay entrada PSR-4 para tests

  **Criterios de Aceptación**:
  - [ ] `tests/Feature/InstagramWebhookMessagesTest.php` no existe
  - [ ] Directorios vacíos eliminados (si aplica)
  - [ ] `composer.json` no referencia el archivo eliminado

  **Escenarios QA**:
  ```
  Escenario: Archivo de tests eliminado
    Herramienta: Bash
    Pasos: ls tests/Feature/InstagramWebhookMessagesTest.php 2>&1
    Esperado: "No such file or directory"
    Evidencia: .sisyphus/evidence/task-7-tests-deleted.txt

  Escenario: Composer.json sin referencias huérfanas
    Herramienta: Bash
    Pasos: rg "InstagramWebhookMessagesTest" composer.json
    Esperado: Sin coincidencias (exit code 1)
    Evidencia: .sisyphus/evidence/task-7-composer.txt
  ```

  **Commit**: SÍ | Mensaje: `chore: eliminar archivo de tests obsoleto (se testea desde proyecto consumidor)` | Archivos: [`tests/Feature/InstagramWebhookMessagesTest.php`]

## Ola de Verificación Final (OBLIGATORIA — después de TODAS las tareas de implementación)
> 4 agentes de revisión se ejecutan en PARALELO. TODOS deben APROBAR. Presentar resultados consolidados al usuario y obtener "ok" explícito antes de finalizar.
> **NO auto-avanzar después de verificación. Esperar aprobación explícita del usuario antes de marcar el trabajo como completo.**
> **Nunca marcar F1-F4 como checked antes de recibir el ok del usuario.** Si hay rechazo o feedback → corregir → re-ejecutar → presentar de nuevo → esperar ok.
- [ ] F1. Auditoría de Cumplimiento del Plan — oracle
- [ ] F2. Revisión de Calidad de Código — unspecified-high
- [ ] F3. QA Manual Real — unspecified-high
- [ ] F4. Verificación de Fidelidad de Alcance — deep

## Estrategia de Commits
Cada tarea genera UN commit atómico. Secuencia:
0. `chore: actualizar versión por defecto de API a v25.0 (última disponible)`
1. `refactor(api): agregar inyección de Guzzle y métodos fluidos baseUrl/version a ApiClient`
2. `refactor(provider): registrar Guzzle Client compartido y binding transient de ApiClient`
3. `refactor(message): usar ApiClient resuelto por contenedor con configuración fluida`
4. `refactor(account): usar ApiClient resuelto por contenedor con configuración fluida`
5. `refactor(menu): usar ApiClient resuelto por contenedor`
6. `refactor(facebook): usar ApiClient resuelto por contenedor con configuración de Facebook`
7. `chore: eliminar archivo de tests obsoleto (se testea desde proyecto consumidor)`

Commit 0 va primero (actualización de versión). Commits 1-2 en paralelo (Wave 1), commits 3-6 en cualquier orden (Wave 2), commit 7 al final (Wave 3).

## Criterios de Éxito
- `rg "new ApiClient\(" src/` retorna 0 resultados (o solo el del ServiceProvider)
- `rg "new Client\(" src/` retorna 0 resultados (solo el del ServiceProvider)
- Los 4 servicios inyectan ApiClient desde el contenedor
- FacebookAccountService usa el mismo ApiClient con configuración propia
- Cero cambios de comportamiento en requests existentes
- El paquete sigue instalable y funcional desde un proyecto Laravel consumidor


