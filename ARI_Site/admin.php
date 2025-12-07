<?php
session_start();
include 'baglan.php'; // Veritabanı bağlantısı

// Admin kontrolü
if (!isset($_SESSION['kullanici_id']) || $_SESSION['yetki'] != 9) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

// Silme işlemleri
if (isset($_GET['action'], $_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];

    if ($action === 'delete_kitap') {
        $stmt = $baglanti->prepare("DELETE FROM kitaplar WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Kitap başarıyla silindi.";
        } else {
            $error = "Kitap silme işlemi başarısız.";
        }
        $stmt->close();
    } elseif ($action === 'delete_yorum') {
        $stmt = $baglanti->prepare("DELETE FROM yorumlar WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Yorum başarıyla silindi.";
        } else {
            $error = "Yorum silme işlemi başarısız.";
        }
        $stmt->close();
    } elseif ($action === 'delete_kullanici') {
        $stmt = $baglanti->prepare("DELETE FROM kullanicilar WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Kullanıcı başarıyla silindi.";
        } else {
            $error = "Kullanıcı silme işlemi başarısız.";
        }
        $stmt->close();
    }
}

// Kitap ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kitap_ekle'])) {
    $baslik = $_POST['baslik'] ?? '';
    $kategori_id = $_POST['kategori_id'] ?? 0;
    $fiyat = $_POST['fiyat'] ?? 0;
    $ozet = $_POST['ozet'] ?? '';
    $resim_url = $_POST['resim_url'] ?? '';

    if ($baslik && $kategori_id && $fiyat && $ozet) {
        $stmt = $baglanti->prepare("INSERT INTO kitaplar (baslik, kategori_id, fiyat, ozet, resim_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiss", $baslik, $kategori_id, $fiyat, $ozet, $resim_url);
        if ($stmt->execute()) {
            $success = "Kitap başarıyla eklendi.";
        } else {
            $error = "Kitap ekleme işlemi başarısız.";
        }
        $stmt->close();
    } else {
        $error = "Lütfen tüm kitap bilgilerini doldurun.";
    }
}

// Verileri çek
$kitaplar = $baglanti->query("SELECT kitaplar.*, kategoriler.ad AS kategori_adi FROM kitaplar LEFT JOIN kategoriler ON kitaplar.kategori_id = kategoriler.id");

$yorumlar = $baglanti->query("SELECT yorumlar.*, kitaplar.baslik AS kitap_baslik, kullanicilar.ad_soyad AS kullanici_adi 
                             FROM yorumlar 
                             LEFT JOIN kitaplar ON yorumlar.kitap_id = kitaplar.id 
                             LEFT JOIN kullanicilar ON yorumlar.kullanici_id = kullanicilar.id");

$kullanicilar = $baglanti->query("SELECT * FROM kullanicilar");

$satislar = $baglanti->query("SELECT satislar.*, kullanicilar.ad_soyad AS kullanici_adi, kitaplar.baslik AS kitap_adi 
                              FROM satislar 
                              LEFT JOIN kullanicilar ON satislar.kullanici_id = kullanicilar.id 
                              LEFT JOIN kitaplar ON satislar.kitap_id = kitaplar.id");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <title>Admin Paneli - ArıKitap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="CSS/admin.css" />
</head>
<body>
    <div class="admin-box">
        <h2>🐝 ArıKitap Admin Paneli</h2>
        <p>Hoş geldin, <strong><?= htmlspecialchars($_SESSION['kullanici_adi']) ?></strong>!</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <a href="cikis.php" class="btn-exit">Çıkış Yap</a>

        <!-- Kitap Ekleme Formu -->
        <h3>Yeni Kitap Ekle</h3>
        <form method="POST" class="mb-5">
            <input type="hidden" name="kitap_ekle" value="1" />
            <div class="mb-3">
                <label for="baslik">Başlık:</label>
                <input type="text" id="baslik" name="baslik" required />
            </div>
            <div class="mb-3">
                <label for="kategori_id">Kategori ID:</label>
                <input type="number" id="kategori_id" name="kategori_id" required />
            </div>
            <div class="mb-3">
                <label for="fiyat">Fiyat:</label>
                <input type="number" step="0.01" id="fiyat" name="fiyat" required />
            </div>
            <div class="mb-3">
                <label for="ozet">Özet:</label>
                <textarea id="ozet" name="ozet" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="resim_url">Resim URL:</label>
                <input type="text" id="resim_url" name="resim_url" />
            </div>
            <button type="submit" class="btn btn-success">Kitap Ekle</button>
        </form>

        <!-- Kitap Listesi -->
        <h3>Kitaplar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Başlık</th>
                    <th>Kategori</th>
                    <th>Fiyat</th>
                    <th>Özet</th>
                    <th>Resim URL</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while($kitap = $kitaplar->fetch_assoc()): ?>
                    <tr>
                        <td><?= $kitap['id'] ?></td>
                        <td><?= htmlspecialchars($kitap['baslik']) ?></td>
                        <td><?= htmlspecialchars($kitap['kategori_adi']) ?></td>
                        <td><?= $kitap['fiyat'] ?></td>
                        <td><?= htmlspecialchars($kitap['ozet']) ?></td>
                        <td><?= htmlspecialchars($kitap['resim_url']) ?></td>
                        <td>
                            <a href="?action=delete_kitap&id=<?= $kitap['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Yorumlar -->
        <h3>Yorumlar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kitap</th>
                    <th>Kullanıcı</th>
                    <th>Yorum</th>
                    <th>Puan</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while($yorum = $yorumlar->fetch_assoc()): ?>
                    <tr>
                        <td><?= $yorum['id'] ?></td>
                        <td><?= htmlspecialchars($yorum['kitap_baslik']) ?></td>
                        <td><?= $yorum['kullanici_adi'] ? htmlspecialchars($yorum['kullanici_adi']) : 'Bilinmeyen Kullanıcı' ?></td>
                        <td><?= htmlspecialchars($yorum['yorum']) ?></td>
                        <td><?= $yorum['puan'] ?></td>
                        <td>
                            <a href="?action=delete_yorum&id=<?= $yorum['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Kullanıcılar -->
        <h3>Kullanıcılar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Yetki</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php while($kullanici = $kullanicilar->fetch_assoc()): ?>
                    <tr>
                        <td><?= $kullanici['id'] ?></td>
                        <td><?= htmlspecialchars($kullanici['ad_soyad']) ?></td>
                        <td><?= htmlspecialchars($kullanici['eposta']) ?></td>
                        <td><?= $kullanici['yetki'] ?></td>
                        <td>
                            <a href="?action=delete_kullanici&id=<?= $kullanici['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Silmek istediğine emin misin?')">Sil</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Satışlar -->
        <h3>Satışlar</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Kullanıcı</th>
                    <th>Kitap</th>
                    <th>Adet</th>
                    <th>Toplam Tutar</th>
                    <th>Adres</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($satis = $satislar->fetch_assoc()): ?>
                    <tr>
                        <td><?= $satis['id'] ?></td>
                        <td><?= htmlspecialchars($satis['kullanici_adi'] ?? 'Bilinmeyen Kullanıcı') ?></td>
                        <td><?= htmlspecialchars($satis['kitap_adi'] ?? 'Bilinmeyen Kitap') ?></td>
                        <td><?= $satis['adet'] ?></td>
                        <td><?= number_format($satis['toplam_tutar'], 2) ?> ₺</td>
                        <td><?= htmlspecialchars($satis['adres']) ?></td>
                        <td><?= $satis['tarih'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

    </div>
</body>
</html>