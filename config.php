<?php
return [
    'app_name' => getenv('GE_APP_NAME') ?: 'Global Energie',
    'base_url' => getenv('GE_BASE_URL') ?: '',

    // Default admin password can be set with GE_ADMIN_PASSWORD. Change it after first login in production.
    // Configure these variables in Railway / cPanel / Apache environment.
    'db' => [
        'host' => getenv('GE_DB_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('GE_DB_PORT') ?: 3306),
        'name' => getenv('GE_DB_NAME') ?: 'spix',
        'user' => getenv('GE_DB_USER') ?: 'root',
        'pass' => getenv('GE_DB_PASS') ?: '',
        'charset' => getenv('GE_DB_CHARSET') ?: 'utf8mb4',
    ],

    'mail' => [
        'host' => getenv('GE_SMTP_HOST') ?: '',
        'port' => (int)(getenv('GE_SMTP_PORT') ?: 587),
        'username' => getenv('GE_SMTP_USERNAME') ?: '',
        'password' => getenv('GE_SMTP_PASSWORD') ?: '',
        'from_email' => getenv('GE_SMTP_FROM_EMAIL') ?: 'no-reply@example.com',
        'from_name' => getenv('GE_SMTP_FROM_NAME') ?: 'Global Energie',
        'secure' => getenv('GE_SMTP_SECURE') ?: 'tls',
        // Resend support. Put RESEND_API_KEY in Railway Variables when using Resend.
        'resend_api_key' => getenv('RESEND_API_KEY') ?: getenv('GE_RESEND_API_KEY') ?: getenv('RESEND_KEY') ?: getenv('RESEND_APIKEY') ?: '',
    ],

    'security' => [
        'force_https' => filter_var(getenv('GE_FORCE_HTTPS') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'session_samesite' => getenv('GE_SESSION_SAMESITE') ?: 'Lax',
        'admin_password' => getenv('GE_ADMIN_PASSWORD') ?: '',
    ],

    'tenancy' => [
        // Multi-tenancy / Row-Level Security. Keep enabled in production.
        'enabled' => filter_var(getenv('GE_MULTI_TENANCY_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        'default_tenant_id' => (int)(getenv('GE_DEFAULT_TENANT_ID') ?: 1),
        'default_tenant_slug' => getenv('GE_DEFAULT_TENANT_SLUG') ?: 'global-energie',
        'default_tenant_name' => getenv('GE_DEFAULT_TENANT_NAME') ?: 'Global Energie',
    ],

    'uploads' => [
        'max_image_mb' => (int)(getenv('GE_UPLOAD_IMAGE_MAX_MB') ?: 5),
        'max_document_mb' => (int)(getenv('GE_UPLOAD_DOCUMENT_MAX_MB') ?: 15),
        'allowed_images' => ['jpg','jpeg','png','webp','gif'],
        'allowed_documents' => ['pdf','jpg','jpeg','png','webp'],
    ],

    'backup' => [
        // Stored outside public web root in /storage/backups.
        'keep_last' => (int)(getenv('GE_BACKUP_KEEP_LAST') ?: 20),
    ],

    'stock_alert' => [
        'enabled' => filter_var(getenv('GE_STOCK_ALERT_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        'threshold' => (float)(getenv('GE_STOCK_ALERT_THRESHOLD') ?: 4),
        'email' => getenv('GE_STOCK_ALERT_EMAIL') ?: getenv('GE_ALERT_EMAIL') ?: getenv('GE_ADMIN_EMAIL') ?: (getenv('GE_SMTP_FROM_EMAIL') ?: ''),
    ],
];
