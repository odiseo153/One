CONTEXTO DEL PROYECTO ACTUAL (Control Municipal)

Estoy construyendo una plataforma de gestión municipal en Laravel + React 
+ Inertia, multi-tenant (cada municipio es un tenant aislado por 
municipality_id).

Entidades ya implementadas:

User: name, email, password, municipality_id, role_id, phone, status
Municipality: name, logo_url, domain, subdomain, province_id, status, 
  registration_date, contracted_plan
Sector: municipality_id, name, geojson_polygon
Province: name

Ya existe un dashboard básico con vistas de usuarios y municipios.

OBJETIVO DE ESTA TAREA

Voy a construir un módulo de "administración del sector mediante mapeo" 
que permita:

1. Visualizar un mapa por municipio con los sectores dibujados como 
   polígonos (ya tengo geojson_polygon en la tabla Sector, pero no está 
   siendo usado visualmente todavía)
2. Registrar "locales" (negocios) como puntos (lat/lng) dentro de esos 
   sectores, con estado: registrado / no_registrado / pendiente_verificar
3. Permitir que el usuario dibuje un polígono libre sobre el mapa 
   (no solo usar los sectores predefinidos) para seleccionar una zona 
   custom y ver estadísticas de esa zona (cuántos locales, cuántos 
   registrados/no registrados)
4. Filtrar y visualizar los locales como pines de colores según su 
   estado

TAREA PARA EL AGENTE

Tengo otro proyecto llamado "Gloria" al que también tienes acceso, que 
maneja lógica de pedidos, pero internamente ya resuelve problemas de 
mapeo similares a los que necesito: coordenadas, zonas, polígonos, y 
probablemente lógica de "¿este punto está dentro de esta zona?".

Quiero que hagas lo siguiente, en este orden:

PASO 1 - ANÁLISIS DE GLORIA (no toques nada todavía, solo analiza y 
reporta)

Investiga en el proyecto Gloria:
- Qué stack usa para mapas en el frontend (¿Leaflet? ¿Mapbox? ¿Google 
  Maps? ¿react-leaflet u otro wrapper?)
- Cómo está modelada la geometría en la base de datos (¿usa PostGIS? 
  ¿guarda geojson en columna JSON/text como yo? ¿usa algún paquete 
  Laravel para tipos geoespaciales, como clickbar/laravel-magellan o 
  similar?)
- Cómo resuelve la lógica de "¿este punto (coordenada) está dentro de 
  este polígono (zona)?" — ¿lo hace con una query SQL/PostGIS, o con 
  una librería en el backend (PHP), o lo calcula en el frontend con 
  algo como Turf.js?
- Cómo maneja el dibujo de polígonos en el mapa si es que tiene esa 
  funcionalidad (¿usa Leaflet.draw o algo similar?), o si Gloria solo 
  consume zonas ya definidas sin permitir dibujarlas desde la UI
- Qué componentes de React reutiliza para renderizar el mapa, los 
  pines, y los polígonos — identifica si son componentes lo 
  suficientemente genéricos como para extraerlos o adaptarlos, o si 
  están muy acoplados a la lógica de pedidos de Gloria
- Cualquier paquete de composer o npm relacionado a geoespacial que 
  Gloria tenga instalado y que yo no tenga todavía

Repórtame esto en un resumen ANTES de escribir código, incluyendo tu 
recomendación de qué es reutilizable tal cual, qué hay que adaptar, y 
qué no aplica a mi caso porque la lógica de negocio es distinta 
(pedidos con origen/destino y rutas, vs. mi caso que es zonas 
estáticas con puntos de negocios dentro).

PASO 2 - PROPUESTA DE INTEGRACIÓN (espera mi aprobación antes de 
implementar)

Con base en el análisis, propón:
- Qué librerías instalar en Control Municipal (backend y frontend), 
  basándote en lo que ya validaste que funciona en Gloria
- Si Gloria NO usa PostGIS pero yo lo necesito (para queries eficientes 
  de "todos los locales dentro de este polígono dibujado"), sugiere si 
  vale la pena migrar a PostGIS ahora o si la solución de Gloria 
  (aunque sea distinta) es suficiente para mi escala actual
- Estructura de la nueva tabla `locals` (o `businesses`), con esta 
  base mínima que ya definí: municipality_id, sector_id, name, 
  category, latitude, longitude, address_text, registration_status 
  (registered/unregistered/pending_verification), rnc (nullable), 
  detected_at, last_verified_at, inspector_id (nullable, FK a users)
- Estructura de componentes React/Inertia: qué se reutiliza de Gloria 
  tal cual, qué se crea nuevo, y cómo se integra con las páginas de 
  dashboard que ya existen (vistas de usuarios y municipios)

PASO 3 - IMPLEMENTACIÓN (solo después de que yo apruebe el paso 2)

Implementa:
1. Migración y modelo Eloquent para `locals`/`businesses`
2. Endpoint(s) para: listar locales por municipio, filtrar por sector 
   o por polígono custom dibujado, crear/editar local, cambiar estado 
   de registro
3. Página de mapa en React (vía Inertia) que muestre:
   - Los sectores del municipio actual como polígonos (usando 
     geojson_polygon ya existente)
   - Los locales como pines de color según registration_status
   - Herramienta de dibujo de polígono libre sobre el mapa
   - Al dibujar un polígono, mostrar conteo de locales dentro 
     (total, registrados, no registrados, pendientes) sin necesidad 
     de recargar la página
   - Al hacer clic en un pin, mostrar panel/modal con el detalle del 
     local
4. Respeta el patrón multi-tenant existente: todo debe filtrarse 
   automáticamente por el municipality_id del usuario autenticado, 
   igual que en los módulos de usuarios y municipios que ya existen — 
   revisa cómo está implementado ese filtrado actualmente (scope 
   global, middleware, o manual) y sigue el mismo patrón, no inventes 
   uno nuevo

RESTRICCIONES IMPORTANTES

- No modifiques nada del proyecto Gloria, solo es referencia de lectura
- Sigue las convenciones de código, nombres de tablas/columnas 
  (snake_case) y estructura de carpetas que ya existen en Control 
  Municipal, no las de Gloria si difieren
- No implementes por ahora integración con DGII ni con ninguna API 
  externa — el registro de locales es manual/por inspección de campo 
  en esta fase
- No avances al paso 2 sin mostrarme el resultado del paso 1, ni al 
  paso 3 sin mi aprobación del paso 2
