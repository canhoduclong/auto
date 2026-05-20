class AuthSession {
  AuthSession({
    required this.token,
    required this.userName,
    required this.role,
    required this.layout,
    required this.menu,
  });

  final String token;
  final String userName;
  final String role;
  final String layout;
  final List<Map<String, dynamic>> menu;

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    final user = (json['user'] as Map<String, dynamic>? ?? {});
    return AuthSession(
      token: json['token']?.toString() ?? '',
      userName: user['name']?.toString() ?? '',
      role: user['role']?.toString() ?? '',
      layout: user['layout']?.toString() ?? '',
      menu: (user['menu'] as List<dynamic>? ?? const [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList(),
    );
  }
}
