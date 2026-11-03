# Diagrama entidad-relación de Convivium

## Alcance

Diagrama generado a partir de [`migrations/000_schema_inicial.sql`](https://github.com/sebastianmg-netizen/proyecto-convivium/blob/main/migrations/000_schema_inicial.sql), esquema DDL versionado y cargado en la base de producción. Incluye **67 tablas y 81 claves foráneas declaradas**; el archivo no contiene datos de usuarios ni registros operativos.

El volcado original excluyó las tablas locales `pago` y `tipos_unidad` por errores de almacenamiento MariaDB (1932). No aparecen en el esquema versionado ni en esta base de Railway. Si una instalación distinta conserva esas tablas, comparar y validar su estructura por separado antes de incorporarlas.

Las líneas muestran las columnas, tipos SQL base y marcas PK/FK/UK. Los nombres y acciones exactos de constraints, tamaños y definiciones completas permanecen en el SQL fuente.

## Diagrama completo

```mermaid
erDiagram
    agrupacion_tipos_unidad {
        int(11) id_agrupacion_tipo PK
        int(11) id_agrupacion FK
        int(11) id_tipo_config FK
        int(11) cantidad
        timestamp fecha_creacion
        timestamp fecha_actualizacion
        tinyint(1) activo
    }
    agrupaciones {
        int(11) id_agrupacion PK
        int(11) id_tipo_agrupacion FK
        varchar(100) nombre
        varchar(255) descripcion "nullable"
        tinyint(1) activo
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    aplicaciones_pagos {
        int(11) id_aplicacion PK
        int(11) id_pago FK
        int(11) id_cartera FK
        decimal(15,2) valor_aplicado
        datetime fecha_aplicacion
        enum('automatica','manual') tipo_aplicacion
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
    }
    aplicaciones_saldo_favor {
        int(11) id_aplicacion_saldo PK
        int(11) id_saldo_favor FK
        int(11) id_cartera FK
        decimal(15,2) valor_aplicado
        datetime fecha_aplicacion
        enum('automatica','manual') tipo_aplicacion
        varchar(255) observaciones "nullable"
    }
    articulos {
        int(11) id_articulo PK
        varchar(100) nombre
        varchar(50) categoria "nullable"
        varchar(20) unidad_medida "nullable"
        int(11) stock_actual
        tinyint(1) activo
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    asignaciones_parqueadero {
        int(11) id_asignacion PK
        int(11) id_parqueadero FK
        int(11) id_vehiculo FK "nullable"
        int(11) id_unidad FK
        int(11) id_residente FK "nullable"
        date fecha_desde
        date fecha_hasta "nullable"
        enum('activa','finalizada') estado
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    calendario_financiero {
        int(11) id_calendario PK
        date periodo UK
        date fecha_inicio_cierre
        date fecha_fin_cierre
        date fecha_facturacion
        date fecha_generacion_intereses
        date fecha_vencimiento
        enum('abierto','en_cierre','cerrado') estado
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    cargos_facturacion {
        int(11) id_cargo PK
        int(11) id_concepto FK
        varchar(150) nombre
        varchar(255) descripcion "nullable"
        enum('todas_unidades','tipo_unidad','unidad') tipo_aplicacion
        int(11) id_tipo_config FK "nullable"
        int(11) id_unidad FK "nullable"
        enum('valor_fijo','metro_cuadrado','coeficiente') tipo_distribucion
        decimal(15,2) valor_total
        int(11) numero_cuotas
        date periodo_inicio
        enum('borrador','activo','finalizado','anulado') estado
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        datetime fecha_actualizacion
    }
    cargos_facturacion_cuotas {
        int(11) id_cuota PK
        int(11) id_cargo_unidad FK
        int(11) numero_cuota
        date periodo
        decimal(15,2) valor
        enum('pendiente','facturada','anulada') estado
        date fecha_facturacion "nullable"
        int(11) id_detalle FK "nullable"
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion "nullable"
    }
    cargos_facturacion_unidades {
        int(11) id_cargo_unidad PK
        int(11) id_cargo FK
        int(11) id_unidad FK
        decimal(15,2) valor_asignado
        int(11) cantidad_cuotas
        enum('activo','anulado') estado
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        datetime fecha_actualizacion
    }
    cartera {
        int(11) id_cartera PK
        int(11) id_factura FK "nullable"
        int(11) id_detalle FK,UK "nullable"
        int(11) id_unidad FK
        int(11) id_tipo_obligacion FK
        date periodo
        varchar(255) descripcion "nullable"
        decimal(15,2) valor_original
        decimal(15,2) valor_pagado
        decimal(15,2) saldo
        date fecha_vencimiento
        enum('pendiente','pagada','anulada') estado
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    ciudades {
        int(11) id_ciudad PK
        int(11) id_departamento FK
        varchar(100) nombre
        varchar(10) codigo_dane "nullable"
        tinyint(1) Activo "nullable"
    }
    comunicacion {
        int(11) id PK
        varchar(100) titulo
        text mensaje
        datetime fecha
        int(11) emisor_id FK
        int(11) tipo_id FK
    }
    comunicacion_receptores {
        int(11) comunicacion_id FK
        int(11) usuario_id FK
    }
    conceptos_facturacion {
        int(11) id_concepto PK
        varchar(100) nombre
        varchar(255) descripcion "nullable"
        enum('fijo','metro_cuadrado','coeficiente','porcentaje') tipo_calculo
        enum('general','cartera','espacio','particular') origen_cobro
        int(11) id_tipo_obligacion FK "nullable"
        int(11) id_cuenta_contable FK "nullable"
        tinyint(1) obligatorio
        tinyint(1) aplica_interes_mora
        tinyint(1) estado
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    configuracion_cartera {
        int(11) id_configuracion PK
        tinyint(2) dia_vencimiento
        tinyint(1) activo
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    configuracion_conceptos_espacio {
        int(11) id_config PK
        enum('parqueadero','cuarto_util','deposito','bodega','otro') tipo_espacio UK
        int(11) id_concepto FK
        tinyint(1) activo
    }
    configuracion_correo {
        int(11) id_configuracion_correo PK
        varchar(100) nombre_configuracion
        varchar(150) correo_remitente
        varchar(150) nombre_remitente
        varchar(150) correo_respuesta "nullable"
        varchar(150) smtp_host
        int(11) smtp_puerto
        enum('tls','ssl','ninguna') smtp_seguridad
        tinyint(1) smtp_autenticacion
        varchar(150) smtp_usuario "nullable"
        text smtp_password "nullable"
        varchar(150) imap_host "nullable"
        int(11) imap_puerto
        enum('tls','ssl','ninguna') imap_seguridad
        varchar(150) imap_usuario "nullable"
        text imap_password "nullable"
        varchar(100) imap_carpeta
        tinyint(1) activo
        datetime ultima_prueba_smtp "nullable"
        varchar(255) resultado_prueba_smtp "nullable"
        datetime ultima_prueba_imap "nullable"
        varchar(255) resultado_prueba_imap "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    configuracion_financiera {
        int(11) id_configuracion PK
        tinyint(4) dia_inicio_cierre
        tinyint(4) dia_fin_cierre
        tinyint(4) dia_facturacion
        tinyint(4) dia_vencimiento
        tinyint(1) generar_intereses
        enum('mensual') periodo_interes
        int(11) id_tasa_interes FK "nullable"
        tinyint(1) activo
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    configuracion_mora {
        int(11) id_configuracion_mora PK
        varchar(150) nombre
        enum('porcentaje','valor_fijo') tipo_tasa
        decimal(15,6) tasa
        enum('diaria','mensual') periodicidad
        int(11) dias_gracia
        enum('dia_siguiente_vencimiento','despues_dias_gracia') aplicar_desde
        int(11) id_concepto FK "nullable"
        int(11) id_tasa_interes FK "nullable"
        date fecha_inicio
        date fecha_fin "nullable"
        tinyint(1) estado
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    configuracion_pagos {
        int(11) id_configuracion PK
        int(11) id_unidad FK
        int(11) id_tipo_obligacion FK
        int(11) prioridad
        tinyint(1) activo
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    cuentas_bancarias {
        int(11) id_cuenta_bancaria PK
        varchar(100) banco
        enum('ahorros','corriente') tipo_cuenta
        varchar(50) numero_cuenta
        varchar(150) titular "nullable"
        varchar(30) nit_titular "nullable"
        varchar(10) moneda
        tinyint(1) estado
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    cuentas_contables {
        int(11) id_cuenta_contable PK
        varchar(20) codigo UK
        varchar(150) nombre
        varchar(255) descripcion "nullable"
        enum('ingreso','activo','pasivo','gasto','patrimonio','otro') tipo
        tinyint(1) estado
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    datos_unidad {
        int(11) id PK
        int(11) version
        tinyint(1) es_actual
        varchar(150) nombre
        varchar(20) nit "nullable"
        varchar(120) representante_legal "nullable"
        varchar(120) correo "nullable"
        varchar(20) telefono "nullable"
        int(11) id_pais "nullable"
        int(11) id_departamento "nullable"
        int(11) id_ciudad "nullable"
        varchar(200) direccion "nullable"
        varchar(150) sector "nullable"
        int(11) id_tipo_copropiedad "nullable"
        int(11) cantidad_unidades "nullable"
        varchar(255) logo "nullable"
        varchar(255) reglamento "nullable"
        varchar(255) manual "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
        tinyint(1) activo
    }
    departamentos {
        int(11) id_departamento PK
        int(11) id_pais FK
        varchar(100) nombre
        varchar(10) codigo "nullable"
        tinyint(1) Activo "nullable"
    }
    detalle_tipos_unidad {
        int(11) id_tipo_config PK
        int(11) id_tipo_vivienda FK,UK
        varchar(120) nombre_grupo
        int(11) cantidad_unidades
        decimal(12,2) area_total
        decimal(8,5) coeficiente_total
        tinyint(1) activo
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    documentos_bancarios {
        int(11) id_documento PK
        int(11) id_cuenta_bancaria FK "nullable"
        varchar(255) nombre_archivo
        varchar(255) nombre_original "nullable"
        varchar(500) ruta_archivo
        varchar(50) tipo_archivo "nullable"
        varchar(128) hash_archivo "nullable"
        enum('texto','ocr','manual') metodo_extraccion
        enum('pendiente','procesando','procesado','error','revisado') estado_procesamiento
        longtext texto_extraido "nullable"
        varchar(255) observaciones "nullable"
        datetime fecha_procesamiento "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    envios_facturas {
        int(11) id_envio PK
        int(11) id_factura FK
        enum('individual','masivo') tipo_envio
        varchar(150) destinatario
        varchar(150) nombre_destinatario "nullable"
        varchar(255) asunto
        enum('pendiente','enviando','enviado','error') estado
        datetime fecha_programacion "nullable"
        datetime fecha_envio "nullable"
        int(11) intentos
        text mensaje_error "nullable"
        varchar(255) message_id "nullable"
        int(11) usuario_envia_id FK "nullable"
        datetime fecha_creacion
    }
    espacios {
        int(11) id_espacio PK
        int(11) id_tipo_espacio FK
        varchar(50) codigo UK
        varchar(100) nombre "nullable"
        decimal(10,2) area "nullable"
        varchar(255) observaciones "nullable"
        tinyint(1) activo
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    espacios_unidad {
        int(11) id_espacio_unidad PK
        int(11) id_unidad FK "nullable"
        int(11) usuario_id FK "nullable"
        enum('parqueadero','cuarto_util','deposito','bodega','otro') tipo_espacio
        varchar(50) codigo
        decimal(10,2) area "nullable"
        date fecha_desde
        date fecha_hasta "nullable"
        tinyint(1) activo
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        varchar(150) clave_vigente UK
    }
    estados_civiles {
        int(11) id_estado_civil PK
        varchar(50) nombre UK
        tinyint(1) estado
    }
    extractos_bancarios {
        int(11) id_extracto PK
        int(11) id_documento FK "nullable"
        int(11) id_cuenta_bancaria FK "nullable"
        date fecha_movimiento
        date fecha_valor "nullable"
        varchar(255) descripcion "nullable"
        varchar(150) referencia "nullable"
        varchar(100) numero_documento "nullable"
        decimal(15,2) valor
        enum('ingreso','egreso') tipo_movimiento
        enum('pendiente','conciliado','rechazado','con_diferencia') estado_conciliacion
        varchar(255) archivo_origen "nullable"
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    facturas {
        int(11) id_factura PK
        int(11) id_unidad FK
        varchar(50) numero_factura "nullable"
        year(4) periodo
        tinyint(2) mes
        date fecha_generacion
        date fecha_vencimiento
        decimal(15,2) subtotal
        decimal(15,2) intereses
        decimal(15,2) saldos_anteriores
        decimal(15,2) total
        enum('borrador','generada','parcial','pagada','vencida','anulada') estado
        varchar(500) observaciones "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    facturas_detalle {
        int(11) id_detalle PK
        int(11) id_factura FK
        int(11) id_concepto FK
        int(11) id_tarifa FK "nullable"
        int(11) id_interes FK "nullable"
        varchar(255) descripcion
        decimal(15,4) cantidad
        decimal(15,2) valor_unitario
        decimal(15,2) subtotal
        enum('fijo','metro_cuadrado','coeficiente','porcentaje') tipo_calculo
        decimal(15,4) base_calculo "nullable"
        timestamp fecha_creacion
    }
    facturas_saldos {
        int(11) id_saldo PK
        int(11) id_factura FK
        int(11) id_factura_origen FK
        decimal(15,2) saldo_inicial
        decimal(15,2) intereses
        decimal(15,2) valor_aplicado
        decimal(15,2) saldo_pendiente
        enum('pendiente','parcial','pagado') estado
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    generos {
        int(11) id_genero PK
        varchar(5) codigo UK
        varchar(50) nombre UK
        tinyint(1) estado
    }
    horario_zona {
        int(11) id PK
        int(11) id_zona FK
        tinyint(4) dia_semana
        time hora_inicio
        time hora_fin
    }
    intereses_cartera {
        int(11) id_interes PK
        int(11) id_cartera FK
        int(11) id_tasa_interes FK "nullable"
        date periodo_interes
        date fecha_calculo
        date fecha_vencimiento
        int(11) dias_mora
        decimal(10,6) tasa_interes
        decimal(15,2) valor_base
        decimal(15,2) valor_interes
        decimal(15,2) valor_pagado
        decimal(15,2) saldo
        enum('pendiente','pagado','anulado') estado
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    mantenimiento {
        int(11) id PK
        int(11) zona_id FK
        int(11) usuario_reporta_id FK
        text descripcion
        enum('baja','media','alta','critica') prioridad
        varchar(150) responsable "nullable"
        datetime fecha_reporte
        datetime fecha_inicio "nullable"
        datetime fecha_solucion "nullable"
        enum('pendiente','en_proceso','solucionado') estado
        decimal(12,2) costo "nullable"
        text comentarios "nullable"
        varchar(255) evidencia "nullable"
        datetime fecha_actualizacion
    }
    mascotas {
        int(11) id_mascota PK
        varchar(100) nombre
        varchar(50) especie
        int(11) id_unidad FK
        timestamp fecha_registro
    }
    movimientos_inventario {
        int(11) id_movimiento PK
        int(11) id_articulo FK
        int(11) id_usuario FK
        enum('entrada','salida') tipo_movimiento
        int(11) cantidad
        varchar(255) nota "nullable"
        datetime fecha_movimiento
    }
    obligaciones {
        int(11) id_obligacion PK
        int(11) id_unidad
        int(11) id_tipo_obligacion
        date periodo
        date fecha_generacion
        date fecha_vencimiento
        decimal(15,2) valor
        varchar(255) descripcion "nullable"
        enum('generada','pagada','anulada') estado
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    ocupaciones {
        int(11) id_ocupacion PK
        varchar(100) nombre UK
        tinyint(1) estado
    }
    pagos {
        int(11) id_pago PK
        int(11) id_unidad FK
        int(11) id_extracto FK "nullable"
        date fecha_pago
        decimal(15,2) valor
        enum('efectivo','transferencia','consignacion','pse','tarjeta','otro') medio_pago
        enum('manual','banco','pasarela') origen_pago
        enum('pendiente','conciliado','rechazado','con_diferencia') estado_conciliacion
        datetime fecha_conciliacion "nullable"
        varchar(100) referencia "nullable"
        varchar(150) referencia_externa "nullable"
        varchar(150) id_externo "nullable"
        varchar(255) observaciones "nullable"
        enum('registrado','anulado') estado
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    paises {
        int(11) id_pais PK
        varchar(100) nombre UK
        tinyint(1) Activo
    }
    parentescos {
        int(11) id_parentesco PK
        varchar(50) nombre UK
        tinyint(1) estado
    }
    parqueaderos {
        int(11) id_parqueadero PK
        varchar(20) codigo UK
        int(11) id_unidad FK "nullable"
        enum('privado','visitantes','motos','bicicletas') tipo
        varchar(100) ubicacion "nullable"
        enum('disponible','ocupado','mantenimiento') estado
        varchar(255) observaciones "nullable"
        tinyint(1) activo
        datetime fecha_creacion
    }
    pqrs {
        int(11) id PK
        int(11) usuario_id FK
        enum('peticion','queja','reclamo','sugerencia') tipo
        varchar(100) asunto "nullable"
        text descripcion
        datetime fecha
        enum('pendiente','en_proceso','cerrado') estado
    }
    reserva_zona {
        int(11) id PK
        int(11) zona_id FK
        int(11) usuario_id FK
        date fecha_reserva
        time hora_inicio
        time hora_fin
        enum('pendiente','aprobada','cancelada') estado
    }
    residente {
        int(11) id PK
        int(11) unidad_id
        int(11) usuario_id
        enum('propietario','inquilino','residente') tipo
        tinyint(1) recibe_factura
        datetime fecha_desde
        datetime fecha_hasta "nullable"
        tinyint(1) activo
    }
    rol {
        int(11) id PK
        varchar(50) nombre UK
    }
    saldo_favor {
        int(11) id_saldo_favor PK
        int(11) id_unidad FK
        int(11) id_pago FK
        decimal(15,2) valor_original
        decimal(15,2) valor_utilizado
        decimal(15,2) saldo_disponible
        enum('disponible','utilizado','anulado') estado
        datetime fecha_generacion
        datetime fecha_ultimo_uso "nullable"
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    tarifas_facturacion {
        int(11) id_tarifa PK
        int(11) id_concepto FK
        int(11) id_tipo_config FK
        varchar(150) nombre "nullable"
        decimal(15,2) valor
        date fecha_inicio
        date fecha_fin "nullable"
        tinyint(1) estado
        varchar(255) observaciones "nullable"
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    tasas_interes {
        int(11) id_tasa_interes PK
        varchar(150) nombre
        decimal(10,6) tasa_anual
        decimal(10,6) tasa_mensual
        date fecha_inicio
        date fecha_fin "nullable"
        varchar(255) fuente "nullable"
        tinyint(1) activo
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    tipo_comunicacion {
        int(11) id PK
        varchar(50) nombre
    }
    tipos_agrupacion {
        int(11) id_tipo_agrupacion PK
        varchar(50) nombre
        varchar(150) descripcion "nullable"
        tinyint(1) activo
        timestamp fecha_creacion
    }
    tipos_copropiedad {
        int(11) id PK
        varchar(100) nombre
        text observacion "nullable"
    }
    tipos_documento {
        int(11) id_tipo_documento PK
        varchar(10) codigo
        varchar(80) nombre
        tinyint(1) estado
    }
    tipos_espacio {
        int(11) id_tipo_espacio PK
        varchar(100) nombre
        varchar(255) descripcion "nullable"
        int(11) id_concepto FK "nullable"
        tinyint(1) activo
        timestamp fecha_creacion
        timestamp fecha_actualizacion
    }
    tipos_obligacion {
        int(11) id_tipo_obligacion PK
        varchar(100) nombre UK
        varchar(255) descripcion "nullable"
        int(11) orden_defecto
        tinyint(1) genera_intereses
        tinyint(1) activo
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    tipos_persona {
        int(11) id_tipo_persona PK
        varchar(50) nombre UK
        tinyint(1) estado
    }
    tipos_vivienda {
        int(11) id_tipo_vivienda PK
        varchar(10) codigo UK "nullable"
        varchar(100) nombre
        varchar(255) descripcion "nullable"
        tinyint(1) activo
        int(11) orden
        timestamp fecha_creacion
    }
    unidades {
        int(11) id_unidad PK
        int(11) id_tipo_config FK
        varchar(30) codigo
        varchar(100) nombre "nullable"
        varchar(10) piso "nullable"
        decimal(10,2) area "nullable"
        decimal(12,8) coeficiente "nullable"
        enum('disponible','habitada','desocupada','en mantenimiento') estado
        text observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
        tinyint(1) activo
    }
    usos_vivienda {
        int(11) id_uso PK
        varchar(50) nombre
    }
    usuario {
        int(11) id PK
        varchar(100) nombres
        varchar(100) apellidos
        int(11) id_tipo_documento FK "nullable"
        varchar(30) numero_documento UK "nullable"
        varchar(150) correo UK "nullable"
        varchar(255) contrasena "nullable"
        varchar(20) telefono "nullable"
        varchar(20) celular "nullable"
        date fecha_nacimiento "nullable"
        int(11) id_genero FK "nullable"
        int(11) id_estado_civil "nullable"
        int(11) id_ocupacion "nullable"
        varchar(200) direccion "nullable"
        int(11) id_pais FK "nullable"
        int(11) id_departamento FK "nullable"
        int(11) id_ciudad FK "nullable"
        varchar(255) foto "nullable"
        int(11) rol_id FK "nullable"
        tinyint(1) estado
        datetime ultimo_login "nullable"
        datetime fecha_creacion
        char(64) token_recuperacion "nullable"
        datetime expiracion_token "nullable"
    }
    vehiculos {
        int(11) id_vehiculo PK
        varchar(10) placa UK
        enum('automovil','motocicleta','bicicleta','otro') tipo
        varchar(50) marca "nullable"
        varchar(50) modelo "nullable"
        varchar(30) color "nullable"
        int(11) id_residente FK "nullable"
        int(11) id_unidad FK "nullable"
        tinyint(1) estado
        datetime fecha_desde
        datetime fecha_hasta "nullable"
        varchar(255) observaciones "nullable"
        datetime fecha_creacion
        datetime fecha_actualizacion
    }
    zona_comun {
        int(11) id PK
        varchar(100) nombre UK
        text descripcion "nullable"
        int(11) capacidad "nullable"
    }
    agrupaciones ||--o{ agrupacion_tipos_unidad : "id_agrupacion"
    detalle_tipos_unidad ||--o{ agrupacion_tipos_unidad : "id_tipo_config"
    tipos_agrupacion ||--o{ agrupaciones : "id_tipo_agrupacion"
    cartera ||--o{ aplicaciones_pagos : "id_cartera"
    pagos ||--o{ aplicaciones_pagos : "id_pago"
    cartera ||--o{ aplicaciones_saldo_favor : "id_cartera"
    saldo_favor ||--o{ aplicaciones_saldo_favor : "id_saldo_favor"
    parqueaderos ||--o{ asignaciones_parqueadero : "id_parqueadero"
    residente o|--o{ asignaciones_parqueadero : "id_residente"
    unidades ||--o{ asignaciones_parqueadero : "id_unidad"
    vehiculos o|--o{ asignaciones_parqueadero : "id_vehiculo"
    conceptos_facturacion ||--o{ cargos_facturacion : "id_concepto"
    detalle_tipos_unidad o|--o{ cargos_facturacion : "id_tipo_config"
    unidades o|--o{ cargos_facturacion : "id_unidad"
    cargos_facturacion_unidades ||--o{ cargos_facturacion_cuotas : "id_cargo_unidad"
    facturas_detalle o|--o{ cargos_facturacion_cuotas : "id_detalle"
    cargos_facturacion ||--o{ cargos_facturacion_unidades : "id_cargo"
    unidades ||--o{ cargos_facturacion_unidades : "id_unidad"
    facturas_detalle o|--o| cartera : "id_detalle"
    facturas o|--o{ cartera : "id_factura"
    tipos_obligacion ||--o{ cartera : "id_tipo_obligacion"
    unidades ||--o{ cartera : "id_unidad"
    departamentos ||--o{ ciudades : "id_departamento"
    usuario ||--o{ comunicacion : "emisor_id"
    tipo_comunicacion ||--o{ comunicacion : "tipo_id"
    comunicacion ||--o{ comunicacion_receptores : "comunicacion_id"
    usuario ||--o{ comunicacion_receptores : "usuario_id"
    cuentas_contables o|--o{ conceptos_facturacion : "id_cuenta_contable"
    tipos_obligacion o|--o{ conceptos_facturacion : "id_tipo_obligacion"
    conceptos_facturacion ||--o{ configuracion_conceptos_espacio : "id_concepto"
    tasas_interes o|--o{ configuracion_financiera : "id_tasa_interes"
    conceptos_facturacion o|--o{ configuracion_mora : "id_concepto"
    tasas_interes o|--o{ configuracion_mora : "id_tasa_interes"
    tipos_obligacion ||--o{ configuracion_pagos : "id_tipo_obligacion"
    unidades ||--o{ configuracion_pagos : "id_unidad"
    paises ||--o{ departamentos : "id_pais"
    tipos_vivienda ||--|| detalle_tipos_unidad : "id_tipo_vivienda"
    tipos_vivienda ||--|| detalle_tipos_unidad : "id_tipo_vivienda"
    cuentas_bancarias o|--o{ documentos_bancarios : "id_cuenta_bancaria"
    facturas ||--o{ envios_facturas : "id_factura"
    usuario o|--o{ envios_facturas : "usuario_envia_id"
    tipos_espacio ||--o{ espacios : "id_tipo_espacio"
    usuario o|--o{ espacios_unidad : "usuario_id"
    unidades o|--o{ espacios_unidad : "id_unidad"
    cuentas_bancarias o|--o{ extractos_bancarios : "id_cuenta_bancaria"
    documentos_bancarios o|--o{ extractos_bancarios : "id_documento"
    unidades ||--o{ facturas : "id_unidad"
    conceptos_facturacion ||--o{ facturas_detalle : "id_concepto"
    facturas ||--o{ facturas_detalle : "id_factura"
    tarifas_facturacion o|--o{ facturas_detalle : "id_tarifa"
    intereses_cartera o|--o{ facturas_detalle : "id_interes"
    facturas ||--o{ facturas_saldos : "id_factura"
    facturas ||--o{ facturas_saldos : "id_factura_origen"
    zona_comun ||--o{ horario_zona : "id_zona"
    cartera ||--o{ intereses_cartera : "id_cartera"
    tasas_interes o|--o{ intereses_cartera : "id_tasa_interes"
    zona_comun ||--o{ mantenimiento : "zona_id"
    usuario ||--o{ mantenimiento : "usuario_reporta_id"
    unidades ||--o{ mascotas : "id_unidad"
    articulos ||--o{ movimientos_inventario : "id_articulo"
    usuario ||--o{ movimientos_inventario : "id_usuario"
    unidades ||--o{ pagos : "id_unidad"
    extractos_bancarios o|--o{ pagos : "id_extracto"
    unidades o|--o{ parqueaderos : "id_unidad"
    usuario ||--o{ pqrs : "usuario_id"
    zona_comun ||--o{ reserva_zona : "zona_id"
    usuario ||--o{ reserva_zona : "usuario_id"
    pagos ||--o{ saldo_favor : "id_pago"
    unidades ||--o{ saldo_favor : "id_unidad"
    conceptos_facturacion ||--o{ tarifas_facturacion : "id_concepto"
    detalle_tipos_unidad ||--o{ tarifas_facturacion : "id_tipo_config"
    conceptos_facturacion o|--o{ tipos_espacio : "id_concepto"
    detalle_tipos_unidad ||--o{ unidades : "id_tipo_config"
    ciudades o|--o{ usuario : "id_ciudad"
    departamentos o|--o{ usuario : "id_departamento"
    generos o|--o{ usuario : "id_genero"
    paises o|--o{ usuario : "id_pais"
    tipos_documento o|--o{ usuario : "id_tipo_documento"
    rol o|--o{ usuario : "rol_id"
    residente o|--o{ vehiculos : "id_residente"
    unidades o|--o{ vehiculos : "id_unidad"
```

## Fuente y despliegue

- Repositorio: [sebastianmg-netizen/proyecto-convivium](https://github.com/sebastianmg-netizen/proyecto-convivium)
- Esquema SQL: [`migrations/000_schema_inicial.sql`](https://github.com/sebastianmg-netizen/proyecto-convivium/blob/main/migrations/000_schema_inicial.sql)
- Plataforma actual: Railway; base de datos MySQL en el proyecto `artistic-bravery`.
- El diagrama representa únicamente las relaciones declaradas como `FOREIGN KEY`; vínculos que la aplicación implementa sin constraint no se infieren aquí.
