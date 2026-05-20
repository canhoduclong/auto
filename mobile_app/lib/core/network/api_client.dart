import 'package:dio/dio.dart';
import 'package:get/get.dart' hide Response;

import '../constants/api_endpoints.dart';
import '../storage/session_storage.dart';

class ApiClient extends GetxService {
  late final Dio dio;

  final SessionStorage _sessionStorage = Get.find<SessionStorage>();

  @override
  void onInit() {
    super.onInit();

    dio = Dio(
      BaseOptions(
        baseUrl: ApiEndpoints.baseUrl,
        connectTimeout: const Duration(seconds: 20),
        receiveTimeout: const Duration(seconds: 30),
        headers: {'Accept': 'application/json'},
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          final token = _sessionStorage.token;
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }
}
