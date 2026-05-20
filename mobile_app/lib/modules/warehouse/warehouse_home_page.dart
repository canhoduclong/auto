import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import 'warehouse_controller.dart';

class WarehouseHomePage extends StatelessWidget {
  const WarehouseHomePage({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(WarehouseController());

    return Obx(() {
      if (controller.loading.value) {
        return const Center(child: CircularProgressIndicator());
      }

      return RefreshIndicator(
        onRefresh: controller.loadAll,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Warehouse Dashboard', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    Text('Cho dong goi: ${controller.dashboard['ready_to_pack'] ?? 0}'),
                    Text('Dang dong goi: ${controller.dashboard['packing'] ?? 0}'),
                    Text('Cho ship nhan: ${controller.dashboard['packed_waiting_pickup'] ?? 0}'),
                    const SizedBox(height: 12),
                    FilledButton.icon(
                      onPressed: () async {
                        final code = await Get.to<String>(() => const _ScanPage());
                        if (code != null && code.isNotEmpty) {
                          await controller.scanLookup(code);
                        }
                      },
                      icon: const Icon(Icons.qr_code_scanner),
                      label: const Text('Scan QR/Barcode'),
                    ),
                    if (controller.scanResult.value != null) ...[
                      const SizedBox(height: 8),
                      Text('Scan: ${controller.scanResult.value!['sku'] ?? ''} - ${controller.scanResult.value!['name'] ?? ''}'),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Text('Don can xu ly', style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            ...controller.orders.map((item) {
              final map = Map<String, dynamic>.from(item as Map);
              final id = map['id'] as int? ?? 0;
              final status = map['status']?.toString() ?? '';
              return Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('${map['code'] ?? '#$id'} - ${map['customer'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w600)),
                      const SizedBox(height: 4),
                      Text('${map['address'] ?? ''}'),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        children: [
                          if (status == 'approved' || status == 'ready_to_pack')
                            FilledButton(
                              onPressed: () => controller.startPacking(id),
                              child: const Text('Bat dau dong goi'),
                            ),
                          if (status == 'packing')
                            FilledButton.tonal(
                              onPressed: () => controller.completePacking(id),
                              child: const Text('Hoan tat dong goi'),
                            ),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }),
            const SizedBox(height: 12),
            const Text('Nhiem vu kho', style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            ...controller.tasks.map((item) {
              final map = Map<String, dynamic>.from(item as Map);
              return Card(
                child: ListTile(
                  title: Text(map['title']?.toString() ?? 'Task'),
                  subtitle: Text(map['status']?.toString() ?? ''),
                ),
              );
            }),
          ],
        ),
      );
    });
  }
}

class _ScanPage extends StatelessWidget {
  const _ScanPage();

  @override
  Widget build(BuildContext context) {
    final scanned = ''.obs;

    return Scaffold(
      appBar: AppBar(title: const Text('Scan ma')),
      body: Column(
        children: [
          Expanded(
            child: MobileScanner(
              onDetect: (capture) {
                final value = capture.barcodes.firstOrNull?.rawValue;
                if (value != null && value.isNotEmpty && scanned.value.isEmpty) {
                  scanned.value = value;
                  Get.back(result: value);
                }
              },
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Obx(() => Text('Scanned: ${scanned.value}')),
          ),
        ],
      ),
    );
  }
}
