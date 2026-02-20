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

## 2) Згенерувати `planet-filtered.osm.pbf` вручну (крок за кроком)

Команди потрібно запускати послідовно, одна за одною:

### 2.1 Витягнути тільки `highway=*`

```bash
docker compose --profile prepare run --rm pbf-highways
```

Результат: `planet-highways.osm.pbf`

### 2.2 Фільтрація C++ (filter_osmium_cpp)

```bash
docker compose --profile prepare run --rm pbf-filter-cpp
```

Результат: `planet-filtered-cpp.osm.pbf`

### 2.3 Фільтрація Python (filter_osmium.py)

```bash
docker compose --profile prepare run --rm pbf-filter-py
```

Результат: `planet-filtered.osm.pbf`

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
