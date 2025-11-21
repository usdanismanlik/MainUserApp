# USDanışmanlık Main User APP

Bu uygulama, USDanışmanlık ekosistemindeki tüm alt uygulamaların (sub-apps) ortak kullanıcı metalarına ve yetkilendirme bilgilerine erişmesi için geliştirilmiş merkezi bir servistir.

## Amaç

Farklı modüller ve uygulamalar arasında tutarlı bir kullanıcı yönetimi sağlamak amacıyla, kullanıcı detaylarını, firma ilişkilerini, personel bilgilerini ve grup yetkilerini tek bir noktadan sunar.

## Özellikler

- **Read-Only API:** Sadece veri okuma amaçlıdır, veri manipülasyonuna izin vermez.
- **Otomatik JSON Decode:** Veritabanında JSON string olarak saklanan verileri otomatik olarak parse eder.
- **Güvenlik:** `password`, `token`, `auth_key` gibi hassas verileri otomatik olarak filtreler.

## Endpoints

### 1. Kullanıcı Detayları
`GET /user/{id}`
Belirtilen ID'ye sahip kullanıcının detaylarını, meta verilerini ve grup bilgilerini getirir.

### 2. Firma Detayları
`GET /firma/{id}`
Belirtilen kullanıcı ID'sine (Firma Yetkilisi) ait detayları ve aynı organizasyona (username) bağlı diğer kullanıcıları listeler.

### 3. Personel Listesi
`GET /personel/{id}`
`personelFirma` meta değeri belirtilen ID ile eşleşen personelleri listeler.

### 4. Grup Üyeleri
`GET /grup/{id}`
Belirtilen gruba dahil olan kullanıcıları listeler.

### 5. Firma Grupları
`GET /grups/{firmaid}`
Belirtilen firmaya ait alt kullanıcı gruplarını listeler.

## Kurulum

Docker ile ayağa kaldırmak için:

```bash
docker-compose up -d --build
```

Servis `8000` portunda çalışacaktır.
