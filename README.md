# Shipping Quote Backend

API de cotización de envíos multi‑proveedor, desarrollada con **Symfony 6.4**, diseñada para integrarse con un frontend en **React + Vite + TailwindCSS**.

Este backend expone un endpoint para obtener cotizaciones de diferentes paqueterías (Estafeta, Fedex, DHL, UPS), aplicando reglas de pricing por usuario altamente configurables, y simulando la comunicación con carriers reales mediante `webhook.site`.

- Backend: https://github.com/raymundo-salazar/shipping-quote-backend
- Frontend: https://github.com/raymundo-salazar/shipping-quote-frontend

---

## 📁 Estructura del Proyecto (Backend)

Este repo contiene **solo el backend** (API Symfony). Estructura principal:

```text
.
├── .env / .env.dev / .env.test      # Configuración de entornos
├── bin/                             # Consola Symfony, phpunit wrapper
├── config/                          # Configuración de Symfony, Doctrine, seguridad, CORS, etc.
├── docker/                          # Dockerfile, nginx.conf, php.ini, start.sh
├── docker-compose.yml               # Stack Docker (PHP + Nginx + PostgreSQL)
├── migrations/                      # Migraciones de Doctrine
├── public/                          # Document root (index.php, assets API Platform)
├── src/
│   ├── Api/Exception/               # Excepciones de API personalizadas
│   ├── Controller/                  # Controladores (API, Quotes, Users, ShippingProviders)
│   ├── DataFixtures/                # Fixtures (usuarios, proveedores, reglas de pricing)
│   ├── Entity/                      # Entidades Doctrine
│   ├── EventSubscriber/             # Manejo global de errores y respuestas API
│   ├── Repository/                  # Repositorios Doctrine
│   ├── Security/                    # Integración con Clerk (JWT, autenticador)
│   ├── Service/                     # Servicios de dominio (Pricing, Quote, Shipping)
│   └── Test/                        # Helper de pruebas de integración (no usado actualmente)
├── tests/
│   ├── Service/
│   │   ├── PricingServiceTest.php   # Tests unitarios de PricingService
│   │   └── Shipping/GenericHttpShippingProviderTest.php
│   └── bootstrap.php
└── composer.json / phpunit.dist.xml / phpstan.neon / symfony.lock
```

---

## 🧠 Descripción Funcional

El objetivo del backend es:

- Recibir una solicitud de cotización de envío (origen, destino, dimensiones del paquete, proveedor).
- Consultar múltiples proveedores de paquetería (simulados con `webhook.site`) usando distintos formatos (JSON/XML).
- Aplicar reglas de **pricing específicas por usuario**, proveedor y servicio.
- Devolver una lista de cotizaciones con:
  - Provider
  - Servicio
  - Precio base
  - Markup aplicado
  - Precio final
  - Moneda
  - Posibles errores por proveedor (si fallan).

El frontend (otro repo) consume este backend y muestra un formulario + tabla de resultados.

---

## 💰 Sistema de Pricing (muy importante)

El cálculo de precios está centralizado en `App\Service\PricingService` y se basa en una **jerarquía de reglas por usuario**, en este orden de prioridad (de mayor a menor):

1. **Override por Servicio** (`UserServicePricingOverride`)
2. **Regla por Proveedor** (`UserProviderPricingRule`)
3. **Regla Global por Usuario** (`UserGlobalPricingRule`)
4. **Markup por Defecto** (`DEFAULT_MARKUP`, actualmente 15%)

El método principal es:

```php
array PricingService::calculatePrice(
    ?User $user,
    int $providerId,
    ?string $serviceCode,
    float $basePrice
)
```

Devuelve algo como:

```php
[
    'markup_percentage' => 15.0,
    'final_price'       => 115.0,
]
```

para un `basePrice` de 100 con 15% de markup.

### 1. Override por Servicio (`UserServicePricingOverride`)

Nivel más específico:

- Asociado a: **usuario + provider + service_code**.
- Campos:
  - `fixed_price` (opcional)
  - `markup_percentage` (opcional)

Comportamiento:

- Si existe un override de servicio:
  - Si `fixed_price` no es null → se usa ese precio tal cual:
    - `final_price = fixed_price`
    - `markup_percentage = 0`
  - Si `fixed_price` es null y `markup_percentage` no es null → se aplica ese porcentaje:
    - `final_price = basePrice * (1 + markup_percentage / 100)`

