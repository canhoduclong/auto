import 'package:get/get.dart';

import '../../core/constants/api_endpoints.dart';
import '../../core/network/api_client.dart';

class WarehouseService extends GetxService {
  final ApiClient _client = Get.find<ApiClient>();

  Future<Map<String, dynamic>> dashboard() async {
    final response = await _client.dio.get(ApiEndpoints.warehouseDashboard);
    return Map<String, dynamic>.from(response.data['data'] as Map);
  }

  Future<List<dynamic>> orders() async {
    final response = await _client.dio.get(ApiEndpoints.warehouseOrders);
    return (response.data['data'] as List<dynamic>? ?? const []);
  }

  Future<List<dynamic>> tasks() async {
    final response = await _client.dio.get(ApiEndpoints.warehouseTasks);
    return (response.data['data'] as List<dynamic>? ?? const []);
  }

  Future<void> startPacking(int orderId) async {
    await _client.dio.post('/api/mobile/warehouse/orders/$orderId/start-packing');
  }

  Future<void> completePacking(int orderId) async {
    await _client.dio.post('/api/mobile/warehouse/orders/$orderId/complete-packing');
  }

  Future<Map<String, dynamic>> scanLookup(String code) async {
    final response = await _client.dio.get(ApiEndpoints.warehouseScanLookup, queryParameters: {'code': code});
    return Map<String, dynamic>.from(response.data['data'] as Map? ?? const {});
  }

  Future<List<dynamic>> notifications() async {
    final response = await _client.dio.get(ApiEndpoints.warehouseNotifications);
    return (response.data['data'] as List<dynamic>? ?? const []);
  }
}
