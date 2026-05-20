import 'package:get/get.dart';
import 'package:dio/dio.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/api_client.dart';

class ShipperService extends GetxService {
  final ApiClient _client = Get.find<ApiClient>();

  Future<Map<String, dynamic>> dashboard() async {
    final response = await _client.dio.get(ApiEndpoints.shipperDashboard);
    return Map<String, dynamic>.from(response.data['data'] as Map);
  }

  Future<List<dynamic>> myOrders() async {
    final response = await _client.dio.get(ApiEndpoints.shipperMyOrders);
    return (response.data['data'] as List<dynamic>? ?? const []);
  }

  Future<List<dynamic>> history() async {
    final response = await _client.dio.get(ApiEndpoints.shipperHistory);
    return (response.data['data'] as List<dynamic>? ?? const []);
  }

  Future<List<dynamic>> availableOrders() async {
    final response = await _client.dio.get(ApiEndpoints.shipperAvailableOrders);
    return (response.data['data'] as List<dynamic>? ?? const []);
  }

  Future<void> acceptOrder(int orderId) async {
    await _client.dio.post('/api/mobile/shipper/orders/$orderId/accept');
  }

  Future<void> updateStatus({
    required int orderId,
    required String status,
    double? collectedAmount,
    String? returnReason,
    double? lat,
    double? lng,
  }) async {
    await _client.dio.post(
      '/api/mobile/shipper/orders/$orderId/status',
      data: {
        'status': status,
        'collected_amount': collectedAmount,
        'return_reason': returnReason,
        'lat': lat,
        'lng': lng,
      },
    );
  }

  Future<void> uploadProof(int orderId, String filePath) async {
    final formData = FormData.fromMap({
      'proof_image': await MultipartFile.fromFile(filePath),
    });
    await _client.dio.post('/api/mobile/shipper/orders/$orderId/upload-proof', data: formData);
  }

  Future<void> sendLocation(double lat, double lng, {double? accuracy}) async {
    await _client.dio.post(ApiEndpoints.shipperLocation, data: {
      'lat': lat,
      'lng': lng,
      'accuracy': accuracy,
    });
  }

  Future<List<dynamic>> notifications() async {
    final response = await _client.dio.get(ApiEndpoints.shipperNotifications);
    return (response.data['data'] as List<dynamic>? ?? const []);
  }
}
