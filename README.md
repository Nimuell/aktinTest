## Role a oprávnění

- ROLE_ADMIN: Plný přístup ke všem operacím
- ROLE_AUTHOR: Může vytvářet a upravovat vlastní články
- ROLE_READER: Může pouze číst články



## Instalace a spuštění

1. Naklonujte repozitář:
```bash
git clone <repository-url>
cd <repository-name>
```

2. Spusťte aplikaci pomocí Dockeru:
```bash
docker-compose up -d
```

3. Nainstalujte závislosti:
```bash
docker-compose exec php composer install
```

4. Vygenerujte JWT klíče:
```bash
docker-compose exec php bin/console lexik:jwt:generate-keypair
```

5. Vytvořte databázi a spusťte migrace:
```bash
docker-compose exec php bin/console doctrine:database:create
docker-compose exec php bin/console doctrine:migrations:migrate
```

Aplikace bude dostupná na `http://127.0.0.1`

## API Endpointy

### Autentizace

#### Registrace
```bash
curl -X POST http://127.0.0.1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "name": "John Doe",
    "role": "ROLE_AUTHOR"
  }'
```

#### Přihlášení
```bash
curl -X POST http://127.0.0.1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

### Články

#### Seznam článků
```bash
curl http://127.0.0.1/api/articles
```

#### Detail článku
```bash
curl http://127.0.0.1/api/articles/{id}
```

#### Vytvoření článku (vyžaduje ROLE_AUTHOR nebo ROLE_ADMIN)
```bash
curl -X POST http://127.0.0.1/api/articles \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Nový článek",
    "content": "Obsah článku"
  }'
```

#### Úprava článku (vyžaduje vlastnictví nebo ROLE_ADMIN)
```bash
curl -X PUT http://127.0.0.1/api/articles/{id} \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Upravený název",
    "content": "Upravený obsah"
  }'
```

#### Smazání článku (vyžaduje vlastnictví nebo ROLE_ADMIN)
```bash
curl -X DELETE http://127.0.0.1/api/articles/{id} \
  -H "Authorization: Bearer {token}"
```

### Uživatelé (pouze pro ROLE_ADMIN)

#### Seznam uživatelů
```bash
curl http://127.0.0.1/users \
  -H "Authorization: Bearer {token}"
```

#### Detail uživatele
```bash
curl http://127.0.0.1/users/{id} \
  -H "Authorization: Bearer {token}"
```

## Testy

Pro spuštění testů použijte:
```bash
docker-compose exec php bin/phpunit
```
