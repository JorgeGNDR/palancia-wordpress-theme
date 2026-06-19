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
