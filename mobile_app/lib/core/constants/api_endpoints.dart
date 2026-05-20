class ApiEndpoints {
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://hoanglongtnt.com',
  );

  static const String login = '/api/mobile/auth/login';
  static const String me = '/api/mobile/auth/me';
  static const String logout = '/api/mobile/auth/logout';
  static const String refresh = '/api/mobile/auth/refresh';
  static const String sessions = '/api/mobile/auth/sessions';
  static const String appVersion = '/api/mobile/app-version';

  static const String shipperDashboard = '/api/mobile/shipper/dashboard';
  static const String shipperAvailableOrders = '/api/mobile/shipper/available-orders';
  static const String shipperMyOrders = '/api/mobile/shipper/my-orders';
  static const String shipperHistory = '/api/mobile/shipper/history';
  static const String shipperLocation = '/api/mobile/shipper/location';
  static const String shipperNotifications = '/api/mobile/shipper/notifications';

  static const String warehouseDashboard = '/api/mobile/warehouse/dashboard';
  static const String warehouseOrders = '/api/mobile/warehouse/orders';
  static const String warehouseInventory = '/api/mobile/warehouse/inventory';
  static const String warehouseProducts = '/api/mobile/warehouse/products';
  static const String warehouseReturns = '/api/mobile/warehouse/returns';
  static const String warehouseTasks = '/api/mobile/warehouse/tasks';
  static const String warehouseScanLookup = '/api/mobile/warehouse/scan-lookup';
  static const String warehouseNotifications = '/api/mobile/warehouse/notifications';
}