Ejemplo:

- Usuario: Raymundo  
- Provider: DHL  
- Service_code: `EXPRESS`  
- Override:
  - `fixed_price = 150.0`, `markup_percentage = null`
- Base price provider: `100.0`  
- Resultado:
  - `final_price = 150.0`, `markup_percentage = 0.0`

### 2. Regla por Proveedor (`UserProviderPricingRule`)

Si **no hay override por servicio**, se busca una regla por provider:

- Asociada a: **usuario + provider**.
- Campo:
  - `markup_percentage` (ej: 10%)

Comportamiento:

- `final_price = basePrice * (1 + markup_percentage / 100)`

Ejemplo:

- Usuario: Raymundo  
- Provider: Fedex  
- Regla: `markup_percentage = 10%`  
- Base price: `200`  
- Resultado: `final_price = 220`

### 3. Regla Global por Usuario (`UserGlobalPricingRule`)

Si no hay ni override por servicio ni regla por provider:

- Asociada a: **usuario** (aplica a todos los providers).
- Campo:
  - `markup_percentage` (ej: 8.5%)

Comportamiento:

- `final_price = basePrice * (1 + markup_percentage / 100)`

Ejemplo:

- Usuario: Raymundo  
- Regla global: `markup_percentage = 8.5%`  
- Base price: `100`  
- Resultado: `108.5`

### 4. Markup por Defecto (`DEFAULT_MARKUP`)

Si **no hay usuario** o el usuario no tiene ninguna regla configurada:

- Se aplica `DEFAULT_MARKUP` (ej: `15%`).
- `final_price = basePrice * 1.15`

Ejemplo:

- Usuario: `null` (anónimo)  
- Base price: `100`  
- Resultado: `115.0`

### Tests del Pricing

Hay un test unitario cubriendo estos escenarios:  
`tests/Service/PricingServiceTest.php`

Casos cubiertos:

1. Sin usuario → default markup.
2. Override por servicio con `fixed_price`.
3. Override por servicio con `markup_percentage`.
4. Regla por provider.
5. Regla global.
6. Fallback a default cuando todo es null.

---

## 🧩 Diseño de la Integración con Proveedores (sin código específico por proveedor)

Uno de los objetivos principales de este backend es **poder agregar o cambiar proveedores de envío sin tener que escribir código nuevo por cada uno**.  
Aunque Estafeta, DHL, UPS y Fedex responden con **formatos diferentes** (JSON anidado, JSON con otras claves, XML, incluso errores 500), el servicio está diseñado para manejar esto de forma **configurable**, no “hardcodeada”.

La clave está en tres conceptos:

1. `ShippingProvider` (entidad en base de datos)
2. `ShippingProviderInterface` (Strategy)
3. `GenericHttpShippingProvider` + `ShippingProviderFactory`

### 1. Entidad `ShippingProvider`: la configuración vive en la base de datos

Cada proveedor se modela como una fila en la tabla `shipping_providers`, con campos como:

- `name` → “Estafeta”, “DHL”, “UPS”, “Fedex”
- `code` → identificador interno
- `endpoint_url` → URL a la que se hace la petición (en este caso, un `webhook.site`)
- `format` → `"json"` o `"xml"`
- `request_config` → cómo debe construirse el request
- `response_config` → cómo mapear la respuesta externa a un formato interno uniforme
- `active` → si el provider está habilitado o no

Toda esta lógica de “de dónde saco el nombre, el código, el precio y la moneda” **no está escrita a mano en ifs/switches por provider**, sino parametrizada en la base de datos.

### 2. `ShippingProviderInterface`: Strategy

Se define una interfaz única:

```php
interface ShippingProviderInterface
{
    /**
     * @return array<int, array{
     *   service: string,
     *   service_code: string,
     *   base_price: float,
     *   currency: string
     * }>
     */
    public function getQuotes(array $requestData): array;
}
```

La idea es que **cualquier implementación** de proveedor:

- Reciba datos normalizados del dominio (`origin_zipcode`, `destination_zipcode`, `weight`, etc.).
- Devuelva SIEMPRE el mismo tipo de estructura interna:

