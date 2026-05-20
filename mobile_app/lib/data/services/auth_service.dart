import 'package:get/get.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/api_client.dart';
import '../models/auth_session.dart';

class AuthService extends GetxService {
  final ApiClient _client = Get.find<ApiClient>();

  Future<AuthSession> login({
    required String email,
    required String password,
    required String appVersion,
  }) async {
    final response = await _client.dio.post(
      ApiEndpoints.login,
      data: {
        'email': email,
        'password': password,
        'device_name': 'flutter-android',
        'platform': 'android',
        'app_version': appVersion,
      },
    );

    final payload = Map<String, dynamic>.from(response.data as Map);
    final data = Map<String, dynamic>.from(payload['data'] as Map? ?? const {});
    return AuthSession.fromJson(data);
  }

  Future<void> logout() async {
    await _client.dio.post(ApiEndpoints.logout);
  }

  Future<String> refreshToken() async {
    final response = await _client.dio.post(ApiEndpoints.refresh);
    final data = Map<String, dynamic>.from(response.data as Map);
    final inner = Map<String, dynamic>.from(data['data'] as Map? ?? const {});
    return inner['token']?.toString() ?? '';
  }

  Future<List<dynamic>> sessions() async {
    final response = await _client.dio.get(ApiEndpoints.sessions);
    final data = Map<String, dynamic>.from(response.data as Map);
    return (data['data'] as List<dynamic>? ?? const []);
  }
}
