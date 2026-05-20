import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';

import 'core/network/api_client.dart';
import 'core/storage/session_storage.dart';
import 'core/theme/app_theme.dart';
import 'routes/app_pages.dart';
import 'routes/app_routes.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await GetStorage.init();

  final storage = SessionStorage(GetStorage());
  Get.put<SessionStorage>(storage, permanent: true);
  Get.put<ApiClient>(ApiClient(), permanent: true);

  runApp(AutoMobileApp(initialRoute: storage.token == null ? AppRoutes.login : AppRoutes.shell));
}

class AutoMobileApp extends StatelessWidget {
  const AutoMobileApp({super.key, required this.initialRoute});

  final String initialRoute;

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: 'Auto Mobile',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      initialRoute: initialRoute,
      getPages: AppPages.routes,
    );
  }
}