```php
[
  [
    'service'      => 'Ground Shipping',
    'service_code' => 'ground',
    'base_price'   => 100.0,
    'currency'     => 'MXN',
  ],
  // ...
]
```

Así, el resto del sistema (`QuoteService`, `PricingService`) **no sabe ni le importa** si la respuesta original venía de un JSON raro de Estafeta, un XML de UPS o una API real.

### 3. `GenericHttpShippingProvider` + `ShippingProviderFactory`

En lugar de crear una clase por proveedor (`EstafetaProvider`, `DhlProvider`, `UpsProvider`, etc.), se diseña un **provider genérico basado en configuración**.

#### `ShippingProviderFactory`

- Recibe la entidad `ShippingProvider`.
- Crea la implementación correcta de `ShippingProviderInterface`.
- En este proyecto, devuelve siempre una instancia de `GenericHttpShippingProvider` parametrizada con:
  - `endpoint_url`
  - `format` (json/xml)
  - `request_config`
  - `response_config`

Esto permite:

- En el futuro, si se necesita algo muy particular, se podría crear otra implementación (ej: `DatabaseShippingProvider`) y la Factory decidiría cuál usar.
- Pero para la mayoría de casos HTTP/API, **un solo provider genérico es suficiente**.

#### `GenericHttpShippingProvider`

Esta clase hace tres cosas:

1. **Construir el request**:
   - A partir de `request_config`:
     - Decide si el cuerpo va en JSON o XML.
     - Decide qué campos incluir y bajo qué claves.
2. **Hacer la petición HTTP**:
   - Usa `Symfony HttpClient` contra `endpoint_url`.
3. **Normalizar la respuesta**:
   - Según `format` (`json` / `xml`):
     - `json` → `json_decode` a array.
     - `xml` → `simplexml_load_string` + conversión a array.
   - Según `response_config`:
     - Navega al `root_path` donde están las “quotes” o “services”.
     - Por cada item:
       - Lee las claves configuradas:
         - Estafeta: `service_label`, `service_code`, `amount`, `currency_code`.
         - DHL: `label`, `id`, `total`, `curr`.
         - UPS (XML): `Service.Description`, `Service.Code`, `TotalCharges.MonetaryValue`, `TotalCharges.CurrencyCode`.
       - Construye un array interno uniforme.

De esta forma, agregar un proveedor nuevo implica:

1. Insertar una fila nueva en `shipping_providers` (o un fixture nuevo).
2. Configurar:
   - `endpoint_url`
   - `format`
   - `request_config`
   - `response_config`

**No necesitas:**

- Crear una nueva clase PHP.
- Tocar `QuoteService`.
- Tocar `PricingService`.
- Escribir `if ($provider === 'X')` en ningún lado.

---

## 🌍 Integración con Proveedores (webhook.site)

Los proveedores de envío **no son servicios reales** en este entorno: se simulan usando `webhook.site`.

Cada proveedor (Estafeta, Fedex, DHL, UPS) tiene:

- Una **URL distinta** (configurada en variables de entorno).
- Un formato de respuesta diferente (como en la vida real).
- En el caso de Fedex, se simula un **error 500** para probar el manejo de errores.

Symfony, a través de `GenericHttpShippingProvider`, se adapta a cada formato usando la configuración almacenada en la base de datos (`request_config`, `response_config`, `format`).

### Variables de entorno de endpoints

En el `.env` del backend:

```dotenv
PROVIDER_ENDPOINT_ESTAFETA=https://webhook.site/149ddeec-4f0b-4c8e-878b-228183cd9f9a
PROVIDER_ENDPOINT_FEDEX=https://webhook.site/00ae61a2-c3b8-48e2-967c-0deadee50d4f
PROVIDER_ENDPOINT_DHL=https://webhook.site/0e961f91-1157-42b7-b1fd-de853d068440
PROVIDER_ENDPOINT_UPS=https://webhook.site/544226bf-a7e5-44af-9c04-14857cbef88e
```

### Configuración de respuestas por proveedor

#### Estafeta

Status code: `200`  
Content-Type: `application/json`

```json
{
  "data": {
    "quotes": [
      {
        "service_label": "Ground Shipping",
        "service_code": "ground",
        "amount": 100,
        "currency_code": "MXN"
      },
      {
        "service_label": "Express Shipping",
        "service_code": "express",
        "amount": 150,
        "currency_code": "MXN"
      }
    ]
  }
}
```

