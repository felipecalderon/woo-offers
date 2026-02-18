# Woo Offers

Plugin de WooCommerce para forzar precios oferta en productos seleccionados.

## Objetivo funcional
- Buscar productos por nombre o SKU desde el admin.
- Agregarlos a una lista de reglas con dos campos editables: `precio normal` y `precio oferta`.
- Forzar ese precio en catalogo, producto, carrito y checkout sin actualizar el precio del producto en base de datos.
- Aplicar la regla solo cuando `precio oferta < precio normal`.

## Requisitos
- WordPress 6.5+
- WooCommerce 8.0+
- PHP 8.1+

## Arquitectura por capas
- `src/Domain/Offer`: modelo de negocio (`OfferRule`).
- `src/Application/Contract`: contratos (`OfferRuleRepository`).
- `src/Application/Service`: casos de uso (`OfferRuleService`, `ForcedOfferEvaluator`).
- `src/Infrastructure/Persistence`: persistencia en opciones (`OptionOfferRuleRepository`).
- `src/Infrastructure/WooCommerce`: hooks para forzar precios (`ForcedOfferPriceApplier`).
- `src/Presentation/Admin`: UI/admin actions (`AdminHooks`).
- `src/Integrations`: compatibilidad de WooCommerce (HPOS).

## Validacion en admin (tiempo real)
- Cada fila se valida al escribir `precio normal` o `precio oferta`.
- Estado visual por fila: aplicada, invalida o no validable.
- El guardado se bloquea si existe una fila invalida.
- Al agregar productos desde buscador, el plugin consulta por AJAX SKU y precio actual para validar de inmediato.

## Nota de persistencia
El plugin guarda solo sus reglas internas (`producto -> precio normal + precio oferta`) en una opcion propia. No modifica los metadatos de precio de productos de WooCommerce.

## Tests de integracion
Se incluye una suite base en `tests/Integration` para validar:
- evaluacion de reglas (`ForcedOfferEvaluatorTest`),
- aplicacion en hooks (`ForcedOfferPriceApplierTest`),
- roundtrip de persistencia (`OptionOfferRuleRepositoryTest`).

Archivos de soporte:
- `phpunit.xml.dist`
- `tests/bootstrap.php`

Comando esperado (en entorno con WordPress test suite + WooCommerce + PHP):
- `phpunit`
