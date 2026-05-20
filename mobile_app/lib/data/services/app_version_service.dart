import 'dart:io';

import 'package:dio/dio.dart';
import 'package:get/get.dart';
import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/api_client.dart';

class AppVersionInfo {
  AppVersionInfo({
    required this.latestVersion,
    required this.forceUpdate,
    required this.apkUrl,
    required this.changelog,
  });

  final String latestVersion;
  final bool forceUpdate;
  final String apkUrl;
  final String changelog;

  factory AppVersionInfo.fromJson(Map<String, dynamic> json) {
    return AppVersionInfo(
      latestVersion: json['latest_version']?.toString() ?? '1.0.0',
      forceUpdate: json['force_update'] == true,
      apkUrl: json['apk_url']?.toString() ?? '',
      changelog: json['changelog']?.toString() ?? '',
    );
  }
}

class AppVersionService extends GetxService {
  final ApiClient _client = Get.find<ApiClient>();

  Future<AppVersionInfo> checkVersion() async {
    final response = await _client.dio.get(ApiEndpoints.appVersion);
    return AppVersionInfo.fromJson(Map<String, dynamic>.from(response.data as Map));
  }

  Future<String> downloadApk(String apkUrl) async {
    final dir = await getApplicationDocumentsDirectory();
    final filePath = '${dir.path}/auto-mobile-latest.apk';
    await _client.dio.download(apkUrl, filePath);
    return filePath;
  }

  Future<void> installApk(String filePath) async {
    if (!Platform.isAndroid) return;
    await OpenFilex.open(filePath);
  }
}