#### Fedex (error simulado)

Status code: `500`  
Content-Type: `application/json`

```json
{
  "error": "Internal Server Error",
  "message": "Service temporarily unavailable"
}
```

#### DHL

Status code: `200`  
Content-Type: `application/json`

```json
{
  "services": [
    {
      "label": "Economy Ground",
      "id": "ground",
      "total": 200,
      "curr": "MXN"
    },
    {
      "label": "Express",
      "id": "express",
      "total": 250,
      "curr": "MXN"
    }
  ]
}
```

#### UPS (XML)

Status code: `200`  
Content-Type: `application/xml`

```xml
<RateResponse>
  <RatedShipment>
    <Service>
      <Code>ground</Code>
      <Description>UPS Ground</Description>
    </Service>
    <TotalCharges>
      <MonetaryValue>180.00</MonetaryValue>
      <CurrencyCode>MXN</CurrencyCode>
    </TotalCharges>
  </RatedShipment>
  <RatedShipment>
    <Service>
      <Code>express</Code>
      <Description>UPS Express</Description>
    </Service>
    <TotalCharges>
      <MonetaryValue>230.00</MonetaryValue>
      <CurrencyCode>MXN</CurrencyCode>
    </TotalCharges>
  </RatedShipment>
</RateResponse>
```

---

## 🔐 Autenticación y Clerk

Este backend está diseñado para trabajar con **Clerk** como proveedor de autenticación y gestión de sesiones en el frontend.

Variables relevantes:

```dotenv
CLERK_JWKS_URL="https://nice-glider-42.clerk.accounts.dev/.well-known/jwks.json"
CLERK_ISSUER="https://nice-glider-42.clerk.accounts.dev"
CLERK_AUDIENCE=
```

En `src/Security/`:

- `ClerkJwtDecoder.php`: valida y decodifica los JWT de Clerk usando JWKS.
- `ClerkAuthenticator.php`: integra la autenticación basada en JWT con el sistema de seguridad de Symfony.

---

## ⚙️ Variables de Entorno (Backend)

```dotenv
APP_ENV=dev
APP_SECRET=9ca2f3861577a6ea0e089a4ae092137d

DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

CLERK_JWKS_URL="https://nice-glider-42.clerk.accounts.dev/.well-known/jwks.json"
CLERK_ISSUER="https://nice-glider-42.clerk.accounts.dev"
CLERK_AUDIENCE=

PROVIDER_ENDPOINT_ESTAFETA=https://webhook.site/149ddeec-4f0b-4c8e-878b-228183cd9f9a
PROVIDER_ENDPOINT_FEDEX=https://webhook.site/00ae61a2-c3b8-48e2-967c-0deadee50d4f
PROVIDER_ENDPOINT_DHL=https://webhook.site/0e961f91-1157-42b7-b1fd-de853d068440
PROVIDER_ENDPOINT_UPS=https://webhook.site/544226bf-a7e5-44af-9c04-14857cbef88e
```

---

## 🐳 Levantar el Backend con Docker

```bash
git clone https://github.com/raymundo-salazar/shipping-quote-backend.git
cd shipping-quote-backend

docker compose up --build -d

docker compose exec php bash
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --no-interaction
exit
```

API disponible en: `http://localhost:8000`

---

## 📡 Endpoint Principal: `/api/quotes`

```bash
curl -X POST http://localhost:8000/api/quotes   -H "Content-Type: application/json"   -d '{
    "origin_zipcode": "64000",
    "destination_zipcode": "03020",
    "weight": 1.5,
    "length": 20,
    "width": 15,
    "height": 10,
    "provider_id": 1
  }'
```

---

## 🧪 Pruebas (Backend)

```bash
docker compose exec php bash
php bin/phpunit tests/Service/PricingServiceTest.php
exit
```

---

## 🖥️ Frontend (React + Vite + TailwindCSS)

Frontend en repo separado: https://github.com/raymundo-salazar/shipping-quote-frontend

- Formulario para captura de datos del envío.
- Consumo del endpoint `POST /api/quotes`.
- Tabla de resultados (provider, servicio, precio base, markup, precio final, moneda, errores).
- Autenticación y sesiones con Clerk en el frontend.

---

## 🧹 Limpieza

```bash
docker compose down -v --remove-orphans
```
