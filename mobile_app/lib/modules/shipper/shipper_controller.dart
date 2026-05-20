import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../data/services/shipper_service.dart';

class ShipperController extends GetxController {
  final ShipperService _service = Get.find<ShipperService>();

  final loading = false.obs;
  final dashboard = <String, dynamic>{}.obs;
  final availableOrders = <dynamic>[].obs;
  final myOrders = <dynamic>[].obs;
  final history = <dynamic>[].obs;
  final notifications = <dynamic>[].obs;

  @override
  void onInit() {
    super.onInit();
    loadAll();
  }

  Future<void> loadAll() async {
    loading.value = true;
    try {
      dashboard.value = await _service.dashboard();
      availableOrders.value = await _service.availableOrders();
      myOrders.value = await _service.myOrders();
      history.value = await _service.history();
      notifications.value = await _service.notifications();
    } finally {
      loading.value = false;
    }
  }

  Future<void> acceptOrder(int orderId) async {
    await _service.acceptOrder(orderId);
    await loadAll();
  }

  Future<void> markDelivered(int orderId, {double? collectedAmount}) async {
    await _service.updateStatus(orderId: orderId, status: 'delivered', collectedAmount: collectedAmount);
    await loadAll();
  }

  Future<void> markReturning(int orderId, String reason) async {
    await _service.updateStatus(orderId: orderId, status: 'returning', returnReason: reason);
    await loadAll();
  }

  Future<void> uploadProof(int orderId) async {
    final picker = ImagePicker();
    final file = await picker.pickImage(source: ImageSource.camera, imageQuality: 75);
    if (file == null) return;
    await _service.uploadProof(orderId, file.path);
    Get.snackbar('Thanh cong', 'Da upload anh giao hang');
  }

  Future<void> sendGps() async {
    final permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      final requested = await Geolocator.requestPermission();
      if (requested == LocationPermission.denied || requested == LocationPermission.deniedForever) {
        Get.snackbar('Loi', 'Khong co quyen GPS');
        return;
      }
    }

    final position = await Geolocator.getCurrentPosition();
    await _service.sendLocation(position.latitude, position.longitude, accuracy: position.accuracy);
    Get.snackbar('GPS', 'Da cap nhat vi tri');
  }

  Future<void> callCustomer(String phone) async {
    if (phone.trim().isEmpty) return;
    final uri = Uri.parse('tel:$phone');
    await launchUrl(uri);
  }
}
