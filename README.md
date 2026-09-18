# erp-jabalyagri

Sales approval & HR management system for Al-Jabali Agricultural Company.

## Stack

Laravel 13, PHP 8.3+, Filament v5, Livewire v4, Tailwind CSS 4, MySQL 8.

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
composer run dev
```

## Testing

```bash
php artisan test
```
