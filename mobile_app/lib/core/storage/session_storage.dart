import 'package:get_storage/get_storage.dart';

class SessionStorage {
  SessionStorage(this._box);

  final GetStorage _box;

  static const String tokenKey = 'access_token';
  static const String userNameKey = 'user_name';
  static const String roleKey = 'role';
  static const String layoutKey = 'layout';

  String? get token => _box.read<String>(tokenKey);
  String? get role => _box.read<String>(roleKey);
  String? get layout => _box.read<String>(layoutKey);

  Future<void> saveSession({
    required String token,
    required String userName,
    required String role,
    required String layout,
  }) async {
    await _box.write(tokenKey, token);
    await _box.write(userNameKey, userName);
    await _box.write(roleKey, role);
    await _box.write(layoutKey, layout);
  }

  Future<void> clear() async {
    await _box.remove(tokenKey);
    await _box.remove(userNameKey);
    await _box.remove(roleKey);
    await _box.remove(layoutKey);
  }
}
