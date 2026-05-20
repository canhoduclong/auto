import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:package_info_plus/package_info_plus.dart';

import '../../core/storage/session_storage.dart';
import '../../data/services/app_version_service.dart';
import '../../data/services/auth_service.dart';
import '../../routes/app_routes.dart';

class ShellController extends GetxController {
  final SessionStorage _sessionStorage = Get.find<SessionStorage>();
  final AuthService _authService = Get.find<AuthService>();
  final AppVersionService _versionService = Get.find<AppVersionService>();

  final currentIndex = 0.obs;
  final currentLayout = 'shipper'.obs;

  @override
  void onInit() {
    super.onInit();
    currentLayout.value = _sessionStorage.layout ?? 'shipper';
    _checkVersion();
  }

  Future<void> logout() async {
    try {
      await _authService.logout();
    } catch (_) {
      // Ignore network errors and clear local session anyway.
    }
    await _sessionStorage.clear();
    Get.offAllNamed(AppRoutes.login);
  }

  Future<void> _checkVersion() async {
    try {
      final info = await PackageInfo.fromPlatform();
      final remote = await _versionService.checkVersion();
      if (_isRemoteNewer(remote.latestVersion, info.version)) {
        await _showUpdateDialog(remote);
      }
    } catch (_) {
      // Keep app usable if version check fails.
    }
  }

  bool _isRemoteNewer(String remote, String local) {
    List<int> parse(String v) => v.split('.').map((e) => int.tryParse(e) ?? 0).toList();
    final r = parse(remote);
    final l = parse(local);
    for (int i = 0; i < 3; i++) {
      final rv = i < r.length ? r[i] : 0;
      final lv = i < l.length ? l[i] : 0;
      if (rv > lv) return true;
      if (rv < lv) return false;
    }
    return false;
  }

  Future<void> _showUpdateDialog(AppVersionInfo version) async {
    await Get.dialog(
      AlertDialog(
        title: const Text('Co phien ban moi'),
        content: Text('Phien ban moi: ${version.latestVersion}\n${version.changelog}'),
        actions: [
          if (!version.forceUpdate)
            TextButton(
              onPressed: () => Get.back(),
              child: const Text('De sau'),
            ),
          FilledButton(
            onPressed: () async {
              if (version.apkUrl.isEmpty) {
                Get.back();
                return;
              }
              final filePath = await _versionService.downloadApk(version.apkUrl);
              await _versionService.installApk(filePath);
              if (Get.isDialogOpen ?? false) {
                Get.back();
              }
            },
            child: const Text('Tai va cap nhat'),
          ),
        ],
      ),
      barrierDismissible: !version.forceUpdate,
    );
  }
}
