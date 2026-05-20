import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'login_controller.dart';

class LoginPage extends GetView<LoginController> {
  const LoginPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 460),
              child: Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Auto CRM Mobile', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      const Text('Dang nhap de vao dung layout theo role.'),
                      const SizedBox(height: 16),
                      TextField(
                        controller: controller.emailController,
                        decoration: const InputDecoration(labelText: 'Email'),
                        keyboardType: TextInputType.emailAddress,
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: controller.passwordController,
                        decoration: const InputDecoration(labelText: 'Mat khau'),
                        obscureText: true,
                      ),
                      const SizedBox(height: 12),
                      Obx(() => controller.errorText.value == null
                          ? const SizedBox.shrink()
                          : Text(controller.errorText.value!, style: const TextStyle(color: Colors.red))),
                      const SizedBox(height: 16),
                      Obx(() => SizedBox(
                            width: double.infinity,
                            child: FilledButton(
                              onPressed: controller.loading.value ? null : controller.login,
                              child: controller.loading.value
                                  ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2))
                                  : const Text('Dang nhap'),
                            ),
                          )),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
