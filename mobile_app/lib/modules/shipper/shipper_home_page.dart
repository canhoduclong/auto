import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'shipper_controller.dart';

class ShipperHomePage extends StatelessWidget {
  const ShipperHomePage({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(ShipperController());

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
                    const Text('Shipper Dashboard', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                    const SizedBox(height: 8),
                    Text('Dang giao: ${controller.dashboard['delivering'] ?? 0}'),
                    Text('Da giao hom nay: ${controller.dashboard['delivered_today'] ?? 0}'),
                    Text('Don co the nhan: ${controller.dashboard['available'] ?? 0}'),
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      children: [
                        FilledButton.icon(
                          onPressed: controller.sendGps,
                          icon: const Icon(Icons.my_location),
                          label: const Text('Gui GPS'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            const Text('Don co the nhan', style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            ...controller.availableOrders.map((item) {
              final map = Map<String, dynamic>.from(item as Map);
              final id = map['id'] as int? ?? 0;
              final phone = map['phone']?.toString() ?? '';
              return Card(
                child: ListTile(
                  title: Text('${map['code'] ?? '#$id'} - ${map['customer'] ?? ''}'),
                  subtitle: Text('${map['address'] ?? ''}\n${map['status'] ?? ''}'),
                  isThreeLine: true,
                  trailing: Wrap(
                    spacing: 8,
                    children: [
                      IconButton(
                        onPressed: () => controller.callCustomer(phone),
                        icon: const Icon(Icons.call),
                      ),
                      FilledButton(
                        onPressed: () => controller.acceptOrder(id),
                        child: const Text('Nhan'),
                      ),
                    ],
                  ),
                ),
              );
            }),
            const SizedBox(height: 12),
            const Text('Don giao cua toi', style: TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: 6),
            ...controller.myOrders.map((item) {
              final map = Map<String, dynamic>.from(item as Map);
              final id = map['id'] as int? ?? 0;
              final status = map['status']?.toString() ?? '';
              final phone = map['phone']?.toString() ?? '';
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
                        runSpacing: 8,
                        children: [
                          OutlinedButton.icon(
                            onPressed: () => controller.callCustomer(phone),
                            icon: const Icon(Icons.call),
                            label: const Text('Goi khach'),
                          ),
                          OutlinedButton.icon(
                            onPressed: () => controller.uploadProof(id),
                            icon: const Icon(Icons.camera_alt),
                            label: const Text('Upload anh'),
                          ),
                          if (status == 'delivering')
                            FilledButton(
                              onPressed: () => controller.markDelivered(id),
                              child: const Text('Da giao'),
                            ),
                          if (status == 'delivering')
                            FilledButton.tonal(
                              onPressed: () => controller.markReturning(id, 'Khach khong nhan'),
                              child: const Text('Tra hang'),
                            ),
                        ],
                      )
                    ],
                  ),
                ),
              );
            }),
          ],
        ),
      );
    });
  }
}
