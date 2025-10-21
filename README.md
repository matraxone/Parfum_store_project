# Profumeria - E-commerce (PHP/MySQL)

Progetto didattico: e-commerce per la vendita di profumi e fragranze.

Tecnologie: PHP, MySQL, HTML, CSS, JS. Ambiente consigliato: XAMPP.

## Struttura

```
parfum_shop/
├─ public/
│  ├─ index.php
│  ├─ product.php
│  ├─ cart.php
│  ├─ checkout.php
│  ├─ login.php
│  ├─ register.php
│  ├─ assets/css/style.css
│  └─ assets/js/main.js
├─ admin/
│  ├─ index.php
│  ├─ products.php
│  ├─ orders.php
│  └─ users.php
├─ src/
│  ├─ config.php        (da configurare)
│  ├─ db.php            (PDO)
│  ├─ auth.php          (sessioni, ruoli, età)
│  ├─ helpers.php       (CSRF, sanitize, utils)
│  └─ mailer.php        (stub invio email)
├─ database/
│  ├─ schema.sql
│  └─ seed.sql
└─ uploads/
```

## Istruzioni (XAMPP)

1) Copia la cartella `parfum_shop` dentro `C:\\xampp\\htdocs` (o percorso equivalente).
2) Crea un database MySQL chiamato `parfum_shop` (collation utf8mb4_general_ci consigliata).
3) Importa `database/schema.sql` e poi `database/seed.sql`.
4) Apri `src/config.php` e imposta le credenziali DB (host, user, password) e il `BASE_URL`.
5) Avvia Apache e MySQL dal pannello XAMPP.
6) Visita http://localhost/parfum_shop/public

Nota: le email sono simulate e salvate su file di log (vedi `src/mailer.php`).

Se vuoi eseguire comandi PHP dal terminale di Windows, usa l'eseguibile di XAMPP (es. `C:\xampp\php\php.exe`) oppure aggiungi quella cartella al PATH.

## Sicurezza
- PDO con prepared statements
- password_hash / password_verify
- Token CSRF su form sensibili
- htmlspecialchars per output
- Upload sicuri (MIME/size) per immagini prodotti
- Ruoli: admin, customer

## Prossimi passi
- Integrare pagamenti reali (Stripe/PayPal)
- API REST
- Statistiche vendite
- PWA offline
