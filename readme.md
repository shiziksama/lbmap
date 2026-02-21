# LBMap

Цей репозиторій містить:

- пайплайн генерації OSM тайлів для велодоріжок (PostGIS + osmium + власні фільтри + osm2pgsql + Martin)

Нижче — інструкція «з нуля» з акцентом на запуск усіх команд з Docker.

## Передумови

- Встановлений Docker і Docker Compose.
- Достатньо місця на диску (planet файл дуже великий).

## 0) Налаштування `.env`

Docker Compose автоматично читає `.env` поруч із `docker-compose.yml`.

1. Скопіюйте `.env.example` у `.env`.
2. Вкажіть папку, яку потрібно змонтувати з файлами PBF:

```
DATA_DIR=./data
```

Можна вказати абсолютний шлях, наприклад `DATA_DIR=/mnt/d/osm`.

## 1) Підготувати папку з PBF

Покладіть `planet-latest.osm.pbf` у папку, яку ви вказали в `DATA_DIR`.

Приклад:

```
./data/planet-latest.osm.pbf
```

Усі проміжні та фінальні файли будуть створені у цій же папці.

## 2) Пайплайн фільтрації та підготовки даних

Ми вибираємо спочатку всі дороги, потім фільтруємо і тегаємо їх для подальшого імпорту в PostGIS та генерації тайлів.
Команда для запуску всього пайплайну:

```bash
docker compose --profile prepare run --rm pbf-pipeline
```

### Продовження після падіння

Скрипт орієнтується на останній вже існуючий файл пайплайну і починає з того кроку, того можна видалити всі попередні файли. Воно продовжить з попереднього кроку

## 3) Імпорт у PostGIS і запуск сервісів

### 3.1 Підняти базу та тайл-сервер

```bash
docker compose up -d
```

### 3.2 Імпорт `planet-filtered.osm.pbf`

```bash
docker compose --profile import run --rm osm2pgsql
```

### 3.3 Створити view для тайлів

```bash
docker compose exec -T postgres psql -U lbmap -d lbmap -f /sql/02_lbroads_tiles.sql
```

## 4) Де дивитись результат

- Сторінка: `http://localhost/`
- debug-сторінка, де можна дізнатись, чого саме ця дорога позначена як невідома: `http://localhost/debug`

## Довідка

- Маппінг osm2pgsql: `tileproduction/osm2pgsql/flex.lua`
- SQL view/index: `tileproduction/sql/02_lbroads_tiles.sql`
