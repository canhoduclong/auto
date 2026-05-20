import 'package:get/get.dart';

import '../modules/auth/login_binding.dart';
import '../modules/auth/login_page.dart';
import '../modules/shell/shell_binding.dart';
import '../modules/shell/shell_page.dart';
import 'app_routes.dart';

class AppPages {
  static final routes = <GetPage<dynamic>>[
    GetPage(
      name: AppRoutes.login,
      page: () => const LoginPage(),
      binding: LoginBinding(),
    ),
    GetPage(
      name: AppRoutes.shell,
      page: () => const ShellPage(),
      binding: ShellBinding(),
    ),
  ];
}
