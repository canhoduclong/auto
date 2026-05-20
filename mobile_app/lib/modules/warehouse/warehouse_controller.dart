import 'package:get/get.dart';

import '../../data/services/warehouse_service.dart';

class WarehouseController extends GetxController {
  final WarehouseService _service = Get.find<WarehouseService>();

  final loading = false.obs;
  final dashboard = <String, dynamic>{}.obs;
  final orders = <dynamic>[].obs;
  final tasks = <dynamic>[].obs;
  final scanResult = RxnMap<String, dynamic>();

  @override
  void onInit() {
    super.onInit();
    loadAll();
  }

  Future<void> loadAll() async {
    loading.value = true;
    try {
      dashboard.value = await _service.dashboard();
      orders.value = await _service.orders();
      tasks.value = await _service.tasks();
    } finally {
      loading.value = false;
    }
  }

  Future<void> startPacking(int orderId) async {
    await _service.startPacking(orderId);
    await loadAll();
  }

  Future<void> completePacking(int orderId) async {
    await _service.completePacking(orderId);
    await loadAll();
  }

  Future<void> scanLookup(String code) async {
    scanResult.value = await _service.scanLookup(code);
  }
}
