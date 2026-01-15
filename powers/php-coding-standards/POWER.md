---
name: "php-coding-standards"
displayName: "PHP Coding Standards"
description: "PHP coding standards and best practices for the LINE Telepharmacy Platform"
keywords: ["php", "coding", "standards", "psr", "telepharmacy"]
author: "Your Team"
---

# PHP Coding Standards

## Overview

This power provides PHP coding standards and best practices specifically tailored for the LINE Telepharmacy Platform. It covers database access patterns, API response formats, and service class conventions.

## Onboarding

No installation required - this is a knowledge base power that provides guidance when writing PHP code.

## Common Patterns

### Database Access
Always use PDO with prepared statements:
```php
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

### API Response Format
All API endpoints should return consistent JSON:
```php
echo json_encode([
    'success' => true,
    'message' => 'Operation completed',
    'data' => $result
]);
```

### Service Class Pattern
```php
class SomeService {
    private $db;
    private $lineAccountId;
    
    public function __construct($db, $lineAccountId = null) {
        $this->db = $db;
        $this->lineAccountId = $lineAccountId;
    }
}
```

## Best Practices

- Use UTF-8 encoding throughout (Thai language support)
- PDO with prepared statements for all queries
- Session-based authentication for admin panel
- LINE user ID for customer identification in LIFF
- Role-based menu access (owner, admin, pharmacist, staff, marketing, tech)
