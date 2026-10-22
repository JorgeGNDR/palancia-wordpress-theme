# Palancia Theme Releases

La version activa tiene una unica fuente de verdad: el campo `Version` de la
cabecera de `style.css`.

## Como comprobar un despliegue

1. Inicia sesion como administrador y comprueba `PALANCIA vX.Y.Z` en la barra superior.
2. Sin iniciar sesion, abre el codigo fuente y busca `palancia-theme-version`.
3. Si sigue apareciendo la version anterior, el despliegue no se aplico, se revirtio o una cache sigue sirviendo HTML antiguo.
4. Si la pagina falla antes de mostrar HTML, revisa el registro PHP/WordPress y confirma que el hosting ha restaurado la release anterior.

## Proceso de release

1. Incrementar `Version` en `style.css` siguiendo SemVer.
2. Actualizar este registro con los cambios relevantes.
3. Validar PHP y JavaScript.
4. Crear el commit `Release vX.Y.Z`.
5. Crear y subir la etiqueta Git `vX.Y.Z` solo para una version validada.
6. Tras desplegar, verificar la version activa en la web.

## Historial

### 2.0.0-beta.11 - 2026-10-22

- Tarjetas de escritorio con imagen desenfocada y título, precio, talla y acción de compra al pasar el ratón o acceder con teclado.
- Información contenida dentro de la foto, títulos largos limitados visualmente y botones de 44 px de alto.
- En móvil, tablet y pantallas sin hover los datos y acciones permanecen visibles debajo de la imagen.
- Botones nativos de WooCommerce con AJAX, opciones de variantes, disponibilidad y contador del carrito actualizado mediante fragments.
- Enlaces de compra válidos también en respuestas de filtros y sin enlaces interactivos anidados.

### 2.0.0-beta.10 - 2026-10-22

- Portada y categorías comparten la presentación de solo fotos en escritorio, también al acceder desde una ficha de producto.
- Nombres y precios siguen disponibles para lectores de pantalla y visibles en móvil y tablet.

### 2.0.0-beta.9 - 2026-10-22

- Desenfoque suave durante las peticiones de filtros y ordenación, sin esperas artificiales.
- Movimiento de prendas conservadas mediante ID de producto y posiciones antes/después; fundido de entradas y salidas visibles.
- Efectos cancelables al interrumpir, desplazar o redimensionar la página; foco conservado y respeto a movimiento reducido.
- Transiciones direccionales al cambiar fotos en la ficha y el visor, esperando a que la imagen original esté lista y cediendo al zoom.
- Pruebas de posiciones, cancelación, foco, compatibilidad y transiciones de galería.

### 2.0.0-beta.8 - 2026-10-22

- Contacto compacto: título reducido, introducción alineada junto al título y sin «PALANCIA / 01».
- Espaciado y altura de campos ajustados para mostrar todo el formulario en escritorio, manteniendo su estilo.
- Reglas responsive de contacto agrupadas con el componente; campos móviles con texto de 16 px.

### 2.0.0-beta.7 - 2026-10-22

- Visor de producto a pantalla completa con una foto, flechas, contador y cierre con el estilo de filtros.
- Zoom por clic que sigue al ratón, con desplazamiento limitado al tamaño real de la foto; otro clic lo desactiva.
- Navegación con teclado y deslizamiento táctil, arrastre de detalle en móvil y reinicio del zoom al cambiar de foto.
- Imágenes originales para ampliación y diapositivas inactivas excluidas del clic y del teclado.
- Pruebas de navegación, zoom, gestos, límites y retorno del foco, más integración de galería en WordPress.

### 2.0.0-beta.6 - 2026-10-22

- Rutas de los iconos de carrito y menú corregidas tras separar los estilos en módulos.
- Carrito compacto con títulos pequeños, eliminar junto al producto y resumen lateral en escritorio.
- Resumen bajo los productos en móvil y un único precio por fila.
- Comprobación automática de recursos locales CSS y prueba de marcado del carrito en WooCommerce.

### 2.0.0-beta.5 - 2026-10-22

- Bootstrap y módulos separados para configuración, recursos, personalizador, contacto y WooCommerce.
- Controlador común de paneles y visor: foco, teclado, cierre, pila y desplazamiento.
- Estado, consultas y presentación del catálogo separados; visibilidad del catálogo y categorías hijas respetadas.
- URLs generadas por WordPress y confirmación de resultados solo tras respuestas válidas.
- Estilos por componentes, niveles de superposición declarados y altura real del encabezado.
- Pruebas automatizadas de DOM, CSS, PHP e integración en WordPress/WooCommerce aislados.
- Se restaura la política nativa de WordPress para Application Passwords.

### 2.0.0-beta.4 - 2026-06-19

- Nueva pagina de contacto editorial y responsive con estetica Palancia.
- Formulario ampliado para producto, pedidos, envios y devoluciones.
- Estados de envio accesibles y proteccion honeypot contra spam.

### 2.0.0-beta.3 - 2026-06-19

- Tallas agrupadas por formato y ordenadas de forma natural.
- Seleccion multiple de tallas antes de modificar el grid.
- Nuevo boton para aplicar explicitamente los filtros seleccionados.

### 2.0.0-beta.2 - 2026-06-19

- Visor movil a ancho completo con swipe entre imagenes.
- Zoom y arrastre conservados por imagen dentro del visor.
- Filtros moviles aplicados al instante mostrando directamente el grid.
- Corregido el estado visual tactil de las tallas activas.

### 2.0.0-beta.1 - 2026-06-19

- Primera version controlada del rediseno brutalista responsive.
- Galeria de producto y zoom adaptados para desktop y movil.
- Navegacion por categorias y filtro de tallas responsive.
- Carrito, checkout y estados vacios personalizados.
