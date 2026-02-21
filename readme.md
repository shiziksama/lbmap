# LBMap

Цей репозиторій містить:

- пайплайн генерації OSM тайлів (PostGIS + osmium + власні фільтри + osm2pgsql + Martin) у `tileproduction`
- легасі-сторінку, яку сервить Nginx

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

Можна вказати абсолютний шлях, наприклад `DATA_DIR=/mnt/d/downloads`.

## 1) Підготувати папку з PBF

Покладіть `planet-latest.osm.pbf` у папку, яку ви вказали в `DATA_DIR`.

Приклад:

```
./data/planet-latest.osm.pbf
```

Усі проміжні та фінальні файли будуть створені у цій же папці.

## 2) Один контейнер, весь пайплайн

Якщо хочете все однією командою:

```bash
docker compose --profile prepare run --rm pbf-pipeline
```

### Продовжити після падіння

Скрипт орієнтується на останній вже існуючий файл пайплайну і починає з того кроку, того можна видалити всі попередні файли. Воно продовжить з попереднього кроку

## 3) Імпорт у PostGIS і запуск сервісів

### 3.1 Підняти базу та тайл-сервер

```bash
docker compose up -d postgres martin
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

- Тайли Martin: `http://localhost:3000/tiles/lbroads_tiles/{z}/{x}/{y}.pbf`
- Легасі сторінка: `http://localhost/`

## Довідка

- Маппінг osm2pgsql: `tileproduction/osm2pgsql/flex.lua`
- SQL view/index: `tileproduction/sql/02_lbroads_tiles.sql`
- Усі теги зберігаються в `osm_lbroads.tags`
