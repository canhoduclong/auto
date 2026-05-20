# Mobile API Contract

Base URL:
- `https://your-domain.com`

Auth:
- Bearer token via `Authorization: Bearer <token>`

## Public

### POST /api/mobile/auth/login
Body:
```json
{
  "email": "shipper@example.com",
  "password": "secret",
  "device_name": "Pixel 7",
  "platform": "android",
  "app_version": "1.0.0"
}
```

### GET /api/mobile/app-version
Response:
```json
{
  "latest_version": "1.0.5",
  "force_update": false,
  "apk_url": "https://your-domain.com/apk/app-v105.apk",
  "changelog": "Fix bugs"
}
```

## Protected

### GET /api/mobile/auth/me
### POST /api/mobile/auth/logout
### POST /api/mobile/auth/refresh
### GET /api/mobile/auth/sessions
### DELETE /api/mobile/auth/sessions/{id}

## Shipper

### GET /api/mobile/shipper/dashboard
### GET /api/mobile/shipper/available-orders
### POST /api/mobile/shipper/orders/{order}/accept
### GET /api/mobile/shipper/my-orders
### GET /api/mobile/shipper/history
### POST /api/mobile/shipper/orders/{order}/status
Body:
```json
{
  "status": "delivered",
  "collected_amount": 120000,
  "lat": 10.762,
  "lng": 106.681
}
```

### POST /api/mobile/shipper/orders/{order}/upload-proof
Multipart:
- `proof_image`: file

### POST /api/mobile/shipper/location
Body:
```json
{
  "lat": 10.762,
  "lng": 106.681,
  "accuracy": 7.2
}
```

### GET /api/mobile/shipper/notifications

## Warehouse

### GET /api/mobile/warehouse/dashboard
### GET /api/mobile/warehouse/orders
### POST /api/mobile/warehouse/orders/{order}/start-packing
### POST /api/mobile/warehouse/orders/{order}/complete-packing
### GET /api/mobile/warehouse/inventory
### GET /api/mobile/warehouse/products
### GET /api/mobile/warehouse/returns
### GET /api/mobile/warehouse/tasks
### GET /api/mobile/warehouse/scan-lookup?code=SKU_OR_BARCODE
### GET /api/mobile/warehouse/notifications
