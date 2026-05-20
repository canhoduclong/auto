import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../shipper/shipper_home_page.dart';
import '../warehouse/warehouse_home_page.dart';
import 'shell_controller.dart';

class ShellPage extends GetView<ShellController> {
  const ShellPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final isWarehouse = controller.currentLayout.value == 'warehouse';
      final pages = isWarehouse
          ? const [WarehouseHomePage(), Placeholder(), Placeholder(), Placeholder()]
          : const [ShipperHomePage(), Placeholder(), Placeholder(), Placeholder()];

      return Scaffold(
        appBar: AppBar(
          title: Text(isWarehouse ? 'Warehouse App' : 'Shipper App'),
          actions: [
            IconButton(onPressed: controller.logout, icon: const Icon(Icons.logout)),
          ],
        ),
        body: pages[controller.currentIndex.value],
        bottomNavigationBar: NavigationBar(
          selectedIndex: controller.currentIndex.value,
          onDestinationSelected: (index) => controller.currentIndex.value = index,
          destinations: const [
            NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
            NavigationDestination(icon: Icon(Icons.list_alt_outlined), selectedIcon: Icon(Icons.list_alt), label: 'Tasks'),
            NavigationDestination(icon: Icon(Icons.notifications_outlined), selectedIcon: Icon(Icons.notifications), label: 'Alerts'),
            NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Me'),
          ],
        ),
      );
    });
  }
}
