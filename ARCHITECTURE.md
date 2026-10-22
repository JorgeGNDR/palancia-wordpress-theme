# Estructura del tema PALANCIA

## Carga en WordPress

`functions.php` es únicamente el punto de entrada. Carga módulos internos del tema padre; las plantillas y los recursos públicos utilizan las rutas de WordPress que permiten sustituciones desde un tema hijo.

- `inc/setup.php`: soporte del tema y saneamiento de ajustes.
- `inc/version.php`: versión y huellas de caché de los recursos.
- `inc/assets.php`: recursos y dependencias explícitas de CSS/JavaScript.
- `inc/customizer.php`: una única implementación del personalizador.
- `inc/contact.php`: formulario de contacto.
- `inc/woocommerce/`: catálogo, producto, carrito, checkout e integración con WooCommerce.

Los módulos de WooCommerce se cargan en `after_setup_theme`, solo si el plugin está activo. Las redirecciones se realizan en `template_redirect`, antes de imprimir HTML. Se mantienen los hooks de WooCommerce en las plantillas y no se copian sus formularios de pago al tema.

La antigua excepción que forzaba Application Passwords se ha retirado: vuelve a aplicarse la política nativa de WordPress. Las reglas comerciales existentes (venta individual, campos de checkout) se conservan en los módulos de integración; si se necesitan independientemente del tema, deben migrarse a un plugin de la tienda.

## Catálogo

`catalog-state.php` define el estado canónico: categoría, tallas, stock y orden. La carga de página y el endpoint público pasan por la misma normalización, incluidos parámetros mal formados.

`catalog-query.php` construye la consulta y obtiene IDs. Excluye productos ocultos y solo visibles en búsqueda, respeta la opción global de ocultar agotados, agrupa por prioridad de categoría y considera subcategorías. Cada producto se devuelve una sola vez. Las consultas repetidas se reutilizan únicamente dentro de la misma petición, sin caché persistente que requiera invalidación.

`catalog.php` contiene categorías, tallas y presentación de tarjetas. El grid y la navegación de la ficha llaman al mismo servicio de IDs. Se conserva el comportamiento de navegación entre productos por el orden predeterminado de categorías.

La portada sigue mostrando el catálogo completo: la refactorización reduce las consultas repetidas, pero no añade paginación ni garantiza un coste constante con catálogos grandes.

PHP entrega el estado inicial y los enlaces reales. JavaScript no deduce la categoría desde `/collections/`: usa los enlaces de WordPress, incluso con subdirectorios y permalinks distintos. Al actualizar la URL conserva parámetros externos y excluye los parámetros de enrutamiento antiguos.

`filter-products.js` solo gestiona selección, borrador de filtros, petición y confirmación. Una respuesta obsoleta no modifica el grid; un fallo conserva el último grid y URL válidos y permite reintentar. Abrir/cancelar filtros no confirma el borrador. La URL se actualiza tras una respuesta válida.

## Paneles y galería

`panels.js` es el único propietario de apertura, cierre, pila de paneles, Escape, Tab, foco, fondo inerte y bloqueo del desplazamiento. El clic exterior se evalúa en captura, antes de que el botón de cierre cambie la pila. Un mismo clic no cierra también el panel que quedaba debajo.

`shop-panels.js` declara menú, filtros y ordenación. Escucha `catalog:committed` para cerrar la interfaz tras una aplicación válida. En un cambio entre móvil y escritorio limpia los paneles. Mide el encabezado mediante `ResizeObserver` para reservar su altura real.

`product-gallery.js` conserva sus gestos y renderizado de imágenes, pero delega el ciclo de vida del visor en el controlador compartido. Las imágenes se abren también con teclado.

## Estilos

`style.css` conserva metadatos, fuentes, variables y base. `assets/css/` separa layout, catálogo, paneles, producto, zoom, carrito, formularios, páginas y reglas responsive. WordPress carga los componentes en un orden explícito; `checkout.css` solo se carga en el checkout. Los niveles de superposición se declaran como variables, incluyendo la barra administrativa.

No hay compilación obligatoria para instalar el tema. `package.json` y sus dependencias son solo para pruebas de desarrollo.

## Pruebas

```sh
npm ci
npm test
find . -name '*.php' -not -path './node_modules/*' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
```

Las pruebas de DOM incluyen clic con propagación, paneles anidados, foco, Escape, Tab, cambio de viewport, cancelación de borradores, URLs con subcarpeta, errores y respuestas fuera de orden. No sustituyen una comprobación de layout en un navegador real.

Para integración, usar una instalación **desechable** de WordPress con URL `http://palancia.test`, WooCommerce activo y este tema instalado. No ejecutar las pruebas que crean productos contra producción:

```sh
wp eval-file /ruta/al/tema/tests/wordpress-bootstrap.php
wp eval-file /ruta/al/tema/tests/wordpress-bootstrap.php --skip-plugins=woocommerce
wp eval-file /ruta/al/tema/tests/wordpress.php
PALANCIA_RENDERED_GRID=/tmp/palancia-wp-integration/rendered-grid.html npm test
```

Se ha utilizado PHP 8.5.12, WordPress 7.1.3, WooCommerce 11.2.1 y SQLite Database Integration para comprobar carga, personalizador, renderizado y consultas. También se ha probado la carga con un tema hijo y sin WooCommerce. Falta verificar el layout y los métodos de pago concretos en el entorno de staging de la tienda, con su base de datos y plugins; las pruebas no crean pedidos ni realizan pagos.

Referencias de diseño de la integración: [carga de funciones del tema](https://developer.wordpress.org/themes/classic-themes/basics/theme-functions/), [rutas de archivos del tema](https://developer.wordpress.org/themes/classic-themes/basics/linking-theme-files-directories/), [consulta de productos WooCommerce](https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query).
