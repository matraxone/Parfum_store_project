<?php
// Configurazione di base
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Imposta qui i dati del DB XAMPP
const DB_HOST = '127.0.0.1';
const DB_NAME = 'parfum_shop';
const DB_USER = 'root';
const DB_PASS = '';

// Base URL (aggiorna se necessario)
const BASE_URL = '/parfum_shop/public/';

// Email log file (simulazione invii)
const MAIL_LOG = __DIR__ . '/../mail.log';

// Uploads
const UPLOAD_DIR = __DIR__ . '/../public/uploads/';
const MAX_UPLOAD_SIZE = 2 * 1024 * 1024; // 2MB
const ALLOWED_IMAGE_TYPES = ['image/jpeg','image/png','image/gif'];

// CSRF token name
const CSRF_TOKEN_NAME = '_csrf';

// Development seeding/repairs (set to false in production)
const DEV_SEED = true;
