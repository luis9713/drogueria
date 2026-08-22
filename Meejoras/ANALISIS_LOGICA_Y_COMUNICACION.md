# Analisis de Logica y Comunicacion entre Modulos

Fecha: 2026-07-20
Proyecto: Drogueria (PHP + JS + MySQL)

## Objetivo
Documentar errores detectados de logica y comunicacion entre modulos, su impacto y una solucion practica, junto con una ruta de correccion por fases.

## Resumen Ejecutivo
Se detectaron fallas criticas en integridad de datos (ventas/inventario), condiciones de carrera en contabilidad y contratos fragiles entre frontend y backend. La prioridad es corregir primero concurrencia e inconsistencias de respuesta, luego endurecer validaciones y acceso.

## Hallazgos

### 1) Condicion de carrera al obtener ultima venta (CRITICO)
- Evidencia:
  - [controlador/CompraController.php#L52](../controlador/CompraController.php#L52)
  - [controlador/CompraController.php#L53](../controlador/CompraController.php#L53)
  - [modelo/Venta.php#L20](../modelo/Venta.php#L20)
  - [modelo/Venta.php#L21](../modelo/Venta.php#L21)
- Que ocasiona:
  - Se inserta la venta y luego se obtiene `MAX(id_venta)`.
  - En concurrencia, un usuario puede tomar el id de otro usuario.
  - Resultado: detalle_venta y venta_producto pueden quedar asociados a la venta equivocada.
- Posible solucion:
  - Reemplazar `SELECT MAX(id_venta)` por `lastInsertId()` de la misma conexion/tx.
  - Envolver creacion de venta + detalle + descuento de lotes en una sola transaccion.

### 2) Riesgo de bucle infinito en descuento por lotes (CRITICO)
- Evidencia:
  - [controlador/CompraController.php#L70](../controlador/CompraController.php#L70)
  - [controlador/CompraController.php#L71](../controlador/CompraController.php#L71)
- Que ocasiona:
  - Si no hay lote disponible para un producto, el `while ($cantidad!=0)` no reduce cantidad.
  - Resultado: request colgado, venta inconclusa o timeout.
- Posible solucion:
  - Validar `if (empty($lote))` y cortar con error controlado.
  - Agregar guardas de iteracion maxima y rollback inmediato.
  - Bloquear lotes seleccionados (`FOR UPDATE`) si se trabaja con InnoDB.

### 3) Bug de comparacion en frontend (CRITICO)
- Evidencia:
  - [js/Venta.js#L101](../js/Venta.js#L101)
  - [js/Credito.js#L159](../js/Credito.js#L159)
- Que ocasiona:
  - Se usa asignacion `response = "nodelete"` en vez de comparacion.
  - Resultado: la rama de error se ejecuta casi siempre y se rompe la logica de borrado.
- Posible solucion:
  - Cambiar a `response === "nodelete"`.
  - Estandarizar respuestas del backend en JSON (`{success:true/false, code, message}`).

### 4) Actualizacion no atomica de saldos contables (CRITICO)
- Evidencia:
  - [modelo/Contabilidad.php#L57](../modelo/Contabilidad.php#L57)
  - [modelo/Contabilidad.php#L63](../modelo/Contabilidad.php#L63)
  - [modelo/Contabilidad.php#L177](../modelo/Contabilidad.php#L177)
  - [modelo/Contabilidad.php#L183](../modelo/Contabilidad.php#L183)
- Que ocasiona:
  - Patron read-modify-write sin bloqueo/tx.
  - En concurrencia, dos movimientos pueden pisar el saldo.
- Posible solucion:
  - Ejecutar registro de movimiento y actualizacion de saldo dentro de transaccion.
  - Bloquear fila de contabilidad (`SELECT ... FOR UPDATE`) antes de calcular saldo_nuevo.
  - Definir isolation apropiado y pruebas de concurrencia.

### 5) SQL dinamico con interpolacion de valores (ALTO)
- Evidencia:
  - [controlador/CompraController.php#L84](../controlador/CompraController.php#L84)
  - [controlador/CompraController.php#L86](../controlador/CompraController.php#L86)
  - [controlador/CompraController.php#L104](../controlador/CompraController.php#L104)
- Que ocasiona:
  - Riesgo de inyeccion si algun dato deja de ser confiable.
  - Mantenimiento mas dificil y errores silenciosos por tipos.
- Posible solucion:
  - Migrar a `prepare/execute` con bind en todos los INSERT/UPDATE.
  - Centralizar acceso a DB en metodos de modelo y no en controlador.

### 6) Contratos de respuesta inconsistentes (ALTO)
- Evidencia:
  - [controlador/CajaController.php#L10](../controlador/CajaController.php#L10)
  - [js/Caja.js#L114](../js/Caja.js#L114)
  - [js/Caja.js#L157](../js/Caja.js#L157)
  - [js/Contabilidad.js#L126](../js/Contabilidad.js#L126)
- Que ocasiona:
  - Algunos endpoints devuelven texto plano (`success`, `edit`, `add`) y otros JSON.
  - El frontend hace `JSON.parse` en multiples rutas sin tolerancia a respuestas invalidas.
  - Resultado: caidas del flujo por parse error.
- Posible solucion:
  - Definir contrato unico JSON para todos los endpoints.
  - Agregar `try/catch` + `.fail()` en todos los llamados AJAX/fetch.

### 7) Validaciones criticas solo en frontend (MEDIO-ALTO)
- Evidencia:
  - [controlador/ContabilidadController.php#L63](../controlador/ContabilidadController.php#L63)
  - [controlador/ContabilidadController.php#L76](../controlador/ContabilidadController.php#L76)
- Que ocasiona:
  - Si llaman endpoint directo (Postman/curl), pueden enviar montos invalidos.
  - Resultado: saldos corruptos o movimientos inconsistentes.
- Posible solucion:
  - Validar backend: requerido, numerico, > 0, maximo razonable, categoria valida.
  - Responder errores de validacion con JSON y codigo de error.

### 8) Control de acceso no uniforme en controladores (MEDIO)
- Evidencia:
  - [controlador/ProductoController.php#L1](../controlador/ProductoController.php#L1)
  - [controlador/ClienteController.php#L1](../controlador/ClienteController.php#L1)
  - [controlador/VentaProductoController.php#L1](../controlador/VentaProductoController.php#L1)
  - [controlador/ComprasController.php#L1](../controlador/ComprasController.php#L1)
- Que ocasiona:
  - Endpoints sin `session_start`/validacion de rol uniforme.
  - Riesgo de exposicion de operaciones segun despliegue y configuracion.
- Posible solucion:
  - Crear middleware comun de autenticacion/autorizacion.
  - Rechazar cualquier request sin sesion/rol valido.

### 9) Uso de `json[0]` sin garantizar datos (MEDIO)
- Evidencia:
  - [controlador/VentaController.php#L126](../controlador/VentaController.php#L126)
  - [controlador/VentaController.php#L185](../controlador/VentaController.php#L185)
  - [controlador/ProductoController.php#L159](../controlador/ProductoController.php#L159)
- Que ocasiona:
  - Si la consulta retorna vacio, aparecen warnings y respuestas mal formadas.
  - Resultado: frontend falla al parsear.
- Posible solucion:
  - Verificar arreglo vacio antes de acceder indice 0.
  - Devolver JSON explicito: `{success:false, code:"NOT_FOUND"}`.

## Ruta de Correccion Recomendada

### Fase 1: Estabilizacion inmediata (1-2 dias)
1. Corregir comparaciones de respuesta en frontend (`Venta.js`, `Credito.js`).
2. Unificar mensajes minimos de backend para operaciones criticas (borrar, crear, editar).
3. Agregar validaciones backend de monto en contabilidad.
4. Agregar manejo de errores `.fail()` y `try/catch` en parseo JSON.

Entregable:
- Flujos de borrar venta/credito y caja/contabilidad estables, sin errores de parse.

### Fase 2: Integridad transaccional (2-4 dias)
1. Reemplazar `MAX(id_venta)` por `lastInsertId()` dentro de la misma transaccion.
2. Mover la creacion de venta + detalle + venta_producto + lotes a una sola transaccion atomica.
3. Eliminar SQL interpolada en `CompraController.php` y pasar a `prepare/execute`.
4. Evitar bucle infinito con validacion de lote vacio y rollback.

Entregable:
- Integridad de ventas/inventario ante concurrencia normal.

### Fase 3: Endurecimiento de arquitectura (3-5 dias)
1. Contrato unico JSON para todos los controladores (`success`, `code`, `message`, `data`).
2. Middleware comun de auth/rol en todos los endpoints.
3. Validaciones centrales de entrada por modulo (ventas, compras, contabilidad).
4. Normalizar codigos de error y trazabilidad.

Entregable:
- Comunicacion backend-frontend predecible y segura.

### Fase 4: Pruebas y monitoreo (2-3 dias)
1. Pruebas funcionales de regresion para ventas, creditos, caja y contabilidad.
2. Pruebas de concurrencia en venta/contabilidad (2-5 usuarios simultaneos).
3. Logs de error unificados en backend + alertas basicas.

Entregable:
- Evidencia de estabilidad y reduccion de riesgo operativo.

## Plan de Prioridad Tecnica
1. Integridad de datos (concurrencia y transacciones).
2. Contrato de respuestas y manejo de errores.
3. Seguridad de entrada y control de acceso.
4. Refactor de arquitectura y cobertura de pruebas.

## Criterios de Cierre
- No se usa `MAX(id_venta)` para recuperar la venta recien insertada.
- No existen bucles potencialmente infinitos en asignacion de lotes.
- Todos los endpoints criticos responden JSON consistente.
- Todas las operaciones contables validan datos en backend.
- Controladores criticos requieren sesion y rol.
- Pruebas de concurrencia sin desalineacion de inventario/saldo.
