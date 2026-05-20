import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../core/storage/session_storage.dart';
import '../../data/services/auth_service.dart';
import '../../routes/app_routes.dart';

class LoginController extends GetxController {
  final AuthService _authService = Get.find<AuthService>();
  final SessionStorage _sessionStorage = Get.find<SessionStorage>();

  final emailController = TextEditingController();
  final passwordController = TextEditingController();
  final loading = false.obs;
  final errorText = RxnString();

  Future<void> login() async {
    loading.value = true;
    errorText.value = null;

    try {
      final info = await PackageInfo.fromPlatform();
      final session = await _authService.login(
        email: emailController.text.trim(),
        password: passwordController.text,
        appVersion: info.version,
      );

      await _sessionStorage.saveSession(
        token: session.token,
        userName: session.userName,
        role: session.role,
        layout: session.layout,
      );

      Get.offAllNamed(AppRoutes.shell);
    } catch (e) {
      errorText.value = 'Dang nhap that bai. Vui long kiem tra tai khoan.';
    } finally {
      loading.value = false;
    }
  }

  @override
  void onClose() {
    emailController.dispose();
    passwordController.dispose();
    super.onClose();
  }
}
