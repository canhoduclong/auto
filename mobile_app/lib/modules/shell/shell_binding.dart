import 'package:get/get.dart';

import '../../data/services/app_version_service.dart';
import '../../data/services/auth_service.dart';
import '../../data/services/shipper_service.dart';
import '../../data/services/warehouse_service.dart';
import 'shell_controller.dart';

class ShellBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<AuthService>(() => AuthService());
    Get.lazyPut<AppVersionService>(() => AppVersionService());
    Get.lazyPut<ShipperService>(() => ShipperService());
    Get.lazyPut<WarehouseService>(() => WarehouseService());
    Get.lazyPut<ShellController>(() => ShellController());
  }
}
