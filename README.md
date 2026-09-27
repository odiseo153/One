# One

One es una plataforma web de gestión municipal. Permite a la ciudadanía registrar quejas sin crear una cuenta, consultar su evolución mediante un código de seguimiento y mantener visibilidad sobre la respuesta del municipio.

El área administrativa reúne en un solo lugar la gestión de:

- Quejas, asignaciones, estados y actualizaciones.
- Proyectos municipales, presupuestos, hitos, responsables, fotografías y reportes.
- Provincias, municipios y sectores con información geográfica.
- Negocios registrados o detectados en el territorio, categorías económicas, empleados y ubicación en el mapa.
- Usuarios, roles y acceso por municipio.

## Tecnologías

- PHP 8.3 y Laravel 13.
- React 19, TypeScript e Inertia.js 3.
- Tailwind CSS 4 y componentes Radix UI.
- Spatie Laravel Query Builder para filtros, ordenamiento e inclusiones.
- MySQL en producción y SQLite para pruebas.
- Vite Plus y Bun/npm para el flujo de frontend.

## Arquitectura

El backend está organizado por módulos y sigue el flujo `Controller -> Service -> Repository`. Los controladores reciben y validan las peticiones, los servicios concentran la lógica de negocio y los repositorios gestionan la persistencia con Eloquent y Spatie Query Builder.

El frontend usa páginas Inertia y componentes React reutilizables. Las funcionalidades principales están separadas en módulos de autenticación, negocios, quejas, municipios, proyectos, provincias, sectores y usuarios.

## Instalación local

Requisitos: PHP 8.3+, Composer, Node.js o Bun y una base de datos compatible con Laravel.

```bash
composer install
bun install
```

Crea un archivo `.env`, configura la conexión de base de datos y ejecuta:

```bash
php artisan key:generate
php artisan migrate --seed
bun run dev
```

En otra terminal inicia el servidor de Laravel:

```bash
php artisan serve
```

## Calidad y pruebas

```bash
composer lint:check
composer types:check
vendor/bin/phpunit
bun run check
bun run types:check
```

Las fotografías y demás archivos públicos utilizan el almacenamiento configurado por Laravel. En un entorno local puede ser necesario ejecutar `php artisan storage:link`.
