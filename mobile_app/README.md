# Auto Mobile (Flutter + GetX)

## 1) Setup

1. Install Flutter stable (3.24+ recommended).
2. Open folder `mobile_app`.
3. Run:

```bash
flutter pub get
```

## 2) Run app

```bash
flutter run --dart-define=API_BASE_URL=https://hoanglongtnt.com
```

## 3) Android release APK

```bash
flutter build apk --release --dart-define=API_BASE_URL=https://hoanglongtnt.com
```

APK output:
- `build/app/outputs/flutter-apk/app-release.apk`

## 4) Internal update by APK

App checks API:
- `GET /api/mobile/app-version`

Response example:

```json
{
  "latest_version": "1.0.5",
  "force_update": false,
  "apk_url": "https://hoanglongtnt.com/apk/app-v105.apk",
  "changelog": "Sua loi va toi uu toc do"
}
```

When newer version detected, app shows update popup and downloads APK, then opens installer.

## 5) Upload APK to server

1. Upload file to public URL, example: `https://hoanglongtnt.com/apk/app-v105.apk`.
2. Update Laravel settings table:
- `mobile_latest_version`
- `mobile_force_update`
- `mobile_apk_url`
- `mobile_changelog`

## 6) API auth flow

1. `POST /api/mobile/auth/login`
2. Save bearer token returned.
3. Include `Authorization: Bearer <token>` for protected endpoints.
4. `POST /api/mobile/auth/logout` to revoke token.
