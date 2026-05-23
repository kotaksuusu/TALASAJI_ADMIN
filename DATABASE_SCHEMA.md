# DATABASE SCHEMA — TALASAJI

Source of truth untuk kedua repository (talasaji-admin dan talasaji-app).

## Tabel

### users
| Column | Type | Notes |
|--------|------|-------|
| id | uuid | Primary key |
| name | string | |
| email | string | Unique |
| password | string | |
| role | string | admin/penjual/pelanggan |
| phone_number | string | Nullable |
| avatar_url | string | Nullable |
| timestamps | | |

### stores
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| user_id | uuid | FK → users |
| name | string | |
| description | text | Nullable |
| address | string | Nullable |
| phone | string | Nullable |
| logo | string | Nullable |
| latitude | decimal(10,8) | Nullable |
| longitude | decimal(11,8) | Nullable |
| radius_meter | integer | Default 50 |
| operational_status | string | open/closed |
| registration_status | string | pending/active/rejected |
| rejection_reason | string | Nullable |
| rejection_category | string | Nullable |
| open_time | time | Nullable |
| close_time | time | Nullable |
| category | string | Nullable |
| service_type | string | Nullable |
| timestamps | | |

### categories
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| store_id | bigint | FK → stores |
| name | string | |
| description | text | Nullable |
| display_order | integer | Default 0 |
| icon | string | Nullable |
| is_active | boolean | Default true |
| timestamps | | |

### menus
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| store_id | bigint | FK → stores |
| category_id | bigint | FK → categories |
| name | string | |
| description | text | Nullable |
| price | decimal(10,2) | |
| image | string | Nullable |
| stock_status | string | tersedia/habis |
| is_recommended | boolean | Default false |
| display_order | integer | Default 0 |
| timestamps | | |

### tables
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| store_id | bigint | FK → stores |
| number | integer | |
| capacity | integer | |
| status | string | available/occupied/reserved |
| qr_code_content | string | Nullable |
| timestamps | | |

### orders
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| table_id | bigint | FK → tables, Nullable |
| user_id | uuid | FK → users |
| store_id | bigint | FK → stores |
| status | string | pending/confirmed/preparing/ready/completed/cancelled |
| total_amount | decimal(10,2) | |
| service_type | string | dine_in/take_away |
| notes | text | Nullable |
| location_validation | boolean | Default false |
| timestamps | | |

### order_items
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| order_id | bigint | FK → orders |
| menu_id | bigint | FK → menus |
| menu_name | string | |
| quantity | integer | |
| price | decimal(10,2) | |
| subtotal | decimal(10,2) | |
| notes | text | Nullable |
| timestamps | | |

### payments
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| order_id | bigint | FK → orders |
| amount | decimal(10,2) | |
| payment_method | string | |
| payment_status | string | pending/confirmed/cancelled |
| payment_proof | string | Nullable |
| payment_date | timestamp | Nullable |
| timestamps | | |

### reviews
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| menu_id | bigint | FK → menus |
| user_id | uuid | FK → users |
| store_id | bigint | FK → stores |
| rating | tinyint | |
| comment | text | Nullable |
| photo | string | Nullable |
| recommend | boolean | Default false |
| timestamps | | |

### settings
| Column | Type | Notes |
|--------|------|-------|
| id | bigint | Primary key |
| app_name | string | Nullable |
| logo | string | Nullable |
| timestamps | | |

### sessions
| Column | Type | Notes |
|--------|------|-------|
| id | string | Primary key |
| user_id | bigint | Nullable, indexed |
| ip_address | string(45) | Nullable |
| user_agent | text | Nullable |
| payload | longtext | |
| last_activity | integer | Indexed |
